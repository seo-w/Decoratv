<?php
/**
 * Quote API for DecoraTV
 * Receives design selection and customer data, saves to DB, and triggers email.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

$pdo = get_db_connection();

// 1. Robust Validation
$errors = [];
$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName'] ?? '');
$email     = trim($data['email'] ?? '');
$phone     = trim($data['phone'] ?? '');

if (empty($firstName)) $errors[] = "First name is required.";
if (empty($lastName))  $errors[] = "Last name is required.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
if (empty($phone) || strlen($phone) < 7) $errors[] = "A valid phone number is required.";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    // 2. Save to Database
    $stmt = $pdo->prepare("INSERT INTO quotes (customer_name, customer_last_name, customer_email, customer_phone, selection_json) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $phone,
        json_encode($data['selection'] ?? [])
    ]);

    // 2. Fetch SMTP Settings
    $settingsRaw = $pdo->query("SELECT * FROM settings")->fetchAll();
    $s = [];
    foreach ($settingsRaw as $row) { $s[$row['key']] = $row['value']; }

    // 3. Prepare Email
    $adminEmail = $s['admin_email'] ?? 'admin@decoratv.com';
    $fromEmail  = !empty($s['smtp_from_email']) ? $s['smtp_from_email'] : 'no-reply@' . $_SERVER['HTTP_HOST'];
    $fromName   = $s['smtp_from_name'] ?? 'DecoraTV Studio';

    $subject = "New Quote Request: $firstName $lastName";
    
    $sel = $data['selection'] ?? [];
    $frame = $sel['frame_name'] ?? 'None';
    $frameId = $sel['frame_id'] ?? '-';
    $liner = $sel['liner_name'] ?? 'None';
    $linerId = $sel['liner_id'] ?? '-';
    $art = $sel['art_name'] ?? 'None';
    $artId = $sel['art_id'] ?? '-';

    $messageHtml = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
            <h2 style='color: #f59e0b; text-transform: uppercase;'>New Design Quote</h2>
            <p>You have received a new inquiry from the simulator.</p>
            
            <h3 style='border-bottom: 1px solid #eee; padding-bottom: 10px;'>Customer Info</h3>
            <p><strong>Name:</strong> $firstName $lastName<br>
               <strong>Email:</strong> $email<br>
               <strong>Phone:</strong> $phone</p>
            
            <h3 style='border-bottom: 1px solid #eee; padding-bottom: 10px;'>Selection Details</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr><td style='padding: 8px 0;'><strong>Frame:</strong></td><td>$frame ($frameId)</td></tr>
                <tr><td style='padding: 8px 0;'><strong>Liner:</strong></td><td>$liner ($linerId)</td></tr>
                <tr><td style='padding: 8px 0;'><strong>Art:</strong></td><td>$art ($artId)</td></tr>
            </table>
            
            <div style='margin-top: 30px; font-size: 12px; color: #999;'>
                Sent from DecoraTV Management Studio
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $fromName <$fromEmail>" . "\r\n";
    $headers .= "Reply-To: $email" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $mailSent = @mail($adminEmail, $subject, $messageHtml, $headers);

    echo json_encode([
        'success' => true, 
        'message' => 'Your quote request has been registered. Our team will contact you soon.',
        'mail_status' => $mailSent ? 'sent' : 'failed'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
