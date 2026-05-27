<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
//Load Composer's autoloader (created by composer, not included with PHPMailer)
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../includes/smtp.php';
function mailer($to_email, $to_name, $subject, $message)
{
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        $settings = get_smtp_settings();
        apply_smtp_settings($mail, $settings);

        $mail->addAddress($to_email, $to_name);

        

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();
    } catch (\Throwable $e) {
        error_log("Mailer failed for {$to_email}: " . $e->getMessage() . " " . $mail->ErrorInfo);
        return false;
    }
}
