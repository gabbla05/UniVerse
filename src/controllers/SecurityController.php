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

        $maxAttempts = 5;
        $lockoutTime = 60; //60 sek
        
        $attemptKey = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $lockoutKey = 'login_lockout_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        if (isset($_SESSION[$lockoutKey]) && time() < $_SESSION[$lockoutKey]) {
            return $this->render('login', ['messages' => ['Account temporarily locked. Try again in 60 seconds.']]);
        }
        
        if (isset($_SESSION[$lockoutKey]) && time() >= $_SESSION[$lockoutKey]) {
            unset($_SESSION[$lockoutKey]);
            unset($_SESSION[$attemptKey]);
        }

        if (isset($_SESSION['user_id'])) {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'app_admin') {
                header("Location: /admin");
            } else {
                header("Location: /dashboard");
            }
            exit();
        }

        if (!$this->isPost()) {
            return $this->render('login');
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
             return $this->render('login', ['messages' => ['Session expired. Please try again.']]);
        }

        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (strlen($email) > 255) {
            error_log("[AUDIT] Failed login attempt: Invalid email length from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " at " . date('Y-m-d H:i:s'));
            $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
            if ($_SESSION[$attemptKey] >= $maxAttempts) {
                $_SESSION[$lockoutKey] = time() + $lockoutTime;
                unset($_SESSION[$attemptKey]);
            }
            return $this->render('login', ['messages' => ['Incorrect email or password!']]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("[AUDIT] Failed login attempt: Invalid email format '" . htmlspecialchars($email) . "' from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " at " . date('Y-m-d H:i:s'));
            $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
            if ($_SESSION[$attemptKey] >= $maxAttempts) {
                $_SESSION[$lockoutKey] = time() + $lockoutTime;
                unset($_SESSION[$attemptKey]);
            }
            return $this->render('login', ['messages' => ['Incorrect email or password!']]);
        }

        $user = $this->userRepository->getUser($email);

        if (!$user || !password_verify($password, $user->getPassword())) {
            error_log("[AUDIT] Failed login attempt: Invalid credentials for email '" . htmlspecialchars($email) . "' from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " at " . date('Y-m-d H:i:s'));
            $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
            if ($_SESSION[$attemptKey] >= $maxAttempts) {
                $_SESSION[$lockoutKey] = time() + $lockoutTime;
                unset($_SESSION[$attemptKey]);
            }
            return $this->render('login', ['messages' => ['Incorrect email or password!']]);
        }

        unset($_SESSION[$attemptKey]);

        error_log("[AUDIT] Successful login: User '" . htmlspecialchars($email) . "' from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " at " . date('Y-m-d H:i:s'));

        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->getId(); 
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();
        $_SESSION['user_name'] = $user->getName();
        $_SESSION['user_surname'] = $user->getSurname();
        $_SESSION['user_university_id'] = $user->getUniversityId();
        $_SESSION['user_faculty_id'] = $user->getFacultyId();

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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Invalid email format!'], 'universities' => $universities]);
        }

        if (strlen($email) > 255) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Email is too long (max 255 characters)!'], 'universities' => $universities]);
        }

        if (strlen($name) > 100) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Name is too long (max 100 characters)!'], 'universities' => $universities]);
        }

        if (strlen($surname) > 100) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Surname is too long (max 100 characters)!'], 'universities' => $universities]);
        }

        if (strlen($studentId) > 50) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Student ID is too long (max 50 characters)!'], 'universities' => $universities]);
        }

        if (strlen($password) > 255) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Password is too long (max 255 characters)!'], 'universities' => $universities]);
        }

        if (empty($name) || empty($surname) || empty($universityId) || empty($facultyId)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['All fields are required!'], 'universities' => $universities]);
        }

        if (strlen($password) < 6) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Password must be at least 6 chars long!'], 'universities' => $universities]);
        }

        if (!$this->validatePasswordComplexity($password)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Password must contain uppercase, lowercase, digit, and special character!'], 'universities' => $universities]);
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
             if (isset($_SESSION['user_role'])) {
                 if ($_SESSION['user_role'] === 'app_admin') {
                     header("Location: /admin");
                 } elseif ($_SESSION['user_role'] === 'uni_admin') {
                     header("Location: /dashboard"); 
                 } else {
                     header("Location: /profile");   
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
        echo "⚠️ Hasło było ustawione na wdrożenie. Zmień je logując się do panelu admin!";
    }

    private function validatePasswordComplexity($password) {
        
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasDigit = preg_match('/[0-9]/', $password);
        $hasSpecialChar = preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password);
        
        return $hasUppercase && $hasLowercase && $hasDigit && $hasSpecialChar;
    }
}
