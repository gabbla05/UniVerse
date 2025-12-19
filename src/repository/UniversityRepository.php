<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/University.php';

class UniversityRepository extends Repository {

    public function getUniversities(): array {
        $result = [];

        // ZMIANA: Skomplikowane zapytanie, które pobiera wszystko naraz
        // STRING_AGG to funkcja Postgresa, która łączy teksty (jak GROUP_CONCAT w MySQL)
        $stmt = $this->database->connect()->prepare('
            SELECT 
                u.id, 
                u.name, 
                u.city,
                CONCAT(usr.name, \' \', usr.surname) as admin_full_name,
                STRING_AGG(f.name, \'|\') as faculties_list
            FROM universities u
            LEFT JOIN users usr ON u.id = usr.university_id AND usr.role = \'uni_admin\'
            LEFT JOIN faculties f ON u.id = f.university_id
            GROUP BY u.id, u.name, u.city, usr.name, usr.surname
            ORDER BY u.id ASC
        ');
        
        $stmt->execute();
        $universities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($universities as $university) {
            // Zamieniamy string "Wydział A|Wydział B" na tablicę ['Wydział A', 'Wydział B']
            $facultiesArray = $university['faculties_list'] 
                ? explode('|', $university['faculties_list']) 
                : [];

            $result[] = new University(
                $university['id'],
                $university['name'],
                $university['city'],
                $university['admin_full_name'] ?? 'No Admin', // Jeśli null, wpisz brak
                $facultiesArray
            );
        }

        return $result;
    }

    // Metoda dodaje uczelnię i ZWRACA jej nowe ID (int)
    public function addUniversity(string $name, string $city): int {
        $pdo = $this->database->connect();
        
        $stmt = $pdo->prepare('
            INSERT INTO universities (name, city)
            VALUES (?, ?)
        ');

        $stmt->execute([$name, $city]);

        // To jest kluczowe dla naszego formularza konfiguracji:
        // Zwracamy ID rekordu, który przed chwilą dodaliśmy
        return (int)$pdo->lastInsertId();
    }

    // Metoda do dodawania wydziału do konkretnej uczelni
    public function addFaculty(int $universityId, string $facultyName) {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO faculties (name, university_id)
            VALUES (?, ?)
        ');

        $stmt->execute([$facultyName, $universityId]);
    }
}