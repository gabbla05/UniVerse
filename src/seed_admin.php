<?php

// seed_admin.php

// 1. Dołączamy Twoje klasy (korzystamy z istniejącej architektury!)
require_once 'src/models/User.php';
require_once 'src/repository/UserRepository.php';
require_once 'src/repository/Database.php';

try {
    $userRepository = new UserRepository();

    // 2. Dane admina
    $email = 'admin@universe.com';
    $plainPassword = 'admin';
    
    // 3. Generowanie hasha (kluczowy moment)
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // 4. Sprawdźmy, czy admin już nie istnieje, żeby nie dublować
    if ($userRepository->getUser($email)) {
        die("Admin ($email) już istnieje w bazie danych!");
    }

    // 5. Tworzymy obiekt User (zgodnie z Twoim modelem)
    $admin = new User(
        $email,             // email
        $hashedPassword,    // password (zahashowane!)
        'App',              // name
        'Admin',            // surname
        null,               // student_id (admin nie ma indeksu)
        null,               // university_id (główny admin nie należy do uczelni)
        null,               // faculty_id
        'app_admin'         // role
    );

    // 6. Zapisujemy do bazy używając Twojego Repozytorium
    $userRepository->addUser($admin);

    echo "<b>Sukces!</b> Konto administratora zostało utworzone.<br>";
    echo "Email: $email<br>";
    echo "Hasło: $plainPassword<br>";
    echo "Hash w bazie: $hashedPassword";

} catch (Exception $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
}