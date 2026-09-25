<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Comments on a decklist: posting (POST /user/comment) and hiding (POST /user/hidecomment).
 *
 * The comment form is built in JavaScript (ui.decklist.js) and posted with AJAX: "id" (the
 * decklist id) and "comment" (Markdown). The server answers with a redirect to the decklist.
 *
 * Fixtures: decklist 1 belongs to "test" and has one comment by "test"; decklists 2 to 4 belong
 * to "test" and have no comment. Everything created here is removed in tearDown(), and the
 * decklists' counters and dates (used by the API's Last-Modified) are restored.
 */
class DecklistCommentTest extends WebTestCase {
    const DECKLIST_1_URL = '/decklist/view/1/dwarfloreleadershiptactics-1.0';

    /** @var int */
    private $maxCommentId;
    /** @var array */
    private $decklists;

    protected function setUp() {
        $connection = $this->db(static::createClient());
        $this->maxCommentId = (int) $connection->fetchColumn('SELECT MAX(id) FROM comment');
        $this->decklists = $connection->fetchAll('SELECT id, nb_comments, date_update, date_last_comment FROM decklist');
    }

    protected function tearDown() {
        $connection = $this->db(static::createClient());
        $connection->executeUpdate('DELETE FROM comment WHERE id > ?', [$this->maxCommentId]);
        $connection->executeUpdate('UPDATE comment SET is_hidden = 0');
        foreach ($this->decklists as $decklist) {
            $connection->update('decklist', $decklist, ['id' => $decklist['id']]);
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

    /**
     * Posts the comment form as the JavaScript does it (AJAX), with the profiler enabled to
     * inspect the notification emails.
     */
    private function postComment(Client $client, $decklistId, $text) {
        $client->enableProfiler();
        $client->request('POST', '/user/comment', ['id' => $decklistId, 'comment' => $text], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        return $client->getResponse();
    }

    private function newComments(Client $client) {
        return $this->db($client)->fetchAll(
            'SELECT c.decklist_id, u.username, c.text, c.is_hidden FROM comment c JOIN user u ON u.id = c.user_id WHERE c.id > ? ORDER BY c.id',
            [$this->maxCommentId]
        );
    }

    /**
     * @return array [recipient => subject]
     */
    private function sentEmails(Client $client) {
        $emails = [];
        /** @var \Swift_Message $message */
        foreach ($client->getProfile()->getCollector('swiftmailer')->getMessages() as $message) {
            $this->assertSame(['seastan@ringsdb.com'], array_keys($message->getFrom()));
            $emails[key($message->getTo())] = $message->getSubject();
        }

        return $emails;
    }

    /* ------------------------------------------------------------ posting */

    public function testCommentOnAnotherUsersDecklist() {
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->postComment($client, 1, "Nice **deck**!");

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(self::DECKLIST_1_URL, $response->headers->get('Location'));
        $this->assertSame([[
            'decklist_id' => '1',
            'username' => 'admin',
            'text' => '<p>Nice <strong>deck</strong>!</p>',
            'is_hidden' => '0',
        ]], $this->newComments($client));

        $decklist = $this->db($client)->fetchAssoc('SELECT nb_comments, date_update, date_last_comment FROM decklist WHERE id = 1');
        $this->assertSame('2', $decklist['nb_comments']);
        $this->assertSame($decklist['date_update'], $decklist['date_last_comment']);
        $this->assertGreaterThan('2015-08-16 00:00:00', $decklist['date_update']);

        // the author is notified; the commenter is never notified of their own comment
        $message = $client->getProfile()->getCollector('swiftmailer')->getMessages()[0];
        $this->assertSame(['test@example.com' => '[ringsdb] New comment'], $this->sentEmails($client));
        $this->assertSame(['seastan@ringsdb.com' => 'admin'], $message->getFrom());
        $this->assertContains('<p>Nice <strong>deck</strong>!</p>', $message->getBody());

        // the comment is displayed on the decklist page
        $crawler = $client->request('GET', self::DECKLIST_1_URL);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $comments = $crawler->filter('.comment-text');
        $this->assertCount(2, $comments);
        $this->assertSame('Nice deck!', trim($comments->eq(1)->text()));
        // the author's name is followed by their reputation
        $this->assertSame('admin 1', trim(preg_replace('/\s+/', ' ', $crawler->filter('.comment-author')->eq(1)->text())));
        $this->assertSame('2 comments', trim($crawler->filter('th:contains("comments")')->text()));
    }

    public function testCommentOnOwnDecklistNotifiesMentionedUsers() {
        $client = $this->createAuthenticatedClient('test');

        // mentions are written `@username` (the autocomplete of the form inserts the backticks)
        $response = $this->postComment($client, 2, 'What do you think, `@admin`?');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/decklist/view/2/gondordunedainleadershipspirit-1.0', $response->headers->get('Location'));
        $this->assertSame([[
            'decklist_id' => '2',
            'username' => 'test',
            'text' => '<p>What do you think, <code>@admin</code>?</p>',
            'is_hidden' => '0',
        ]], $this->newComments($client));
        $this->assertSame(['admin@example.com' => '[ringsdb] New comment'], $this->sentEmails($client));
    }

    public function testPreviousCommentersAreNotified() {
        // decklist 2 is commented by admin, then by test (its author): admin is notified as a commenter
        $client = $this->createAuthenticatedClient('admin');
        $this->postComment($client, 2, 'First');
        $this->assertSame(['test@example.com' => '[ringsdb] New comment'], $this->sentEmails($client));

        $client = $this->createAuthenticatedClient('test');
        $this->postComment($client, 2, 'Second');
        $this->assertSame(['admin@example.com' => '[ringsdb] New comment'], $this->sentEmails($client));
    }

    public function testNotificationsCanBeDisabled() {
        $client = $this->createAuthenticatedClient('admin');
        $connection = $this->db($client);
        try {
            // "test" is both the author of decklist 1 and a commenter on it (fixture comment)
            $connection->update('user', ['is_notif_author' => 0], ['username' => 'test']);
            $this->postComment($client, 1, 'Still notified as a commenter');
            $this->assertSame(['test@example.com' => '[ringsdb] New comment'], $this->sentEmails($client));

            $connection->update('user', ['is_notif_commenter' => 0], ['username' => 'test']);
            $this->postComment($client, 1, 'Nobody will know');
            $this->assertSame([], $this->sentEmails($client));
        } finally {
            $connection->update('user', ['is_notif_author' => 1, 'is_notif_commenter' => 1], ['username' => 'test']);
        }
    }

    /**
     * @dataProvider markdownProvider
     */
    public function testCommentMarkdown($text, $expectedHtml) {
        $client = $this->createAuthenticatedClient('test');
        $this->postComment($client, 1, $text);

        $comments = $this->newComments($client);
        $this->assertCount(1, $comments);
        $this->assertSame($expectedHtml, $comments[0]['text']);
    }

    public function markdownProvider() {
        return [
            'bare URLs become links' => ['See http://example.com/page', '<p>See <a href="http://example.com/page">example.com</a></p>'],
            'markdown links are kept' => ['[a link](http://example.com)', '<p><a href="http://example.com">a link</a></p>'],
            'scripts are removed' => ['Hello <script>alert(1)</script>', '<p>Hello </p>'],
            'event handlers are removed' => ['<img src="x.png" onerror="alert(1)">', '<img class="img-responsive" src="x.png" alt="x.png" />'],
        ];
    }

    public function testEmptyCommentIsIgnored() {
        $client = $this->createAuthenticatedClient('test');

        $response = $this->postComment($client, 1, "   \n  ");

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(self::DECKLIST_1_URL, $response->headers->get('Location'));
        $this->assertSame([], $this->newComments($client));
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT nb_comments FROM decklist WHERE id = 1'));
        $this->assertSame([], $this->sentEmails($client));
    }

    public function testAnonymousCannotComment() {
        $client = static::createClient();
        $client->request('POST', '/user/comment', ['id' => 1, 'comment' => 'Anonymous']);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
        $this->assertSame([], $this->newComments($client));
    }

    public function testCommentOnUnknownDecklist() {
        $client = $this->createAuthenticatedClient('test');
        $client->request('POST', '/user/comment', ['id' => 999, 'comment' => 'Lost']);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $this->assertSame([], $this->newComments($client));
    }

    /**
     * BUG: for AJAX requests, CoreExceptionListener takes the status from getCode() instead of
     * getStatusCode(): every HTTP exception (400, 403, 404...) becomes a 500. The JSON body is right.
     */
    public function testCommentOnUnknownDecklistWithAjax() {
        $client = $this->createAuthenticatedClient('test');
        $client->request('POST', '/user/comment', ['id' => 999, 'comment' => 'Lost'], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(500, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertSame(['success' => false, 'message' => 'Wrong decklist id'], json_decode($client->getResponse()->getContent(), true));
        $this->assertSame([], $this->newComments($client));
    }

    /* ------------------------------------------------------------- hiding */

    public function testDecklistAuthorCanHideAndShowAComment() {
        $client = $this->createAuthenticatedClient('admin');
        $this->postComment($client, 1, 'Hide me');
        $commentId = (int) $this->db($client)->fetchColumn('SELECT MAX(id) FROM comment');

        $client = $this->createAuthenticatedClient('test');
        $client->request('POST', "/user/hidecomment/$commentId/1");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('true', $client->getResponse()->getContent());
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT is_hidden FROM comment WHERE id = ?', [$commentId]));

        // hidden comments are still in the page, collapsed
        $crawler = $client->request('GET', self::DECKLIST_1_URL);
        $this->assertSame('collapse', $crawler->filter("#div-comment-$commentId")->attr('class'));

        $client->request('POST', "/user/hidecomment/$commentId/0");
        $this->assertSame('true', $client->getResponse()->getContent());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT is_hidden FROM comment WHERE id = ?', [$commentId]));
    }

    public function testOnlyTheDecklistAuthorCanHideComments() {
        // comment 1 of the fixtures: by test, on test's decklist 1
        $client = $this->createAuthenticatedClient('admin');
        $client->request('POST', '/user/hidecomment/1/1');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('"You don\'t have permission to edit this comment."', $client->getResponse()->getContent());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT is_hidden FROM comment WHERE id = 1'));
    }

    public function testHidingAnUnknownComment() {
        $client = $this->createAuthenticatedClient('test');
        $client->request('POST', '/user/hidecomment/999/1');

        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }
}
