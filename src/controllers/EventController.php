<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../repository/EventRepository.php';
require_once __DIR__ . '/../repository/UniversityRepository.php';

// --- TO SĄ TE DWIE LINIE, KTÓRYCH CI BRAKUJE: ---
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../services/EmailService.php';
class EventController extends AppController {

    const MAX_FILE_SIZE = 1024 * 1024 * 20; // To jest 20 MB
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
    $this->ensureSession();
    $universityId = $_SESSION['user_university_id'] ?? 0;
    $role = $_SESSION['user_role'] ?? 'guest';

    if ($role === 'user') {
        // Student widzi: Uczelniane + Wydziałowe
        $facultyId = $_SESSION['user_faculty_id'] ?? 0;
        $events = $this->eventRepository->getStudentEvents($universityId, $facultyId);
    } else {
        // Admin (i inni) widzi: Wszystko z uczelni (STARA LOGIKA)
        $events = $this->eventRepository->getEvents($universityId);
    }

    $this->render('dashboard', ['events' => $events]);
    }

    public function addEvent() {
        $this->ensureSession();
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

            // Tworzymy obiekt z pełnymi danymi (tak jak miałaś)
            $event = new Event(
                $_POST['title'],
                $_POST['description'],
                $_FILES['file']['name'],
                $_POST['date'],
                $_POST['location'],
                $_POST['category'],
                $_SESSION['user_university_id'],
                $facultyId, // <-- Twój wydział tu jest bezpieczny
                $_SESSION['user_id']
            );

            try {
                // 1. Zapis do bazy
                $this->eventRepository->addEvent($event);

                // =========================================================
                // START: NOWA LOGIKA WYSYŁANIA MAILI
                // =========================================================
                
                // A. Pobieramy studentów z tej uczelni
                // Musimy utworzyć instancję UserRepository, bo nie mamy jej w konstruktorze EventControllera
                $userRepository = new UserRepository();
                $students = $userRepository->getStudentsByUniversityId($_SESSION['user_university_id']);

                // B. Inicjujemy serwis mailowy
                $emailService = new \src\services\EmailService();

                // C. Wysyłamy powiadomienia
                // C. Lecimy pętlą i sprawdzamy komu wysłać
                foreach ($students as $student) {
    
                    $eventFacultyId = $event->getFacultyId();
                    // Zakładam, że w modelu User masz metodę getFacultyId() - zobacz uwagę niżej!
                    $studentFacultyId = $student->getFacultyId(); 

                    // WARUNEK:
                    // 1. Jeśli event nie ma przypisanego wydziału (jest dla wszystkich) -> Wyślij
                    // 2. LUB jeśli ID wydziału eventu zgadza się z ID wydziału studenta -> Wyślij
                    if (empty($eventFacultyId) || $eventFacultyId == $studentFacultyId) {
        
                        $emailService->sendNewEventNotification(
                            $student->getEmail(), 
                            $event->getTitle(),
                            $event->getDate()
                        );
                    }
        }
                // =========================================================
                // KONIEC LOGIKI EMAIL
                // =========================================================

                header("Location: /dashboard");
                return;

            } catch (PDOException $e) {
                // --- Obsługa błędów (Twoja logika) ---
                error_log("Event creation error: " . $e->getMessage());
                
                if (strpos($e->getMessage(), 'Event date must be in the future') !== false) {
                    $this->messages[] = 'Event date must be in the future!';
                } else {
                    $this->messages[] = 'An error occurred while creating the event. Please try again.';
                }
            }
        }
        
        // W razie błędu walidacji też musimy podać wydziały
        $faculties = $this->universityRepository->getFacultiesForSelect($_SESSION['user_university_id']);
        return $this->render('add_event', ['messages' => $this->messages, 'faculties' => $faculties]);
    }

    public function editEvent() {
        $this->ensureSession();
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
                $facultyId,
                $event->getCreatorId()
            );
            
            // Ważne: musimy ustawić ID, żeby formularz wiedział co edytujemy w razie błędu
            $updatedEvent->setId($id); 

            // --- NOWA OBSŁUGA BŁĘDÓW PRZY EDYCJI ---
            try {
                $this->eventRepository->updateEvent($id, $updatedEvent);
                header("Location: /dashboard");
                return;
            } catch (PDOException $e) {
                // --- WYMÓG: Nie pokazuję surowych błędów użytkownikowi ---
                error_log("Event update error: " . $e->getMessage());
                if (strpos($e->getMessage(), 'Event date must be in the future') !== false) {
                    $this->messages[] = 'Event date must be in the future!';
                } else {
                    $this->messages[] = 'An error occurred while updating the event. Please try again.';
                }

                // Musimy znowu pobrać wydziały, bo renderujemy widok od nowa
                $faculties = $this->universityRepository->getFacultiesForSelect($_SESSION['user_university_id']);
                
                // Renderujemy formularz z wpisanymi przez Ciebie danymi ($updatedEvent) i błędem
                return $this->render('edit_event', [
                    'event' => $updatedEvent, 
                    'faculties' => $faculties, 
                    'messages' => $this->messages
                ]);
            }
            // ----------------------------------------
        }
    }

    public function deleteEvent() {
        $this->ensureSession();
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
        
        header("Location: /dashboard");
    }

    public function search() {
        $this->ensureSession();
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if (strpos($contentType, 'application/json') !== false) {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            header('Content-Type: application/json');
            http_response_code(200);

            $userId = $_SESSION['user_id'] ?? 0;
            $universityId = $_SESSION['user_university_id'] ?? 0;
            
            // Pobieramy flagi
            $isArchive = isset($decoded['isArchive']) && $decoded['isArchive'] === true;
            // Pobieramy filtr kategorii (np. 'Workshops')
            $categoryFilter = $decoded['filter'] ?? 'All';

            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user') {
                $isArchive = false;
                $facultyId = $_SESSION['user_faculty_id'] ?? null;
            } else {
                $facultyId = null;
            }

            // Przekazujemy kategorię do repozytorium (ostatni parametr)
            $events = $this->eventRepository->getEventsByTitle(
                $decoded['search'], 
                $userId, 
                $universityId, 
                $facultyId, 
                $isArchive, 
                $categoryFilter // <-- NOWOŚĆ
            );
            
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
    
    public function join() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
             header("Location: /dashboard"); return;
        }
        
        $eventId = $_GET['id'];
        $userId = $_SESSION['user_id'];
        
        // 1. Zapisz w bazie
        $this->eventRepository->joinEvent($userId, $eventId);
        
        // --- 2. WYSYŁKA MAILA (DODANE) ---
        
        // Pobieramy dane do powiadomienia
        $userEmail = $_SESSION['user_email']; // Email studenta z sesji
        $event = $this->eventRepository->getEvent($eventId); // Szczegóły wydarzenia
        
        // Sprawdzamy czy user chce powiadomienia (pobieramy z sesji, domyślnie true)
        $wantsEmails = $_SESSION['email_notifications'] ?? true; 

        if ($wantsEmails && $event) {
            // 1. Tworzymy obiekt z pełną ścieżką (namespace)
            $emailService = new \src\services\EmailService();

            // 2. Wywołujemy gotową metodę z EmailService.php
            // Ona sama sobie ustawi temat i treść "Confirmation...", wystarczy jej podać maila i tytuł wydarzenia
            $emailService->sendConfirmationEmail($userEmail, $event->getTitle());
        }
        // ---------------------------------

        header("Location: /dashboard");
    }

    public function leave() {
        $this->ensureSession();
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

    public function event() {
        $this->ensureSession();
        
        // 1. Sprawdzamy czy ID istnieje w URL
        if (!isset($_GET['id'])) { 
            http_response_code(404);
            $this->render('404'); // Ładujemy widok błędu
            return;
        }
        
        $eventId = $_GET['id'];
        $userId = $_SESSION['user_id'] ?? 0;

        $event = $this->eventRepository->getEvent($eventId);
        
        // 2. Sprawdzamy czy wydarzenie istnieje w bazie
        if (!$event) {
            http_response_code(404);
            $this->render('404'); // Ładujemy widok błędu
            return;
        }

        $isJoined = $this->eventRepository->isJoined($userId, $eventId);
        $this->render('event_details', ['event' => $event, 'isJoined' => $isJoined]);
    }

    public function profile() {
        $this->ensureSession();
        if (!isset($_SESSION['user_id'])) { header("Location: /login"); return; }

        $userId = $_SESSION['user_id'];
        $myEvents = $this->eventRepository->getJoinedEvents($userId);
        
        $user = [
            'name' => $_SESSION['user_name'] ?? 'Student',
            'surname' => $_SESSION['user_surname'] ?? '',
            'email' => $_SESSION['user_email'],
            // DODAJEMY TO POLE: (Domyślnie true, jeśli nie ma w sesji)
            'email_notifications' => $_SESSION['email_notifications'] ?? true 
        ];

        $this->render('profile', ['user' => $user, 'events' => $myEvents]);
    }
}