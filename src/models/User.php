<?php

class User {
    private $email;
    private $password;
    private $name;
    private $surname;
    private $studentId;
    private $universityId;
    private $facultyId;
    private $role;
    private $id; // NOWE POLE

    public function __construct(
        string $email, 
        string $password, 
        string $name, 
        string $surname,
        ?string $studentId = null,
        ?int $universityId = null,
        ?int $facultyId = null,
        string $role = 'user',
        ?int $id = null // NOWY ARGUMENT (na końcu)
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->surname = $surname;
        $this->studentId = $studentId;
        $this->universityId = $universityId;
        $this->facultyId = $facultyId;
        $this->role = $role;
        $this->id = $id; // Przypisujemy ID
    }

    // ... stare gettery ...
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getName(): string { return $this->name; }
    public function getSurname(): string { return $this->surname; }
    public function getStudentId(): ?string { return $this->studentId; }
    public function getUniversityId(): ?int { return $this->universityId; }
    public function getFacultyId(): ?int { return $this->facultyId; }
    public function getRole(): string { return $this->role; }
    
    // NOWY GETTER
    public function getId(): ?int { return $this->id; }
}