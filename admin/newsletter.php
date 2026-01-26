<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$page_title = 'Newsletter Management';
$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE newsletter_subscriptions SET status = :status, unsubscribed_at = :unsubscribed_at WHERE id = :id");
    $unsubscribed_at = ($status === 'unsubscribed') ? date('Y-m-d H:i:s') : null;
    $stmt->execute([':status' => $status, ':unsubscribed_at' => $unsubscribed_at, ':id' => $id]);
    
    header('Location: newsletter.php?updated=1');
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM newsletter_subscriptions WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if ($searchQuery) {
    $query .= " AND (email LIKE :search OR source LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$query .= " ORDER BY subscribed_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
    SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
FROM newsletter_subscriptions";
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

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Subscribers</div>
            <div class="stat-number"><?php echo $stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-number text-success"><?php echo $stats['active']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Unsubscribed</div>
            <div class="stat-number text-secondary"><?php echo $stats['unsubscribed']; ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Bounced</div>
            <div class="stat-number text-danger"><?php echo $stats['bounced']; ?></div>
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
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="unsubscribed" <?php echo $statusFilter === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                    <option value="bounced" <?php echo $statusFilter === 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by email or source..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Subscribers Table -->
<div class="row">
    <div class="col-12">
        <div class="data-table">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-mailbox"></i> Newsletter Subscribers</h5>
                <span class="text-muted small"><?php echo count($subscribers); ?> result(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 dataTable" id="newsletterTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Subscribed</th>
                            <th>Last Email Sent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No subscribers found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscribers as $s): ?>
                            <tr>
                                <td>#<?php echo $s['id']; ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($s['email']); ?>"><?php echo htmlspecialchars($s['email']); ?></a></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <option value="active" <?php echo $s['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="unsubscribed" <?php echo $s['status'] === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                                            <option value="bounced" <?php echo $s['status'] === 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($s['source'] ?? 'website'); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($s['subscribed_at'])); ?></td>
                                <td>
                                    <?php if ($s['last_email_sent']): ?>
                                        <?php echo date('M d, Y', strtotime($s['last_email_sent'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $s['id']; ?>">
                                        <i class="bi bi-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Details Modal -->
                            <div class="modal fade" id="detailsModal<?php echo $s['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Subscriber Details #<?php echo $s['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Email:</strong><br>
                                                    <a href="mailto:<?php echo htmlspecialchars($s['email']); ?>"><?php echo htmlspecialchars($s['email']); ?></a>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Status:</strong><br>
                                                    <span class="badge bg-<?php echo $s['status'] === 'active' ? 'success' : ($s['status'] === 'bounced' ? 'danger' : 'secondary'); ?>">
                                                        <?php echo ucfirst($s['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Source:</strong><br>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($s['source'] ?? 'website'); ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>IP Address:</strong><br>
                                                    <code><?php echo htmlspecialchars($s['ip_address'] ?? 'N/A'); ?></code>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Subscribed At:</strong><br>
                                                    <?php echo date('F d, Y H:i:s', strtotime($s['subscribed_at'])); ?>
                                                </div>
                                                <?php if ($s['unsubscribed_at']): ?>
                                                <div class="col-md-6">
                                                    <strong>Unsubscribed At:</strong><br>
                                                    <?php echo date('F d, Y H:i:s', strtotime($s['unsubscribed_at'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($s['last_email_sent']): ?>
                                            <div class="mb-3">
                                                <strong>Last Email Sent:</strong><br>
                                                <?php echo date('F d, Y H:i:s', strtotime($s['last_email_sent'])); ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <hr>
                                            
                                            <!-- Update Status -->
                                            <h6 class="mb-3">Update Status</h6>
                                            <form method="POST">
                                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <div class="row g-2">
                                                    <div class="col-md-8">
                                                        <select name="status" class="form-select form-select-sm" required>
                                                            <option value="active" <?php echo $s['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="unsubscribed" <?php echo $s['status'] === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                                                            <option value="bounced" <?php echo $s['status'] === 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="submit" class="btn btn-sm btn-primary w-100">Update</button>
                                                    </div>
                                                </div>
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
