<?php

class Event {
    private $id;
    private $title;
    private $description;
    private $image;
    private $date;
    private $location;
    private $category; 
    private $universityId;
    private $facultyId;
    private $creatorId;

    public function __construct($title, $description, $image, $date, $location, $category, $universityId, $facultyId, $creatorId) {
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
        $this->date = $date;
        $this->location = $location;
        $this->category = $category; // Przypisanie
        $this->universityId = $universityId;
        $this->facultyId = $facultyId;
        $this->creatorId = $creatorId;
    }

    // --- Gettery i Settery ---
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getImage() { return $this->image; }
    public function getDate() { return $this->date; }
    public function getLocation() { return $this->location; }
    public function getCategory() { return $this->category; } 
    public function getUniversityId() { return $this->universityId; }
    public function getFacultyId() { return $this->facultyId; }
    public function getCreatorId() { return $this->creatorId; }
}