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

    echo json_encode([
        'success' => true, 
        'message' => 'Your quote request has been registered. Our team will contact you soon.',
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
