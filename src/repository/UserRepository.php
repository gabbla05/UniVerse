<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

class UserRepository extends Repository {

    // --- 1. Statyczna instancja ---
    private static $instance = null;

    // --- 2. Prywatny konstruktor (blokujemy 'new') ---
    // Ale uwaga: Repository dziedziczy po klasie Repository, która może mieć swój konstruktor.
    // W PHP przy Singletonie zazwyczaj zostawiamy konstruktor publiczny, jeśli dziedziczymy,
    // albo musimy dostosować klasę bazową.
    // Dla uproszczenia w Twoim projekcie: zostawiamy konstruktor z Repository,
    // ale dodajemy metodę getInstance.

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new UserRepository();
        }
        return self::$instance;
    }

    // ... Reszta metod (getUser, addUser, itd.) BEZ ZMIAN ...
    public function getUser(string $email): ?User 
    {
        // --- WYMÓG: Pobieramy tylko niezbędne kolumny do logowania ---
        $stmt = $this->database->connect()->prepare('
            SELECT id, email, password, name, surname, student_id, university_id, faculty_id, role 
            FROM public.users 
            WHERE email = :email
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
            $user['role'], 
            $user['id']
        );
    }

    public function addUser(User $user)
    {
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
            $user->getRole()
        ]);
    }

    public function updateUserInfo(int $userId, string $name, string $surname) {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET name = :name, surname = :surname WHERE id = :id
        ');
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':surname', $surname, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function changePassword(int $userId, string $newHashedPassword) {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET password = :password WHERE id = :id
        ');
        $stmt->bindParam(':password', $newHashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    public function getUserById(int $id): ?User {
        // --- WYMÓG: Pobieramy tylko niezbędne kolumny ---
        $stmt = $this->database->connect()->prepare('
            SELECT id, email, name, surname, student_id, university_id, faculty_id, role 
            FROM users 
            WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;

        // Dla editProfile nie potrzebujemy hasła, dlatego przekazujemy pusty string
        return new User(
            $user['email'], 
            '', // BRAK HASŁA - nie potrzebujemy do wyświetlania
            $user['name'], 
            $user['surname'],
            $user['student_id'], 
            $user['university_id'], 
            $user['faculty_id'],
            $user['role'], 
            $user['id']
        );
    }

    public function getStudentsByUniversityId(int $universityId): array
    {
        // Pobieramy tylko użytkowników z rolą 'user' (studentów) z danej uczelni
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM public.users 
            WHERE university_id = :university_id AND role = \'user\'
        ');
        $stmt->bindParam(':university_id', $universityId, PDO::PARAM_INT);
        $stmt->execute();

        $users = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // TERAZ KOLEJNOŚĆ JEST ZGODNA Z KONSTRUKTOREM User.php:
            $users[] = new User(
                $row['email'],
                $row['password'],
                $row['name'],
                $row['surname'],
                $row['student_id'] ?? null,    // 5. studentId
                $row['university_id'] ?? null, // 6. universityId
                $row['faculty_id'] ?? null,    // 7. facultyId (To jest kluczowe dla Twojego ifa!)
                $row['role'],                  // 8. role
                $row['id']                     // 9. id
            );
        }

        return $users;
    }
}