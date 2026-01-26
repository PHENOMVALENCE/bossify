<?php
/**
 * Database Tables Setup Script
 * Run this once to create missing tables
 */

require_once __DIR__ . '/../config/database.php';

$db = getDB();
$errors = [];
$success = [];

// Read and execute SQL file
$sqlFile = __DIR__ . '/../config/content_management.sql';
if (file_exists($sqlFile)) {
    $sql = file_get_contents($sqlFile);
    
    // Remove USE statement if present (we're already connected)
    $sql = preg_replace('/USE\s+\w+\s*;/i', '', $sql);
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                if (isset($matches[1])) {
                    $success[] = "Table '{$matches[1]}' created successfully";
                }
            }
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
} else {
    $errors[] = "SQL file not found: {$sqlFile}";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Bossify Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h2>Database Tables Setup</h2>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <h5>Success:</h5>
                <ul class="mb-0">
                    <?php foreach ($success as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5>Errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($errors) && !empty($success)): ?>
            <div class="alert alert-info">
                <strong>Setup Complete!</strong> You can now access the admin dashboard.
                <a href="dashboard.php" class="btn btn-primary mt-2">Go to Dashboard</a>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>
