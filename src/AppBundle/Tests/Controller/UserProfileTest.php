<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Profile forms:
 * - the site's own profile form (GET /user/profile_edit, POST /user/profile_save);
 * - the FOSUserBundle forms: account (/profile/edit), password change
 *   (/profile/change-password), password reset (/resetting/*).
 *
 * The users' rows are restored in tearDown() (passwords included).
 */
class UserProfileTest extends WebTestCase {
    /** @var array */
    private $fixtureUsers;

    protected function setUp() {
        $this->fixtureUsers = $this->db(static::createClient())->fetchAll('SELECT * FROM user ORDER BY id');
    }

    protected function tearDown() {
        $connection = $this->db(static::createClient());
        foreach ($this->fixtureUsers as $user) {
            $connection->update('user', $user, ['id' => $user['id']]);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    private function login(Client $client, $username, $password) {
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $password]));

        return $client->getResponse()->isRedirect() && $client->getResponse()->headers->get('Location') !== 'http://localhost/login';
    }

    private function createAuthenticatedClient($username = 'test') {
        $client = static::createClient();
        $this->assertTrue($this->login($client, $username, $username), "Login as $username failed");

        return $client;
    }

    private function fetchUser(Client $client, $id = 1) {
        return $this->db($client)->fetchAssoc('SELECT * FROM user WHERE id = ?', [$id]);
    }

    private function profileForm(Client $client) {
        $crawler = $client->request('GET', '/user/profile_edit');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return $crawler->filter('form[action="/user/profile_save"]')->form();
    }

    /* ---------------------------------------------- site's profile form */

    public function testProfileFormIsPrefilled() {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);

        $this->assertSame('test', $form['username']->getValue());
        $this->assertSame('test@example.com', $form['email']->getValue());
        $this->assertSame('', $form['resume']->getValue());
        $this->assertTrue($form['notif_author']->hasValue());
        $this->assertTrue($form['notif_commenter']->hasValue());
        $this->assertTrue($form['notif_mention']->hasValue());
        $this->assertFalse($form['share_decks']->hasValue());
        $this->assertFalse($form['dark_mode']->hasValue());
        $this->assertFalse($form['user_sphere_code']->hasValue());
    }

    public function testEditProfile() {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['resume'] = 'I play <b>Dwarves</b>.';
        $form['user_sphere_code'] = 'lore';
        $form['notif_author']->untick();
        $form['notif_mention']->untick();
        $form['share_decks']->tick();
        $form['dark_mode']->tick();
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/user/profile_edit', $client->getResponse()->headers->get('Location'));
        $cookie = $client->getCookieJar()->get('dark_mode');
        $this->assertSame('1', $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());

        $user = $this->fetchUser($client);
        $this->assertSame([
            'username' => 'test', 'email' => 'test@example.com',
            // FILTER_SANITIZE_STRING strips the tags
            'resume' => 'I play Dwarves.', 'color' => 'lore',
            'is_notif_author' => '0', 'is_notif_commenter' => '1', 'is_notif_mention' => '0',
            'is_share_decks' => '1', 'dark_mode' => '1',
        ], array_intersect_key($user, array_flip(['username', 'email', 'resume', 'color', 'is_notif_author', 'is_notif_commenter', 'is_notif_mention', 'is_share_decks', 'dark_mode'])));

        $crawler = $client->followRedirect();
        $this->assertContains('Successfully saved your profile.', $crawler->filter('body')->text());
        $client->request('GET', '/api/public/user/info');
        $info = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['lore', true], [$info['sphere'], $info['dark_mode']]);

        // unticked checkboxes are not posted: they become false
        $form = $this->profileForm($client);
        $form['dark_mode']->untick();
        $client->submit($form);
        $this->assertSame('0', $this->fetchUser($client)['dark_mode']);
        $this->assertSame('0', $client->getCookieJar()->get('dark_mode')->getValue());
    }

    public function testRenameUser() {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['username'] = 'phpunit_renamed';
        $client->submit($form);

        $user = $this->fetchUser($client);
        $this->assertSame(['phpunit_renamed', 'phpunit_renamed'], [$user['username'], $user['username_canonical']]);
        $this->assertFalse($this->login(static::createClient(), 'test', 'test'));
        $this->assertTrue($this->login(static::createClient(), 'phpunit_renamed', 'test'));
    }

    public function testUsernameAlreadyTaken() {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['username'] = 'admin';
        $form['resume'] = 'Not saved';
        $client->submit($form);

        $this->assertSame('/user/profile_edit', $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains('Username admin is already taken.', $crawler->filter('body')->text());
        $user = $this->fetchUser($client);
        $this->assertSame(['test', null], [$user['username'], $user['resume']]);
    }

    /**
     * Unlike the username, the email is neither validated nor checked for uniqueness.
     */
    public function testEmailIsNotValidated() {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['email'] = 'not-an-email';
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $user = $this->fetchUser($client);
        $this->assertSame(['not-an-email', 'not-an-email'], [$user['email'], $user['email_canonical']]);
    }

    /**
     * BUG: a duplicated email is only stopped by the database's unique index (500).
     */
    public function testDuplicatedEmail() {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['email'] = 'admin@example.com';
        $client->submit($form);

        $this->assertSame(500, $client->getResponse()->getStatusCode());
        $this->assertSame('test@example.com', $this->fetchUser($client)['email']);
    }

    /* ------------------------------------------------ FOSUser: account */

    public function testFosProfileEditRequiresTheCurrentPassword() {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/profile/edit');
        $form = $crawler->filter('form[action="/profile/edit"]')->form([
            'fos_user_profile_form[email]' => 'phpunit_new@example.com',
            'fos_user_profile_form[current_password]' => 'wrong',
        ]);
        $crawler = $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('The entered password is invalid.', $crawler->filter('body')->text());
        $this->assertSame('test@example.com', $this->fetchUser($client)['email']);

        $form['fos_user_profile_form[current_password]'] = 'test';
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/profile/', $client->getResponse()->headers->get('Location'));
        $user = $this->fetchUser($client);
        $this->assertSame(['phpunit_new@example.com', 'phpunit_new@example.com'], [$user['email'], $user['email_canonical']]);
    }

    /* ------------------------------------------- FOSUser: change password */

    /**
     * @dataProvider invalidPasswordChangeProvider
     */
    public function testInvalidPasswordChange($current, $first, $second, $error) {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/profile/change-password');
        $crawler = $client->submit($crawler->filter('form[action="/profile/change-password"]')->form([
            'fos_user_change_password_form[current_password]' => $current,
            'fos_user_change_password_form[plainPassword][first]' => $first,
            'fos_user_change_password_form[plainPassword][second]' => $second,
        ]));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains($error, $crawler->filter('body')->text());
        $this->assertSame($this->fixtureUsers[0]['password'], $this->fetchUser($client)['password']);
    }

    public function invalidPasswordChangeProvider() {
        return [
            'wrong current password' => ['wrong', 'secret123', 'secret123', 'The entered password is invalid.'],
            'confirmation mismatch' => ['test', 'secret123', 'other123', 'The entered passwords don\'t match'],
        ];
    }

    public function testChangePassword() {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/profile/change-password');
        $client->submit($crawler->filter('form[action="/profile/change-password"]')->form([
            'fos_user_change_password_form[current_password]' => 'test',
            'fos_user_change_password_form[plainPassword][first]' => 'secret123',
            'fos_user_change_password_form[plainPassword][second]' => 'secret123',
        ]));

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/profile/', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->login(static::createClient(), 'test', 'test'));
        $this->assertTrue($this->login(static::createClient(), 'test', 'secret123'));
    }

    /* -------------------------------------------- FOSUser: reset password */

    public function testResetPassword() {
        $client = static::createClient();

        // 1. request: an email with a reset link is sent
        $crawler = $client->request('GET', '/resetting/request');
        $form = $crawler->filter('form[action="/resetting/send-email"]')->form(['username' => 'test']);
        $client->enableProfiler();
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/resetting/check-email?username=test', $client->getResponse()->headers->get('Location'));
        $messages = $client->getProfile()->getCollector('swiftmailer')->getMessages();
        $this->assertCount(1, $messages);
        $this->assertSame(['test@example.com'], array_keys($messages[0]->getTo()));
        $token = $this->fetchUser($client)['confirmation_token'];
        $this->assertNotEmpty($token);
        $this->assertContains("/resetting/reset/$token", $messages[0]->getBody());

        // 2. a second request is ignored while the first one is recent (no second email)
        $client->enableProfiler();
        $client->submit($form);
        $this->assertCount(0, $client->getProfile()->getCollector('swiftmailer')->getMessages());
        $this->assertSame($token, $this->fetchUser($client)['confirmation_token']);

        // 3. the link opens the reset form; the new password logs the user in
        $crawler = $client->request('GET', "/resetting/reset/$token");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $client->submit($crawler->filter("form[action=\"/resetting/reset/$token\"]")->form([
            'fos_user_resetting_form[plainPassword][first]' => 'secret123',
            'fos_user_resetting_form[plainPassword][second]' => 'secret123',
        ]));
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/profile/', $client->getResponse()->headers->get('Location'));
        $client->request('GET', '/decks');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 4. the token is consumed, and the new password works
        $this->assertNull($this->fetchUser($client)['confirmation_token']);
        $client->request('GET', "/resetting/reset/$token");
        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertTrue($this->login(static::createClient(), 'test', 'secret123'));
    }

    public function testResetPasswordOfUnknownUser() {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('POST', '/resetting/send-email', ['username' => 'nobody']);

        // no hint that the user does not exist, and no email
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/resetting/check-email?username=nobody', $client->getResponse()->headers->get('Location'));
        $this->assertCount(0, $client->getProfile()->getCollector('swiftmailer')->getMessages());
    }

    public function testUnknownResetToken() {
        $client = static::createClient();
        $client->request('GET', '/resetting/reset/unknown-token');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
