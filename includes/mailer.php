<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once BASE_PATH . '/vendor/autoload.php';

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);

    try {
        // ── SMTP beállítások (Nethely/Omega) ──
        $mail->isSMTP();
        $mail->Host       = 'mail.foglalasi-rendszer.hu'; // ← Omega SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hello@foglalasi-rendszer.hu'; // ← email fiók
        $mail->Password   = 'Mazsika1230';                   // ← email jelszó
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // ── Feladó ──
        $mail->setFrom('hello@foglalasi-rendszer.hu', 'Foglalási Rendszer');
        $mail->addReplyTo('hello@foglalasi-rendszer.hu', 'Foglalási Rendszer');

        // ── Címzett ──
        $mail->addAddress($toEmail, $toName);

        // ── Tartalom ──
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer hiba: ' . $mail->ErrorInfo);
        return false;
    }
}