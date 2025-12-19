<?php

require_once 'src/controllers/SecurityController.php';

class Routing {

    public static $routes = [
        "" => [ // Pusty string oznacza stronę główną "/"
            "controller" => "AppController", // Użyjemy AppController bo to prosta strona statyczna
            "action" => "landing"
        ],
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
         "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ]
    ];

    public static function run(string $path) {
        switch($path) {
            case 'dashboard':
                // TODO connect with database
                // get elements to present on dashboard

                include 'public/views/dashboard.html';
                break;
            case 'login':
            case 'register':
                $controller = Routing::$routes[$path]["controller"];
                $action = Routing::$routes[$path]["action"];

                $controllerObj = new $controller;
                $controllerObj->$action();
                break; 
            default:
                include 'public/views/landing.html';
                break;
        }
    }
}