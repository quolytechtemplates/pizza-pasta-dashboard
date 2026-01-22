<?php
require_once 'config/database.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = sanitize($_POST['role']);
    
    if ($role == 'admin') {
        // Admin login: username + email + password
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id, password, full_name FROM users WHERE username = ? AND email = ? AND role = 'admin' AND is_active = TRUE");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['full_name'] = $user['full_name'];
                logActivity($user['id'], 'Login', 'Admin logged in');
                redirectToDashboard();
            } else {
                $error = 'Fjalëkalimi i gabuar!';
            }
        } else {
            $error = 'Kredencialet e gabuara!';
        }
        $stmt->close();
        
    } elseif ($role == 'teacher') {
        // Teacher login: email + password
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id, password, full_name FROM users WHERE email = ? AND role = 'teacher' AND is_active = TRUE");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'teacher';
                $_SESSION['full_name'] = $user['full_name'];
                logActivity($user['id'], 'Login', 'Teacher logged in');
                redirectToDashboard();
            } else {
                $error = 'Fjalëkalimi i gabuar!';
            }
        } else {
            $error = 'Email-i i gabuar!';
        }
        $stmt->close();
        
    } elseif ($role == 'parent') {
        // Parent login: email + password + child's full name
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $child_name = sanitize($_POST['child_name']);
        
        $stmt = $conn->prepare("
            SELECT u.id, u.password, u.full_name 
            FROM users u
            INNER JOIN parents p ON u.id = p.user_id
            INNER JOIN parent_student ps ON p.id = ps.parent_id
            INNER JOIN students s ON ps.student_id = s.id
            INNER JOIN users child ON s.user_id = child.id
            WHERE u.email = ? AND u.role = 'parent' AND child.full_name = ? AND u.is_active = TRUE
            LIMIT 1
        ");
        $stmt->bind_param("ss", $email, $child_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'parent';
                $_SESSION['full_name'] = $user['full_name'];
                logActivity($user['id'], 'Login', 'Parent logged in');
                redirectToDashboard();
            } else {
                $error = 'Fjalëkalimi i gabuar!';
            }
        } else {
            $error = 'Kredencialet e gabuara ose emri i fëmijës nuk përputhet!';
        }
        $stmt->close();
        
    } elseif ($role == 'student') {
        // Student login: full name + student ID (hashed)
        $full_name = sanitize($_POST['full_name']);
        $student_id = $_POST['student_id'];
        
        $stmt = $conn->prepare("
            SELECT u.id, u.full_name, s.student_id 
            FROM users u
            INNER JOIN students s ON u.id = s.user_id
            WHERE u.full_name = ? AND u.role = 'student' AND u.is_active = TRUE
        ");
        $stmt->bind_param("s", $full_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Verify student ID (comparing hashed version)
            if (password_verify($student_id, password_hash($user['student_id'], PASSWORD_DEFAULT)) || $student_id == $user['student_id']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'student';
                $_SESSION['full_name'] = $user['full_name'];
                
                // Get student record ID
                $stmt2 = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
                $stmt2->bind_param("i", $user['id']);
                $stmt2->execute();
                $student_result = $stmt2->get_result();
                $student = $student_result->fetch_assoc();
                $_SESSION['student_id'] = $student['id'];
                $stmt2->close();
                
                logActivity($user['id'], 'Login', 'Student logged in');
                redirectToDashboard();
            } else {
                $error = 'ID-ja e studentit e gabuar!';
            }
        } else {
            $error = 'Emri i plotë i gabuar!';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kyçu - Sistemi i Menaxhimit Shkollor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 450px;
            margin: auto;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border: none;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        .role-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .role-btn:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .role-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        .role-btn i {
            font-size: 24px;
            display: block;
            margin-bottom: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .login-fields {
            display: none;
        }
        .login-fields.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card login-card">
                <div class="login-header">
                    <h2 class="mb-0"><i class="ti ti-school"></i> Sistemi Shkollor</h2>
                    <p class="mb-0 mt-2">Zgjidhni rolin tuaj për t'u kyçur</p>
                </div>
                <div class="login-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="ti ti-alert-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="ti ti-check"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="loginForm">
                        <div class="role-selector">
                            <div class="role-btn" data-role="admin">
                                <i class="ti ti-shield-check"></i>
                                <small>Admin</small>
                            </div>
                            <div class="role-btn" data-role="teacher">
                                <i class="ti ti-book"></i>
                                <small>Mësues</small>
                            </div>
                            <div class="role-btn" data-role="student">
                                <i class="ti ti-school"></i>
                                <small>Student</small>
                            </div>
                            <div class="role-btn" data-role="parent">
                                <i class="ti ti-users"></i>
                                <small>Prind</small>
                            </div>
                        </div>
                        
                        <input type="hidden" name="role" id="selectedRole" value="">
                        
                        <!-- Admin Login Fields -->
                        <div class="login-fields" id="admin-fields">
                            <div class="form-group">
                                <label><i class="ti ti-user"></i> Emri i përdoruesit</label>
                                <input type="text" name="username" class="form-control" placeholder="Shkruani emrin e përdoruesit">
                            </div>
                            <div class="form-group">
                                <label><i class="ti ti-mail"></i> Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Shkruani email-in">
                            </div>
                            <div class="form-group">
                                <label><i class="ti ti-lock"></i> Fjalëkalimi</label>
                                <input type="password" name="password" class="form-control" placeholder="Shkruani fjalëkalimin">
                            </div>
                        </div>
                        
                        <!-- Teacher Login Fields -->
                        <div class="login-fields" id="teacher-fields">
                            <div class="form-group">
                                <label><i class="ti ti-mail"></i> Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Shkruani email-in tuaj">
                            </div>
                            <div class="form-group">
                                <label><i class="ti ti-lock"></i> Fjalëkalimi</label>
                                <input type="password" name="password" class="form-control" placeholder="Shkruani fjalëkalimin">
                            </div>
                        </div>
                        
                        <!-- Student Login Fields -->
                        <div class="login-fields" id="student-fields">
                            <div class="form-group">
                                <label><i class="ti ti-user"></i> Emri i plotë</label>
                                <input type="text" name="full_name" class="form-control" placeholder="Emri dhe mbiemri">
                            </div>
                            <div class="form-group">
                                <label><i class="ti ti-id"></i> ID e Studentit</label>
                                <input type="text" name="student_id" class="form-control" placeholder="Shkruani ID-në tuaj">
                            </div>
                        </div>
                        
                        <!-- Parent Login Fields -->
                        <div class="login-fields" id="parent-fields">
                            <div class="form-group">
                                <label><i class="ti ti-mail"></i> Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Shkruani email-in tuaj">
                            </div>
                            <div class="form-group">
                                <label><i class="ti ti-lock"></i> Fjalëkalimi</label>
                                <input type="password" name="password" class="form-control" placeholder="Shkruani fjalëkalimin">
                            </div>
                            <div class="form-group">
                                <label><i class="ti ti-user-check"></i> Emri i plotë i fëmijës</label>
                                <input type="text" name="child_name" class="form-control" placeholder="Emri dhe mbiemri i fëmijës">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-login" id="loginBtn" disabled>
                            <i class="ti ti-login"></i> Kyçu
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-white">© 2025 School Management System - Developed by QUOLYTECH</small>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleBtns = document.querySelectorAll('.role-btn');
            const loginFields = document.querySelectorAll('.login-fields');
            const selectedRoleInput = document.getElementById('selectedRole');
            const loginBtn = document.getElementById('loginBtn');
            
            roleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    roleBtns.forEach(b => b.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get selected role
                    const role = this.getAttribute('data-role');
                    selectedRoleInput.value = role;
                    
                    // Hide all login fields
                    loginFields.forEach(field => field.classList.remove('active'));
                    
                    // Show selected role fields
                    document.getElementById(role + '-fields').classList.add('active');
                    
                    // Enable login button
                    loginBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
