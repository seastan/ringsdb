<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Card search by name (CardsData::get_search_rows), through the public API
 * (/api/public/cards/search/{q}) and the site search (/find?q=...).
 *
 * A term in capitals (2 letters or more) is also searched as an acronym: case-sensitively in the
 * name (BINARY, AppBundle\DQL\BinaryFunction), and as the initials of the words of the name, dashes
 * counting as spaces (REPLACE, AppBundle\DQL\ReplaceFunction).
 */
class CardSearchTest extends WebTestCase {

    /**
     * @return string[] the names of the cards found by the API
     */
    private function search($q) {
        $client = static::createClient();
        $client->request('GET', '/api/public/cards/search/' . rawurlencode($q));
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "search $q");

        return array_column(json_decode($client->getResponse()->getContent(), true), 'name');
    }

    /**
     * @dataProvider acronymProvider
     */
    public function testAcronym($acronym, array $expected) {
        $names = $this->search($acronym);
        sort($names);

        $this->assertSame($expected, $names);
    }

    public function acronymProvider() {
        return [
            'initials' => ['LOS', ['Longbeard Orc Slayer']],
            // small words count: the initials are matched case-insensitively
            'with "of"' => ['SOG', ['Soldier of Gondor', 'Steward of Gondor']],
            // a dash counts as a space
            'with a dash' => ['LMM', ['Longbeard Map-Maker']],
            'with a dash, several cards' => ['WHB', ['Westfold Horse-breaker', 'Westfold Horse-breeder']],
        ];
    }

    public function testAcronymsAreCaseSensitive() {
        // in lower case, "los" is only searched in the names, case-insensitively
        $names = $this->search('los');
        $this->assertNotContains('Longbeard Orc Slayer', $names);
        foreach ($names as $name) {
            $this->assertContains('los', strtolower($name));
        }
    }

    public function testSiteSearch() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/find?q=LOS');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Longbeard Orc Slayer', $crawler->filter('body')->text());
    }
}
