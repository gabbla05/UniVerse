<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Event.php';

class EventRepository extends Repository {

    public function addEvent(Event $event) {
       $stmt = $this->database->connect()->prepare('
            INSERT INTO events (title, description, image_url, date, location, category, university_id, faculty_id, creator_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $event->getTitle(),
            $event->getDescription(),
            $event->getImage(),
            $event->getDate(),
            $event->getLocation(),
            $event->getCategory(),
            $event->getUniversityId(),
            $event->getFacultyId(),
            $event->getCreatorId()
        ]);
    }

    // ZMIANA: Domyślnie pobieramy tylko NADCHODZĄCE (plus 24h wstecz)
    public function getEvents(int $universityId): array {
        $result = [];
        $stmt = $this->database->connect()->prepare("
            SELECT * FROM events 
            WHERE university_id = :uniId 
            AND date > (NOW() - INTERVAL '1 DAY')
            ORDER BY date ASC
        ");
        $stmt->bindParam(':uniId', $universityId, PDO::PARAM_INT);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $evt = new Event(
                $event['title'],
                $event['description'],
                $event['image_url'],
                $event['date'],
                $event['location'],
                $event['category'],
                $event['university_id'], 
                $event['faculty_id'],
                $event['creator_id']
            );
            $evt->setId($event['id']);
            $result[] = $evt;
        }
        return $result;
    }

    public function getEvent(int $id): ?Event {
        $stmt = $this->database->connect()->prepare('SELECT * FROM events WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) return null;

        $evt = new Event(
            $event['title'],
            $event['description'],
            $event['image_url'],
            $event['date'],
            $event['location'],
            $event['category'],
            $event['university_id'],
            $event['faculty_id'],
            $event['creator_id']
        );
        $evt->setId($event['id']);
        return $evt;
    }

    // --- ZMODYFIKOWANA METODA SEARCH ---
    // Dodano parametr $isArchive
    public function getEventsByTitle(string $searchString, int $userId, int $universityId, ?int $facultyId = null, bool $isArchive = false) {
        $searchString = '%' . strtolower($searchString) . '%';

        $sql = '
            SELECT e.*, 
                   (CASE WHEN ep.user_id IS NOT NULL THEN true ELSE false END) as is_joined
            FROM events e
            LEFT JOIN event_participants ep ON e.id = ep.event_id AND ep.user_id = :userid
            WHERE (LOWER(e.title) LIKE :search OR LOWER(e.description) LIKE :search)
            AND e.university_id = :uniId
        ';

        // 1. FILTRACJA DATY (ARCHIWUM vs AKTUALNE)
        if ($isArchive) {
            // Archiwum: starsze niż 24h temu
            $sql .= " AND e.date <= (NOW() - INTERVAL '1 DAY')";
        } else {
            // Aktualne: od teraz (minus 24h buforu, żeby nie znikały w trakcie trwania) w przyszłość
            $sql .= " AND e.date > (NOW() - INTERVAL '1 DAY')";
        }

        // 2. FILTRACJA WYDZIAŁU
        if ($facultyId) {
            $sql .= ' AND (e.faculty_id IS NULL OR e.faculty_id = :facId)';
        }

        // 3. SORTOWANIE
        if ($isArchive) {
            // W archiwum chcemy widzieć "najświeższe starocie" na górze
            $sql .= ' ORDER BY e.date DESC';
        } else {
            // W aktualnych chcemy "najbliższe" na górze
            $sql .= ' ORDER BY e.date ASC';
        }

        $stmt = $this->database->connect()->prepare($sql);
        
        $stmt->bindParam(':search', $searchString, PDO::PARAM_STR);
        $stmt->bindParam(':userid', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':uniId', $universityId, PDO::PARAM_INT);
        
        if ($facultyId) {
            $stmt->bindParam(':facId', $facultyId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateEvent(int $id, Event $event) {
        $stmt = $this->database->connect()->prepare('
            UPDATE events 
            SET title=?, description=?, image_url=?, date=?, location=?, category=?, faculty_id=?
            WHERE id=?
        ');
        $stmt->execute([
            $event->getTitle(), $event->getDescription(), $event->getImage(),
            $event->getDate(), $event->getLocation(), $event->getCategory(), 
            $event->getFacultyId(),
            $id
        ]);
    }

    public function deleteEvent(int $id) {
        $stmt = $this->database->connect()->prepare('DELETE FROM events WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function joinEvent(int $userId, int $eventId) {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO event_participants (user_id, event_id) VALUES (?, ?) 
            ON CONFLICT DO NOTHING
        ');
        $stmt->execute([$userId, $eventId]);
    }

    public function leaveEvent(int $userId, int $eventId) {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM event_participants WHERE user_id = ? AND event_id = ?
        ');
        $stmt->execute([$userId, $eventId]);
    }

    // Dla studenta: Pokaż wydarzenia z jego uczelni (ogólne LUB jego wydziału)
    public function getStudentEvents(int $universityId, int $facultyId): array {
        $result = [];
        $stmt = $this->database->connect()->prepare("
            SELECT * FROM events 
            WHERE university_id = :uniId 
            AND (faculty_id IS NULL OR faculty_id = :facId)
            AND date > (NOW() - INTERVAL '1 DAY')
            ORDER BY date ASC
        ");
        
        $stmt->bindParam(':uniId', $universityId, PDO::PARAM_INT);
        $stmt->bindParam(':facId', $facultyId, PDO::PARAM_INT);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $result[] = $this->mapEvent($event);
        }
        return $result;
    }

    // Dla profilu: Pokaż wydarzenia, na które user się zapisał
    public function getJoinedEvents(int $userId): array {
        $result = [];
        $stmt = $this->database->connect()->prepare('
            SELECT e.* FROM events e
            JOIN event_participants ep ON e.id = ep.event_id
            WHERE ep.user_id = :userId
            ORDER BY e.date ASC
        ');
        
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $result[] = $this->mapEvent($event);
        }
        return $result;
    }

    // Sprawdzenie czy user jest już zapisany (żeby wyświetlić guzik Join lub Leave)
    public function isJoined(int $userId, int $eventId): bool {
        $stmt = $this->database->connect()->prepare('
            SELECT 1 FROM event_participants WHERE user_id = ? AND event_id = ?
        ');
        $stmt->execute([$userId, $eventId]);
        return (bool)$stmt->fetch();
    }

    // Pomocnicza metoda, żeby nie powielać kodu tworzenia obiektu (opcjonalnie, można też kopiować kod z getEvents)
    private function mapEvent(array $event): Event {
        $evt = new Event(
            $event['title'], $event['description'], $event['image_url'],
            $event['date'], $event['location'], $event['category'],
            $event['university_id'], $event['faculty_id'], $event['creator_id']
        );
        $evt->setId($event['id']);
        return $evt;
    }
}