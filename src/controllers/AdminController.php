<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/University.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UniversityRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class AdminController extends AppController {

    private $universityRepository;
    private $userRepository;

    public function __construct() {
        $this->universityRepository = new UniversityRepository();
        $this->userRepository = new UserRepository();
    }

    public function admin() {
        session_start();
        // Zabezpieczenie: Tylko app_admin
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

        // 1. Dodaj uczelnię
        $newUniId = $this->universityRepository->addUniversity($uniName, $uniCity);

        // 2. Dodaj wydziały
        $faculties = explode(',', $facultiesString);
        foreach ($faculties as $faculty) {
            $facultyName = trim($faculty);
            if (!empty($facultyName)) {
                $this->universityRepository->addFaculty($newUniId, $facultyName);
            }
        }

        // 3. Dodaj admina
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

    // --- NOWE METODY ---

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

    // ... wewnątrz AdminController ...

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

        // GET: Wyświetl formularz z pełnymi danymi
        if ($this->isGet()) {
            // Używamy nowej metody zwracającej tablicę danych
            $data = $this->universityRepository->getUniversityDetails($id);
            
            // Jeśli nie znaleziono uczelni, wróć
            if (empty($data)) {
                header("Location: /admin");
                exit();
            }

            return $this->render('edit_university', ['data' => $data]);
        }

        // POST: Zapisz zmiany
        if ($this->isPost()) {
            // Zbieramy wszystko w jedną paczkę
            $updateData = [
                'uni_name' => $_POST['name'],
                'uni_city' => $_POST['city'],
                'admin_name' => $_POST['admin_name'],
                'admin_surname' => $_POST['admin_surname'],
                'admin_email' => $_POST['admin_email'],
                'faculties' => $_POST['faculties'] // String z przecinkami
            ];
            
            $this->universityRepository->updateUniversityData($id, $updateData);
            
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/admin");
        }
    }
}