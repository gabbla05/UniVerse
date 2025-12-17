<?php

require_once 'AppController.php';
require_once __DIR__ .'/../Models/User.php';
require_once __DIR__ .'/../Repository/UserRepository.php';

class SecurityController extends AppController {

    public function register() {
        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmedPassword = $_POST['confirmedPassword'];
        $name = $_POST['name'];
        $surname = $_POST['surname'];

        if ($password !== $confirmedPassword) {
            return $this->render('register', ['messages' => ['Hasła nie są identyczne!']]);
        }

        // Haszowanie hasła (BINGO E2)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $user = new User($email, $hashedPassword, $name, $surname);

        $userRepository = new UserRepository();
        $userRepository->addUser($user);

        return $this->render('login', ['messages' => ['Rejestracja udana! Zaloguj się.']]);
    }
    
    // Dodajemy pustą metodę login, żeby nie wywalało błędu przy przekierowaniu
    public function login() {
        return $this->render('login');
    }
}