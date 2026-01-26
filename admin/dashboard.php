<?php
/**
 * Admin Dashboard - Enhanced Mobile Responsive
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$page_title = 'Dashboard';

$db = getDB();

// Get statistics
$stats = [];

// Contact submissions
$stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count FROM contact_submissions");
$stats['contacts'] = $stmt->fetch();

// Newsletter subscriptions
$stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count FROM newsletter_subscriptions");
$stats['newsletter'] = $stmt->fetch();

// Enrollments
$stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN application_status = 'submitted' THEN 1 ELSE 0 END) as pending_count, SUM(CASE WHEN application_status = 'accepted' THEN 1 ELSE 0 END) as accepted_count FROM enrollments");
$stats['enrollments'] = $stmt->fetch();

// Recent enrollments
$stmt = $db->query("SELECT * FROM enrollments ORDER BY created_at DESC LIMIT 5");
$recentEnrollments = $stmt->fetchAll();

// Recent contacts
$stmt = $db->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5");
$recentContacts = $stmt->fetchAll();

// Package stats
$stmt = $db->query("SELECT package_type, COUNT(*) as count FROM enrollments GROUP BY package_type");
$packageStats = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Total Enrollments</div>
            <div class="stat-number"><?php echo $stats['enrollments']['total']; ?></div>
            <div class="text-success small"><i class="bi bi-check-circle"></i> <?php echo $stats['enrollments']['accepted_count']; ?> Accepted</div>
            <div class="text-warning small"><i class="bi bi-clock"></i> <?php echo $stats['enrollments']['pending_count']; ?> Pending</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Contact Messages</div>
            <div class="stat-number"><?php echo $stats['contacts']['total']; ?></div>
            <div class="text-danger small"><i class="bi bi-envelope"></i> <?php echo $stats['contacts']['new_count']; ?> New</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Newsletter Subscribers</div>
            <div class="stat-number"><?php echo $stats['newsletter']['active_count']; ?></div>
            <div class="text-success small"><i class="bi bi-check-circle"></i> Active</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="stat-label">Total Subscribers</div>
            <div class="stat-number"><?php echo $stats['newsletter']['total']; ?></div>
            <div class="text-info small"><i class="bi bi-people"></i> All Time</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="data-table">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-people"></i> Recent Enrollments</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 dataTable" id="recentEnrollmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentEnrollments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No enrollments yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentEnrollments as $e): ?>
                            <tr>
                                <td>#<?php echo $e['id']; ?></td>
                                <td><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($e['email']); ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $e['package_type'])); ?></span></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $e['application_status'] === 'accepted' ? 'success' : 
                                            ($e['application_status'] === 'rejected' ? 'danger' : 
                                            ($e['application_status'] === 'reviewing' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($e['application_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <a href="enrollments.php" class="btn btn-primary btn-sm">View All Enrollments</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="data-table">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> Recent Contacts</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 dataTable" id="recentContactsTable" data-export="false">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentContacts)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">No contacts yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentContacts as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $c['status'] === 'new' ? 'danger' : 'success'; ?>">
                                        <?php echo ucfirst($c['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <a href="contacts.php" class="btn btn-primary btn-sm">View All Contacts</a>
            </div>
        </div>
        
        <!-- Package Statistics -->
        <div class="data-table mt-4">
            <div class="p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Package Distribution</h5>
            </div>
            <div class="p-3">
                <?php if (empty($packageStats)): ?>
                    <p class="text-muted text-center mb-0">No data available</p>
                <?php else: ?>
                    <?php foreach ($packageStats as $ps): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo ucfirst(str_replace('_', ' ', $ps['package_type'])); ?></span>
                            <span class="badge bg-primary"><?php echo $ps['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
