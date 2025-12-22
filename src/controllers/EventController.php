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
        $events = $this->eventRepository->getEvents();
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

            // ZMIANA: Dodajemy $_POST['category']
            $event = new Event(
                $_POST['title'],
                $_POST['description'],
                $_FILES['file']['name'],
                $_POST['date'],
                $_POST['location'],
                $_POST['category'], // Odbieramy kategorię!
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

    // --- NOWE METODY ---

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
            $newImage = $event->getImage(); // Domyślnie stare zdjęcie

            // Jeśli przesłano nowe zdjęcie, podmień je
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
            // Usuń plik zdjęcia
            $imagePath = dirname(__DIR__) . self::UPLOAD_DIRECTORY . $event->getImage();
            if (file_exists($imagePath)) { unlink($imagePath); }

            $this->eventRepository->deleteEvent($id);
        }
        
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
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

    // 2. NOWA METODA: API Search
    public function search() {
        // Pobieramy Content-Type
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        // ZMIANA: Sprawdzamy czy zawiera "application/json" zamiast sztywnego porównania
        if (strpos($contentType, 'application/json') !== false) {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            header('Content-Type: application/json');
            http_response_code(200);

            // Pobieramy eventy pasujące do wpisanej frazy
            $events = $this->eventRepository->getEventsByTitle($decoded['search']);
            
            // Konwertujemy obiekty na tablicę, żeby wysłać jako JSON
            // (Można to też zrobić implementując JsonSerializable w modelu)
            $eventsArray = [];
            foreach ($events as $event) {
                $eventsArray[] = [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'date' => str_replace('T', ' ', $event->getDate()),
                    'location' => $event->getLocation(),
                    'image' => $event->getImage(),
                    'category' => $event->getCategory() // Ważne dla filtrów JS!
                ];
            }

            echo json_encode($eventsArray);
        }
    }
}