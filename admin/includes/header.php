<?php
/**
 * Admin Header Include
 * Mobile-responsive sidebar navigation
 */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Bossify Academy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4B2C5E;
            --accent-color: #D4AF37;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: white;
            padding: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .sidebar-nav {
            padding: 10px 0;
        }
        
        .sidebar-nav a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav a i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(212, 175, 55, 0.15);
            border-left-color: var(--accent-color);
            color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        /* Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--accent-color);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Tables */
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .data-table .border-bottom {
            border-bottom: 2px solid #f0f0f0 !important;
        }
        
        .data-table .border-top {
            border-top: 2px solid #f0f0f0 !important;
        }
        
        /* Button improvements */
        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Form improvements */
        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(75, 44, 94, 0.25);
        }
        
        /* Badge improvements */
        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            border-radius: 0.375rem;
        }
        
        /* Alert improvements */
        .alert {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Modal improvements */
        .modal-content {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-bottom: 2px solid #f0f0f0;
            border-radius: 0.75rem 0.75rem 0 0;
        }
        
        .modal-footer {
            border-top: 2px solid #f0f0f0;
            border-radius: 0 0 0.75rem 0.75rem;
        }
        
        /* Table improvements */
        .table {
            margin-bottom: 0;
        }
        
        /* Card/tile improvements */
        .stat-card .stat-label {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }
        
        /* Loading states */
        .loading {
            display: inline-block;
        }
        
        /* Better spacing */
        .mb-4 {
            margin-bottom: 2rem !important;
        }
        
        .mt-4 {
            margin-top: 2rem !important;
        }
        
        /* Improved filters section */
        .data-table .p-3 {
            background: #f8f9fa;
        }
        
        /* Better form labels */
        .form-label {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .table thead {
            background: #f8f9fa;
            color: var(--primary-color);
        }
        
        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--accent-color);
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color) 0%, #CD7F32 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #CD7F32 0%, var(--accent-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 8px;
                font-size: 1.2rem;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none;
            }
        }
        
        /* DataTables Custom Styling */
        .dataTables_wrapper {
            padding: 1rem;
        }
        
        .dataTables_wrapper .row {
            margin: 0;
        }
        
        .dataTables_wrapper .row > div {
            padding: 0.5rem;
        }
        
        .dataTables_filter {
            margin-bottom: 1rem;
            text-align: right;
        }
        
        .dataTables_filter label {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            margin: 0;
            font-weight: normal;
        }
        
        .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: auto;
            min-width: 200px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .dataTables_filter input:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(75, 44, 94, 0.25);
        }
        
        .dataTables_length {
            margin-bottom: 1rem;
        }
        
        .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            font-weight: normal;
        }
        
        .dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            margin: 0 0.5rem;
            background-color: white;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .dataTables_length select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(75, 44, 94, 0.25);
        }
        
        .dt-buttons {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .dt-buttons .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .dt-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .dataTables_info {
            padding-top: 0.75rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .dataTables_paginate {
            padding-top: 0.75rem;
            text-align: right;
        }
        
        .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
            color: var(--primary-color);
            background: white;
            transition: all 0.2s ease;
        }
        
        .dataTables_paginate .paginate_button:hover:not(.disabled) {
            background: var(--accent-color) !important;
            color: white !important;
            border-color: var(--accent-color) !important;
            transform: translateY(-2px);
        }
        
        .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
            font-weight: 600;
        }
        
        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* DataTable specific styling - override general table styles */
        .dataTable thead th {
            background-color: #f8f9fa !important;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid var(--accent-color);
            padding: 1rem 0.75rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .dataTable tbody td {
            padding: 0.75rem;
            vertical-align: middle;
        }
        
        .dataTable tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Responsive DataTables */
        @media (max-width: 768px) {
            .dataTables_wrapper {
                padding: 0.5rem;
            }
            
            .dataTables_filter,
            .dataTables_length {
                margin-bottom: 0.75rem;
            }
            
            .dataTables_filter {
                text-align: left;
            }
            
            .dataTables_filter input {
                width: 100%;
                min-width: auto;
            }
            
            .dt-buttons {
                flex-direction: column;
            }
            
            .dt-buttons .btn {
                width: 100%;
            }
            
            .dataTables_paginate {
                text-align: center;
            }
        }
        
        /* Hide default DataTables search when we have custom filters */
        .has-custom-filters .dataTables_filter {
            display: none;
        }
        
        /* Improve table container */
        .table-responsive {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        /* Better spacing for DataTables controls */
        .dataTables_wrapper .row:first-child {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
        }
        
        .dataTables_wrapper .row:last-child {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-shield-check"></i> Bossify Admin</h4>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="enrollments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'enrollments.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Enrollments
            </a>
            <a href="contacts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
                <i class="bi bi-envelope"></i> Contacts
            </a>
            <a href="newsletter.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? 'active' : ''; ?>">
                <i class="bi bi-mailbox"></i> Newsletter
            </a>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="bi bi-person-badge"></i> Users
            </a>
            <a href="logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
            <div class="user-info">
                <span class="text-muted">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></strong></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? $_SESSION['admin_username'], 0, 1)); ?>
                </div>
            </div>
        </div>
        
        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        </script>
