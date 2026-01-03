<?php

class AppController {

    protected function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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