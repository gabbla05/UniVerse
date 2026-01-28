<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UniversityRepository.php';

class SecurityController extends AppController {

    private $userRepository;
    private $universityRepository;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
        $this->universityRepository = new UniversityRepository();
    }

    #[AllowedMethods(['GET', 'POST'])]
    public function login() {
        $this->ensureSession();

        // --- POPRAWKA TUTAJ ---
        // Jeśli użytkownik jest już zalogowany, sprawdzamy kim jest i kierujemy odpowiednio!
        if (isset($_SESSION['user_id'])) {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'app_admin') {
                header("Location: /admin");
            } else {
                header("Location: /dashboard");
            }
            exit();
        }
        // ----------------------

        if (!$this->isPost()) {
            return $this->render('login');
        }

        // 1. Walidacja CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
             return $this->render('login', ['messages' => ['Session expired. Please try again.']]);
        }

        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // --- ZABEZPIECZENIE C1: Szybka walidacja formatu ---
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('login', ['messages' => ['Incorrect email or password!']]);
        }

        $user = $this->userRepository->getUser($email);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Incorrect email or password!']]);
        }

        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->getId(); 
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();
        $_SESSION['user_name'] = $user->getName();
        $_SESSION['user_surname'] = $user->getSurname();
        $_SESSION['user_university_id'] = $user->getUniversityId();
        $_SESSION['user_faculty_id'] = $user->getFacultyId();

        // Przekierowanie po udanym logowaniu (też poprawione na ścieżki względne)
        if ($user->getRole() === 'app_admin') {
            header("Location: /admin");
        } else {
            header("Location: /dashboard");
        }
    }

    #[AllowedMethods(['GET', 'POST'])]
    public function register() {
        $this->ensureSession();

        if (!$this->isPost()) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['universities' => $universities]);
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
             $universities = $this->universityRepository->getUniversities();
             return $this->render('register', ['messages' => ['Session expired (CSRF). Try again.'], 'universities' => $universities]);
        }

        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmedPassword = $_POST['password_confirm'];
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $studentId = trim($_POST['student_id']);
        $universityId = $_POST['university'];
        $facultyId = $_POST['faculty'];

        // --- WALIDACJA ---
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Invalid email format!'], 'universities' => $universities]);
        }

        if (empty($name) || empty($surname) || empty($universityId) || empty($facultyId)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['All fields are required!'], 'universities' => $universities]);
        }

        if (strlen($password) < 6) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Password must be at least 6 chars long!'], 'universities' => $universities]);
        }

        if ($password !== $confirmedPassword) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Passwords do not match!'], 'universities' => $universities]);
        }

        if ($this->userRepository->getUser($email)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['User with this email already exists!'], 'universities' => $universities]);
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $user = new User(
            $email, 
            $hashedPassword, 
            $name, 
            $surname, 
            $studentId, 
            $universityId, 
            $facultyId,
            'user'
        );

        $this->userRepository->addUser($user);

        return $this->render('login', ['messages' => ['You\'ve been successfully registered!']]);
    }

    public function logout() {
        $this->ensureSession();
        session_unset();
        session_destroy();
        
        header("Location: /login");
    }

    public function getFaculties() {
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if ($contentType === "application/json") {
            $content = trim(file_get_contents("php://input"));
            $decoded = json_decode($content, true);

            header('Content-Type: application/json');
            http_response_code(200);

            $universityId = $decoded['id'];
            $faculties = $this->universityRepository->getFacultiesForSelect($universityId);
            
            echo json_encode($faculties);
        }
    }

    public function editProfile() {
        $this->ensureSession();
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            return;
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->getUserById($userId);

        if (!$this->isPost()) {
            return $this->render('edit_profile', ['user' => $user]);
        }

        $name = $_POST['name'];
        $surname = $_POST['surname'];
        $password = $_POST['password'];
        $passwordConfirm = $_POST['password_confirm'];
        $oldPassword = $_POST['old_password'];

        $this->userRepository->updateUserInfo($userId, $name, $surname);
        
        $_SESSION['user_name'] = $name;
        $_SESSION['user_surname'] = $surname;

        $messages = [];

        if (!empty($password)) {
            if (empty($oldPassword)) {
                $messages[] = 'To change password, provide current password!';
            } elseif (!password_verify($oldPassword, $user->getPassword())) {
                $messages[] = 'Current password is incorrect!';
            } elseif ($password !== $passwordConfirm) {
                $messages[] = 'New passwords do not match!';
            } else {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->userRepository->changePassword($userId, $newHash);
                $messages[] = 'Password changed successfully.';
            }
        }

        if (empty($messages) || $messages[0] === 'Password changed successfully.') {
             // Sprawdzamy rolę, żeby wiedzieć gdzie odesłać po sukcesie
             if (isset($_SESSION['user_role'])) {
                 if ($_SESSION['user_role'] === 'app_admin') {
                     header("Location: /admin");
                 } elseif ($_SESSION['user_role'] === 'uni_admin') {
                     header("Location: /dashboard"); // <--- Admin Uczelni idzie tutaj
                 } else {
                     header("Location: /profile");   // Student idzie tutaj
                 }
             } else {
                 header("Location: /profile");
             }
        } else {
             return $this->render('edit_profile', ['user' => $user, 'messages' => $messages]);
        }
    }

    public function seedAdmin() {
        $email = 'admin@universe.com';
        $plainPassword = 'admin';

        if ($this->userRepository->getUser($email)) {
            echo "❌ Admin ($email) już istnieje w bazie danych! Możesz się logować.";
            return;
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $admin = new User(
            $email,
            $hashedPassword,
            'App',
            'Admin',
            null,
            null,
            null,
            'app_admin'
        );

        $this->userRepository->addUser($admin);

        echo "✅ <b>Sukces!</b> Konto administratora zostało utworzone.<br>";
        echo "Login: $email<br>";
        echo "Hasło: $plainPassword";
    }
}