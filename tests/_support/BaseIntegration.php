<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Db.php';

abstract class BaseIntegration extends TestCase {
  protected mysqli $con;

  protected function setUp(): void {
    $this->con = Db::conn();

    Db::truncate($this->con, ['books', 'users']);

    $this->con->query("
      INSERT INTO users (id, name, email, password, role)
      VALUES (1, 'Test', 'test@mail.com', 'hash', 'client')
    ");
  }

  protected function tearDown(): void {
    $this->con->close();
  }
}