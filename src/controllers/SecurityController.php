<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class SecurityController extends AppController {

    private $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
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
        
        session_start();
        
        // ZMIANA: Zapisujemy ID do sesji
        $_SESSION['user_id'] = $user->getId(); 
        
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();
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
            return $this->render('register');
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
        session_start();
        session_unset();
        session_destroy();
        
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }
}