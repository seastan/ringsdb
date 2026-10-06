<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

/**
 * Profile forms:
 * - the site's own profile form (GET /user/profile_edit, POST /user/profile_save);
 * - the account forms (formerly FOSUserBundle's): account (/profile/edit), password change
 *   (/profile/change-password), password reset (/resetting/*).
 *
 * The users' rows are restored in tearDown() (passwords included).
 */
class UserProfileTest extends WebTestCase
{
    use SentEmailsTrait;
    use \App\Tests\FormFieldTrait;

    private KernelBrowser $client;

    private array $fixtureUsers;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->fixtureUsers = $this->db()->fetchAll('SELECT * FROM user ORDER BY id');
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        foreach ($this->fixtureUsers as $user) {
            $connection->update('user', $user, ['id' => $user['id']]);
        }

        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function db(): \Doctrine\DBAL\Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function login(KernelBrowser $client, $username, $password): bool
    {
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $password]));

        return $client->getResponse()->isRedirect() && 'http://localhost/login' !== $client->getResponse()->headers->get('Location');
    }

    /**
     * @param string $username
     */
    private function createAuthenticatedClient($username = 'test'): KernelBrowser
    {
        $client = $this->client;
        $this->assertTrue($this->login($client, $username, $username), "Login as $username failed");

        return $client;
    }

    /**
     * @param int $id
     */
    private function fetchUser($id = 1)
    {
        return $this->db()->fetchAssoc('SELECT * FROM user WHERE id = ?', [$id]);
    }

    private function checkbox(Form $form, string $name): ChoiceFormField
    {
        $field = $form[$name];
        if (!$field instanceof ChoiceFormField) {
            throw new \UnexpectedValueException("$name is not a checkbox");
        }

        return $field;
    }

    private function profileForm(KernelBrowser $client): Form
    {
        $crawler = $client->request('GET', '/user/profile_edit');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return $crawler->filter('form[action="/user/profile_save"]')->form();
    }

    /* ---------------------------------------------- site's profile form */

    public function testProfileFormIsPrefilled(): void
    {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);

        $this->assertSame('test', self::field($form, 'username')->getValue());
        $this->assertSame('test@example.com', self::field($form, 'email')->getValue());
        $this->assertSame('', self::field($form, 'resume')->getValue());
        $this->assertTrue(self::field($form, 'notif_author')->hasValue());
        $this->assertTrue(self::field($form, 'notif_commenter')->hasValue());
        $this->assertTrue(self::field($form, 'notif_mention')->hasValue());
        $this->assertFalse(self::field($form, 'share_decks')->hasValue());
        $this->assertFalse(self::field($form, 'dark_mode')->hasValue());
        $this->assertFalse(self::field($form, 'user_sphere_code')->hasValue());
    }

    public function testEditProfile(): void
    {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['resume'] = 'I play <b>Dwarves</b>.';
        $form['user_sphere_code'] = 'lore';
        $this->checkbox($form, 'notif_author')->untick();
        $this->checkbox($form, 'notif_mention')->untick();
        $this->checkbox($form, 'share_decks')->tick();
        $this->checkbox($form, 'dark_mode')->tick();
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/user/profile_edit', $client->getResponse()->headers->get('Location'));
        $cookie = $client->getCookieJar()->get('dark_mode');
        $this->assertInstanceOf(\Symfony\Component\BrowserKit\Cookie::class, $cookie);
        $this->assertSame('1', $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());

        $user = $this->fetchUser();
        $this->assertSame([
            'username' => 'test', 'email' => 'test@example.com',
            // FILTER_SANITIZE_STRING strips the tags
            'resume' => 'I play Dwarves.', 'color' => 'lore',
            'is_notif_author' => '0', 'is_notif_commenter' => '1', 'is_notif_mention' => '0',
            'is_share_decks' => '1', 'dark_mode' => '1',
        ], array_intersect_key($user, array_flip(['username', 'email', 'resume', 'color', 'is_notif_author', 'is_notif_commenter', 'is_notif_mention', 'is_share_decks', 'dark_mode'])));

        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Successfully saved your profile.', $crawler->filter('body')->text());
        $client->request('GET', '/api/private/user/info');
        $info = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['lore', true], [$info['sphere'], $info['dark_mode']]);

        // unticked checkboxes are not posted: they become false
        $form = $this->profileForm($client);
        $this->checkbox($form, 'dark_mode')->untick();
        $client->submit($form);
        $this->assertSame('0', $this->fetchUser()['dark_mode']);
        $cookie = $client->getCookieJar()->get('dark_mode');
        $this->assertInstanceOf(\Symfony\Component\BrowserKit\Cookie::class, $cookie);
        $this->assertSame('0', $cookie->getValue());
    }

    public function testRenameUser(): void
    {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['username'] = 'phpunit_renamed';
        $client->submit($form);

        $user = $this->fetchUser();
        $this->assertSame(['phpunit_renamed', 'phpunit_renamed'], [$user['username'], $user['username_canonical']]);
        $this->assertFalse($this->login($this->client, 'test', 'test'));
        $this->assertTrue($this->login($this->client, 'phpunit_renamed', 'test'));
    }

    public function testUsernameAlreadyTaken(): void
    {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['username'] = 'admin';
        $form['resume'] = 'Not saved';
        $client->submit($form);

        $this->assertSame('/user/profile_edit', $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Username admin is already taken.', $crawler->filter('body')->text());
        $user = $this->fetchUser();
        $this->assertSame(['test', null], [$user['username'], $user['resume']]);
    }

    /**
     * Unlike the username, the email is neither validated nor checked for uniqueness.
     */
    public function testEmailIsNotValidated(): void
    {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['email'] = 'not-an-email';
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $user = $this->fetchUser();
        $this->assertSame(['not-an-email', 'not-an-email'], [$user['email'], $user['email_canonical']]);
    }

    /**
     * BUG: a duplicated email is only stopped by the database's unique index (500).
     */
    public function testDuplicatedEmail(): void
    {
        $client = $this->createAuthenticatedClient();
        $form = $this->profileForm($client);
        $form['email'] = 'admin@example.com';
        $client->submit($form);

        $this->assertSame(500, $client->getResponse()->getStatusCode());
        $this->assertSame('test@example.com', $this->fetchUser()['email']);
    }

    /* ---------------------------------------------------------- account */

    public function testFosProfileEditRequiresTheCurrentPassword(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/profile/edit');
        $form = $crawler->filter('form[action="/profile/edit"]')->form([
            'fos_user_profile_form[email]' => 'phpunit_new@example.com',
            'fos_user_profile_form[current_password]' => 'wrong',
        ]);
        $crawler = $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('The entered password is invalid.', $crawler->filter('body')->text());
        $this->assertSame('test@example.com', $this->fetchUser()['email']);

        $form['fos_user_profile_form[current_password]'] = 'test';
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/profile/', $client->getResponse()->headers->get('Location'));
        $user = $this->fetchUser();
        $this->assertSame(['phpunit_new@example.com', 'phpunit_new@example.com'], [$user['email'], $user['email_canonical']]);
    }

    /* -------------------------------------------------- change password */

    /**
     * @dataProvider invalidPasswordChangeProvider
     */
    public function testInvalidPasswordChange(string $current, string $first, string $second, string $error): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/profile/change-password');
        $crawler = $client->submit($crawler->filter('form[action="/profile/change-password"]')->form([
            'fos_user_change_password_form[current_password]' => $current,
            'fos_user_change_password_form[plainPassword][first]' => $first,
            'fos_user_change_password_form[plainPassword][second]' => $second,
        ]));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString($error, $crawler->filter('body')->text());
        $this->assertSame($this->fixtureUsers[0]['password'], $this->fetchUser()['password']);
    }

    /**
     * @return array<string, string[]>
     */
    public function invalidPasswordChangeProvider(): array
    {
        return [
            'wrong current password' => ['wrong', 'secret123', 'secret123', 'The entered password is invalid.'],
            'confirmation mismatch' => ['test', 'secret123', 'other123', "The entered passwords don't match"],
        ];
    }

    public function testChangePassword(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/profile/change-password');
        $client->submit($crawler->filter('form[action="/profile/change-password"]')->form([
            'fos_user_change_password_form[current_password]' => 'test',
            'fos_user_change_password_form[plainPassword][first]' => 'secret123',
            'fos_user_change_password_form[plainPassword][second]' => 'secret123',
        ]));

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/profile/', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->login($this->client, 'test', 'test'));
        $this->assertTrue($this->login($this->client, 'test', 'secret123'));
    }

    /* --------------------------------------------------- reset password */

    public function testResetPassword(): void
    {
        $client = $this->client;

        // 1. request: an email with a reset link is sent
        $crawler = $client->request('GET', '/resetting/request');
        $form = $crawler->filter('form[action="/resetting/send-email"]')->form(['username' => 'test']);
        $client->enableProfiler();
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/resetting/check-email?username=test', $client->getResponse()->headers->get('Location'));
        $messages = $this->sentMessages($client);
        $this->assertCount(1, $messages);
        $this->assertEquals('test@example.com', $messages[0]->getTo()[0]->getAddress());
        $token = $this->fetchUser()['confirmation_token'];
        $this->assertNotEmpty($token);
        $this->assertStringContainsString("/resetting/reset/$token", str_replace("=\r\n", '', $messages[0]->getBody()->toString()));

        // 2. a second request is ignored while the first one is recent (no second email)
        $client->enableProfiler();
        $client->submit($form);
        $this->assertCount(0, $this->sentMessages($client));
        $this->assertSame($token, $this->fetchUser()['confirmation_token']);

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
        $this->assertNull($this->fetchUser()['confirmation_token']);
        $client->request('GET', "/resetting/reset/$token");
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/login', $client->getResponse()->headers->get('Location'));
        $this->assertTrue($this->login($this->client, 'test', 'secret123'));
    }

    public function testResetPasswordOfUnknownUser(): void
    {
        $client = $this->client;
        $client->enableProfiler();
        $client->request('POST', '/resetting/send-email', ['username' => 'nobody']);

        // no hint that the user does not exist, and no email
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/resetting/check-email?username=nobody', $client->getResponse()->headers->get('Location'));
        $this->assertCount(0, $this->sentMessages($client));
    }

    public function testUnknownResetToken(): void
    {
        $client = $this->client;
        $client->request('GET', '/resetting/reset/unknown-token');

        // redirect to the login page (a 404 before FOSUserBundle 2.1)
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/login', $client->getResponse()->headers->get('Location'));
    }
}
