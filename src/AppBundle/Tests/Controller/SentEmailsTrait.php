<?php

namespace AppBundle\Tests\Controller;

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
        /** @var MessageDataCollector $collector */
        $collector = $client->getProfile()->getCollector('swiftmailer');

        return $collector->getMessages();
    }
}
