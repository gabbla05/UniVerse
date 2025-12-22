<?php

require_once 'src/controllers/AppController.php';
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/AdminController.php';
require_once 'src/controllers/EventController.php'; // NOWY KONTROLER (zaraz go stworzymy)

class Routing {

    public static $routes = [
        "" => [
            "controller" => "AppController",
            "action" => "landing"
        ],
        "logout" => [ // TRASA WYLOGOWANIA
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "dashboard" => [ // Dashboard przejmuje EventController
            "controller" => "EventController",
            "action" => "dashboard"
        ],
        "add-event" => [ // Formularz dodawania
            "controller" => "EventController",
            "action" => "addEvent"
        ],
        "admin" => [ 
            "controller" => "AdminController", 
            "action" => "admin" 
        ],
        "addUniversity" => [ 
            "controller" => "AdminController", 
            "action" => "addUniversity" 
        ],
        "login" => [ 
            "controller" => "SecurityController", 
            "action" => "login" 
        ],
        "register" => [ 
            "controller" => "SecurityController", 
            "action" => "register" 
        ],
        "edit-event" => [
            "controller" => "EventController",
            "action" => "editEvent"
        ],
        "delete-event" => [
            "controller" => "EventController",
            "action" => "deleteEvent"
        ],
        "search" => [
        "controller" => "EventController",
        "action" => "search"
        ],
    ];

    public static function run(string $path) {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]["controller"];
            $action = self::$routes[$path]["action"];

            $object = new $controller;
            $object->$action();
        } 
        else {
            include 'public/views/landing.html'; // Domyślny widok
        }
    }
}