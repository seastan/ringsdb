<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Registration / login / logout workflow (FOSUserBundle + security firewall "default").
 *
 * Relies on the "test" / "test" user loaded by LoadUserData.
 * Users created by these tests are prefixed with "phpunit_" and removed in tearDown().
 */
class SecurityControllerTest extends WebTestCase {
    const PREFIX = 'phpunit_';

    protected function tearDown() {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM AppBundle:User u WHERE u.username LIKE :prefix')
            ->setParameter('prefix', self::PREFIX . '%')
            ->execute();
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function findUser(Client $client, $username) {
        $em = $client->getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    private function submitRegistration(Client $client, $username, $email, $password, $confirmation = null, $withProfiler = false) {
        $crawler = $client->request('GET', '/register/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form.fos_user_registration_register')->form([
            'fos_user_registration_form[email]' => $email,
            'fos_user_registration_form[username]' => $username,
            'fos_user_registration_form[plainPassword][first]' => $password,
            'fos_user_registration_form[plainPassword][second]' => $confirmation === null ? $password : $confirmation,
        ]);
        if ($withProfiler) {
            $client->enableProfiler();
        }

        return $client->submit($form);
    }

    private function login(Client $client, $username, $password, $rememberMe = false) {
        $crawler = $client->request('GET', '/login');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $values = ['_username' => $username, '_password' => $password];
        if ($rememberMe) {
            $values['_remember_me'] = 'on';
        }
        $form = $crawler->selectButton('_submit')->form($values);

        return $client->submit($form);
    }

    private function assertRedirectsTo(Client $client, $pathPattern) {
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect(), 'Expected a redirect, got ' . $response->getStatusCode());
        $this->assertRegExp($pathPattern, $response->headers->get('Location'));
    }

    private function assertAnonymous(Client $client) {
        $client->request('GET', '/decks');
        $this->assertRedirectsTo($client, '#/login$#');
    }

    private function assertAuthenticatedAs(Client $client, $username) {
        $client->request('GET', '/decks');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/public/user/info');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertInternalType('array', $data);
        $this->assertEquals($username, $data['name']);
    }

    /* ------------------------------------------------------- registration */

    public function testRegistrationPageDisplaysForm() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form.fos_user_registration_register');
        $this->assertCount(1, $form);
        $this->assertEquals('/register/', $form->attr('action'));
        foreach (['email', 'username', 'plainPassword_first', 'plainPassword_second', '_token'] as $field) {
            $this->assertCount(1, $crawler->filter('#fos_user_registration_form_' . $field), "Missing field $field");
        }
    }

    public function testFullRegistrationWorkflow() {
        $username = self::PREFIX . 'frodo';
        $email = 'phpunit_frodo@example.com';
        $client = static::createClient();

        // 1. submit the form: a confirmation email is sent, the user must check their inbox
        $this->submitRegistration($client, $username, $email, 'secret123', null, true);
        $this->assertRedirectsTo($client, '#/register/check-email$#');

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());
        /** @var \Swift_Message $message */
        $message = $mailCollector->getMessages()[0];
        $this->assertEquals([$email => null], $message->getTo());

        // 2. user is created, disabled, with a confirmation token
        $user = $this->findUser($client, $username);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertFalse($user->isEnabled());
        $this->assertNotEmpty($user->getConfirmationToken());
        $this->assertNotEquals('secret123', $user->getPassword(), 'Password must be encoded');
        $token = $user->getConfirmationToken();
        $this->assertContains('/register/confirm/' . $token, $message->getBody());

        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains($email, $client->getResponse()->getContent());

        // 3. login is refused until the account is confirmed
        $this->login($client, $username, 'secret123');
        $this->assertRedirectsTo($client, '#/login$#');
        $crawler = $client->followRedirect();
        $this->assertContains('Account is disabled', $crawler->filter('.alert-danger')->text());
        $this->assertCount(1, $crawler->filter('a[href="/user/remind/' . $username . '"]'));
        $this->assertAnonymous($client);

        // 4. confirmation link enables the account and logs the user in
        $client->request('GET', '/register/confirm/' . $token);
        $this->assertRedirectsTo($client, '#/register/confirmed$#');
        $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $user = $this->findUser($client, $username);
        $this->assertTrue($user->isEnabled());
        $this->assertNull($user->getConfirmationToken());
        $this->assertAuthenticatedAs($client, $username);

        // 5. the token cannot be reused
        $client->request('GET', '/register/confirm/' . $token);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

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
    public function testRegistrationValidationErrors($username, $email, $password, $confirmation, $expectedError) {
        $client = static::createClient();
        $crawler = $this->submitRegistration($client, $username, $email, $password, $confirmation);

        // form is displayed again, with errors, and no user is created
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('form.fos_user_registration_register'));
        $this->assertContains($expectedError, $client->getResponse()->getContent());
        if ($username !== 'test') {
            $this->assertNull($this->findUser($client, $username));
        }
    }

    public function invalidRegistrationProvider() {
        return [
            'password mismatch' => [self::PREFIX . 'sam', 'phpunit_sam@example.com', 'secret123', 'other123', 'The entered passwords don'],
            'username taken' => ['test', 'phpunit_other@example.com', 'secret123', null, 'The username is already used'],
            'email taken' => [self::PREFIX . 'merry', 'test@example.com', 'secret123', null, 'The email is already used'],
            'invalid email' => [self::PREFIX . 'pippin', 'not-an-email', 'secret123', null, 'The email is not valid'],
            'username too short' => ['p', 'phpunit_p@example.com', 'secret123', null, 'The username is too short'],
        ];
    }

    public function testRegistrationRequiresCsrfToken() {
        $client = static::createClient();
        $client->request('POST', '/register/', ['fos_user_registration_form' => [
            'email' => 'phpunit_csrf@example.com',
            'username' => self::PREFIX . 'csrf',
            'plainPassword' => ['first' => 'secret123', 'second' => 'secret123'],
            '_token' => 'invalid',
        ]]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('The CSRF token is invalid', $client->getResponse()->getContent());
        $this->assertNull($this->findUser($client, self::PREFIX . 'csrf'));
    }

    /* -------------------------------------------------------------- login */

    public function testLoginPageDisplaysForm() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/login_check"]');
        $this->assertCount(1, $form);
        foreach (['_username', '_password', '_remember_me', '_csrf_token'] as $field) {
            $this->assertCount(1, $form->filter('input[name="' . $field . '"]'), "Missing field $field");
        }
    }

    public function testProtectedPageRedirectsAnonymousToLogin() {
        $client = static::createClient();
        $this->assertAnonymous($client);

        $client->request('GET', '/api/public/user/info');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testLoginWithUsername() {
        $client = static::createClient();
        $this->login($client, 'test', 'test');

        $this->assertRedirectsTo($client, '#^http://localhost/$#');
        $this->assertAuthenticatedAs($client, 'test');
    }

    public function testLoginWithEmail() {
        $client = static::createClient();
        $this->login($client, 'test@example.com', 'test');

        $this->assertRedirectsTo($client, '#^http://localhost/$#');
        $this->assertAuthenticatedAs($client, 'test');
    }

    public function testLoginRedirectsToOriginallyRequestedPage() {
        $client = static::createClient();
        $client->request('GET', '/decks');
        $this->assertRedirectsTo($client, '#/login$#');

        $this->login($client, 'test', 'test');
        $this->assertRedirectsTo($client, '#^http://localhost/decks$#');
    }

    /**
     * @dataProvider invalidCredentialsProvider
     */
    public function testLoginWithInvalidCredentials($username, $password) {
        $client = static::createClient();
        $this->login($client, $username, $password);

        $this->assertRedirectsTo($client, '#/login$#');
        $crawler = $client->followRedirect();
        $this->assertContains('Invalid credentials.', $crawler->filter('.alert-danger')->text());
        $this->assertEquals($username, $crawler->filter('#username')->attr('value'));
        $this->assertAnonymous($client);
    }

    public function invalidCredentialsProvider() {
        return [
            'wrong password' => ['test', 'wrong'],
            'unknown user' => ['nobody', 'test'],
        ];
    }

    public function testLoginRequiresCsrfToken() {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->request('POST', '/login_check', ['_username' => 'test', '_password' => 'test', '_csrf_token' => 'invalid']);

        $this->assertRedirectsTo($client, '#/login$#');
        $crawler = $client->followRedirect();
        $this->assertContains('Invalid CSRF token.', $crawler->filter('.alert-danger')->text());
        $this->assertAnonymous($client);
    }

    public function testRememberMe() {
        $client = static::createClient();
        $this->login($client, 'test', 'test', true);
        $this->assertTrue($client->getResponse()->isRedirect());

        $cookie = $client->getCookieJar()->get('REMEMBERME');
        $this->assertNotNull($cookie, 'REMEMBERME cookie must be set');

        // drop the session: the remember-me cookie alone must authenticate the user
        $client->getCookieJar()->clear();
        $client->getCookieJar()->set($cookie);
        $client->getContainer()->get('session')->invalidate();
        $this->assertAuthenticatedAs($client, 'test');
    }

    /* ------------------------------------------------------------- logout */

    public function testLogout() {
        $client = static::createClient();
        $this->login($client, 'test', 'test', true);
        $this->assertAuthenticatedAs($client, 'test');

        $client->request('GET', '/logout');
        $this->assertRedirectsTo($client, '#^http://localhost/$#');
        $this->assertNull($client->getCookieJar()->get('REMEMBERME'), 'REMEMBERME cookie must be cleared');
        $this->assertAnonymous($client);
    }
}
