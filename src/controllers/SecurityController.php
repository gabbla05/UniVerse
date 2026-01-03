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

    public function login() 
    {
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $this->userRepository->getUser($email);

        if (!$user) {
            return $this->render('login', ['messages' => ['User not found!']]);
        }

        // ZMIANA: Używamy password_verify do sprawdzenia hash'a
        if (!password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Wrong password!']]);
        }
        
        $this->ensureSession();
        
        // ZMIANA: Zapisujemy ID do sesji
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

    public function register() 
    {
        if (!$this->isPost()) {
            $universities = $this->universityRepository->getUniversities();
            return $this->render('register', ['universities' => $universities]);
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmedPassword = $_POST['password_confirm'];
        $name = $_POST['name'];
        $surname = $_POST['surname'];
        $studentId = $_POST['student_id']; 
        $universityId = (int)$_POST['university'];
        $facultyId = (int)$_POST['faculty'];

        if ($password !== $confirmedPassword) {
            return $this->render('register', ['messages' => ['Please provide proper password']]);
        }

        // ZMIANA: Hashujemy hasło przed zapisaniem!
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Przekazujemy zahaszowane hasło do obiektu User
        $user = new User($email, $hashedPassword, $name, $surname, $studentId, $universityId, $facultyId);

        $this->userRepository->addUser($user);

        return $this->render('login', ['messages' => ['You have been successfully registered!']]);
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