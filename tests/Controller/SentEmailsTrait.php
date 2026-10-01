<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mime\Email;

/**
 * The emails sent during the last request, from the profiler (call $client->enableProfiler()
 * before the request).
 */
trait SentEmailsTrait {
    /**
     * @return MessageEvents
     */
    private function sentMessageEvents(Client $client) {
        $profile = $client->getProfile();
        self::assertNotFalse($profile, 'the profiler is not enabled');
        /** @var MessageDataCollector $collector */
        $collector = $profile->getCollector('mailer');

        return $collector->getEvents();
    }

    /**
     * @param Client $client
     * @return Email[]
     */
    private function sentMessages(Client $client): array {
        return $this->sentMessageEvents($client)->getMessages();
    }
}
