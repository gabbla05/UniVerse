<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Event.php';

class EventRepository extends Repository {

    public function addEvent(Event $event) {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO events (title, description, image_url, date, location, category, university_id, creator_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $event->getTitle(),
            $event->getDescription(),
            $event->getImage(),
            $event->getDate(),
            $event->getLocation(),
            $event->getCategory(), // Zapisujemy kategorię
            $event->getUniversityId(),
            $event->getCreatorId()
        ]);
    }

    public function getEvents(): array {
        $result = [];
        // Pobieramy wszystkie wydarzenia posortowane po dacie
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM events ORDER BY date ASC
        ');
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            // Tworzymy obiekt Event, uwzględniając nowe pole 'category'
            $evt = new Event(
                $event['title'],
                $event['description'],
                $event['image_url'],
                $event['date'],
                $event['location'],
                $event['category'], // Tutaj przekazujemy kategorię z bazy
                $event['university_id'],
                $event['creator_id']
            );
            
            // Ustawiamy ID (które nie jest w konstruktorze)
            $evt->setId($event['id']);
            
            $result[] = $evt;
        }

        return $result;
    }

    // --- NOWE METODY ---

    public function getEvent(int $id): ?Event {
        $stmt = $this->database->connect()->prepare('SELECT * FROM events WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            return null;
        }

        $evt = new Event(
            $event['title'],
            $event['description'],
            $event['image_url'],
            $event['date'],
            $event['location'],
            $event['category'], // <--- TU BRAKOWAŁO TEGO ARGUMENTU!
            $event['university_id'],
            $event['creator_id']
        );
        $evt->setId($event['id']);
        return $evt;
    }

    public function getEventsByTitle(string $searchString) {
        $searchString = '%' . strtolower($searchString) . '%';

        $stmt = $this->database->connect()->prepare('
            SELECT * FROM events WHERE LOWER(title) LIKE :search OR LOWER(description) LIKE :search ORDER BY date ASC
        ');
        
        $stmt->bindParam(':search', $searchString, PDO::PARAM_STR);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($events as $event) {
            $evt = new Event(
                $event['title'],
                $event['description'],
                $event['image_url'],
                $event['date'],
                $event['location'],
                $event['category'], // Pobieramy z bazy
                $event['university_id'],
                $event['creator_id']
            );
            $evt->setId($event['id']);
            $result[] = $evt;
        }
        return $result;
    }

    public function updateEvent(int $id, Event $event) {
        // ZMIANA: Dodano "category = ?" do zapytania SQL
        $stmt = $this->database->connect()->prepare('
            UPDATE events 
            SET title = ?, description = ?, image_url = ?, date = ?, location = ?, category = ?
            WHERE id = ?
        ');

        $stmt->execute([
            $event->getTitle(),
            $event->getDescription(),
            $event->getImage(),
            $event->getDate(),
            $event->getLocation(),
            $event->getCategory(), // Przekazujemy nową kategorię do bazy
            $id
        ]);
    }

    public function deleteEvent(int $id) {
        $stmt = $this->database->connect()->prepare('DELETE FROM events WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}