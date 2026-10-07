<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * app:patron <email or username> [donation]: adds a donation to a user (users with a donation are
 * the "Gracious Patrons": badge next to their name, /patrons page, play simulator features),
 * or shows the total donation when no amount is given. Every line starts with the date (ISO 8601).
 *
 * The users' donations are restored in tearDown().
 */
class PatronCommandTest extends KernelTestCase
{
    private Connection $connection;

    private array $fixtureUsers;

    protected function setUp(): void
    {
        static::bootKernel();
        $this->connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        $this->fixtureUsers = $this->connection->fetchAllAssociative('SELECT id, donation FROM user');
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureUsers as $user) {
            $this->connection->update('user', $user, ['id' => $user['id']]);
        }

        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */
    /**
     * @param array<string, string> $arguments
     *
     * @return string the output, without the leading dates
     */
    private function runCommand(array $arguments): string
    {
        $application = new Application(static::$kernel);
        $tester = new CommandTester($application->find('app:patron'));

        $this->assertSame(0, (int) $tester->execute(['command' => 'app:patron'] + $arguments));
        $display = $tester->getDisplay();
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d[+-]\d\d:\d\d /', $display);

        return (string) preg_replace('/^\S+ /m', '', $display);
    }

    private function donation(string $username): int
    {
        return $this->connection->fetchOne('SELECT donation FROM user WHERE username = ?', [$username]);
    }

    /* -------------------------------------------------------------- tests */

    public function testAddADonationByEmail(): void
    {
        $this->assertSame("Success\n", $this->runCommand(['email' => 'test@example.com', 'donation' => '10']));
        $this->assertSame(10, $this->donation('test'));

        // donations add up
        $this->runCommand(['email' => 'test@example.com', 'donation' => '5']);
        $this->assertSame(15, $this->donation('test'));
        $this->assertSame(0, $this->donation('admin'));
    }

    public function testAddADonationByUsername(): void
    {
        $this->assertSame("Success\n", $this->runCommand(['email' => 'admin', 'donation' => '7']));
        $this->assertSame(7, $this->donation('admin'));
    }

    public function testShowTheDonation(): void
    {
        $this->connection->update('user', ['donation' => 25], ['username' => 'test']);

        $this->assertSame("User test donated 25\n", $this->runCommand(['email' => 'test']));
        // an amount of 0 also only shows the donation
        $this->assertSame("User test donated 25\n", $this->runCommand(['email' => 'test', 'donation' => '0']));
        $this->assertSame(25, $this->donation('test'));
    }

    public function testUnknownUser(): void
    {
        // not an error for the console: the exit code is 0
        $this->assertSame("Cannot find user [nobody@example.com]\n", $this->runCommand(['email' => 'nobody@example.com', 'donation' => '10']));
    }

    /**
     * Nothing checks the amount: a negative one is subtracted.
     */
    public function testNegativeDonation(): void
    {
        $this->connection->update('user', ['donation' => 10], ['username' => 'test']);

        $this->runCommand(['email' => 'test', 'donation' => '-4']);
        $this->assertSame(6, $this->donation('test'));
    }

    public function testEmailOrUsernameIsRequired(): void
    {
        $application = new Application(static::$kernel);
        $tester = new CommandTester($application->find('app:patron'));

        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments');
        $tester->execute(['command' => 'app:patron']);
    }
}
