<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$page_title = 'Website Settings';

$db = getDB();

$message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = :value, updated_by = :admin_id WHERE setting_key = :key");
        $stmt->execute([
            ':value' => $value,
            ':key' => $key,
            ':admin_id' => $_SESSION['admin_id']
        ]);
    }
    $message = 'Settings updated successfully';
}

// Get all settings - check if table exists
$settingsArray = [];
try {
    $stmt = $db->query("SELECT * FROM site_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll();
    foreach ($settings as $s) {
        $settingsArray[$s['setting_key']] = $s;
    }
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') {
        // Table doesn't exist - use defaults
        $settingsArray = [
            'site_name' => ['setting_value' => 'Bossify Academy'],
            'site_email' => ['setting_value' => 'info@bossifyacademy.com'],
            'site_phone' => ['setting_value' => '+255 XXX XXX XXX'],
            'site_address' => ['setting_value' => 'Dar es Salaam, Tanzania'],
            'hero_title' => ['setting_value' => 'Bossify Academy: Empowering the Next Generation of Women Leaders'],
            'hero_subtitle' => ['setting_value' => 'Cultivate excellence in leadership, communication, and financial acumen within an empowering, Afrocentric learning environment.'],
            'pricing_section_title' => ['setting_value' => 'Investment in Excellence'],
            'pricing_section_subtitle' => ['setting_value' => 'Select the program option that best aligns with your professional development objectives']
        ];
        $dbError = '<div class="alert alert-warning"><strong>Database Setup Required:</strong> The site_settings table does not exist. <a href="setup_tables.php" class="btn btn-sm btn-primary">Run Setup Script</a> or <a href="../config/content_management.sql" download>Download SQL File</a> to create it manually.</div>';
    } else {
        throw $e;
    }
}

include 'includes/header.php';
?>

<?php if (isset($dbError)): ?>
    <?php echo $dbError; ?>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="update_settings" value="1">
    
    <div class="row g-4">
        <!-- General Settings -->
        <div class="col-lg-6">
            <div class="data-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> General Settings</h5>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="settings[site_name]" value="<?php echo htmlspecialchars($settingsArray['site_name']['setting_value'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control" name="settings[site_email]" value="<?php echo htmlspecialchars($settingsArray['site_email']['setting_value'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" name="settings[site_phone]" value="<?php echo htmlspecialchars($settingsArray['site_phone']['setting_value'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="settings[site_address]" value="<?php echo htmlspecialchars($settingsArray['site_address']['setting_value'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hero Section -->
        <div class="col-lg-6">
            <div class="data-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0"><i class="bi bi-image"></i> Hero Section</h5>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label">Hero Title</label>
                        <input type="text" class="form-control" name="settings[hero_title]" value="<?php echo htmlspecialchars($settingsArray['hero_title']['setting_value'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hero Subtitle</label>
                        <textarea class="form-control" name="settings[hero_subtitle]" rows="3"><?php echo htmlspecialchars($settingsArray['hero_subtitle']['setting_value'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pricing Section -->
        <div class="col-lg-6">
            <div class="data-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0"><i class="bi bi-currency-dollar"></i> Pricing Section</h5>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="settings[pricing_section_title]" value="<?php echo htmlspecialchars($settingsArray['pricing_section_title']['setting_value'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Section Subtitle</label>
                        <textarea class="form-control" name="settings[pricing_section_subtitle]" rows="2"><?php echo htmlspecialchars($settingsArray['pricing_section_subtitle']['setting_value'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> Save All Settings
            </button>
        </div>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
