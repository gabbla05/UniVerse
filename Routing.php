<?php

require_once 'src/attributes/AllowedMethods.php';
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
        "event" => [
            "controller" => "EventController",
            "action" => "event"
        ],
        "profile" => [
            "controller" => "EventController",
            "action" => "profile"
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
        "delete-university" => [
            "controller" => "AdminController",
            "action" => "deleteUniversity"
        ],
        "edit-university" => [
            "controller" => "AdminController",
            "action" => "editUniversity"
        ],
        "search-universities" => [
            "controller" => "AdminController",
            "action" => "searchUniversities"
        ],
        "edit-profile" => [
            "controller" => "SecurityController",
            "action" => "editProfile"
        ],
        "get-faculties" => [
            "controller" => "SecurityController",
            "action" => "getFaculties"
        ],
        "event-participants" => [
            "controller" => "AdminController",
            "action" => "eventParticipants"
        ],
        "seed-admin" => [
            "controller" => "SecurityController",
            "action" => "seedAdmin"
        ]
    ];

    public static function run($url) {
        $actionUrl = explode("/", $url)[0];

        if (!array_key_exists($actionUrl, self::$routes)) {
            http_response_code(404);
            include 'public/views/404.html';
            return;
        }

        $routeConfig = self::$routes[$actionUrl];
        
        $controllerName = $routeConfig['controller'];
        $actionName = $routeConfig['action'];

        $object = new $controllerName;

        $id = $_GET['id'] ?? null;
        
        if (method_exists($object, $actionName)) {
            
            $reflection = new ReflectionMethod($controllerName, $actionName);
            $attributes = $reflection->getAttributes(AllowedMethods::class);

            if (!empty($attributes)) {
                $allowedMethods = $attributes[0]->newInstance()->methods;
                $currentMethod = $_SERVER['REQUEST_METHOD'];

                if (!in_array($currentMethod, $allowedMethods)) {
                    http_response_code(405); 
                    include 'public/views/404.html'; 
                    return;
                }
            }
        }

        $object->$actionName($id);
    }
}