<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

/**
 * The emails sent during the last request, from the profiler (call $client->enableProfiler()
 * before the request).
 */
trait SentEmailsTrait {
    /**
     * @return \Swift_Message[]
     */
    private function sentMessages(Client $client) {
        $profile = $client->getProfile();
        self::assertNotFalse($profile, 'the profiler is not enabled');
        /** @var MessageDataCollector $collector */
        $collector = $profile->getCollector('swiftmailer');

        return $collector->getMessages();
    }
}
