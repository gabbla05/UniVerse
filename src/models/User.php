<?php

class User {
    private $email;
    private $password;
    private $name;
    private $surname;
    private $studentId;
    private $universityId;
    private $facultyId;
    private $role; // NOWE POLE

    public function __construct(
        string $email, 
        string $password, 
        string $name, 
        string $surname,
        ?string $studentId = null,
        ?int $universityId = null,
        ?int $facultyId = null,
        string $role = 'user' // Domyślnie user, ale możemy zmienić
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->surname = $surname;
        $this->studentId = $studentId;
        $this->universityId = $universityId;
        $this->facultyId = $facultyId;
        $this->role = $role;
    }

    // ... stare gettery ...
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getName(): string { return $this->name; }
    public function getSurname(): string { return $this->surname; }
    public function getStudentId(): ?string { return $this->studentId; }
    public function getUniversityId(): ?int { return $this->universityId; }
    public function getFacultyId(): ?int { return $this->facultyId; }
    
    // NOWY GETTER
    public function getRole(): string { return $this->role; }
}