<?php

// ZMIANA: Używamy __DIR__ przy imporcie AppController
require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/University.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UniversityRepository.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class AdminController extends AppController {

    private $universityRepository;
    private $userRepository;

    public function __construct() {
        // AppController nie ma konstruktora, więc nie musimy wołać parent::__construct()
        // Ale inicjalizujemy nasze repozytoria
        $this->universityRepository = new UniversityRepository();
        $this->userRepository = new UserRepository();
    }

    public function admin() {
        session_start();
        // Tutaj w przyszłości odkomentuj sprawdzanie roli:
        // if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'app_admin') { ... }

        $universities = $this->universityRepository->getUniversities();
        return $this->render('admin', ['universities' => $universities]);
    }

    public function addUniversity() {
        if (!$this->isPost()) {
             return $this->admin();
        }

        // 1. DANE UCZELNI
        $uniName = $_POST['name'];
        $uniCity = $_POST['city'];
        
        // 2. DANE WYDZIAŁÓW
        $facultiesString = $_POST['faculties']; 
        
        // 3. DANE ADMINA UCZELNI
        $adminName = $_POST['admin_name'];
        $adminSurname = $_POST['admin_surname'];
        $adminEmail = $_POST['admin_email'];
        $adminPassword = $_POST['admin_password'];

        // LOGIKA
        // 1. Dodaj uczelnię i weź jej ID
        $newUniId = $this->universityRepository->addUniversity($uniName, $uniCity);

        // 2. Dodaj wydziały
        $faculties = explode(',', $facultiesString);
        foreach ($faculties as $faculty) {
            $facultyName = trim($faculty);
            if (!empty($facultyName)) {
                $this->universityRepository->addFaculty($newUniId, $facultyName);
            }
        }

        // 3. Dodaj admina uczelni
        $hashedAdminPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

        $uniAdmin = new User(
            $adminEmail, 
            $hashedAdminPassword, // Przekazujemy zahaszowane
            $adminName, 
            $adminSurname,
            null,       
            $newUniId,  
            null,       
            'uni_admin' 
        );

        $this->userRepository->addUser($uniAdmin);;

        // Odśwież stronę po dodaniu
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/admin");
    }
}