<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailService.php';
$page_title = 'Enrollments Management';
$db = getDB();

$message = '';
$messageType = 'success';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    $stmt = $db->prepare("UPDATE enrollments SET application_status = :status, notes = :notes WHERE id = :id");
    $stmt->execute([':status' => $status, ':notes' => $notes, ':id' => $id]);
    
    // Send email notification if status changed to accepted
    if ($status === 'accepted') {
        $stmt = $db->prepare("SELECT * FROM enrollments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $enrollment = $stmt->fetch();
        
        if ($enrollment) {
            $emailService = new EmailService();
            $subject = "Congratulations! Your Enrollment Application Has Been Accepted";
            $emailMessage = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #D4AF37 0%, #CD7F32 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>🎉 Application Accepted!</h2>
                    </div>
                    <div class='content'>
                        <p>Dear {$enrollment['first_name']},</p>
                        <p>We are delighted to inform you that your enrollment application to Bossify Academy has been <strong>accepted</strong>!</p>
                        <p>Our team will contact you shortly with next steps and payment instructions.</p>
                        <p>Best regards,<br><strong>Bossify Academy Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";
            
            $emailService->send($enrollment['email'], $subject, $emailMessage);
        }
    }
    
    $message = 'Status updated successfully!';
    header('Location: enrollments.php?updated=1');
    exit;
}

// Handle sending response email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
    $id = $_POST['id'];
    $response_subject = $_POST['response_subject'];
    $response_message = $_POST['response_message'];
    
    $stmt = $db->prepare("SELECT * FROM enrollments WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $enrollment = $stmt->fetch();
    
    if ($enrollment) {
        $emailService = new EmailService();
        $emailMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4B2C5E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Response from Bossify Academy</h2>
                </div>
                <div class='content'>
                    <p>Dear {$enrollment['first_name']},</p>
                    " . nl2br(htmlspecialchars($response_message)) . "
                    <p>Best regards,<br><strong>Bossify Academy Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
        
        $emailService->send($enrollment['email'], $response_subject, $emailMessage);
        $message = 'Response email sent successfully!';
        header('Location: enrollments.php?emailed=1');
        exit;
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$packageFilter = $_GET['package'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM enrollments WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND application_status = :status";
    $params[':status'] = $statusFilter;
}

if ($packageFilter) {
    $query .= " AND package_type = :package";
    $params[':package'] = $packageFilter;
}

if ($searchQuery) {
    $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN application_status = 'submitted' THEN 1 ELSE 0 END) as submitted,
    SUM(CASE WHEN application_status = 'reviewing' THEN 1 ELSE 0 END) as reviewing,
    SUM(CASE WHEN application_status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN application_status = 'waitlisted' THEN 1 ELSE 0 END) as waitlisted
FROM enrollments";
$statsStmt = $db->query($statsQuery);
$stats = $statsStmt->fetch();

include 'includes/header.php';
?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Status updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['emailed'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-envelope-check"></i> Response email sent successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Total</div>
            <div class="stat-number"><?php echo $stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Submitted</div>
            <div class="stat-number text-warning"><?php echo $stats['submitted']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Reviewing</div>
            <div class="stat-number text-info"><?php echo $stats['reviewing']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Accepted</div>
            <div class="stat-number text-success"><?php echo $stats['accepted']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Rejected</div>
            <div class="stat-number text-danger"><?php echo $stats['rejected']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Waitlisted</div>
            <div class="stat-number text-secondary"><?php echo $stats['waitlisted']; ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="data-table mb-4">
    <div class="p-3">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?php echo $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="reviewing" <?php echo $statusFilter === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                    <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="waitlisted" <?php echo $statusFilter === 'waitlisted' ? 'selected' : ''; ?>>Waitlisted</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Package</label>
                <select name="package" class="form-select">
                    <option value="">All Packages</option>
                    <option value="single_track" <?php echo $packageFilter === 'single_track' ? 'selected' : ''; ?>>Single Track</option>
                    <option value="full_cohort" <?php echo $packageFilter === 'full_cohort' ? 'selected' : ''; ?>>Full Cohort</option>
                    <option value="corporate" <?php echo $packageFilter === 'corporate' ? 'selected' : ''; ?>>Corporate</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Enrollments Table -->
<div class="row">
    <div class="col-12">
        <div class="data-table">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people"></i> Enrollment Applications</h5>
                <span class="text-muted small"><?php echo count($enrollments); ?> result(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 dataTable" id="enrollmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrollments)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No enrollments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td>#<?php echo $e['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($e['email']); ?></td>
                                <td><?php echo htmlspecialchars($e['phone']); ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $e['package_type'])); ?></span></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <option value="submitted" <?php echo $e['application_status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                            <option value="reviewing" <?php echo $e['application_status'] === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                                            <option value="accepted" <?php echo $e['application_status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                            <option value="rejected" <?php echo $e['application_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="waitlisted" <?php echo $e['application_status'] === 'waitlisted' ? 'selected' : ''; ?>>Waitlisted</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="notes" value="">
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $e['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Details Modal with Management Features -->
                            <div class="modal fade" id="detailsModal<?php echo $e['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Enrollment Details #<?php echo $e['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Application Information -->
                                            <div class="row mb-3">
                                                <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></div>
                                                <div class="col-md-6"><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($e['email']); ?>"><?php echo htmlspecialchars($e['email']); ?></a></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6"><strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($e['phone']); ?>"><?php echo htmlspecialchars($e['phone']); ?></a></div>
                                                <div class="col-md-6"><strong>Package:</strong> <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $e['package_type'])); ?></span></div>
                                            </div>
                                            <?php if ($e['course_track']): ?>
                                            <div class="mb-3"><strong>Course Track:</strong> <?php echo ucfirst(str_replace('_', ' ', $e['course_track'])); ?></div>
                                            <?php endif; ?>
                                            <?php if ($e['company_name']): ?>
                                            <div class="mb-3"><strong>Company:</strong> <?php echo htmlspecialchars($e['company_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($e['position']): ?>
                                            <div class="mb-3"><strong>Position:</strong> <?php echo htmlspecialchars($e['position']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($e['years_experience']): ?>
                                            <div class="mb-3"><strong>Years of Experience:</strong> <?php echo $e['years_experience']; ?></div>
                                            <?php endif; ?>
                                            <div class="mb-3"><strong>Motivation:</strong><br><div class="p-2 bg-light rounded"><?php echo nl2br(htmlspecialchars($e['motivation'])); ?></div></div>
                                            <div class="mb-3"><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $e['payment_method'])); ?></div>
                                            <div class="mb-3"><strong>Applied:</strong> <?php echo date('F d, Y H:i', strtotime($e['created_at'])); ?></div>
                                            
                                            <?php if ($e['notes']): ?>
                                            <div class="mb-3">
                                                <strong>Admin Notes:</strong>
                                                <div class="p-2 bg-warning bg-opacity-10 rounded"><?php echo nl2br(htmlspecialchars($e['notes'])); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <hr>
                                            
                                            <!-- Management Actions -->
                                            <h6 class="mb-3">Management Actions</h6>
                                            
                                            <!-- Update Status with Notes -->
                                            <form method="POST" class="mb-3">
                                                <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Update Status</label>
                                                        <select name="status" class="form-select form-select-sm" required>
                                                            <option value="submitted" <?php echo $e['application_status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                                            <option value="reviewing" <?php echo $e['application_status'] === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                                                            <option value="accepted" <?php echo $e['application_status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                            <option value="rejected" <?php echo $e['application_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                            <option value="waitlisted" <?php echo $e['application_status'] === 'waitlisted' ? 'selected' : ''; ?>>Waitlisted</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Add Note (Optional)</label>
                                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Internal note..." value="<?php echo htmlspecialchars($e['notes']); ?>">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary mt-2">Update Status</button>
                                            </form>
                                            
                                            <!-- Send Response Email -->
                                            <form method="POST" class="border-top pt-3">
                                                <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                                <input type="hidden" name="send_response" value="1">
                                                <h6>Send Response Email</h6>
                                                <div class="mb-2">
                                                    <label class="form-label">Subject</label>
                                                    <input type="text" name="response_subject" class="form-control form-control-sm" value="Re: Your Enrollment Application - Bossify Academy" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Message</label>
                                                    <textarea name="response_message" class="form-control" rows="4" required placeholder="Type your response message here..."></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-envelope"></i> Send Email
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
