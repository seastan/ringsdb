<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Registration / login / logout workflow (src/Controller/Security + security firewall "main").
 *
 * Relies on the "test" / "test" user loaded by LoadUserData.
 * Users created by these tests are prefixed with "phpunit_" and removed in tearDown().
 */
class SecurityControllerTest extends WebTestCase
{
    use SentEmailsTrait;
    use \App\Tests\LocationTrait;

    private KernelBrowser $client;

    public const PREFIX = 'phpunit_';

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $client = $this->client;
        /** @var EntityManagerInterface $em */
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\User u WHERE u.username LIKE :prefix')
            ->setParameter('prefix', self::PREFIX.'%')
            ->execute();
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function findUser(KernelBrowser $client, string $username): ?User
    {
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    private function submitRegistration(KernelBrowser $client, string $username, string $email, string $password, ?string $confirmation = null, bool $withProfiler = false): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $client->request('GET', '/register/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form.fos_user_registration_register')->form([
            'fos_user_registration_form[email]' => $email,
            'fos_user_registration_form[username]' => $username,
            'fos_user_registration_form[plainPassword][first]' => $password,
            'fos_user_registration_form[plainPassword][second]' => $confirmation ?? $password,
        ]);
        if ($withProfiler) {
            $client->enableProfiler();
        }

        return $client->submit($form);
    }

    private function login(KernelBrowser $client, string $username, string $password, bool $rememberMe = false): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $client->request('GET', '/login');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $values = ['_username' => $username, '_password' => $password];
        if ($rememberMe) {
            $values['_remember_me'] = 'on';
        }

        $form = $crawler->selectButton('_submit')->form($values);

        return $client->submit($form);
    }

    private function assertRedirectsTo(KernelBrowser $client, string $pathPattern): void
    {
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect(), 'Expected a redirect, got '.$response->getStatusCode());
        $this->assertRegExp($pathPattern, self::location($response));
    }

    private function assertAnonymous(KernelBrowser $client): void
    {
        $client->request('GET', '/decks');
        $this->assertRedirectsTo($client, '#/login$#');
    }

    private function assertAuthenticatedAs(KernelBrowser $client, string $username): void
    {
        $client->request('GET', '/decks');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/private/user/info');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertEquals($username, $data['name']);
    }

    /* ------------------------------------------------------- registration */

    public function testRegistrationPageDisplaysForm(): void
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/register/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form.fos_user_registration_register');
        $this->assertCount(1, $form);
        $this->assertEquals('/register/', $form->attr('action'));
        foreach (['email', 'username', 'plainPassword_first', 'plainPassword_second', '_token'] as $field) {
            $this->assertCount(1, $crawler->filter('#fos_user_registration_form_'.$field), "Missing field $field");
        }
    }

    public function testFullRegistrationWorkflow(): void
    {
        $username = self::PREFIX.'frodo';
        $email = 'phpunit_frodo@example.com';
        $client = $this->client;

        // 1. submit the form: a confirmation email is sent, the user must check their inbox
        $this->submitRegistration($client, $username, $email, 'secret123', null, true);
        $this->assertRedirectsTo($client, '#/register/check-email$#');

        $messages = $this->sentMessages($client);
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertEquals($email, $message->getTo()[0]->getAddress());

        // 2. user is created, disabled, with a confirmation token
        $user = $this->findUser($client, $username);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($email, $user->getEmail());
        $this->assertFalse($user->isEnabled());
        $this->assertNotEmpty($user->getConfirmationToken());
        $this->assertNotSame('secret123', $user->getPassword(), 'Password must be encoded');
        $token = $user->getConfirmationToken();
        $this->assertStringContainsString('/register/confirm/'.$token, str_replace("=\r\n", '', $message->getBody()->toString()));

        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString($email, $client->getResponse()->getContent());

        // 3. login is refused until the account is confirmed
        $this->login($client, $username, 'secret123');
        $this->assertRedirectsTo($client, '#/login$#');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Invalid credentials', $crawler->filter('.alert-danger')->text());
        $this->assertAnonymous($client);

        // 4. confirmation link enables the account and logs the user in
        $client->request('GET', '/register/confirm/'.$token);
        $this->assertRedirectsTo($client, '#/register/confirmed$#');
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $user = $this->findUser($client, $username);
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->isEnabled());
        $this->assertNull($user->getConfirmationToken());
        $this->assertAuthenticatedAs($client, $username);

        // 5. the token cannot be reused
        $client->request('GET', '/register/confirm/'.$token);
        $this->assertRedirectsTo($client, '#/login$#');

        // 6. after logout, the new credentials work
        $client->request('GET', '/logout');
        $this->assertAnonymous($client);
        $this->login($client, $username, 'secret123');
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertAuthenticatedAs($client, $username);
    }

    /**
     * @dataProvider invalidRegistrationProvider
     */
    public function testRegistrationValidationErrors(string $username, string $email, string $password, ?string $confirmation, string $expectedError): void
    {
        $client = $this->client;
        $crawler = $this->submitRegistration($client, $username, $email, $password, $confirmation);

        // form is displayed again, with errors, and no user is created
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('form.fos_user_registration_register'));
        $this->assertStringContainsString($expectedError, $client->getResponse()->getContent());
        if ('test' !== $username) {
            $this->assertNull($this->findUser($client, $username));
        }
    }

    /**
     * @return array<string, string[]|string[]|null[]>
     */
    public function invalidRegistrationProvider(): array
    {
        return [
            'password mismatch' => [self::PREFIX.'sam', 'phpunit_sam@example.com', 'secret123', 'other123', 'The entered passwords don'],
            'username taken' => ['test', 'phpunit_other@example.com', 'secret123', null, 'The username is already used'],
            'email taken' => [self::PREFIX.'merry', 'test@example.com', 'secret123', null, 'The email is already used'],
            'invalid email' => [self::PREFIX.'pippin', 'not-an-email', 'secret123', null, 'The email is not valid'],
            'username too short' => ['p', 'phpunit_p@example.com', 'secret123', null, 'The username is too short'],
        ];
    }

    public function testRegistrationRequiresCsrfToken(): void
    {
        $client = $this->client;
        $client->request('POST', '/register/', ['fos_user_registration_form' => [
            'email' => 'phpunit_csrf@example.com',
            'username' => self::PREFIX.'csrf',
            'plainPassword' => ['first' => 'secret123', 'second' => 'secret123'],
            '_token' => 'invalid',
        ]]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('The CSRF token is invalid', $client->getResponse()->getContent());
        $this->assertNull($this->findUser($client, self::PREFIX.'csrf'));
    }

    /* -------------------------------------------------------------- login */

    public function testLoginPageDisplaysForm(): void
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/login_check"]');
        $this->assertCount(1, $form);
        foreach (['_username', '_password', '_remember_me', '_csrf_token'] as $field) {
            $this->assertCount(1, $form->filter('input[name="'.$field.'"]'), "Missing field $field");
        }
    }

    public function testLoginWithUsername(): void
    {
        $client = $this->client;
        $this->login($client, 'test', 'test');

        $this->assertRedirectsTo($client, '#^http://localhost/$#');
        $this->assertAuthenticatedAs($client, 'test');
    }

    public function testLoginWithEmail(): void
    {
        $client = $this->client;
        $this->login($client, 'test@example.com', 'test');

        $this->assertRedirectsTo($client, '#^http://localhost/$#');
        $this->assertAuthenticatedAs($client, 'test');
    }

    public function testLoginRedirectsToOriginallyRequestedPage(): void
    {
        $client = $this->client;
        $client->request('GET', '/decks');
        $this->assertRedirectsTo($client, '#/login$#');

        $this->login($client, 'test', 'test');
        $this->assertRedirectsTo($client, '#^http://localhost/decks$#');
    }

    /**
     * @dataProvider invalidCredentialsProvider
     */
    public function testLoginWithInvalidCredentials(string $username, string $password): void
    {
        $client = $this->client;
        $this->login($client, $username, $password);

        $this->assertRedirectsTo($client, '#/login$#');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Invalid credentials.', $crawler->filter('.alert-danger')->text());
        $this->assertEquals($username, $crawler->filter('#username')->attr('value'));
        $this->assertAnonymous($client);
    }

    /**
     * @return array<string, string[]>
     */
    public function invalidCredentialsProvider(): array
    {
        return [
            'wrong password' => ['test', 'wrong'],
            'unknown user' => ['nobody', 'test'],
        ];
    }

    public function testLoginRequiresCsrfToken(): void
    {
        $client = $this->client;
        $client->request('GET', '/login');
        $client->request('POST', '/login_check', ['_username' => 'test', '_password' => 'test', '_csrf_token' => 'invalid']);

        $this->assertRedirectsTo($client, '#/login$#');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Invalid CSRF token.', $crawler->filter('.alert-danger')->text());
        $this->assertAnonymous($client);
    }

    public function testRememberMe(): void
    {
        $client = $this->client;
        $this->login($client, 'test', 'test', true);
        $this->assertTrue($client->getResponse()->isRedirect());

        $cookie = $client->getCookieJar()->get('REMEMBERME');
        $this->assertInstanceOf(\Symfony\Component\BrowserKit\Cookie::class, $cookie, 'REMEMBERME cookie must be set');

        // drop the session: the remember-me cookie alone must authenticate the user
        $client->getCookieJar()->clear();
        $client->getCookieJar()->set($cookie);
        $client->getContainer()->get('session')->invalidate();
        $this->assertAuthenticatedAs($client, 'test');
    }

    /* ------------------------------------------------------------- logout */

    public function testLogout(): void
    {
        $client = $this->client;
        $this->login($client, 'test', 'test', true);
        $this->assertAuthenticatedAs($client, 'test');

        $client->request('GET', '/logout');
        $this->assertRedirectsTo($client, '#^http://localhost/$#');
        $this->assertNull($client->getCookieJar()->get('REMEMBERME'), 'REMEMBERME cookie must be cleared');
        $this->assertAnonymous($client);
    }
}
