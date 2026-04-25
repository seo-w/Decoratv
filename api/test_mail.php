<?php
/**
 * Test Email API for DecoraTV
 * Uses PHPMailer with stored settings.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $pdo = get_db_connection();
    $settingsRaw = $pdo->query("SELECT * FROM settings")->fetchAll();
    $s = [];
    foreach ($settingsRaw as $row) { $s[$row['key']] = $row['value']; }

    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = $s['smtp_host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $s['smtp_user'] ?? '';
    $mail->Password   = $s['smtp_pass'] ?? '';
    $mail->SMTPSecure = ($s['smtp_encryption'] ?? 'tls') === 'none' ? false : ($s['smtp_encryption'] ?? 'tls');
    $mail->Port       = $s['smtp_port'] ?? 587;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom($s['smtp_from_email'] ?? $s['smtp_user'], $s['smtp_from_name'] ?? 'DecoraTV Test');
    $mail->addAddress($s['admin_email'] ?? $s['smtp_user']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'DecoraTV SMTP Test Connection';
    $mail->Body    = "
    <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #f59e0b;'>Connection Successful!</h2>
        <p>If you are reading this, your SMTP configuration is working correctly.</p>
        <p style='font-size: 12px; color: #999;'>Sent at: " . date('Y-m-d H:i:s') . "</p>
    </div>";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Test email sent successfully! Check your inbox.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Mailer Error: {$mail->ErrorInfo}"]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
