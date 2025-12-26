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
    private $universityRepository;

    public function __construct() {
        $this->eventRepository = new EventRepository();
        $this->universityRepository = new UniversityRepository();
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
             header("Location: /dashboard"); return;
        }

        // GET: Wyświetl formularz + Lista Wydziałów
        if ($this->isGet()) {
            $faculties = $this->universityRepository->getFacultiesForSelect($_SESSION['user_university_id']);
            return $this->render('add_event', ['faculties' => $faculties]);
        }

        // POST: Dodaj
        if (isset($_FILES['file']) && $this->validate($_FILES['file'])) {
            move_uploaded_file(
                $_FILES['file']['tmp_name'], 
                dirname(__DIR__).self::UPLOAD_DIRECTORY.$_FILES['file']['name']
            );

            // Obsługa wydziału: jeśli wybrano "All" (pusty string), wstaw NULL
            $facultyId = !empty($_POST['faculty']) ? $_POST['faculty'] : null;

            $event = new Event(
                $_POST['title'],
                $_POST['description'],
                $_FILES['file']['name'],
                $_POST['date'],
                $_POST['location'],
                $_POST['category'],
                $_SESSION['user_university_id'],
                $facultyId, // <-- Przekazujemy wydział
                $_SESSION['user_id']
            );

            $this->eventRepository->addEvent($event);
            header("Location: /dashboard");
            return;
        }
        
        // W razie błędu walidacji też musimy podać wydziały
        $faculties = $this->universityRepository->getFacultiesForSelect($_SESSION['user_university_id']);
        return $this->render('add_event', ['messages' => $this->messages, 'faculties' => $faculties]);
    }

    public function editEvent() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'uni_admin') {
             header("Location: /dashboard"); return;
        }

        $id = $_GET['id'];
        $event = $this->eventRepository->getEvent($id);

        if ($this->isGet()) {
            $faculties = $this->universityRepository->getFacultiesForSelect($_SESSION['user_university_id']);
            return $this->render('edit_event', ['event' => $event, 'faculties' => $faculties]);
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

            $facultyId = !empty($_POST['faculty']) ? $_POST['faculty'] : null;

            $updatedEvent = new Event(
                $_POST['title'],
                $_POST['description'],
                $newImage,
                $_POST['date'],
                $_POST['location'],
                $_POST['category'],
                $event->getUniversityId(),
                $facultyId, // <-- Update wydziału
                $event->getCreatorId()
            );

            $this->eventRepository->updateEvent($id, $updatedEvent);
            header("Location: /dashboard");
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

    // --- SEARCH Z OBSŁUGĄ RÓL ---
    public function search() {
        session_start();
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if (strpos($contentType, 'application/json') !== false) {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            header('Content-Type: application/json');
            http_response_code(200);

            $userId = $_SESSION['user_id'] ?? 0;
            $universityId = $_SESSION['user_university_id'] ?? 0;
            
            // Jeśli to zwykły student, pobierz jego ID wydziału z sesji
            // Jeśli admin, to facultyId = null (widzi wszystko)
            $facultyId = null;
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user') {
                $facultyId = $_SESSION['user_faculty_id'] ?? null;
            }

            $events = $this->eventRepository->getEventsByTitle($decoded['search'], $userId, $universityId, $facultyId);
            
            $eventsArray = [];
            foreach ($events as $event) {
                $eventsArray[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'date' => str_replace('T', ' ', $event['date']),
                    'location' => $event['location'],
                    'image' => $event['image_url'],
                    'category' => $event['category'],
                    'is_joined' => $event['is_joined']
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