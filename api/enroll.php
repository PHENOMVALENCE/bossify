<?php
/**
 * Enrollment Application API Endpoint
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
    'first_name' => trim($_POST['first_name'] ?? ''),
    'last_name' => trim($_POST['last_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'package_type' => $_POST['package_type'] ?? '',
    'course_track' => $_POST['course_track'] ?? '',
    'company_name' => trim($_POST['company_name'] ?? ''),
    'position' => trim($_POST['position'] ?? ''),
    'years_experience' => $_POST['years_experience'] ?? '',
    'motivation' => trim($_POST['motivation'] ?? ''),
    'payment_method' => $_POST['payment_method'] ?? 'invoice',
    'cohort_id' => $_POST['cohort_id'] ?? ''
];

// Ensure last_name is not empty (fallback to first_name if needed)
if (empty($data['last_name'])) {
    $data['last_name'] = $data['first_name'];
}

$rules = [
    'first_name' => 'required|min:2|max:100',
    'last_name' => 'min:1|max:100', // Optional, will use first_name if empty
    'email' => 'required|email',
    'phone' => 'required|min:10|max:20',
    'package_type' => 'required',
    'motivation' => 'required|min:20'
];

// Validate package type
$validPackages = ['single_track', 'full_cohort', 'corporate'];
if (!in_array($data['package_type'], $validPackages)) {
    Response::error('Invalid package type selected.');
}

// If single track, require course_track
if ($data['package_type'] === 'single_track' && empty($data['course_track'])) {
    Response::error('Please select a course track for the Single Track Program.');
}

if (!$validator->validate($data, $rules)) {
    Response::validationError($validator->getErrors());
}

try {
    $db = getDB();
    
    // Get client IP
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // Get next available cohort if not specified
    if (empty($data['cohort_id'])) {
        $stmt = $db->prepare("
            SELECT id, cohort_code FROM cohorts 
            WHERE status = 'upcoming' 
            ORDER BY start_date ASC 
            LIMIT 1
        ");
        $stmt->execute();
        $cohort = $stmt->fetch();
        $data['cohort_id'] = $cohort ? $cohort['cohort_code'] : null;
    }
    
    // Insert enrollment
    $stmt = $db->prepare("
        INSERT INTO enrollments (
            first_name, last_name, email, phone, package_type, course_track,
            company_name, position, years_experience, motivation, payment_method,
            cohort_id, ip_address
        ) VALUES (
            :first_name, :last_name, :email, :phone, :package_type, :course_track,
            :company_name, :position, :years_experience, :motivation, :payment_method,
            :cohort_id, :ip_address
        )
    ");
    
    $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name' => $data['last_name'],
        ':email' => $data['email'],
        ':phone' => $data['phone'],
        ':package_type' => $data['package_type'],
        ':course_track' => $data['course_track'] ?: null,
        ':company_name' => $data['company_name'] ?: null,
        ':position' => $data['position'] ?: null,
        ':years_experience' => $data['years_experience'] ? (int)$data['years_experience'] : null,
        ':motivation' => $data['motivation'],
        ':payment_method' => $data['payment_method'],
        ':cohort_id' => $data['cohort_id'],
        ':ip_address' => $ip_address
    ]);
    
    $enrollmentId = $db->lastInsertId();
    $data['id'] = $enrollmentId;
    
    // Send confirmation email
    $emailService = new EmailService();
    $emailService->sendEnrollmentConfirmation($data);
    
    Response::success('Your enrollment application has been submitted successfully! We will review it and contact you soon.', [
        'enrollment_id' => $enrollmentId,
        'application_number' => 'BA-' . str_pad($enrollmentId, 6, '0', STR_PAD_LEFT)
    ]);
    
} catch (PDOException $e) {
    error_log("Enrollment error: " . $e->getMessage());
    Response::error('An error occurred while processing your enrollment. Please try again later.', null, 500);
} catch (Exception $e) {
    error_log("Enrollment error: " . $e->getMessage());
    Response::error('An error occurred while processing your enrollment. Please try again later.', null, 500);
}
