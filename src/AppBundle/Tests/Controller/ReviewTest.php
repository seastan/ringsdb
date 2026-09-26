<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Card reviews: write (POST /review/post), edit (POST /review/edit), like (POST /review/like),
 * comment (POST /review/comment), remove (/review/remove/{id}).
 *
 * The forms of the card page are posted with AJAX (ui.card.js, ui.reviews.js); on error, the
 * JavaScript displays the "message" of the JSON answer built by CoreExceptionListener.
 *
 * Fixtures: "test" (reputation 1) wrote review 1 on Aragorn (card 01001, id 1); "admin"
 * (reputation 1, ROLE_ADMIN) wrote none. Everything is restored in tearDown().
 *
 * @see CoreExceptionListener for the status codes of the errors (500 for most of them)
 */
class ReviewTest extends WebTestCase {
    const CARD_URL = '/card/01001';

    /** @var int[] */
    private $maxIds = [];
    /** @var array */
    private $fixtureReview;
    /** @var array */
    private $fixtureUsers;

    protected function setUp() {
        $connection = $this->db(static::createClient());
        foreach (['review', 'reviewcomment'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
        $this->fixtureReview = $connection->fetchAssoc('SELECT * FROM review WHERE id = 1');
        $this->fixtureUsers = $connection->fetchAll('SELECT id, reputation, roles FROM user');
    }

    protected function tearDown() {
        $connection = $this->db(static::createClient());
        $connection->exec("DELETE FROM reviewcomment WHERE id > {$this->maxIds['reviewcomment']}");
        $connection->exec("DELETE FROM reviewvote WHERE review_id > {$this->maxIds['review']} OR review_id = 1");
        $connection->exec("DELETE FROM review WHERE id > {$this->maxIds['review']}");
        if (!$connection->fetchColumn('SELECT COUNT(*) FROM review WHERE id = 1')) {
            $connection->insert('review', $this->fixtureReview);
        } else {
            $connection->update('review', $this->fixtureReview, ['id' => 1]);
        }
        foreach ($this->fixtureUsers as $user) {
            $connection->update('user', $user, ['id' => $user['id']]);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    private function createAuthenticatedClient($username) {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    private function ajax(Client $client, $uri, array $parameters, $method = 'POST') {
        $client->request($method, $uri, $parameters, [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        return $client->getResponse();
    }

    private function assertJsonAnswer(Response $response, $status, array $expected) {
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame($expected, json_decode($response->getContent(), true));
    }

    private function newReviews(Client $client) {
        return $this->db($client)->fetchAll(
            'SELECT c.code, u.username, r.text_md, r.text_html, r.nb_votes FROM review r JOIN card c ON c.id = r.card_id JOIN user u ON u.id = r.user_id WHERE r.id > ? ORDER BY r.id',
            [$this->maxIds['review']]
        );
    }

    private static function reviewText() {
        return "Théodred is a **cheap** hero: he gives a resource to a questing hero.\n\nSee http://example.com/theodred";
    }

    /* ------------------------------------------------------------- write */

    public function testWriteAReview() {
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->ajax($client, '/review/post', ['card_id' => 2, 'review_id' => '', 'review' => self::reviewText()]);

        $this->assertJsonAnswer($response, 200, ['success' => true]);
        $this->assertSame([[
            'code' => '01002',
            'username' => 'admin',
            'text_md' => "Théodred is a **cheap** hero: he gives a resource to a questing hero.\n\nSee [example.com](http://example.com/theodred)",
            'text_html' => "<p>Théodred is a <strong>cheap</strong> hero: he gives a resource to a questing hero.</p>\n<p>See <a href=\"http://example.com/theodred\">example.com</a></p>",
            'nb_votes' => '0',
        ]], $this->newReviews($client));

        $crawler = $client->request('GET', '/card/01002');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('is a cheap hero', $crawler->filter('.review-text')->text());
        $crawler = $client->request('GET', '/reviews');
        $this->assertContains('is a cheap hero', $crawler->filter('body')->text());
    }

    /**
     * Errors are generic exceptions: 500, with the message in the JSON answer.
     *
     * @dataProvider refusedReviewProvider
     */
    public function testRefusedReview($username, $reputation, array $parameters, $message) {
        $client = $this->createAuthenticatedClient($username);
        $this->db($client)->update('user', ['reputation' => $reputation], ['username' => $username]);

        $response = $this->ajax($client, '/review/post', $parameters + ['review_id' => '']);

        $this->assertJsonAnswer($response, 500, ['success' => false, 'message' => $message]);
        $this->assertSame([], $this->newReviews($client));
    }

    public function refusedReviewProvider() {
        $text = self::reviewText();

        return [
            // "test" already wrote 1 review, with a reputation of 1
            'reputation too low' => ['test', 1, ['card_id' => 2, 'review' => $text], "Your reputation doesn't allow you to write more reviews."],
            'second review on a card' => ['test', 5, ['card_id' => 1, 'review' => $text], 'You cannot write more than 1 review for a given card.'],
            'unknown card' => ['admin', 1, ['card_id' => 99999, 'review' => $text], 'This card does not exist.'],
            'empty review' => ['admin', 1, ['card_id' => 2, 'review' => "  \n "], 'Your review is empty.'],
        ];
    }

    /**
     * The release date is the one of the card's primary printing: card 02001 (id 74) is only
     * printed in The Hunt for Gollum, whose release date is removed for the test.
     */
    public function testNoReviewOnUnreleasedCards() {
        $client = $this->createAuthenticatedClient('admin');
        $connection = $this->db($client);
        $releaseDate = $connection->fetchColumn("SELECT date_release FROM pack WHERE code = 'HfG'");
        try {
            $connection->update('pack', ['date_release' => null], ['code' => 'HfG']);
            $response = $this->ajax($client, '/review/post', ['card_id' => 74, 'review_id' => '', 'review' => self::reviewText()]);
            $this->assertJsonAnswer($response, 500, ['success' => false, 'message' => 'You may not write a review for an unreleased card.']);
        } finally {
            $connection->update('pack', ['date_release' => $releaseDate], ['code' => 'HfG']);
        }
        $this->assertSame([], $this->newReviews($client));
    }

    public function testAnonymousCannotWriteAReview() {
        $client = static::createClient();
        $response = $this->ajax($client, '/review/post', ['card_id' => 2, 'review' => self::reviewText()]);

        $this->assertJsonAnswer($response, 403, ['success' => false, 'message' => 'You are not logged in.']);
        $this->assertSame([], $this->newReviews($client));
    }

    /* -------------------------------------------------------------- edit */

    public function testEditOwnReview() {
        $client = $this->createAuthenticatedClient('test');

        $response = $this->ajax($client, '/review/edit', ['card_id' => 1, 'review_id' => 1, 'review' => 'Aragorn is *still* great.']);

        $this->assertJsonAnswer($response, 200, ['success' => true]);
        $review = $this->db($client)->fetchAssoc('SELECT text_md, text_html FROM review WHERE id = 1');
        $this->assertSame(['text_md' => 'Aragorn is *still* great.', 'text_html' => '<p>Aragorn is <em>still</em> great.</p>'], $review);
    }

    public function testEditWithAnEmptyReview() {
        $client = $this->createAuthenticatedClient('test');

        $response = $this->ajax($client, '/review/edit', ['review_id' => 1, 'review' => '']);

        // not JSON, unlike the other answers
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Your review is empty.', $response->getContent());
        $this->assertSame($this->fixtureReview['text_md'], $this->db($client)->fetchColumn('SELECT text_md FROM review WHERE id = 1'));
    }

    /**
     * @dataProvider refusedEditProvider
     */
    public function testRefusedEdit($username, $reviewId, $message) {
        $client = $this->createAuthenticatedClient($username);

        $response = $this->ajax($client, '/review/edit', ['review_id' => $reviewId, 'review' => 'Hacked']);

        // BUG (CoreExceptionListener): the 403 / 400 HTTP exceptions become 500s for AJAX requests
        $this->assertJsonAnswer($response, 500, ['success' => false, 'message' => $message]);
        $this->assertSame($this->fixtureReview['text_md'], $this->db($client)->fetchColumn('SELECT text_md FROM review WHERE id = 1'));
    }

    public function refusedEditProvider() {
        return [
            'another user\'s review' => ['admin', 1, 'You cannot edit this review.'],
            'unknown review' => ['test', 999, 'Unable to find review.'],
        ];
    }

    /* -------------------------------------------------------------- like */

    public function testLikeAReview() {
        $client = $this->createAuthenticatedClient('admin');

        $this->assertJsonAnswer($this->ajax($client, '/review/like', ['id' => 1]), 200, ['success' => true, 'nbVotes' => 1]);

        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT nb_votes FROM review WHERE id = 1'));
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM reviewvote WHERE review_id = 1'));
        // the author earns 1 reputation point
        $this->assertSame('2', $this->db($client)->fetchColumn("SELECT reputation FROM user WHERE username = 'test'"));

        // liking twice does nothing
        $this->assertJsonAnswer($this->ajax($client, '/review/like', ['id' => 1]), 200, ['success' => true, 'nbVotes' => 1]);
        $this->assertSame('2', $this->db($client)->fetchColumn("SELECT reputation FROM user WHERE username = 'test'"));
    }

    public function testCannotLikeOwnReview() {
        $client = $this->createAuthenticatedClient('test');

        $this->assertJsonAnswer($this->ajax($client, '/review/like', ['id' => 1]), 200, ['success' => true, 'nbVotes' => 0]);

        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM reviewvote WHERE review_id = 1'));
        $this->assertSame('1', $this->db($client)->fetchColumn("SELECT reputation FROM user WHERE username = 'test'"));
    }

    public function testLikeAnUnknownReview() {
        $client = $this->createAuthenticatedClient('admin');

        $this->assertJsonAnswer($this->ajax($client, '/review/like', ['id' => 999]), 500, ['success' => false, 'message' => 'Unable to find review.']);
    }

    /* ----------------------------------------------------------- comment */

    public function testCommentAReview() {
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->ajax($client, '/review/comment', ['comment_review_id' => 1, 'comment' => 'Agreed <b>100%</b>']);

        $this->assertJsonAnswer($response, 200, ['success' => true]);
        // comments are plain text, HTML-escaped
        $comment = $this->db($client)->fetchAssoc('SELECT c.text, u.username FROM reviewcomment c JOIN user u ON u.id = c.user_id WHERE c.review_id = 1');
        $this->assertSame(['text' => 'Agreed &lt;b&gt;100%&lt;/b&gt;', 'username' => 'admin'], $comment);
        $this->assertGreaterThan('2015-08-16 00:00:00', $this->db($client)->fetchColumn('SELECT date_last_comment FROM review WHERE id = 1'));

        // BUG: escaped once more by Twig when displayed, the entities are shown as is
        $crawler = $client->request('GET', self::CARD_URL);
        $text = $crawler->filter('#review-1 .review-comment')->first()->getNode(0)->firstChild->nodeValue;
        $this->assertSame('Agreed &lt;b&gt;100%&lt;/b&gt; —', trim(preg_replace('/\s+/u', ' ', $text)));
    }

    /**
     * @dataProvider refusedCommentProvider
     */
    public function testRefusedComment(array $parameters, $message) {
        $client = $this->createAuthenticatedClient('admin');

        $this->assertJsonAnswer($this->ajax($client, '/review/comment', $parameters), 500, ['success' => false, 'message' => $message]);
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM reviewcomment'));
    }

    public function refusedCommentProvider() {
        return [
            'empty comment' => [['comment_review_id' => 1, 'comment' => ' '], 'Your comment is empty.'],
            'unknown review' => [['comment_review_id' => 999, 'comment' => 'Hello'], 'Unable to find review.'],
        ];
    }

    /* ------------------------------------------------------------ remove */

    /**
     * Removing a review requires ROLE_SUPER_ADMIN: the fixture admin (ROLE_ADMIN) cannot.
     */
    public function testOnlySuperAdminsCanRemoveReviews() {
        $client = $this->createAuthenticatedClient('admin');
        $this->assertJsonAnswer($this->ajax($client, '/review/remove/1', []), 403, ['success' => false, 'message' => 'No user or not admin']);
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM review WHERE id = 1'));

        $this->db($client)->update('user', ['roles' => serialize(['ROLE_SUPER_ADMIN'])], ['username' => 'admin']);
        $client = $this->createAuthenticatedClient('admin');
        $this->ajax($client, '/review/like', ['id' => 1]);

        // the route accepts any method, GET included
        $this->assertJsonAnswer($this->ajax($client, '/review/remove/1', [], 'GET'), 200, ['success' => true]);
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM review WHERE id = 1'));
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM reviewvote WHERE review_id = 1'));
    }
}
