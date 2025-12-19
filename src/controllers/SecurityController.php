<?php

// ZMIANA: Używamy __DIR__, żeby wskazać, że plik jest w tym samym folderze
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

        if ($user->getPassword() !== $password) {
            return $this->render('login', ['messages' => ['Wrong password!']]);
        }
        
        // Logika sesji admina
        session_start();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole();
        
        $url = "http://$_SERVER[HTTP_HOST]";
        
        if ($user->getRole() === 'app_admin') {
             header("Location: {$url}/admin-view"); // Przekierowanie do panelu admina
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

        $user = new User($email, $password, $name, $surname, $studentId, $universityId, $facultyId);

        $this->userRepository->addUser($user);

        return $this->render('login', ['messages' => ['You have been successfully registered!']]);
    }
}