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

        // Zwracamy pełnego usera z bazy
        return new User(
            $user['email'],
            $user['password'],
            $user['name'],
            $user['surname'],
            $user['student_id'],    // mapowanie kolumny z bazy
            $user['university_id'],
            $user['faculty_id']
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
}