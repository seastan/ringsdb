<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase {
    public function testGetCard() {
        $client = static::createClient();
        $client->request('GET', '/api/public/card/01001');
        $response = $client->getResponse();
        $json = $response->getContent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertNotNull($data);
        $this->assertInternalType('array', $data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey("code", $data);
        $this->assertEquals("01001", $data['code']);
    }

    public function testListCards() {
        $client = static::createClient();
        $client->request('GET', '/api/public/cards/');
        $response = $client->getResponse();
        $json = $response->getContent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertNotNull($data);
        $this->assertInternalType('array', $data);
        $this->assertNotEmpty($data);
    }

    public function testListCardsByPack() {
        $client = static::createClient();
        $client->request('GET', '/api/public/cards/core');
        $response = $client->getResponse();
        $json = $response->getContent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertNotNull($data);
        $this->assertInternalType('array', $data);
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertInternalType('array', $item);
            $this->assertArrayHasKey("pack_code", $item);
            $this->assertEquals("Core", $item['pack_code']);
        }
    }

    public function testListPacks() {
        $client = static::createClient();
        $client->request('GET', '/api/public/packs/');
        $response = $client->getResponse();
        $json = $response->getContent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertNotNull($data);
        $this->assertInternalType('array', $data);
        $this->assertNotEmpty($data);
    }

    public function testGetDecklist() {
        $client = static::createClient();

        $id = static::$kernel
            ->getContainer()
            ->get('doctrine')
            ->getConnection()
            ->executeQuery("SELECT id FROM decklist LIMIT 1")
            ->fetchColumn();

        $client->request('GET', '/api/public/decklist/' . $id);
        $response = $client->getResponse();
        $json = $response->getContent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertNotNull($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey("id", $data);
        $this->assertEquals($id, $data['id']);
    }

    public function testListDecklists() {
        $client = static::createClient();

        $date = static::$kernel
            ->getContainer()
            ->get('doctrine')
            ->getConnection()
            ->executeQuery("SELECT DATE_FORMAT(date_creation, '%Y-%m-%d') FROM decklist LIMIT 1")
            ->fetchColumn();

        $client->request('GET', '/api/public/decklists/by_date/' . $date);
        $response = $client->getResponse();
        $json = $response->getContent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertNotNull($data);
        $this->assertInternalType('array', $data);
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertInternalType('array', $item);
            $this->assertArrayHasKey("date_creation", $item);
            $this->assertStringStartsWith($date, $item['date_creation']);
        }
    }
}
