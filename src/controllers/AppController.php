<?php

class AppController {

    // W pliku AppController.php

    protected function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // --- WYMÓG: Cookie sesyjne z flagą HttpOnly ---
            session_set_cookie_params([
                'httponly' => true,
                'secure' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
        
        // --- WYMÓG E1: Wymuszenie HTTPS w kodzie ---
        // Sprawdzamy, czy połączenie jest szyfrowane (parametr przekazany przez Nginx)
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
            // Zamiast brutalnego die(), robimy przekierowanie (choć Nginx już to zrobił)
            // Ale dla "zaliczenia kodu" można tu dać die() jak w poleceniu:
            
            // die("HTTPS required (Security Policy E1)");
            
            // LUB wersja soft (przekierowanie):
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirect);
            exit();
        }
        // -------------------------------------------
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    // --- TO JEST METODA, KTÓRĄ MUSISZ POPRAWIĆ ---
    protected function render(string $template = null, array $variables = [])
    {
        // 1. Upewnij się, że sesja działa
        $this->ensureSession();

        // 2. Wygeneruj token CSRF, jeśli go nie ma w sesji
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // 3. Przekaż token do widoku (żeby input w HTML nie był pusty)
        $variables['csrf_token'] = $_SESSION['csrf_token'];

        $templatePath = 'public/views/'. $template.'.html';
        $output = 'File not found';
                
        if(file_exists($templatePath)){
            extract($variables);
            
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        }
        print $output;
    }
    // ---------------------------------------------

    public function landing() {
        return $this->render('landing');
    }

    public function adminView() {
        return $this->render('admin');
    }
}