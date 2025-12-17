<?php
declare(strict_types=1);

final class Books
{
    public function __construct(private $db) {}

    public function addBook(int $userId, string $title, string $author, string $genre='Other', string $status='Reading'): array
    {
        if ($userId <= 0) return ['ok'=>false, 'error'=>'INVALID_USER'];
        if (trim($title) === '') return ['ok'=>false, 'error'=>'TITLE_REQUIRED'];
        if (trim($author) === '') return ['ok'=>false, 'error'=>'AUTHOR_REQUIRED'];

        $bookId = $this->db->insertBook($userId, $title, $author, $genre, $status);
        return ['ok'=>true, 'book_id'=>$bookId];
    }
}
