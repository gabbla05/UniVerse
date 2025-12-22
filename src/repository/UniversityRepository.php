<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/University.php';

class UniversityRepository extends Repository {

    public function getUniversities(): array {
        $result = [];

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
            $facultiesArray = $university['faculties_list'] 
                ? explode('|', $university['faculties_list']) 
                : [];

            $result[] = new University(
                $university['id'],
                $university['name'],
                $university['city'],
                $university['admin_full_name'] ?? 'No Admin',
                $facultiesArray
            );
        }

        return $result;
    }

    public function addUniversity(string $name, string $city): int {
        $pdo = $this->database->connect();
        
        $stmt = $pdo->prepare('
            INSERT INTO universities (name, city)
            VALUES (?, ?)
        ');

        $stmt->execute([$name, $city]);

        return (int)$pdo->lastInsertId();
    }

    public function addFaculty(int $universityId, string $facultyName) {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO faculties (name, university_id)
            VALUES (?, ?)
        ');

        $stmt->execute([$facultyName, $universityId]);
    }

    // --- NOWE METODY ---

    public function deleteUniversity(int $id) {
        $stmt = $this->database->connect()->prepare('DELETE FROM universities WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getUniversity(int $id): ?University {
        $stmt = $this->database->connect()->prepare('SELECT * FROM universities WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $uni = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$uni) return null;

        return new University($uni['id'], $uni['name'], $uni['city']);
    }

    public function updateUniversity(int $id, string $name, string $city) {
        $stmt = $this->database->connect()->prepare('
            UPDATE universities SET name = ?, city = ? WHERE id = ?
        ');
        $stmt->execute([$name, $city, $id]);
    }

    public function getUniversityDetails(int $id): array {
        // 1. Pobierz dane uczelni
        $stmt = $this->database->connect()->prepare('SELECT * FROM universities WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $university = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$university) {
            return [];
        }

        // 2. Pobierz dane admina tej uczelni
        $stmt = $this->database->connect()->prepare("
            SELECT * FROM users WHERE university_id = :id AND role = 'uni_admin' LIMIT 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Pobierz listę wydziałów
        $stmt = $this->database->connect()->prepare('SELECT name FROM faculties WHERE university_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $faculties = $stmt->fetchAll(PDO::FETCH_COLUMN); // Zwraca tablicę stringów ['Wydział A', 'Wydział B']

        // Zwracamy paczkę danych
        return [
            'university' => $university,
            'admin' => $admin, // może być false jeśli brak admina
            'faculties' => implode(', ', $faculties) // Zamieniamy tablicę na string "Wydział A, Wydział B" do textarea
        ];
    }

    // Nowa metoda do zapisu wszystkiego naraz
    public function updateUniversityData(int $id, array $data) {
        $pdo = $this->database->connect();
        
        try {
            $pdo->beginTransaction(); // Rozpoczynamy transakcję (wszystko albo nic)

            // 1. Aktualizacja Uczelni
            $stmt = $pdo->prepare('UPDATE universities SET name = ?, city = ? WHERE id = ?');
            $stmt->execute([$data['uni_name'], $data['uni_city'], $id]);

            // 2. Aktualizacja Admina (jeśli istnieje)
            // Zakładamy, że admin już jest. Jeśli chcesz tworzyć nowego przy edycji, trzeba by dodać logikę INSERT
            if (!empty($data['admin_email'])) {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, surname = ?, email = ? 
                    WHERE university_id = ? AND role = 'uni_admin'
                ");
                $stmt->execute([
                    $data['admin_name'], 
                    $data['admin_surname'], 
                    $data['admin_email'], 
                    $id
                ]);
            }

            // 3. Aktualizacja Wydziałów (Logika: Usuń stare -> Dodaj nowe z listy)
            // Najbezpieczniejsza opcja przy edycji listy tekstowej:
            
            // A. Pobierz obecne wydziały z bazy
            $stmt = $pdo->prepare('SELECT name FROM faculties WHERE university_id = ?');
            $stmt->execute([$id]);
            $currentFaculties = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // B. Przetwórz nowe wydziały z formularza
            $newFacultiesRaw = explode(',', $data['faculties']);
            $newFaculties = array_map('trim', $newFacultiesRaw);
            $newFaculties = array_filter($newFaculties); // Usuń puste

            // C. Usuń te, których nie ma w nowej liście
            $toDelete = array_diff($currentFaculties, $newFaculties);
            if (!empty($toDelete)) {
                // Przygotuj placeholdery (?,?,?)
                $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
                $stmt = $pdo->prepare("DELETE FROM faculties WHERE university_id = ? AND name IN ($placeholders)");
                // Merge ID uczelni i nazwy do usunięcia
                $params = array_merge([$id], array_values($toDelete));
                $stmt->execute($params);
            }

            // D. Dodaj te, które są nowe
            $toAdd = array_diff($newFaculties, $currentFaculties);
            if (!empty($toAdd)) {
                $stmt = $pdo->prepare('INSERT INTO faculties (name, university_id) VALUES (?, ?)');
                foreach ($toAdd as $facultyName) {
                    $stmt->execute([$facultyName, $id]);
                }
            }

            $pdo->commit(); // Zatwierdź zmiany
        } catch (Exception $e) {
            $pdo->rollBack(); // Cofnij w razie błędu
            throw $e;
        }
    }
}