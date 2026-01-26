<?php
/**
 * Contact Form API Endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Response.php';
require_once __DIR__ . '/../includes/Validator.php';
require_once __DIR__ . '/../includes/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', null, 405);
}

$validator = new Validator();
$data = [
    'name' => trim($_POST['name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'subject' => trim($_POST['subject'] ?? ''),
    'message' => trim($_POST['message'] ?? '')
];

$rules = [
    'name' => 'required|min:2|max:255',
    'email' => 'required|email',
    'subject' => 'required|min:3|max:255',
    'message' => 'required|min:10'
];

if (!$validator->validate($data, $rules)) {
    Response::validationError($validator->getErrors());
}

try {
    $db = getDB();
    
    // Get client IP and user agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Insert into database
    $stmt = $db->prepare("
        INSERT INTO contact_submissions (name, email, subject, message, ip_address, user_agent)
        VALUES (:name, :email, :subject, :message, :ip_address, :user_agent)
    ");
    
    $stmt->execute([
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':subject' => $data['subject'],
        ':message' => $data['message'],
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);
    
    // Send email notification
    $emailService = new EmailService();
    $emailService->sendContactNotification($data);
    
    Response::success('Your message has been sent successfully. We will get back to you soon!', [
        'submission_id' => $db->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    Response::error('An error occurred while processing your request. Please try again later.', null, 500);
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    Response::error('An error occurred while processing your request. Please try again later.', null, 500);
}
