<?php

class EmailService {
    
    public static function send(string $to, string $subject, string $body) {
        // Ustal folder zapisu
        $uploadDir = __DIR__ . '/../../public/uploads/emails/';
        
        // Jeśli folder nie istnieje, stwórz go
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // --- PRZYGOTOWANIE NAZWY PLIKU ---
        
        // 1. Data i czas
        $timestamp = date('Y-m-d_H-i-s');
        
        // 2. Temat (usuwamy dziwne znaki i spacje zamieniamy na podkreślniki)
        $safeSubject = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $subject);
        $safeSubject = str_replace(' ', '_', $safeSubject);
        
        // 3. Unikalne ID (żeby pliki z tej samej sekundy się nie nadpisały)
        $randomId = uniqid();
        
        // FORMAT NAZWY: DATA__EMAIL__TEMAT__ID.txt
        // Np: 2025-12-27_21-30-05__jan.kowalski@pk.edu.pl__Confirmation_Joined__a1b2.txt
        $filename = "{$timestamp}__{$to}__{$safeSubject}__{$randomId}.txt";
        
        $filePath = $uploadDir . $filename;

        // --- TREŚĆ PLIKU ---
        $content = "========================================\n";
        $content .= "TO: $to\n";
        $content .= "SUBJECT: $subject\n";
        $content .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $content .= "========================================\n\n";
        $content .= $body;
        $content .= "\n\n========================================\n";
        $content .= "Sent via UniVerse Mock Mailer";

        // Zapisz plik
        file_put_contents($filePath, $content);
    }
}