<?php

class AppController {
    
    // Pusty konstruktor, żeby klasy dziedziczące mogły bezpiecznie wołać parent::__construct()
    public function __construct() {}

    protected function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function render(string $template = null, array $variables = []) {
        $templatePath = 'public/views/' . $template . '.php'; // Szukamy plików .php
        $output = 'File not found';
                
        if (file_exists($templatePath)) {
            // Rozpakowuje tablicę zmiennych do widoku
            extract($variables);
            
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        }
        
        print $output;
    }
}