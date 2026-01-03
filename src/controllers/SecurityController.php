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
        $this->userRepository = new UserRepository();
        $this->universityRepository = new UniversityRepository();
    }

    #[AllowedMethods(['GET', 'POST'])]
    public function login() {
        $this->ensureSession();

        if (isset($_SESSION['user_id'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
            exit();
        }

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
            // Zwracamy ten sam błąd co przy złym haśle (Bingo B1 - nie zdradzamy szczegółów)
            return $this->render('login', ['messages' => ['Incorrect email or password!']]);
        }
        // ---------------------------------------------------

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

        $url = "http://$_SERVER[HTTP_HOST]";
        
        if ($user->getRole() === 'app_admin') {
             header("Location: {$url}/admin");
        } else {
             header("Location: {$url}/dashboard");
        }
    }

    #[AllowedMethods(['GET', 'POST'])]
    public function register() {
        $this->ensureSession();

        if (!$this->isPost()) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['universities' => $universities]);
        }

        // 1. Ochrona CSRF (Jeśli wdrożyłeś)
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
             // Możesz też po prostu przeładować stronę
             $universities = $this->universityRepository->getUniversities();
             return $this->render('register', ['messages' => ['Session expired (CSRF). Try again.'], 'universities' => $universities]);
        }

        $email = trim($_POST['email']); // Trim usuwa spacje
        $password = $_POST['password'];
        $confirmedPassword = $_POST['password_confirm'];
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $studentId = trim($_POST['student_id']);
        $universityId = $_POST['university'];
        $facultyId = $_POST['faculty'];

        // --- WALIDACJA PO STRONIE SERWERA (C1 w Bingo) ---

        // 2. Walidacja Emaila (musi mieć @ i kropkę)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Invalid email format!'], 'universities' => $universities]);
        }

        // 3. Walidacja Pustych Pól (Imię, Nazwisko)
        if (empty($name) || empty($surname) || empty($universityId) || empty($facultyId)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['All fields are required!'], 'universities' => $universities]);
        }

        // 4. Walidacja Hasła (min 6 znaków)
        if (strlen($password) < 6) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Password must be at least 6 chars long!'], 'universities' => $universities]);
        }

        // 5. Walidacja Zgodności Haseł
        if ($password !== $confirmedPassword) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['Passwords do not match!'], 'universities' => $universities]);
        }

        // 6. Sprawdzenie unikalności emaila
        if ($this->userRepository->getUser($email)) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['messages' => ['User with this email already exists!'], 'universities' => $universities]);
        }

        // ------------------------------------------------

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
        
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
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
            // GET: Wyświetl formularz z obecnymi danymi
            return $this->render('edit_profile', ['user' => $user]);
        }

        // POST: Obsługa formularza
        $name = $_POST['name'];
        $surname = $_POST['surname'];
        $password = $_POST['password']; // Nowe hasło (opcjonalne)
        $passwordConfirm = $_POST['password_confirm'];
        $oldPassword = $_POST['old_password']; // Stare hasło (wymagane do zmian hasła)

        // 1. Aktualizacja danych osobowych (zawsze)
        $this->userRepository->updateUserInfo($userId, $name, $surname);
        
        // Aktualizacja sesji, żeby zmiany były widoczne od razu
        $_SESSION['user_name'] = $name;
        $_SESSION['user_surname'] = $surname;

        $messages = [];

        // 2. Aktualizacja hasła (tylko jeśli podano)
        if (!empty($password)) {
            if (empty($oldPassword)) {
                $messages[] = 'To change password, provide current password!';
            } elseif (!password_verify($oldPassword, $user->getPassword())) {
                $messages[] = 'Current password is incorrect!';
            } elseif ($password !== $passwordConfirm) {
                $messages[] = 'New passwords do not match!';
            } else {
                // Wszystko OK - zmieniamy hasło
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->userRepository->changePassword($userId, $newHash);
                $messages[] = 'Password changed successfully.';
            }
        }

        if (empty($messages) || $messages[0] === 'Password changed successfully.') {
             // Przekierowanie z sukcesem
             header("Location: /profile");
        } else {
             // Błąd - zostań w formularzu
             return $this->render('edit_profile', ['user' => $user, 'messages' => $messages]);
        }
    }

    // W pliku: src/controllers/SecurityController.php

    public function seedAdmin() {
        // 1. Dane admina
        $email = 'admin@universe.com';
        $plainPassword = 'admin';

        // 2. Sprawdź czy admin już istnieje
        if ($this->userRepository->getUser($email)) {
            echo "❌ Admin ($email) już istnieje w bazie danych! Możesz się logować.";
            return;
        }

        // 3. Generowanie hasha
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        // 4. Tworzymy obiekt User
        // Kolejność argumentów: email, password, name, surname, studentId, uniId, facId, role
        $admin = new User(
            $email,
            $hashedPassword,
            'App',
            'Admin',
            null,
            null,
            null,
            'app_admin' // Ważne: rola app_admin
        );

        // 5. Zapisujemy
        $this->userRepository->addUser($admin);

        echo "✅ <b>Sukces!</b> Konto administratora zostało utworzone.<br>";
        echo "Login: $email<br>";
        echo "Hasło: $plainPassword";
    }
}