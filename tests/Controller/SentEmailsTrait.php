<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Mailer\Event\MessageEvents;
use Symfony\Component\Mime\Email;

/**
 * The emails sent during the last request, from the profiler (call $client->enableProfiler()
 * before the request).
 */
trait SentEmailsTrait
{
    private function sentMessageEvents(KernelBrowser $client): MessageEvents
    {
        $profile = $client->getProfile();
        self::assertNotFalse($profile, 'the profiler is not enabled');
        /** @var MessageDataCollector $collector */
        $collector = $profile->getCollector('mailer');

        return $collector->getEvents();
    }

    /**
     * @return Email[]
     */
    private function sentMessages(KernelBrowser $client): array
    {
        return $this->sentMessageEvents($client)->getMessages();
    }
}
