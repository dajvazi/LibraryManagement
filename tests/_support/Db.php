<?php
declare(strict_types=1);

final class Db {
  public static function conn(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $name = 'libmanagement_test';

    $db = new mysqli($host, $user, $pass, $name);
    $db->set_charset('utf8mb4');
    return $db;
  }
    public static function exec(mysqli $con, string $sql): void {
    if ($con->multi_query($sql)) {
      while ($con->more_results() && $con->next_result()) {}
    } else {
      throw new RuntimeException("SQL error: {$con->error}");
    }
  }

  public static function truncate(mysqli $con, array $tables): void {
    $con->query("SET FOREIGN_KEY_CHECKS=0");
    foreach ($tables as $t) {
      $con->query("TRUNCATE TABLE `$t`");
    }
    $con->query("SET FOREIGN_KEY_CHECKS=1");
  }

}
