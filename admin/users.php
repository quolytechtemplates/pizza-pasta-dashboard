<?php
require_once '../config/database.php';
checkRole(['admin']);

$page_title = 'Menaxhimi i Përdoruesve';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'create_user') {
            $role = sanitize($_POST['role']);
            $full_name = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $username = isset($_POST['username']) ? sanitize($_POST['username']) : null;
            
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $password, $role, $full_name);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                
                // Handle role-specific data
                if ($role == 'student') {
                    $student_id = 'STU' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
                    $phone = sanitize($_POST['phone'] ?? '');
                    $address = sanitize($_POST['address'] ?? '');
                    
                    $stmt2 = $conn->prepare("INSERT INTO students (user_id, student_id, class_id, date_of_birth, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt2->bind_param("ississ", $user_id, $student_id, $class_id, $dob, $phone, $address);
                    $stmt2->execute();
                    $stmt2->close();
                    
                } elseif ($role == 'teacher') {
                    $specialization = sanitize($_POST['specialization'] ?? '');
                    $phone = sanitize($_POST['phone'] ?? '');
                    $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d');
                    
                    $stmt2 = $conn->prepare("INSERT INTO teachers (user_id, specialization, phone, hire_date) VALUES (?, ?, ?, ?)");
                    $stmt2->bind_param("isss", $user_id, $specialization, $phone, $hire_date);
                    $stmt2->execute();
                    $stmt2->close();
                    
                } elseif ($role == 'parent') {
                    $phone = sanitize($_POST['phone'] ?? '');
                    $address = sanitize($_POST['address'] ?? '');
                    
                    $stmt2 = $conn->prepare("INSERT INTO parents (user_id, phone, address) VALUES (?, ?, ?)");
                    $stmt2->bind_param("iss", $user_id, $phone, $address);
                    $stmt2->execute();
                    $parent_id = $stmt2->insert_id;
                    $stmt2->close();
                    
                    // Link children if provided
                    if (!empty($_POST['children']) && is_array($_POST['children'])) {
                        foreach ($_POST['children'] as $student_id) {
                            $stmt3 = $conn->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
                            $stmt3->bind_param("ii", $parent_id, $student_id);
                            $stmt3->execute();
                            $stmt3->close();
                        }
                    }
                }
                
                logActivity($_SESSION['user_id'], 'Create User', "Created $role: $full_name");
                $message = "Përdoruesi u krijua me sukses!";
            } else {
                $error = "Gabim në krijimin e përdoruesit: " . $stmt->error;
            }
            $stmt->close();
            
        } elseif ($action == 'delete_user') {
            $user_id = intval($_POST['user_id']);
            
            // Get user info before deleting
            $info_stmt = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
            $info_stmt->bind_param("i", $user_id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result();
            $user_info = $info_result->fetch_assoc();
            $info_stmt->close();
            
            // Delete user (cascade will handle related tables)
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Delete User', "Deleted {$user_info['role']}: {$user_info['full_name']}");
                $message = "Përdoruesi u fshi me sukses!";
            } else {
                $error = "Gabim në fshirjen e përdoruesit";
            }
            $stmt->close();
            
        } elseif ($action == 'toggle_status') {
            $user_id = intval($_POST['user_id']);
            $new_status = intval($_POST['new_status']);
            
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $user_id);
            
            if ($stmt->execute()) {
                $status_text = $new_status ? 'aktivizua' : 'dezaktivizua';
                $message = "Përdoruesi u $status_text me sukses!";
            } else {
                $error = "Gabim në ndryshimin e statusit";
            }
            $stmt->close();
        }
    }
}

// Get filter parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where_clauses = ["role != 'admin' OR id = {$_SESSION['user_id']}"]; // Hide other admins
$params = [];
$types = '';

if ($role_filter) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($search) {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

$where_sql = implode(' AND ', $where_clauses);

// Get users
$users_query = "SELECT * FROM users WHERE $where_sql ORDER BY created_at DESC";
$stmt = $conn->prepare($users_query);

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$users_result = $stmt->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all classes for dropdowns
$classes_query = "SELECT id, class_name FROM classes ORDER BY class_name";
$classes = $conn->query($classes_query)->fetch_all(MYSQLI_ASSOC);

// Get all students for parent assignment
$students_query = "SELECT s.id, u.full_name, c.class_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id ORDER BY u.full_name";
$students = $conn->query($students_query)->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check"></i> <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Lista e Përdoruesve</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="ti ti-plus"></i> Shto Përdorues
                    </button>
                </div>
                
                <!-- Filters -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <select name="role" class="form-select" onchange="this.form.submit()">
                            <option value="">Të gjithë rolet</option>
                            <option value="student" <?php echo $role_filter == 'student' ? 'selected' : ''; ?>>Studentë</option>
                            <option value="teacher" <?php echo $role_filter == 'teacher' ? 'selected' : ''; ?>>Mësues</option>
                            <option value="parent" <?php echo $role_filter == 'parent' ? 'selected' : ''; ?>>Prindër</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Kërko sipas emrit ose email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search"></i> Kërko</button>
                    </div>
                </form>
                
                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Emri i plotë</th>
                                <th>Email</th>
                                <th>Roli</th>
                                <th>Krijuar më</th>
                                <th>Statusi</th>
                                <th>Veprimet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] == 'admin' ? 'danger' : 
                                            ($user['role'] == 'teacher' ? 'primary' : 
                                            ($user['role'] == 'student' ? 'success' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Jo-aktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>" 
                                                        onclick="return confirm('Jeni i sigurt?')">
                                                    <i class="ti ti-<?php echo $user['is_active'] ? 'x' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Jeni i sigurt që doni ta fshini këtë përdorues?')">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (count($users) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nuk u gjet asnjë përdorues</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shto Përdorues të Ri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createUserForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="mb-3">
                        <label class="form-label">Roli *</label>
                        <select name="role" id="userRole" class="form-select" required>
                            <option value="">Zgjidhni rolin</option>
                            <option value="student">Student</option>
                            <option value="teacher">Mësues</option>
                            <option value="parent">Prind</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Emri i plotë *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3" id="usernameField" style="display: none;">
                            <label class="form-label">Emri i përdoruesit</label>
                            <input type="text" name="username" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fjalëkalimi *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    
                    <!-- Student-specific fields -->
                    <div id="studentFields" style="display: none;">
                        <h6 class="mb-3">Informacioni i Studentit</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Klasa</label>
                                <select name="class_id" class="form-select">
                                    <option value="">Zgjidhni klasën</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data e lindjes</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefoni</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Adresa</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Teacher-specific fields -->
                    <div id="teacherFields" style="display: none;">
                        <h6 class="mb-3">Informacioni i Mësuesit</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specializimi</label>
                                <input type="text" name="specialization" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefoni</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data e punësimit</label>
                            <input type="date" name="hire_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <!-- Parent-specific fields -->
                    <div id="parentFields" style="display: none;">
                        <h6 class="mb-3">Informacioni i Prindit</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefoni</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Adresa</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fëmijët (përzgjidh një ose më shumë)</label>
                            <select name="children[]" class="form-select" multiple size="5">
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name']) . ' - ' . htmlspecialchars($student['class_name'] ?? 'N/A'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Mbaj Ctrl (Windows) ose Cmd (Mac) për të zgjedhur më shumë</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulo</button>
                    <button type="submit" class="btn btn-primary">Krijo Përdoruesin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = "
<script>
document.getElementById('userRole').addEventListener('change', function() {
    const role = this.value;
    
    // Hide all role-specific fields
    document.getElementById('studentFields').style.display = 'none';
    document.getElementById('teacherFields').style.display = 'none';
    document.getElementById('parentFields').style.display = 'none';
    document.getElementById('usernameField').style.display = 'none';
    
    // Show relevant fields based on role
    if (role === 'student') {
        document.getElementById('studentFields').style.display = 'block';
    } else if (role === 'teacher') {
        document.getElementById('teacherFields').style.display = 'block';
    } else if (role === 'parent') {
        document.getElementById('parentFields').style.display = 'block';
    } else if (role === 'admin') {
        document.getElementById('usernameField').style.display = 'block';
    }
});
</script>
";

include '../includes/footer.php';
?>
