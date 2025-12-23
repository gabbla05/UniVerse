<?php

require_once 'src/controllers/AppController.php';
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/AdminController.php';
require_once 'src/controllers/EventController.php'; 

class Routing {

    public static $routes = [
        "" => [
            "controller" => "AppController",
            "action" => "landing"
        ],
        "logout" => [ 
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "dashboard" => [ 
            "controller" => "EventController",
            "action" => "dashboard"
        ],
        "add-event" => [ 
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
        "join-event" => [
            "controller" => "EventController",
            "action" => "join"
        ],
        "leave-event" => [
            "controller" => "EventController",
            "action" => "leave"
        ],
        "delete-university" => [ // NOWA TRASA
            "controller" => "AdminController",
            "action" => "deleteUniversity"
        ],
        "edit-university" => [ // NOWA TRASA
            "controller" => "AdminController",
            "action" => "editUniversity"
        ],
        "search-universities" => [
            "controller" => "AdminController",
            "action" => "searchUniversities"
        ]
    ];

    public static function run(string $path) {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]["controller"];
            $action = self::$routes[$path]["action"];

            $object = new $controller;
            $object->$action();
        } 
        else {
            include 'public/views/landing.html'; 
        }
    }
}