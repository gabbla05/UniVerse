<?php

require_once 'src/controllers/AppController.php';
require_once 'src/controllers/SecurityController.php';
// ZMIANA: Musimy zaimportować AdminController, żeby Routing go widział!
require_once 'src/controllers/AdminController.php';

class Routing {

    public static $routes = [
        "" => [
            "controller" => "AppController",
            "action" => "landing"
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
        ]
    ];

    public static function run(string $path) {
        if (array_key_exists($path, self::$routes)) {
            $controller = self::$routes[$path]["controller"];
            $action = self::$routes[$path]["action"];

            $object = new $controller;
            $object->$action();
        } 
        elseif ($path === 'dashboard') {
             include 'public/views/dashboard.html';
        }
        else {
            include 'public/views/landing.html';
        }
    }
}