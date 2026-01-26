<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailService.php';
$page_title = 'Contact Management';
$db = getDB();

$message = '';
$messageType = 'success';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Check if notes column exists, if not, just update status
    try {
        $stmt = $db->prepare("UPDATE contact_submissions SET status = :status, notes = :notes WHERE id = :id");
        $stmt->execute([':status' => $status, ':notes' => $notes, ':id' => $id]);
    } catch (PDOException $e) {
        // If notes column doesn't exist, update only status
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $db->prepare("UPDATE contact_submissions SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
        } else {
            throw $e;
        }
    }
    
    $message = 'Status updated successfully!';
    header('Location: contacts.php?updated=1');
    exit;
}

// Handle reply email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $id = $_POST['id'];
    $reply_subject = trim($_POST['reply_subject']);
    $reply_message = trim($_POST['reply_message']);
    
    // Get contact details
    $stmt = $db->prepare("SELECT * FROM contact_submissions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $contact = $stmt->fetch();
    
    if ($contact) {
        $emailService = new EmailService();
        
        // Create reply email with original message quoted
        $emailBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4B2C5E; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 8px 8px; }
                .original-message { background: #e9ecef; padding: 15px; border-left: 4px solid #4B2C5E; margin-top: 20px; border-radius: 4px; }
                .original-message-header { font-weight: bold; margin-bottom: 10px; color: #4B2C5E; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Response from Bossify Academy</h2>
                </div>
                <div class='content'>
                    <p>Dear {$contact['name']},</p>
                    " . nl2br(htmlspecialchars($reply_message)) . "
                    <div class='original-message'>
                        <div class='original-message-header'>Your Original Message:</div>
                        <p><strong>Subject:</strong> {$contact['subject']}</p>
                        <p>" . nl2br(htmlspecialchars($contact['message'])) . "</p>
                    </div>
                    <p>Best regards,<br><strong>Bossify Academy Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
        
        $emailService->send($contact['email'], $reply_subject, $emailBody);
        
        // Update status to 'replied' if not already
        if ($contact['status'] !== 'replied') {
            $stmt = $db->prepare("UPDATE contact_submissions SET status = 'replied' WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }
        
        header('Location: contacts.php?replied=1');
        exit;
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM contact_submissions WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if ($searchQuery) {
    $query .= " AND (name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
FROM contact_submissions";
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

<?php if (isset($_GET['replied'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-envelope-check"></i> Reply email sent successfully!
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
            <div class="stat-label">New</div>
            <div class="stat-number text-warning"><?php echo $stats['new']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Read</div>
            <div class="stat-number text-info"><?php echo $stats['read_count']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Replied</div>
            <div class="stat-number text-success"><?php echo $stats['replied']; ?></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">Archived</div>
            <div class="stat-number text-secondary"><?php echo $stats['archived']; ?></div>
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
                    <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                    <option value="replied" <?php echo $statusFilter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, subject, or message..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Contacts Table -->
<div class="row">
    <div class="col-12">
        <div class="data-table">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> Contact Submissions</h5>
                <span class="text-muted small"><?php echo count($contacts); ?> result(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 dataTable" id="contactsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No contact submissions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $c): ?>
                            <tr>
                                <td>#<?php echo $c['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>"><?php echo htmlspecialchars($c['email']); ?></a></td>
                                <td><?php echo htmlspecialchars($c['subject']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <option value="new" <?php echo $c['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="read" <?php echo $c['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                            <option value="replied" <?php echo $c['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                            <option value="archived" <?php echo $c['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="notes" value="">
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $c['id']; ?>">
                                        <i class="bi bi-eye"></i> View & Reply
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Message Modal with Reply Interface -->
                            <div class="modal fade" id="messageModal<?php echo $c['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Message from <?php echo htmlspecialchars($c['name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Original Message -->
                                            <div class="mb-4">
                                                <h6 class="mb-3">Original Message</h6>
                                                <div class="p-3 bg-light rounded">
                                                    <p><strong>From:</strong> <?php echo htmlspecialchars($c['name']); ?> &lt;<?php echo htmlspecialchars($c['email']); ?>&gt;</p>
                                                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($c['subject']); ?></p>
                                                    <p><strong>Message:</strong></p>
                                                    <p><?php echo nl2br(htmlspecialchars($c['message'])); ?></p>
                                                    <p class="text-muted small mb-0">
                                                        <strong>Submitted:</strong> <?php echo date('F d, Y H:i', strtotime($c['created_at'])); ?>
                                                        <?php if ($c['ip_address']): ?>
                                                            | <strong>IP:</strong> <?php echo htmlspecialchars($c['ip_address']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <?php if (isset($c['notes']) && !empty($c['notes'])): ?>
                                            <div class="mb-4">
                                                <h6 class="mb-2">Admin Notes</h6>
                                                <div class="p-2 bg-warning bg-opacity-10 rounded">
                                                    <?php echo nl2br(htmlspecialchars($c['notes'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <hr>
                                            
                                            <!-- Reply Interface -->
                                            <h6 class="mb-3">Reply to <?php echo htmlspecialchars($c['name']); ?></h6>
                                            <form method="POST">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                <input type="hidden" name="send_reply" value="1">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Subject</label>
                                                    <input type="text" name="reply_subject" class="form-control" value="Re: <?php echo htmlspecialchars($c['subject']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Message</label>
                                                    <textarea name="reply_message" class="form-control" rows="6" required placeholder="Type your reply message here..."></textarea>
                                                    <small class="text-muted">The original message will be included automatically in the email.</small>
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-envelope"></i> Send Reply
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                </div>
                                            </form>
                                            
                                            <hr class="my-4">
                                            
                                            <!-- Update Status with Notes -->
                                            <h6 class="mb-3">Update Status</h6>
                                            <form method="POST">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-6">
                                                        <select name="status" class="form-select form-select-sm" required>
                                                            <option value="new" <?php echo $c['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                            <option value="read" <?php echo $c['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                                            <option value="replied" <?php echo $c['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                                            <option value="archived" <?php echo $c['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Add internal note (optional)..." value="<?php echo htmlspecialchars($c['notes'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
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
