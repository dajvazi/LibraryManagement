<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../_support/Auth.php';
require_once __DIR__ . '/TestDb.php';


final class AuthUnitTest extends TestCase
{
    public function test_register_success(): void
    {
        $db = new TestDb();
        $auth = new Auth($db);

        $res = $auth->register('Test', 'a@mail.com', '123456', 'client');

        $this->assertTrue($res['ok']);
        $this->assertIsInt($res['user_id']);
        $this->assertNotNull($db->findUserByEmail('a@mail.com'));
    }

    public function test_register_email_exists(): void
    {
        $db = new TestDb();
        $db->seedUser('X', 'dup@mail.com', 'pass');

        $auth = new Auth($db);
        $res = $auth->register('Y', 'dup@mail.com', '123456');

        $this->assertFalse($res['ok']);
        $this->assertSame('EMAIL_EXISTS', $res['error']);
    }

    public function test_login_success(): void
    {
        $db = new TestDb();
        $db->seedUser('X', 'login@mail.com', 'pass123', 'admin');

        $auth = new Auth($db);
        $res = $auth->login('login@mail.com', 'pass123');

        $this->assertTrue($res['ok']);
        $this->assertSame('login@mail.com', $res['user']['email']);
        $this->assertSame('admin', $res['user']['role']);
        $this->assertIsInt($res['user']['id']);
    }

    public function test_login_wrong_password(): void
    {
        $db = new TestDb();
        $db->seedUser('X', 'wp@mail.com', 'pass123', 'client');

        $auth = new Auth($db);
        $res = $auth->login('wp@mail.com', 'wrong');

        $this->assertFalse($res['ok']);
        $this->assertSame('INVALID_PASSWORD', $res['error']);
    }

    public function test_login_user_not_found(): void
    {
        $db = new TestDb();
        $auth = new Auth($db);

        $res = $auth->login('no@mail.com', 'pass');

        $this->assertFalse($res['ok']);
        $this->assertSame('USER_NOT_FOUND', $res['error']);
    }
}
