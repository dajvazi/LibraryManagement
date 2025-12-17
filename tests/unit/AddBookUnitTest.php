<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../_support/AddBook.php';
require_once __DIR__ . '/TestDb.php';

final class AddBookUnitTest extends TestCase
{
    public function test_add_book_success(): void
    {
        $db = new TestDb();
        $userId = $db->seedUser('U', 'u@mail.com', 'pass');

        $books = new Books($db);
        $res = $books->addBook($userId, 'Clean Code', 'Robert C. Martin', 'Programming', 'Reading');

        $this->assertTrue($res['ok']);
        $this->assertIsInt($res['book_id']);

        $book = $db->findBookById($res['book_id']);
        $this->assertNotNull($book);
        $this->assertSame('Clean Code', $book['title']);
        $this->assertSame((string)$userId, (string)$book['user_id']);
        $this->assertSame(1, $db->countBooksByUser($userId));
    }

    public function test_add_book_user_not_found(): void
    {
        $db = new TestDb();
        $books = new Books($db);

        $res = $books->addBook(999, 'X', 'Y');

        $this->assertFalse($res['ok']);
        $this->assertSame('USER_NOT_FOUND', $res['error']);
    }

    public function test_add_book_requires_title_and_author(): void
    {
        $db = new TestDb();
        $userId = $db->seedUser('U', 'u2@mail.com', 'pass');

        $books = new Books($db);

        $r1 = $books->addBook($userId, '', 'A');
        $this->assertFalse($r1['ok']);
        $this->assertSame('TITLE_REQUIRED', $r1['error']);

        $r2 = $books->addBook($userId, 'T', '');
        $this->assertFalse($r2['ok']);
        $this->assertSame('AUTHOR_REQUIRED', $r2['error']);
    }

    public function test_add_book_invalid_user_id(): void
    {
        $db = new TestDb();
        $books = new Books($db);

        $res = $books->addBook(0, 'T', 'A');

        $this->assertFalse($res['ok']);
        $this->assertSame('INVALID_USER', $res['error']);
    }
}
