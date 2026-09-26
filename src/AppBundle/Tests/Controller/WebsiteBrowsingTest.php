<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Read-only browsing of the website, as an anonymous visitor and as the "test" user.
 *
 * HTML pages are compared to text snapshots stored in Tests/Resources/snapshots/pages/ (see
 * PageSnapshotTrait). Downloads (text / OCTGN exports) are compared byte for byte.
 *
 * GET routes that write to the database (/deck/new, /deck/clone/{id}, /fellowship/publish/{id})
 * are deliberately left out.
 */
class WebsiteBrowsingTest extends WebTestCase {
    use PageSnapshotTrait;

    /* ------------------------------------------------------------ helpers */

    private function createAuthenticatedClient() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'test', '_password' => 'test']));
        $this->assertTrue($client->getResponse()->isRedirect(), 'Login failed');

        return $client;
    }

    private function assertPage(Client $client, $uri, $snapshot, $title) {
        $crawler = $client->request('GET', $uri);
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode(), "GET $uri");
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame($title, trim($crawler->filter('title')->text()));
        // the home page's daily challenge is picked with srand(<day number>)
        $text = preg_replace('/^Daily Challenge: .*$/m', 'Daily Challenge: <masked, changes every day>', self::pageText($crawler));
        $this->assertMatchesSnapshot($snapshot . '.txt', $text);
    }

    /* ------------------------------------------------------ public pages */

    /**
     * [uri, snapshot name, <title>]
     */
    public function publicPageProvider() {
        return [
            'home' => ['/', 'index', 'Deckbuilder · RingsDB'],
            'about' => ['/about', 'about', 'About · RingsDB'],
            'patrons' => ['/patrons', 'patrons', 'The Gracious Patrons · RingsDB'],
            'card search form' => ['/search', 'search', 'Card Search · RingsDB'],
            'find by name' => ['/find?q=Aragorn', 'find_aragorn', 'Aragorn · RingsDB'],
            'find by code' => ['/find?q=01001', 'find_01001', '01001 · RingsDB'],
            'find as spoiler' => ['/find?q=Aragorn&view=spoiler', 'find_aragorn_spoiler', 'Aragorn · RingsDB'],
            'card' => ['/card/01001', 'card_01001', 'Aragorn · RingsDB'],
            'pack' => ['/set/Core', 'set_Core', 'Core Set · RingsDB'],
            'pack as spoiler' => ['/set/Core/spoiler', 'set_Core_spoiler', 'Core Set · RingsDB'],
            'pack, page 2' => ['/set/Core/card/name/2', 'set_Core_card_name_2', 'Core Set · RingsDB'],
            'cycle' => ['/cycle/Core', 'cycle_Core', 'Core Set · RingsDB'],
            'reviews' => ['/reviews', 'reviews', 'Card Reviews · RingsDB'],
            'popular decklists' => ['/decklists', 'decklists_popular', 'Popular Decklists · RingsDB'],
            'recent decklists' => ['/decklists/recent', 'decklists_recent', 'Recent Decklists · RingsDB'],
            'hall of fame' => ['/decklists/halloffame', 'decklists_halloffame', 'Hall of Fame · RingsDB'],
            'hot topics' => ['/decklists/hottopics', 'decklists_hottopics', 'Hot Topics · RingsDB'],
            'decklist search form' => ['/decklists/search', 'decklists_search', 'Decklist Search · RingsDB'],
            'decklist search results' => ['/decklists/find?cards[]=01001', 'decklists_find_01001', 'Decklist search results · RingsDB'],
            'decklist search by author' => ['/decklists/find?author=test', 'decklists_find_author_test', 'Decklist search results · RingsDB'],
            'decklist search sorted by reputation' => ['/decklists/find?sort=reputation', 'decklists_find_sort_reputation', 'Decklist search results · RingsDB'],
            'decklist' => ['/decklist/view/1/dwarfloreleadershiptactics-1.0', 'decklist_1', 'Dwarf Lore/Leadership/Tactics · RingsDB'],
            'popular fellowships' => ['/fellowships', 'fellowships_popular', 'Popular Fellowships · RingsDB'],
            'recent fellowships' => ['/fellowships/recent', 'fellowships_recent', 'Recent Fellowships · RingsDB'],
            'fellowship search form' => ['/fellowships/search', 'fellowships_search', 'Fellowship Search · RingsDB'],
            'fellowship' => ['/fellowship/view/1/heirs-to-numeror-cycle', 'fellowship_1', 'Fellowship · RingsDB'],
            'popular quest logs' => ['/questlogs', 'questlogs_popular', 'Popular Quest Logs · RingsDB'],
            'recent quest logs' => ['/questlogs/recent', 'questlogs_recent', 'Recent Quest Logs · RingsDB'],
            'quest log search form' => ['/questlogs/search', 'questlogs_search', 'Quest Log Search · RingsDB'],
            'password reset form' => ['/resetting/request', 'resetting_request', 'Deckbuilder · RingsDB'],
        ];
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function testPublicPageAsAnonymous($uri, $snapshot, $title) {
        $this->assertPage(static::createClient(), $uri, 'anonymous/' . $snapshot, $title);
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function testPublicPageAsUser($uri, $snapshot, $title) {
        $this->assertPage($this->createAuthenticatedClient(), $uri, 'user/' . $snapshot, $title);
    }

    /* ---------------------------------------------------- members' pages */

    /**
     * [uri, snapshot name, <title>]
     */
    public function memberPageProvider() {
        return [
            'my decks' => ['/decks', 'decks', 'My Decks · RingsDB'],
            'deck' => ['/deck/view/1', 'deck_view_1', 'Deckbuilder · RingsDB'],
            'deck builder' => ['/deck/edit/1', 'deck_edit_1', 'Deckbuilder · RingsDB'],
            'deck comparison' => ['/deck/compare/1/2', 'deck_compare_1_2', 'Deckbuilder · RingsDB'],
            'deck import form' => ['/deck/import', 'deck_import', 'Import a deck · RingsDB'],
            'decklist publish form' => ['/deck/publish/1', 'deck_publish_1', 'Deckbuilder · RingsDB'],
            'my decklists' => ['/decklists/mine', 'decklists_mine', 'My Decklists · RingsDB'],
            'favorite decklists' => ['/decklists/favorites', 'decklists_favorites', 'Favorite Decklists · RingsDB'],
            'my fellowships' => ['/myfellowships', 'myfellowships', 'My Fellowships · RingsDB'],
            'new fellowship form' => ['/fellowship/new/1/2/0/0', 'fellowship_new_1_2', 'Create a Fellowship · RingsDB'],
            'fellowship edit form' => ['/fellowship/edit/1', 'fellowship_edit_1', 'Edit Fellowship · RingsDB'],
            'my quest logs' => ['/myquestlogs', 'myquestlogs', 'My Quest Logs · RingsDB'],
            'quest log' => ['/questlog/view/1/untitled-questlog', 'questlog_1', 'Passage Through Mirkwood - Quest Log · RingsDB'],
            'new quest log form' => ['/questlog/new/0/1/2/0/0', 'questlog_new_1_2', 'Log a Quest · RingsDB'],
            'quest log edit form' => ['/questlog/edit/1', 'questlog_edit_1', 'Edit Quest Log · RingsDB'],
            'collection' => ['/collection/packs', 'collection_packs', 'My Collection · RingsDB'],
            'new custom pack form' => ['/collection/custom-pack/new', 'custom_pack_new', 'Create Custom Pack · RingsDB'],
            'custom pack edit form' => ['/collection/custom-pack/1/edit', 'custom_pack_edit_1', 'Edit Custom Pack · RingsDB'],
            'public profile' => ['/user/profile/1/test', 'user_profile_1', 'Deckbuilder · RingsDB'],
            'reviews by author' => ['/user/reviews/1', 'user_reviews_1', 'Card Reviews by test · RingsDB'],
            'profile edit form' => ['/user/profile_edit', 'user_profile_edit', 'Deckbuilder · RingsDB'],
            'account' => ['/profile/', 'profile_show', 'Deckbuilder · RingsDB'],
            'account edit form' => ['/profile/edit', 'profile_edit', 'Deckbuilder · RingsDB'],
            'change password form' => ['/profile/change-password', 'profile_change_password', 'Deckbuilder · RingsDB'],
        ];
    }

    /**
     * @dataProvider memberPageProvider
     */
    public function testMemberPage($uri, $snapshot, $title) {
        $this->assertPage($this->createAuthenticatedClient(), $uri, 'user/' . $snapshot, $title);
    }

    /* ----------------------------------------------------- access control */

    /**
     * Pages anonymous visitors cannot see: [uri, expected status, expected Location or error title]
     */
    public function anonymousAccessProvider() {
        $login = 'http://localhost/login';

        return [
            'my decks' => ['/decks', 302, $login],
            'deck builder' => ['/deck/edit/1', 302, $login],
            'deck comparison' => ['/deck/compare/1/2', 302, $login],
            'deck import form' => ['/deck/import', 302, $login],
            'deck export' => ['/deck/export/text/1', 302, $login],
            'my fellowships' => ['/myfellowships', 302, $login],
            'new fellowship form' => ['/fellowship/new/1/2/0/0', 302, $login],
            'fellowship edit form' => ['/fellowship/edit/1', 302, $login],
            'fellowship export' => ['/fellowship/export/text/1', 302, $login],
            // the /questlog/ access rule also covers the view page of public quest logs
            'quest log' => ['/questlog/view/1/untitled-questlog', 302, $login],
            'new quest log form' => ['/questlog/new/0/1/2/0/0', 302, $login],
            'quest log export' => ['/questlog/export/text/1', 302, $login],
            'collection' => ['/collection/packs', 302, $login],
            'new custom pack form' => ['/collection/custom-pack/new', 302, $login],
            'public profile' => ['/user/profile/1/test', 302, $login],
            'reviews by author' => ['/user/reviews/1', 302, $login],
            'profile edit form' => ['/user/profile_edit', 302, $login],
            'account' => ['/profile/', 302, $login],
            // not covered by access_control: the controllers deny access themselves
            'my quest logs' => ['/myquestlogs', 403, 'You must be logged in for this operation. (403 Forbidden)'],
            'private deck' => ['/deck/view/1', 403, 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account. (403 Forbidden)'],
        ];
    }

    /**
     * @dataProvider anonymousAccessProvider
     */
    public function testAnonymousAccessIsDenied($uri, $status, $expected) {
        $client = static::createClient();
        $crawler = $client->request('GET', $uri);
        $response = $client->getResponse();

        $this->assertSame($status, $response->getStatusCode(), "GET $uri");
        if ($response->isRedirect()) {
            $this->assertSame($expected, $response->headers->get('Location'));
        } else {
            $this->assertSame($expected, trim($crawler->filter('title')->text()));
        }
    }

    public function testAnonymousSeesNoDecklistOfHisOwn() {
        $client = static::createClient();
        $this->assertPage($client, '/decklists/mine', 'anonymous/decklists_mine', 'My Decklists · RingsDB');
    }

    /* ---------------------------------------------------- redirects & 404 */

    /**
     * [uri, expected Location]
     */
    public function redirectProvider() {
        return [
            'search matching a pack' => ['/find?q=e:Core', '/set/Core/list/name'],
            'search form processing' => ['/process?q=Aragorn', '/find?q=Aragorn'],
            'decklists by author' => ['/d/test', '/decklists/find?author=test'],
            'fellowships by author' => ['/f/test', '/fellowships/find?author=test'],
            'quest logs by author' => ['/q/test', '/questlogs/find?author=test'],
        ];
    }

    /**
     * @dataProvider redirectProvider
     */
    public function testRedirect($uri, $location) {
        $client = static::createClient();
        $client->request('GET', $uri);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame($location, $client->getResponse()->headers->get('Location'));
    }

    /**
     * [uri, error title]
     */
    public function notFoundProvider() {
        return [
            'unknown pack' => ['/set/nope', 'This pack does not exist (404 Not Found)'],
            'unknown cycle' => ['/cycle/nope', 'This cycle does not exist (404 Not Found)'],
            'unknown card' => ['/card/99999', 'Sorry, this card is not in the database (yet?) (404 Not Found)'],
            'unknown decklist' => ['/decklist/view/999/nope', 'Decklist not found. (404 Not Found)'],
            'unknown deck' => ['/deck/view/999', 'This deck doesn\'t exist. (404 Not Found)'],
        ];
    }

    /**
     * @dataProvider notFoundProvider
     */
    public function testNotFound($uri, $title) {
        $client = static::createClient();
        $crawler = $client->request('GET', $uri);

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertSame($title, trim($crawler->filter('title')->text()));
    }

    /* ---------------------------------------------------------- downloads */

    /**
     * [uri, snapshot file, Content-Type, Content-Disposition, authenticated]
     */
    public function downloadProvider() {
        return [
            'decklist as text' => ['/decklist/export/text/1', 'decklist_1.txt', 'text/plain; charset=UTF-8', 'attachment; filename="dwarfloreleadershiptactics-1.0.txt"', false],
            'decklist as OCTGN' => ['/decklist/export/octgn/1', 'decklist_1.o8d', 'application/octgn', 'attachment; filename="dwarfloreleadershiptactics-1.0.o8d"', false],
            'deck as text' => ['/deck/export/text/1', 'deck_1.txt', 'text/plain; charset=UTF-8', 'attachment; filename="dwarfloreleadershiptactics.txt"', true],
            'deck as OCTGN' => ['/deck/export/octgn/1', 'deck_1.o8d', 'application/octgn', 'attachment; filename="dwarfloreleadershiptactics.o8d"', true],
        ];
    }

    /**
     * @dataProvider downloadProvider
     */
    public function testDownload($uri, $snapshot, $contentType, $disposition, $authenticated) {
        $client = $authenticated ? $this->createAuthenticatedClient() : static::createClient();
        $client->request('GET', $uri);
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode(), "GET $uri");
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
        $this->assertSame($disposition, $response->headers->get('Content-Disposition'));
        $this->assertMatchesSnapshot('downloads/' . $snapshot, $response->getContent());
    }

    /**
     * Zip archives: the content is checked entry by entry (zip metadata contains timestamps).
     *
     * [uri, expected entries => snapshot file]
     */
    public function zipDownloadProvider() {
        return [
            'decks as text' => ['/deck/export/text/list?ids[]=1&ids[]=2', [
                'dwarfloreleadershiptactics 1.1.txt' => 'deck_list/dwarfloreleadershiptactics.txt',
                'gondordunedainleadershipspirit 1.1.txt' => 'deck_list/gondordunedainleadershipspirit.txt',
            ]],
            'fellowship as text' => ['/fellowship/export/text/1', [
                'dwarfloreleadershiptactics 1.1.txt' => 'fellowship_1/dwarfloreleadershiptactics.txt',
                'gondordunedainleadershipspirit 1.1.txt' => 'fellowship_1/gondordunedainleadershipspirit.txt',
                'noldorrohanlorespirit 1.1.txt' => 'fellowship_1/noldorrohanlorespirit.txt',
                'gondorrohansilvantactics 1.1.txt' => 'fellowship_1/gondorrohansilvantactics.txt',
            ]],
            'fellowship as OCTGN' => ['/fellowship/export/octgn/1', [
                'dwarfloreleadershiptactics 1.1.o8d' => 'fellowship_1/dwarfloreleadershiptactics.o8d',
                'gondordunedainleadershipspirit 1.1.o8d' => 'fellowship_1/gondordunedainleadershipspirit.o8d',
                'noldorrohanlorespirit 1.1.o8d' => 'fellowship_1/noldorrohanlorespirit.o8d',
                'gondorrohansilvantactics 1.1.o8d' => 'fellowship_1/gondorrohansilvantactics.o8d',
            ]],
            'quest log as text' => ['/questlog/export/text/1', [
                'dwarfloreleadershiptactics 1.1.txt' => 'questlog_1/dwarfloreleadershiptactics.txt',
                'gondordunedainleadershipspirit 1.1.txt' => 'questlog_1/gondordunedainleadershipspirit.txt',
                'noldorrohanlorespirit 1.1.txt' => 'questlog_1/noldorrohanlorespirit.txt',
                'gondorrohansilvantactics 1.1.txt' => 'questlog_1/gondorrohansilvantactics.txt',
            ]],
            'quest log as OCTGN' => ['/questlog/export/octgn/1', [
                'dwarfloreleadershiptactics 1.1.o8d' => 'questlog_1/dwarfloreleadershiptactics.o8d',
                'gondordunedainleadershipspirit 1.1.o8d' => 'questlog_1/gondordunedainleadershipspirit.o8d',
                'noldorrohanlorespirit 1.1.o8d' => 'questlog_1/noldorrohanlorespirit.o8d',
                'gondorrohansilvantactics 1.1.o8d' => 'questlog_1/gondorrohansilvantactics.o8d',
            ]],
        ];
    }

    /**
     * @dataProvider zipDownloadProvider
     */
    public function testZipDownload($uri, array $entries) {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $uri);
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode(), "GET $uri");
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));

        $file = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($file, $response->getContent());
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file));
        $actual = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $actual[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($file);

        $this->assertSame(array_keys($entries), array_keys($actual));
        foreach ($entries as $name => $snapshot) {
            $this->assertMatchesSnapshot('downloads/' . $snapshot, $actual[$name]);
        }
    }
}
