<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

class UserRepository extends Repository {

    public function getUser(string $email): ?User 
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM public.users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user == false) {
            return null;
        }

        return new User(
            $user['email'],
            $user['password'],
            $user['name'],
            $user['surname'],
            $user['student_id'],
            $user['university_id'],
            $user['faculty_id'],
            $user['role'], // Przekazujemy rolę
            $user['id']    // ZMIANA: Przekazujemy ID z bazy!
        );
    }

    public function addUser(User $user)
    {
        // ZMIANA: Dodajemy kolumnę 'role' do INSERTa
        $stmt = $this->database->connect()->prepare('
            INSERT INTO users (name, surname, email, password, student_id, university_id, faculty_id, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $user->getName(),
            $user->getSurname(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getStudentId(),
            $user->getUniversityId(),
            $user->getFacultyId(),
            $user->getRole() // Przekazujemy rolę (np. 'uni_admin')
        ]);
    }

    // Metoda do zmiany danych (Imię, Nazwisko)
    public function updateUserInfo(int $userId, string $name, string $surname) {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET name = :name, surname = :surname WHERE id = :id
        ');
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':surname', $surname, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Metoda do zmiany hasła
    public function changePassword(int $userId, string $newHashedPassword) {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET password = :password WHERE id = :id
        ');
        $stmt->bindParam(':password', $newHashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Metoda do pobrania usera po ID (żeby sprawdzić stare hasło)
    public function getUserById(int $id): ?User {
        $stmt = $this->database->connect()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;

        return new User(
            $user['email'], $user['password'], $user['name'], $user['surname'],
            $user['student_id'], $user['university_id'], $user['faculty_id'],
            $user['role'], $user['id']
        );
    }
}