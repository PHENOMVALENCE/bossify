<?php
/**
 * Admin Dashboard - Login Page
 * Bossify Academy
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, password_hash, full_name, role FROM admin_users WHERE username = :username AND is_active = 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Login error. Please try again.';
            error_log("Admin login error: " . $e->getMessage());
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bossify Academy Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top left, #D4AF37 0%, #4B2C5E 40%, #1A1023 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .auth-wrapper {
            background: rgba(10, 6, 20, 0.88);
            border-radius: 24px;
            box-shadow:
                0 24px 80px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(255, 255, 255, 0.04);
            max-width: 960px;
            width: 100%;
            color: #f8f9fa;
            overflow: hidden;
        }
        .auth-row {
            display: flex;
            flex-wrap: wrap;
        }
        .auth-info {
            flex: 1 1 50%;
            padding: 40px 40px 40px 48px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            background: radial-gradient(circle at top left, rgba(212, 175, 55, 0.18) 0%, transparent 55%);
        }
        .auth-info h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .auth-info p.lead {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 24px;
        }
        .auth-meta-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
        }
        .auth-meta-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9rem;
            margin-bottom: 12px;
            opacity: 0.95;
        }
        .auth-meta-list i {
            color: #D4AF37;
            margin-top: 2px;
        }
        .auth-meta-footer {
            font-size: 0.85rem;
            opacity: 0.8;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 16px;
            margin-top: 8px;
        }
        .auth-meta-footer span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .auth-meta-footer i {
            font-size: 0.95rem;
        }
        .login-panel {
            flex: 1 1 50%;
            padding: 40px 40px 40px 40px;
            background: #fff;
            color: #212529;
        }
        .login-header {
            margin-bottom: 24px;
        }
        .login-header h3 {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        .login-header p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        .login-subnote {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 8px;
        }
        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: #f8f9fa;
            margin-bottom: 16px;
        }
        .brand-pill i {
            color: #D4AF37;
        }
        .btn-login {
            background: linear-gradient(135deg, #D4AF37 0%, #CD7F32 100%);
            border: none;
        }
        .btn-login:hover {
            filter: brightness(1.05);
        }
        .back-link {
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .auth-info {
                flex: 1 1 100%;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                padding: 28px 24px 24px;
            }
            .login-panel {
                flex: 1 1 100%;
                padding: 28px 24px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-row">
            <section class="auth-info">
                <div class="brand-pill">
                    <i class="bi bi-stars"></i>
                    <span>Bossify Academy Admin Portal</span>
                </div>
                <h1>Manage your cohorts, enrollments & content in one place.</h1>
                <p class="lead">
                    This secure admin area allows the Bossify team to review applications, respond
                    to messages, update program packages, and keep the website content in sync with
                    current cohorts and events.
                </p>
                <ul class="auth-meta-list">
                    <li>
                        <i class="bi bi-people"></i>
                        <span><strong>Enrollment management</strong> – review applications, track program choices,
                        and update statuses from a single dashboard.</span>
                    </li>
                    <li>
                        <i class="bi bi-envelope-paper"></i>
                        <span><strong>Contact & newsletter inbox</strong> – read messages, reply via email, and
                        manage newsletter subscribers.</span>
                    </li>
                    <li>
                        <i class="bi bi-gear"></i>
                        <span><strong>Site configuration</strong> – maintain key settings such as course packages,
                        pricing and hero content.</span>
                    </li>
                </ul>
                <div class="auth-meta-footer">
                    <span>
                        <i class="bi bi-shield-lock"></i>
                        Internal use only. If you are not part of the Bossify team, please return to the main site.
                    </span>
                </div>
            </section>
            <section class="login-panel">
                <div class="login-header">
                    <h3><i class="bi bi-shield-lock"></i> Admin Login</h3>
                    <p>Sign in with your Bossify admin credentials to access the dashboard.</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-2">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <p class="login-subnote">
                        Having trouble logging in? Contact the site administrator for assistance.
                    </p>
                    <button type="submit" class="btn btn-primary w-100 btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                <div class="mt-3 text-center back-link">
                    <a href="../index.html" class="text-decoration-none">
                        <i class="bi bi-arrow-left-short"></i> Back to Bossify website
                    </a>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
