<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$notification_count = getUnreadNotificationCount($_SESSION['user_id']);

// Get user notifications
$notifications_query = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifications_query->bind_param("i", $_SESSION['user_id']);
$notifications_query->execute();
$notifications_result = $notifications_query->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
$notifications_query->close();
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Sistemi Shkollor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .left-sidebar {
            width: var(--sidebar-width);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .brand-logo {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .brand-logo h4 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-item {
            list-style: none;
        }
        
        .sidebar-link {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: #5f6368;
            text-decoration: none;
            transition: all 0.3s;
            gap: 12px;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .sidebar-link i {
            font-size: 20px;
        }
        
        .nav-small-cap {
            padding: 15px 20px 8px;
            font-size: 11px;
            font-weight: 600;
            color: #8f959e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .body-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
        }
        
        .app-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-link {
            color: #5f6368;
            text-decoration: none;
            position: relative;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #f5f7fa;
        }
        
        .nav-link i {
            font-size: 22px;
        }
        
        .notification {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background: #dc3545;
            border-radius: 50%;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 10px;
            min-width: 280px;
        }
        
        .dropdown-item {
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background: #f5f7fa;
        }
        
        .rounded-circle {
            border: 2px solid var(--primary-color);
        }
        
        .container-fluid {
            padding: 30px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 18px;
        }
        
        .card-subtitle {
            color: #8f959e;
            font-size: 13px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            color: #5f6368;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 991px) {
            .left-sidebar {
                transform: translateX(-100%);
            }
            
            .left-sidebar.show {
                transform: translateX(0);
            }
            
            .body-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
<body>
    <div class="page-wrapper">
        <!-- Sidebar Start -->
        <aside class="left-sidebar">
            <div class="brand-logo">
                <h4><i class="ti ti-school"></i> Shkolla</h4>
                <div class="close-btn d-xl-none d-block" id="sidebarClose">
                    <i class="ti ti-x"></i>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul id="sidebarnav" style="padding: 0;">
                    <?php
                    $role = $_SESSION['role'];
                    $current_page = basename($_SERVER['PHP_SELF']);
                    
                    // Define menu items based on role
                    $menu_items = [];
                    
                    if ($role == 'admin') {
                        $menu_items = [
                            ['icon' => 'ti-dashboard', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                            ['icon' => 'ti-users', 'text' => 'Përdoruesit', 'url' => 'users.php'],
                            ['icon' => 'ti-school', 'text' => 'Studentët', 'url' => 'students.php'],
                            ['icon' => 'ti-book', 'text' => 'Mësuesit', 'url' => 'teachers.php'],
                            ['icon' => 'ti-user-check', 'text' => 'Prindërit', 'url' => 'parents.php'],
                            ['icon' => 'ti-building', 'text' => 'Klasat', 'url' => 'classes.php'],
                            ['icon' => 'ti-books', 'text' => 'Lëndët', 'url' => 'subjects.php'],
                            ['icon' => 'ti-calendar', 'text' => 'Orari', 'url' => 'schedule.php'],
                            ['icon' => 'ti-calendar-event', 'text' => 'Viti Akademik', 'url' => 'academic_year.php'],
                            ['icon' => 'ti-file-text', 'text' => 'Raporte', 'url' => 'reports.php'],
                        ];
                    } elseif ($role == 'teacher') {
                        $menu_items = [
                            ['icon' => 'ti-dashboard', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                            ['icon' => 'ti-users', 'text' => 'Klasat e mia', 'url' => 'my_classes.php'],
                            ['icon' => 'ti-calendar', 'text' => 'Orari', 'url' => 'schedule.php'],
                            ['icon' => 'ti-clipboard-check', 'text' => 'Mungesa', 'url' => 'absences.php'],
                            ['icon' => 'ti-award', 'text' => 'Nota', 'url' => 'grades.php'],
                            ['icon' => 'ti-users', 'text' => 'Studentët', 'url' => 'students.php'],
                        ];
                    } elseif ($role == 'student') {
                        $menu_items = [
                            ['icon' => 'ti-dashboard', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                            ['icon' => 'ti-award', 'text' => 'Notat e mia', 'url' => 'grades.php'],
                            ['icon' => 'ti-clipboard-x', 'text' => 'Mungesat', 'url' => 'absences.php'],
                            ['icon' => 'ti-calendar', 'text' => 'Orari', 'url' => 'schedule.php'],
                            ['icon' => 'ti-calendar-event', 'text' => 'Kalendari', 'url' => 'calendar.php'],
                        ];
                    } elseif ($role == 'parent') {
                        $menu_items = [
                            ['icon' => 'ti-dashboard', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
                            ['icon' => 'ti-users', 'text' => 'Fëmijët e mi', 'url' => 'children.php'],
                            ['icon' => 'ti-award', 'text' => 'Nota', 'url' => 'grades.php'],
                            ['icon' => 'ti-clipboard-x', 'text' => 'Mungesat', 'url' => 'absences.php'],
                            ['icon' => 'ti-calendar', 'text' => 'Orari', 'url' => 'schedule.php'],
                        ];
                    }
                    
                    foreach ($menu_items as $item):
                        $active = ($current_page == $item['url']) ? 'active' : '';
                    ?>
                        <li class="sidebar-item">
                            <a class="sidebar-link <?php echo $active; ?>" href="<?php echo $item['url']; ?>">
                                <i class="ti <?php echo $item['icon']; ?>"></i>
                                <span class="hide-menu"><?php echo $item['text']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    
                    <li class="nav-small-cap">
                        <span class="hide-menu">Llogaria</span>
                    </li>
                    
                    <li class="sidebar-item">
                        <a class="sidebar-link" href="../logout.php">
                            <i class="ti ti-logout"></i>
                            <span class="hide-menu">Dil</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <!-- Sidebar End -->
        
        <!-- Main wrapper -->
        <div class="body-wrapper">
            <!-- Header Start -->
            <header class="app-header">
                <div class="navbar-nav">
                    <button class="btn d-xl-none" id="sidebarToggle">
                        <i class="ti ti-menu-2"></i>
                    </button>
                    <h5 class="mb-0 d-none d-md-block"><?php echo $page_title ?? 'Dashboard'; ?></h5>
                </div>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="notificationDropdown" data-bs-toggle="dropdown">
                            <i class="ti ti-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <div class="notification"></div>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                            <h6 class="px-3 py-2 mb-0">Njoftimet (<?php echo $notification_count; ?>)</h6>
                            <div class="dropdown-divider"></div>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <a href="#" class="dropdown-item">
                                        <div class="d-flex align-items-start">
                                            <i class="ti ti-bell me-2 mt-1"></i>
                                            <div>
                                                <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-3 py-2 text-center text-muted">
                                    <p class="mb-0">Nuk ka njoftime</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="profileDropdown" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=667eea&color=fff" 
                                 alt="Profile" width="35" height="35" class="rounded-circle">
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <div class="px-3 py-2">
                                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                                <p class="mb-0 small text-muted text-capitalize"><?php echo $_SESSION['role']; ?></p>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="profile.php" class="dropdown-item">
                                <i class="ti ti-user me-2"></i> Profili
                            </a>
                            <a href="../logout.php" class="dropdown-item text-danger">
                                <i class="ti ti-logout me-2"></i> Dil
                            </a>
                        </div>
                    </li>
                </ul>
            </header>
            <!-- Header End -->
            
            <div class="container-fluid">
