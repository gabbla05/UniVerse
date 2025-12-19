<?php

require_once 'AppController.php';
require_once __DIR__ .'/../models/User.php';
require_once __DIR__ .'/../repository/UserRepository.php';

class SecurityController extends AppController {

    private $userRepository;

    public function __construct()
    {
        // Jeśli AppController miałby konstruktor, należałoby wywołać parent::__construct();
        $this->userRepository = new UserRepository();
    }

    public function login() 
    {
        // Jeśli nie wysłano formularza (metoda GET), pokaż po prostu widok logowania
        if (!$this->isPost()) {
            return $this->render('login');
        }

        // Pobierz dane z formularza
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Sprawdź czy user istnieje w bazie
        $user = $this->userRepository->getUser($email);

        if (!$user) {
            return $this->render('login', ['messages' => ['User not found!']]);
        }

        // Sprawdź hasło (na razie porównujemy tekst, w przyszłości password_verify)
        if ($user->getPassword() !== $password) {
            return $this->render('login', ['messages' => ['Wrong password!']]);
        }

        // Logowanie udane!
        // Na razie wyświetlamy sukces na ekranie logowania
        return $this->render('login', ['messages' => ['Login successful! Witaj ' . $user->getName()]]);
        
        // Docelowo tutaj będzie przekierowanie:
        // header("Location: /dashboard");
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
        
        // ZMIANA: Pobieramy nowe pola z formularza
        $studentId = $_POST['student_id']; 
        $universityId = (int)$_POST['university']; // Rzutujemy na int, bo z selecta przychodzi string "1"
        $facultyId = (int)$_POST['faculty'];

        if ($password !== $confirmedPassword) {
            return $this->render('register', ['messages' => ['Please provide proper password']]);
        }

        // ZMIANA: Tworzymy usera ze wszystkimi danymi
        $user = new User($email, $password, $name, $surname, $studentId, $universityId, $facultyId);

        $this->userRepository->addUser($user);

        return $this->render('login', ['messages' => ['You have been successfully registered!']]);
    }
}