<?php

// IMPORTY DLA ADNOTACJI I KLAS
require_once 'src/attributes/AllowedMethods.php'; // <--- WAŻNE: To ładuje klasę adnotacji
require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/University.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UniversityRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/EventRepository.php';

class AdminController extends AppController {

    private $universityRepository;
    private $userRepository;
    private $eventRepository;

    public function __construct() {
        $this->universityRepository = new UniversityRepository();
        $this->userRepository = UserRepository::getInstance();
        $this->eventRepository = new EventRepository();
    }

    // Wyświetlanie panelu admina (Domyślnie GET)
    #[AllowedMethods(['GET'])]
    public function admin() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             header("Location: /login");
             exit();
        }

        $universities = $this->universityRepository->getUniversities();
        return $this->render('admin', ['universities' => $universities]);
    }

    // Dodawanie uczelni (Formularz wysyłany POSTem)
    #[AllowedMethods(['POST'])]
    public function addUniversity() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             header("Location: /login");
             exit();
        }

        $uniName = trim($_POST['name']);
        $uniCity = trim($_POST['city']);
        $facultiesString = $_POST['faculties']; 
        $adminName = trim($_POST['admin_name']);
        $adminSurname = trim($_POST['admin_surname']);
        $adminEmail = trim($_POST['admin_email']);
        $adminPassword = $_POST['admin_password'];

        // --- ZABEZPIECZENIE C1: Walidacja emaila admina uczelni ---
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            // W panelu admina możemy rzucić wyjątek lub die(), bo to błąd operatora
            die("Błąd: Nieprawidłowy format adresu email administratora uczelni!");
        }
        // ----------------------------------------------------------

        $newUniId = $this->universityRepository->addUniversity($uniName, $uniCity);

        $faculties = explode(',', $facultiesString);
        foreach ($faculties as $faculty) {
            $facultyName = trim($faculty);
            if (!empty($facultyName)) {
                $this->universityRepository->addFaculty($newUniId, $facultyName);
            }
        }

        $hashedAdminPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

        $uniAdmin = new User(
            $adminEmail, 
            $hashedAdminPassword, 
            $adminName, 
            $adminSurname,
            null,       
            $newUniId,  
            null,       
            'uni_admin' 
        );

        $this->userRepository->addUser($uniAdmin);

        header("Location: /admin");
    }

    // Usuwanie uczelni (Link GET)
    #[AllowedMethods(['GET'])]
    public function deleteUniversity() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             header("Location: /login");
             exit();
        }

        $id = $_GET['id'];
        if ($id) {
            $this->universityRepository->deleteUniversity($id);
        }
        
        header("Location: /admin");
    }

    // Wyszukiwanie AJAX (POST)
    #[AllowedMethods(['POST'])]
    public function searchUniversities() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             http_response_code(403);
             return;
        }

        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if (strpos($contentType, 'application/json') !== false) {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            header('Content-Type: application/json');
            http_response_code(200);

            $universities = $this->universityRepository->getUniversitiesByString($decoded['search']);
            
            $data = [];
            foreach ($universities as $uni) {
                $data[] = [
                    'id' => $uni->getId(),
                    'name' => $uni->getName(),
                    'city' => $uni->getCity(),
                    'admin_name' => $uni->getAdminName(),
                    'faculties' => $uni->getFaculties()
                ];
            }

            echo json_encode($data);
        }
    }

    // Edycja uczelni (GET - formularz, POST - zapis)
    // Tu nie dajemy adnotacji, bo jedna funkcja obsługuje obie metody (Twoja logika w środku)
    public function editUniversity() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             header("Location: /login");
             exit();
        }

        $id = $_GET['id'];
        if (!$id) { 
            header("Location: /admin"); 
            exit();
        }

        if ($this->isGet()) {
            $data = $this->universityRepository->getUniversityDetails($id);
            if (empty($data)) {
                header("Location: /admin");
                exit();
            }
            return $this->render('edit_university', ['data' => $data]);
        }

        if ($this->isPost()) {
            $adminEmail = $_POST['admin_email'];

            // --- ZABEZPIECZENIE C1 (BINGO) ---
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                die("Błąd: Nieprawidłowy format adresu email!");
            }
            // ---------------------------------

            $updateData = [
                'uni_name' => $_POST['name'],
                'uni_city' => $_POST['city'],
                'admin_name' => $_POST['admin_name'],
                'admin_surname' => $_POST['admin_surname'],
                'admin_email' => $adminEmail, // Używamy zmiennej
                'faculties' => $_POST['faculties'] 
            ];
            
            $this->universityRepository->updateUniversityData($id, $updateData);
            
            header("Location: /admin");
        }
    }

    #[AllowedMethods(['GET'])]
    public function eventParticipants() {
        $this->ensureSession();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'uni_admin') {
            header("Location: /dashboard");
            return;
        }

        if (!isset($_GET['id'])) {
            header("Location: /dashboard");
            return;
        }

        $eventId = $_GET['id'];
        
        $participants = $this->eventRepository->getEventParticipants($eventId);
        $event = $this->eventRepository->getEvent($eventId);

        $this->render('participants', [
            'participants' => $participants,
            'event' => $event
        ]);
    }
}