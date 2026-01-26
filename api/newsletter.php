<?php
/**
 * Newsletter Subscription API Endpoint
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
$email = trim($_POST['email'] ?? '');

// Validate email is not empty
if (empty($email)) {
    Response::error('Email address is required.');
}

$rules = [
    'email' => 'required|email'
];

if (!$validator->validate(['email' => $email], $rules)) {
    Response::validationError($validator->getErrors());
}

try {
    $db = getDB();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id, status FROM newsletter_subscriptions WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            Response::error('This email is already subscribed to our newsletter.');
        } else {
            // Reactivate subscription
            $stmt = $db->prepare("
                UPDATE newsletter_subscriptions 
                SET status = 'active', subscribed_at = NOW(), unsubscribed_at = NULL
                WHERE email = :email
            ");
            $stmt->execute([':email' => $email]);
            
            $emailService = new EmailService();
            $emailService->sendNewsletterWelcome($email);
            
            Response::success('Welcome back! You have been resubscribed to our newsletter.');
        }
    } else {
        // Get client IP
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Insert new subscription
        $stmt = $db->prepare("
            INSERT INTO newsletter_subscriptions (email, ip_address, source)
            VALUES (:email, :ip_address, 'website')
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':ip_address' => $ip_address
        ]);
        
        // Send welcome email
        $emailService = new EmailService();
        $emailService->sendNewsletterWelcome($email);
        
        Response::success('Thank you for subscribing! Check your email for a welcome message.', [
            'subscription_id' => $db->lastInsertId()
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    Response::error('An error occurred while processing your subscription. Please try again later.', null, 500);
} catch (Exception $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    Response::error('An error occurred while processing your subscription. Please try again later.', null, 500);
}
