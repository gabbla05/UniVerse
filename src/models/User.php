<?php

class User {
    private $email;
    private $password;
    private $name;
    private $surname;
    private $studentId;    // Nowe pole
    private $universityId; // Nowe pole
    private $facultyId;    // Nowe pole

    // Aktualizacja konstruktora - dodajemy nowe argumenty na końcu (jako opcjonalne ?type, żeby stary kod się nie wywalił od razu)
    public function __construct(
        string $email, 
        string $password, 
        string $name, 
        string $surname,
        ?string $studentId = null,
        ?int $universityId = null,
        ?int $facultyId = null
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->surname = $surname;
        $this->studentId = $studentId;
        $this->universityId = $universityId;
        $this->facultyId = $facultyId;
    }

    // Gettery (stare zostają, dodajemy nowe)
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getName(): string { return $this->name; }
    public function getSurname(): string { return $this->surname; }
    
    // Nowe gettery
    public function getStudentId(): ?string { return $this->studentId; }
    public function getUniversityId(): ?int { return $this->universityId; }
    public function getFacultyId(): ?int { return $this->facultyId; }
}