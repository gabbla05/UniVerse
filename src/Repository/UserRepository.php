<?php

require_once 'Repository.php';
require_once __DIR__ . '/../Models/User.php';

class UserRepository extends Repository
{
    public function addUser(User $user): void
    {
        // Zapytanie do bazy (zakładamy, że masz tabelę 'users' z polami: name, surname, email, password)
        $stmt = $this->database->connect()->prepare('
            INSERT INTO users (name, surname, email, password)
            VALUES (?, ?, ?, ?)
        ');

        $stmt->execute([
            $user->getName(),
            $user->getSurname(),
            $user->getEmail(),
            $user->getPassword()
        ]);
    }
}