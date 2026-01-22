<?php
require_once '../config/database.php';
checkRole(['parent']);

$page_title = 'Dashboard Prindi';
$parent_user_id = $_SESSION['user_id'];

// Get parent ID
$parent_query = $conn->prepare("SELECT id FROM parents WHERE user_id = ?");
$parent_query->bind_param("i", $parent_user_id);
$parent_query->execute();
$parent_result = $parent_query->get_result();
$parent_data = $parent_result->fetch_assoc();
$parent_id = $parent_data['id'];
$parent_query->close();

// Get children
$children_query = $conn->prepare("
    SELECT 
        s.id as student_id,
        u.full_name,
        u.id as user_id,
        c.class_name,
        s.student_id as sid
    FROM parent_student ps
    JOIN students s ON ps.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE ps.parent_id = ?
");
$children_query->bind_param("i", $parent_id);
$children_query->execute();
$children_result = $children_query->get_result();
$children = $children_result->fetch_all(MYSQLI_ASSOC);
$children_query->close();

// Get statistics for all children
$stats = [];
foreach ($children as $child) {
    $student_id = $child['student_id'];
    
    $child_stats_query = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM absences WHERE student_id = ? AND is_excused = FALSE AND absence_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as unexcused_absences,
            (SELECT AVG(grade) FROM grades WHERE student_id = ? AND semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as avg_grade,
            (SELECT COUNT(*) FROM grades WHERE student_id = ? AND semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as total_grades
    ");
    $child_stats_query->bind_param("iii", $student_id, $student_id, $student_id);
    $child_stats_query->execute();
    $child_stats_result = $child_stats_query->get_result();
    $stats[$child['full_name']] = $child_stats_result->fetch_assoc();
    $child_stats_query->close();
}

// Get recent notifications for children
$notification_students = implode(',', array_column($children, 'user_id'));
if (!empty($notification_students)) {
    $recent_notifications_query = $conn->query("
        SELECT n.*, u.full_name as student_name
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        WHERE n.user_id IN ($notification_students)
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $recent_notifications = $recent_notifications_query->fetch_all(MYSQLI_ASSOC);
} else {
    $recent_notifications = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <h3>Mirësevini, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
                <p class="mb-0">Monitoroni përparimin e fëmijëve tuaj</p>
            </div>
        </div>
    </div>
</div>

<?php if (count($children) > 0): ?>
    <div class="row">
        <?php foreach ($children as $child): ?>
            <div class="col-lg-6 col-xl-4">
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(90deg, #667eea, #764ba2); color: white;">
                        <h5 class="mb-0"><?php echo htmlspecialchars($child['full_name']); ?></h5>
                        <small><?php echo htmlspecialchars($child['class_name'] ?? 'N/A'); ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="mb-2">
                                    <i class="ti ti-award" style="font-size: 24px; color: var(--primary-color);"></i>
                                </div>
                                <h4 class="mb-0"><?php echo $stats[$child['full_name']]['avg_grade'] ? number_format($stats[$child['full_name']]['avg_grade'], 2) : 'N/A'; ?></h4>
                                <small class="text-muted">Mesatarja</small>
                            </div>
                            <div class="col-4">
                                <div class="mb-2">
                                    <i class="ti ti-clipboard-check" style="font-size: 24px; color: #28a745;"></i>
                                </div>
                                <h4 class="mb-0"><?php echo $stats[$child['full_name']]['total_grades']; ?></h4>
                                <small class="text-muted">Nota</small>
                            </div>
                            <div class="col-4">
                                <div class="mb-2">
                                    <i class="ti ti-clipboard-x" style="font-size: 24px; color: #dc3545;"></i>
                                </div>
                                <h4 class="mb-0"><?php echo $stats[$child['full_name']]['unexcused_absences']; ?></h4>
                                <small class="text-muted">Mungesa</small>
                            </div>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="child_grades.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-award"></i> Shiko Notat
                            </a>
                            <a href="child_absences.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-sm btn-outline-danger">
                                <i class="ti ti-clipboard-x"></i> Shiko Mungesat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="row">
        <!-- Summary Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Krahasimi i Fëmijëve - Mesatarja e Notave</h4>
                    <canvas id="childrenComparisonChart" height="80"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Notifications -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Njoftimet e Fundit</h4>
                    <?php if (count($recent_notifications) > 0): ?>
                        <?php foreach ($recent_notifications as $notif): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-start">
                                    <div class="me-2">
                                        <i class="ti ti-bell" style="color: var(--primary-color);"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong class="d-block"><?php echo htmlspecialchars($notif['student_name']); ?></strong>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($notif['title']); ?></p>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Nuk ka njoftime</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Veprime të Shpejta</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="grades.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="ti ti-award"></i><br>
                                Shiko të gjitha Notat
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="absences.php" class="btn btn-outline-danger w-100 mb-2">
                                <i class="ti ti-clipboard-x"></i><br>
                                Shiko të gjitha Mungesat
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="schedule.php" class="btn btn-outline-info w-100 mb-2">
                                <i class="ti ti-calendar"></i><br>
                                Shiko Orarin
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="children.php" class="btn btn-outline-success w-100 mb-2">
                                <i class="ti ti-users"></i><br>
                                Menaxho Fëmijët
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-users" style="font-size: 64px; color: #ccc;"></i>
                    <h4 class="mt-3">Nuk keni fëmijë të regjistruar</h4>
                    <p class="text-muted">Kontaktoni administratorin për të shtuar fëmijët tuaj në sistem</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
if (count($children) > 0) {
    $child_names = json_encode(array_column($children, 'full_name'));
    $child_averages = [];
    foreach ($children as $child) {
        $avg = $stats[$child['full_name']]['avg_grade'];
        $child_averages[] = $avg ? floatval($avg) : 0;
    }
    $child_averages_json = json_encode($child_averages);
    
    $extra_js = "
    <script>
    const ctx = document.getElementById('childrenComparisonChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: $child_names,
            datasets: [{
                label: 'Mesatarja',
                data: $child_averages_json,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
    ";
}

include '../includes/footer.php';
?>
