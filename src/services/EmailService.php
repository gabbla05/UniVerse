<?php

namespace src\services;

// Importujemy klasy PHPMailera (to sprawia, że PHP wie, skąd brać funkcje mailowe)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService {

    // --- 1. GŁÓWNA FUNKCJA (Tę wywołuje aplikacja) ---
    public function sendConfirmationEmail($userEmail, $eventTitle) {
        $subject = "Confirmation: You joined $eventTitle";
        
        $message = "Hey there!\n\n";
        $message .= "This is a confirmation that you have successfully joined the event: $eventTitle.\n";
        $message .= "See you there!\n\n";
        $message .= "Best regards,\nUniVerse Team";

        // NAJPIERW: Zapisz do pliku (Twoja "atrapa" - działa zawsze jako log)
        $this->saveToTextFile($userEmail, $subject, $message);

        // POTEM: Spróbuj wysłać prawdziwego maila przez Gmail
        try {
            $this->sendViaGmail($userEmail, $subject, $message);
        } catch (Exception $e) {
            // Jak nie ma neta albo coś pójdzie nie tak, to tylko zapiszemy błąd w logach serwera,
            // ale strona się nie wywali studentowi.
            error_log("Błąd wysyłania maila: " . $e->getMessage());
        }
    }

    public function sendNewEventNotification($userEmail, $eventTitle, $eventDate) {
        $subject = "New Event at your Uni: $eventTitle";
        
        $message = "Hello!\n\n";
        $message .= "Good news! A new event '$eventTitle' has just been added to your university calendar.\n";
        $message .= "It takes place on: $eventDate.\n\n";
        $message .= "Log in to UniVerse to check the details and join!\n\n";
        $message .= "Cheers,\nUniVerse Team";

        // Logowanie do pliku (Atrapa)
        $this->saveToTextFile($userEmail, $subject, $message);

        // Wysyłka prawdziwa
        try {
            $this->sendViaGmail($userEmail, $subject, $message);
        } catch (Exception $e) {
            error_log("Failed to send new event email: " . $e->getMessage());
        }
    }

    // --- 2. METODA "ATRAPA" (Zapis do pliku tekstowego) ---
    private function saveToTextFile($to, $subject, $message) {
        // Tworzymy unikalną nazwę pliku
        $filename = date('Y-m-d_H-i-s') . '__' . $this->sanitizeFileName($to) . '.txt';
        $path = __DIR__ . '/../../public/uploads/emails/' . $filename;

        // Jeśli folder nie istnieje, to go stwórz
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $content = "To: $to\nSubject: $subject\n\n$message";
        file_put_contents($path, $content);
    }

    // --- 3. METODA "PRO" (Prawdziwy Gmail - PHPMailer) ---
    private function sendViaGmail($to, $subject, $message) {
        $mail = new PHPMailer(true);

        // Ustawienia serwera Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // =================================================================
        // TU WPISZ SWOJE DANE:
        // =================================================================
        $mail->Username   = 'gabrielablaut@gmail.com';     // <-- Twój adres Gmail (nadawca)
        $mail->Password   = 'fgpp uxmm xjwr ovsr';     // <-- Twoje 16-znakowe hasło aplikacji
        // =================================================================
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Nadawca (to co widzi odbiorca)
        $mail->setFrom('gabrielablaut@gmail.com', 'UniVerse App'); // <-- Tu też wpisz swój mail
        
        // Odbiorca
        $mail->addAddress($to);

        // Treść
        $mail->isHTML(false); // Wysyłamy czysty tekst
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    }

    // Funkcja pomocnicza do czyszczenia nazwy pliku
    private function sanitizeFileName($file) {
        $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file);
        return mb_ereg_replace("([\.]{2,})", '', $file);
    }
}