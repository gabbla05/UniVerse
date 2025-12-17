<?php

require_once 'src/controllers/AppController.php';
require_once 'src/controllers/SecurityController.php';

class Routing {
    public static $routes = [];

    public static function get($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function post($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function run($url) {
        $action = explode("/", $url)[0];

        if (!array_key_exists($action, self::$routes)) {
            die("Wrong url! (404)");
        }

        $controller = self::$routes[$action]['controller'];
        $object = new $controller;
        $action = self::$routes[$action]['action'];

        $object->$action();
    }
}

// Definicja tras
Routing::$routes = [
    'register' => [
        'controller' => 'SecurityController',
        'action' => 'register'
    ],
    'login' => [
        'controller' => 'SecurityController',
        'action' => 'login'
    ]
];