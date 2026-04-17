<?php
// DELETE THIS FILE AFTER TESTING
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

$cfg = include __DIR__ . '/mail_config.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->SMTPDebug  = 2;
    $mail->isSMTP();
    $mail->Host       = $cfg['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['username'];
    $mail->Password   = $cfg['password'];
    $mail->SMTPSecure = $cfg['encryption'];
    $mail->Port       = $cfg['port'];
    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($cfg['username']); // send to self
    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'Test email from PLM system';
    $mail->send();
    echo 'SUCCESS: Email sent.';
} catch (Throwable $e) {
    echo 'FAILED: ' . $e->getMessage() . '<br>';
    echo 'SMTP Error: ' . $mail->ErrorInfo;
}
