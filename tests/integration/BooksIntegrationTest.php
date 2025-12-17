<?php
declare(strict_types=1);
require_once __DIR__ . '/../_support/BaseIntegration.php';

final class BooksIntegrationTest extends BaseIntegration {
  public function test_insert_book_and_read_back(): void {
    $stmt = $this->con->prepare("
      INSERT INTO books (title, author, genre, status, user_id)
      VALUES (?,?,?,?,?)
    ");

    $title = "Clean Code";
    $author = "Robert C. Martin";
    $genre = "Programming";
    $status = "Reading";
    $userId = 1;

    $stmt->bind_param("ssssi", $title, $author, $genre, $status, $userId);
    $this->assertTrue($stmt->execute());

    $res = $this->con->query("SELECT title, author, user_id FROM books WHERE user_id=1 LIMIT 1");
    $row = $res->fetch_assoc();

    $this->assertSame("Clean Code", $row['title']);
    $this->assertSame("Robert C. Martin", $row['author']);
    $this->assertSame("1", (string)$row['user_id']);
  }

  public function test_delete_user_cascades_books(): void {
    $email = 'cascade_' . uniqid() . '@test.com';
    $userId = $this->createUser('Cascade User', $email, 'client');

    $this->insertBook("Book A", "Author A", "Genre A", "Reading", $userId);
    $this->insertBook("Book B", "Author B", "Genre B", "Completed", $userId);

    $countBefore = $this->countBooksByUserId($userId);
    $this->assertSame(2, $countBefore);

    $stmt = $this->con->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $this->assertTrue($stmt->execute());

    $countAfter = $this->countBooksByUserId($userId);
    $this->assertSame(0, $countAfter);
  }

  private function createUser(string $name, string $email, string $role): int {
    $hash = password_hash('secret123', PASSWORD_BCRYPT);

    $stmt = $this->con->prepare("INSERT INTO users(name,email,password,role) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $email, $hash, $role);
    $this->assertTrue($stmt->execute());

    return (int)$this->con->insert_id;
  }

  private function insertBook(string $title, string $author, string $genre, string $status, int $userId): void {
    $stmt = $this->con->prepare("
      INSERT INTO books (title, author, genre, status, user_id)
      VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param("ssssi", $title, $author, $genre, $status, $userId);
    $this->assertTrue($stmt->execute());
  }

  private function countBooksByUserId(int $userId): int {
    $stmt = $this->con->prepare("SELECT COUNT(*) AS c FROM books WHERE user_id=?");
    $stmt->bind_param("i", $userId);
    $this->assertTrue($stmt->execute());
    $row = $stmt->get_result()->fetch_assoc();
    return (int)$row['c'];
  }
}
