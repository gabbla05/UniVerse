<?php

namespace src\services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService {

    public function sendConfirmationEmail($userEmail, $eventTitle) {
        $subject = "Confirmation: You joined $eventTitle";
        
        $message = "Hey there!\n\n";
        $message .= "This is a confirmation that you have successfully joined the event: $eventTitle.\n";
        $message .= "See you there!\n\n";
        $message .= "Best regards,\nUniVerse Team";

        $this->saveToTextFile($userEmail, $subject, $message);

        try {
            $this->sendViaGmail($userEmail, $subject, $message);
        } catch (Exception $e) {
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

        $this->saveToTextFile($userEmail, $subject, $message);

        try {
            $this->sendViaGmail($userEmail, $subject, $message);
        } catch (Exception $e) {
            error_log("Failed to send new event email: " . $e->getMessage());
        }
    }

    private function saveToTextFile($to, $subject, $message) {
        $filename = date('Y-m-d_H-i-s') . '__' . $this->sanitizeFileName($to) . '.txt';
        $path = __DIR__ . '/../../public/uploads/emails/' . $filename;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $content = "To: $to\nSubject: $subject\n\n$message";
        file_put_contents($path, $content);
    }

    private function sendViaGmail($to, $subject, $message) {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        $mail->Username   = 'gabrielablaut05@gmail.com';     
        $mail->Password   = 'fgpp uxmm xjwr ovsr';     
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('gabrielablaut05@gmail.com', 'UniVerse App'); 
        
        $mail->addAddress($to);

        $mail->isHTML(false); 
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    }

    private function sanitizeFileName($file) {
        $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file);
        return mb_ereg_replace("([\.]{2,})", '', $file);
    }
}