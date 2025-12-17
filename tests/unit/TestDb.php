<?php
declare(strict_types=1);

final class TestDb
{
    private array $usersByEmail = []; // email => user
    private array $usersById = [];    // id => user
    private array $books = [];        // list e librave
    private int $nextUserId = 1;
    private int $nextBookId = 1;

    // ---------- USERS ----------
    public function findUserByEmail(string $email): ?array
    {
        return $this->usersByEmail[$email] ?? null;
    }

    public function findUserById(int $id): ?array
    {
        return $this->usersById[$id] ?? null;
    }

    public function insertUser(string $name, string $email, string $passwordHash, string $role): int
    {
        $id = $this->nextUserId++;
        $user = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => $role,
        ];
        $this->usersByEmail[$email] = $user;
        $this->usersById[$id] = $user;
        return $id;
    }

    public function seedUser(string $name, string $email, string $plainPassword, string $role='client'): int
    {
        return $this->insertUser($name, $email, password_hash($plainPassword, PASSWORD_BCRYPT), $role);
    }

    // ---------- BOOKS ----------
    public function insertBook(int $userId, string $title, string $author, string $genre, string $status): int
    {
        $id = $this->nextBookId++;
        $this->books[$id] = [
            'id' => $id,
            'user_id' => $userId,
            'title' => $title,
            'author' => $author,
            'genre' => $genre,
            'status' => $status,
        ];
        return $id;
    }

    public function findBookById(int $id): ?array
    {
        return $this->books[$id] ?? null;
    }

    public function countBooksByUser(int $userId): int
    {
        $c = 0;
        foreach ($this->books as $b) {
            if ($b['user_id'] === $userId) $c++;
        }
        return $c;
    }
}
