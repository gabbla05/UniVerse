<?php

class University {
    private $id;
    private $name;
    private $city;
    private $adminName; // Nowe
    private $faculties; // Nowe (tablica stringów)

    public function __construct($id, $name, $city, $adminName = null, $faculties = []) {
        $this->id = $id;
        $this->name = $name;
        $this->city = $city;
        $this->adminName = $adminName;
        $this->faculties = $faculties;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getCity() { return $this->city; }
    
    // Nowe gettery
    public function getAdminName() { return $this->adminName; }
    public function getFaculties() { return $this->faculties; }
}