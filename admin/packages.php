<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$page_title = 'Package Management';

$db = getDB();

// Handle package updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_package'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $price = $_POST['price'];
        $currency = $_POST['currency'];
        $description = $_POST['description'];
        $features = json_encode(explode("\n", trim($_POST['features'])));
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $button_text = $_POST['button_text'];
        
        $stmt = $db->prepare("UPDATE packages SET title = :title, price = :price, currency = :currency, description = :description, features = :features, is_featured = :is_featured, is_active = :is_active, button_text = :button_text WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':price' => $price,
            ':currency' => $currency,
            ':description' => $description,
            ':features' => $features,
            ':is_featured' => $is_featured,
            ':is_active' => $is_active,
            ':button_text' => $button_text
        ]);
        
        header('Location: packages.php?updated=1');
        exit;
    }
}

// Check if packages table exists, if not show message
try {
    $stmt = $db->query("SELECT * FROM packages ORDER BY display_order ASC");
    $packages = $stmt->fetchAll();
    $packages = array_map(function($p) {
        $p['features'] = json_decode($p['features'], true) ?: [];
        return $p;
    }, $packages);
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') {
        // Table doesn't exist
        $packages = [];
        $message = '<div class="alert alert-warning"><strong>Database Setup Required:</strong> The packages table does not exist. <a href="setup_tables.php" class="btn btn-sm btn-primary">Run Setup Script</a> or <a href="../config/content_management.sql" download>Download SQL File</a> to create it manually.</div>';
    } else {
        throw $e;
    }
}

include 'includes/header.php';
?>

<?php if (isset($message)): ?>
    <?php echo $message; ?>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Package updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="data-table">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Manage Packages</h5>
                <span class="text-muted small">Packages displayed on the frontend pricing section</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 dataTable" id="packagesTable">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $pkg): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($pkg['title']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($pkg['description']); ?></small>
                            </td>
                            <td>
                                <?php if ($pkg['price'] > 0): ?>
                                    <strong><?php echo $pkg['currency']; ?> <?php echo number_format($pkg['price']); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">Custom</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $pkg['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $pkg['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($pkg['is_featured']): ?>
                                    <span class="badge bg-warning"><i class="bi bi-star-fill"></i> Featured</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $pkg['id']; ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $pkg['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Package: <?php echo htmlspecialchars($pkg['title']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                            <input type="hidden" name="update_package" value="1">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Package Title</label>
                                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($pkg['title']); ?>" required>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Price</label>
                                                    <input type="number" class="form-control" name="price" value="<?php echo $pkg['price']; ?>" step="0.01" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Currency</label>
                                                    <input type="text" class="form-control" name="currency" value="<?php echo htmlspecialchars($pkg['currency']); ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="2" required><?php echo htmlspecialchars($pkg['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Features (one per line)</label>
                                                <textarea class="form-control" name="features" rows="6" required><?php echo htmlspecialchars(implode("\n", $pkg['features'])); ?></textarea>
                                                <small class="text-muted">Enter each feature on a new line</small>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Button Text</label>
                                                    <input type="text" class="form-control" name="button_text" value="<?php echo htmlspecialchars($pkg['button_text']); ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured<?php echo $pkg['id']; ?>" <?php echo $pkg['is_featured'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_featured<?php echo $pkg['id']; ?>">
                                                        Mark as Featured (Recommended badge)
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active<?php echo $pkg['id']; ?>" <?php echo $pkg['is_active'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_active<?php echo $pkg['id']; ?>">
                                                        Active (Show on frontend)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
