<?php
declare(strict_types=1);

final class Auth
{
    public function __construct(private $db) {}

    public function register(string $name, string $email, string $plainPassword, string $role='client'): array
    {
        if ($this->db->findUserByEmail($email)) {
            return ['ok'=>false, 'error'=>'EMAIL_EXISTS'];
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $id = $this->db->insertUser($name, $email, $hash, $role);

        return ['ok'=>true, 'user_id'=>$id];
    }

    public function login(string $email, string $plainPassword): array
    {
        $user = $this->db->findUserByEmail($email);
        if (!$user) return ['ok'=>false, 'error'=>'USER_NOT_FOUND'];

        if (!password_verify($plainPassword, $user['password'])) {
            return ['ok'=>false, 'error'=>'INVALID_PASSWORD'];
        }

        return ['ok'=>true, 'user'=>[
            'id'=>(int)$user['id'],
            'email'=>$user['email'],
            'role'=>$user['role'],
        ]];
    }
}
