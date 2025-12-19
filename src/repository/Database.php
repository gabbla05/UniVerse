<?php

class Database {
    private $username;
    private $password;
    private $host;
    private $database;

    public function __construct()
    {
        // Dane biorę z Twojego docker/db/Dockerfile i docker-compose
        $this->username = 'docker';
        $this->password = 'docker';
        $this->host = 'db'; // nazwa serwisu w docker-compose
        $this->database = 'db';
    }

    public function connect()
    {
        try {
            $conn = new PDO(
                "pgsql:host=$this->host;port=5432;dbname=$this->database",
                $this->username,
                $this->password,
                ["sslmode"  => "prefer"]
            );

            // Ustawienie trybu błędów na wyjątki (ważne do debugowania!)
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}