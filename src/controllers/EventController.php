<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/Event.php';
require_once __DIR__ .'/../repository/EventRepository.php';

class EventController extends AppController {

    const MAX_FILE_SIZE = 1024 * 1024;
    const SUPPORTED_TYPES = ['image/png', 'image/jpeg'];
    const UPLOAD_DIRECTORY = '/../public/uploads/';

    private $messages = [];
    private $eventRepository;

    public function __construct() {
        $this->eventRepository = new EventRepository();
    }

    public function dashboard() {
        session_start();
        
        // Pobieramy ID uczelni zalogowanego użytkownika
        // Jeśli z jakiegoś powodu go nie ma (np. błąd sesji), dajemy 0, żeby nie pokazać nic
        $universityId = $_SESSION['user_university_id'] ?? 0;
        
        // Przekazujemy ID do repozytorium
        $events = $this->eventRepository->getEvents($universityId);
        
        $this->render('dashboard', ['events' => $events]);
    }

    public function addEvent() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'uni_admin') {
             $url = "http://$_SERVER[HTTP_HOST]";
             header("Location: {$url}/dashboard");
             return;
        }

        if ($this->isGet()) { return $this->render('add_event'); }

        if (isset($_FILES['file']) && $this->validate($_FILES['file'])) {
            move_uploaded_file(
                $_FILES['file']['tmp_name'], 
                dirname(__DIR__).self::UPLOAD_DIRECTORY.$_FILES['file']['name']
            );

            $event = new Event(
                $_POST['title'],
                $_POST['description'],
                $_FILES['file']['name'],
                $_POST['date'],
                $_POST['location'],
                $_POST['category'],
                $_SESSION['user_university_id'],
                $_SESSION['user_id']
            );

            $this->eventRepository->addEvent($event);
            
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
            return;
        }
        return $this->render('add_event', ['messages' => $this->messages]);
    }

    public function editEvent() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'uni_admin') {
             $url = "http://$_SERVER[HTTP_HOST]";
             header("Location: {$url}/dashboard");
             return;
        }

        $id = $_GET['id'];
        if (!$id) { header("Location: /dashboard"); }

        $event = $this->eventRepository->getEvent($id);

        if ($this->isGet()) {
            return $this->render('edit_event', ['event' => $event]);
        }

        if ($this->isPost()) {
            $newImage = $event->getImage();

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                if ($this->validate($_FILES['file'])) {
                    move_uploaded_file(
                        $_FILES['file']['tmp_name'], 
                        dirname(__DIR__).self::UPLOAD_DIRECTORY.$_FILES['file']['name']
                    );
                    $newImage = $_FILES['file']['name'];
                }
            }

            $newCategory = $_POST['category'];

            $updatedEvent = new Event(
                $_POST['title'],
                $_POST['description'],
                $newImage,
                $_POST['date'],
                $_POST['location'],
                $newCategory,
                $event->getUniversityId(),
                $event->getCreatorId()
            );

            $this->eventRepository->updateEvent($id, $updatedEvent);
            
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
        }
    }

    public function deleteEvent() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'uni_admin') {
             header("Location: /dashboard"); return;
        }

        $id = $_GET['id'];
        if (!$id) { header("Location: /dashboard"); return; }

        $event = $this->eventRepository->getEvent($id);
        
        if ($event) {
            $imagePath = dirname(__DIR__) . self::UPLOAD_DIRECTORY . $event->getImage();
            if (file_exists($imagePath)) { unlink($imagePath); }

            $this->eventRepository->deleteEvent($id);
        }
        
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }

    // --- KLUCZOWA ZMIANA TUTAJ ---
    public function search() {
        session_start(); // Musimy mieć dostęp do sesji!
        
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if (strpos($contentType, 'application/json') !== false) {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            header('Content-Type: application/json');
            http_response_code(200);

            // 1. Pobieramy ID usera (do sprawdzania czy dołączył)
            $userId = $_SESSION['user_id'] ?? 0;
            
            // 2. Pobieramy ID uczelni zalogowanego użytkownika
            // Jeśli user nie jest zalogowany (np. na landingu), uniId może być null, wtedy nic nie zwróci (bezpiecznie)
            $universityId = $_SESSION['user_university_id'] ?? 0;

            // 3. Przekazujemy oba ID do repozytorium
            $events = $this->eventRepository->getEventsByTitle($decoded['search'], $userId, $universityId);
            
            $eventsArray = [];
            foreach ($events as $event) {
                $eventsArray[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'date' => str_replace('T', ' ', $event['date']),
                    'location' => $event['location'],
                    'image' => $event['image_url'],
                    'category' => $event['category'],
                    'is_joined' => $event['is_joined'] // Flaga dla przycisków Join/Leave
                ];
            }

            echo json_encode($eventsArray);
        }
    }

    // Join / Leave methods
    public function join() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
             header("Location: /dashboard"); return;
        }
        $eventId = $_GET['id'];
        $userId = $_SESSION['user_id'];
        $this->eventRepository->joinEvent($userId, $eventId);
        header("Location: /dashboard");
    }

    public function leave() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
             header("Location: /dashboard"); return;
        }
        $eventId = $_GET['id'];
        $userId = $_SESSION['user_id'];
        $this->eventRepository->leaveEvent($userId, $eventId);
        header("Location: /dashboard");
    }

    private function validate(array $file): bool {
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->messages[] = 'File is too large.';
            return false;
        }
        if (!isset($file['type']) || !in_array($file['type'], self::SUPPORTED_TYPES)) {
            $this->messages[] = 'File type is not supported.';
            return false;
        }
        return true;
    }
}