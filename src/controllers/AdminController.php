<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/University.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UniversityRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/EventRepository.php'; // To musi być!

class AdminController extends AppController {

    private $universityRepository;
    private $userRepository;
    private $eventRepository; // <--- 1. BRAKOWAŁO TEGO POLA

    public function __construct() {
        $this->universityRepository = new UniversityRepository();
        $this->userRepository = new UserRepository();
        $this->eventRepository = new EventRepository(); // <--- 2. BRAKOWAŁO INICJALIZACJI
    }

    public function admin() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             $url = "http://$_SERVER[HTTP_HOST]";
             header("Location: {$url}/login");
             exit();
        }

        $universities = $this->universityRepository->getUniversities();
        return $this->render('admin', ['universities' => $universities]);
    }

    public function addUniversity() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             $url = "http://$_SERVER[HTTP_HOST]";
             header("Location: {$url}/login");
             exit();
        }

        if (!$this->isPost()) {
             return $this->admin();
        }

        $uniName = $_POST['name'];
        $uniCity = $_POST['city'];
        $facultiesString = $_POST['faculties']; 
        $adminName = $_POST['admin_name'];
        $adminSurname = $_POST['admin_surname'];
        $adminEmail = $_POST['admin_email'];
        $adminPassword = $_POST['admin_password'];

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

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/admin");
    }

    public function deleteUniversity() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             $url = "http://$_SERVER[HTTP_HOST]";
             header("Location: {$url}/login");
             exit();
        }

        $id = $_GET['id'];
        if ($id) {
            $this->universityRepository->deleteUniversity($id);
        }
        
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/admin");
    }

    public function searchUniversities() {
        session_start();
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

    public function editUniversity() {
        session_start();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') {
             $url = "http://$_SERVER[HTTP_HOST]";
             header("Location: {$url}/login");
             exit();
        }

        $id = $_GET['id'];
        if (!$id) { 
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/admin"); 
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
            $updateData = [
                'uni_name' => $_POST['name'],
                'uni_city' => $_POST['city'],
                'admin_name' => $_POST['admin_name'],
                'admin_surname' => $_POST['admin_surname'],
                'admin_email' => $_POST['admin_email'],
                'faculties' => $_POST['faculties'] 
            ];
            
            $this->universityRepository->updateUniversityData($id, $updateData);
            
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/admin");
        }
    }

    // --- 3. BRAKOWAŁO CAŁEJ TEJ METODY! ---
    public function eventParticipants() {
        session_start();
        // Zabezpieczenie: tylko admin uczelni może to widzieć
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'uni_admin') {
            header("Location: /dashboard");
            return;
        }

        if (!isset($_GET['id'])) {
            header("Location: /dashboard");
            return;
        }

        $eventId = $_GET['id'];
        
        // Pobieramy uczestników z repozytorium
        $participants = $this->eventRepository->getEventParticipants($eventId);
        // Pobieramy info o wydarzeniu (żeby wyświetlić tytuł)
        $event = $this->eventRepository->getEvent($eventId);

        $this->render('participants', [
            'participants' => $participants,
            'event' => $event
        ]);
    }
}