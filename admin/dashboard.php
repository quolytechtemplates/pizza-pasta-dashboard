<?php
require_once '../config/database.php';
checkRole(['admin']);

$page_title = 'Dashboard Administratori';

// Get statistics
$stats = [];

// Total users by role
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = TRUE) as total_students,
        (SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = TRUE) as total_teachers,
        (SELECT COUNT(*) FROM users WHERE role = 'parent' AND is_active = TRUE) as total_parents,
        (SELECT COUNT(*) FROM classes WHERE academic_year_id = (SELECT id FROM academic_years WHERE is_active = TRUE LIMIT 1)) as total_classes,
        (SELECT COUNT(*) FROM subjects) as total_subjects,
        (SELECT COUNT(*) FROM absences WHERE absence_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_absences
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent activities
$activities_query = "
    SELECT al.*, u.full_name 
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
";
$activities_result = $conn->query($activities_query);
$activities = $activities_result->fetch_all(MYSQLI_ASSOC);

// Get grade distribution data for chart
$grade_distribution_query = "
    SELECT 
        FLOOR(grade) as grade_value,
        COUNT(*) as count
    FROM grades
    WHERE semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)
    GROUP BY FLOOR(grade)
    ORDER BY grade_value
";
$grade_dist_result = $conn->query($grade_distribution_query);
$grade_distribution = $grade_dist_result->fetch_all(MYSQLI_ASSOC);

// Get absence trends (last 7 days)
$absence_trends_query = "
    SELECT 
        DATE(absence_date) as date,
        COUNT(*) as count
    FROM absences
    WHERE absence_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(absence_date)
    ORDER BY date
";
$absence_trends_result = $conn->query($absence_trends_query);
$absence_trends = $absence_trends_result->fetch_all(MYSQLI_ASSOC);

// Get top performing students
$top_students_query = "
    SELECT 
        u.full_name,
        c.class_name,
        AVG(g.grade) as avg_grade,
        COUNT(g.id) as total_grades
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON s.class_id = c.id
    WHERE g.semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)
    GROUP BY s.id, u.full_name, c.class_name
    HAVING total_grades >= 3
    ORDER BY avg_grade DESC
    LIMIT 5
";
$top_students_result = $conn->query($top_students_query);
$top_students = $top_students_result->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #667eea, #764ba2); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                        <i class="ti ti-users text-white" style="font-size: 28px;"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                        <p class="mb-0 text-muted">Studentë</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #f093fb, #f5576c); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                        <i class="ti ti-book text-white" style="font-size: 28px;"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0"><?php echo $stats['total_teachers']; ?></h3>
                        <p class="mb-0 text-muted">Mësues</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #4facfe, #00f2fe); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                        <i class="ti ti-building text-white" style="font-size: 28px;"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0"><?php echo $stats['total_classes']; ?></h3>
                        <p class="mb-0 text-muted">Klasa</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #fa709a, #fee140); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                        <i class="ti ti-clipboard-x text-white" style="font-size: 28px;"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0"><?php echo $stats['monthly_absences']; ?></h3>
                        <p class="mb-0 text-muted">Mungesa (30 ditë)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Grade Distribution Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Shpërndarja e Notave</h4>
                <canvas id="gradeDistributionChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Performing Students -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Top 5 Studentët</h4>
                <div class="list-group list-group-flush">
                    <?php if (count($top_students) > 0): ?>
                        <?php foreach ($top_students as $index => $student): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: bold;">
                                            <?php echo $index + 1; ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($student['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($student['class_name']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-success"><?php echo number_format($student['avg_grade'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Nuk ka të dhëna</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Absence Trends Chart -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Trendet e Mungesave (7 ditë)</h4>
                <canvas id="absenceTrendsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Aktivitetet e Fundit</h4>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                <div class="me-3">
                                    <i class="ti ti-circle-check" style="color: var(--primary-color);"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1"><strong><?php echo htmlspecialchars($activity['full_name']); ?></strong> - <?php echo htmlspecialchars($activity['action']); ?></p>
                                    <?php if ($activity['description']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small><br>
                                    <?php endif; ?>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Nuk ka aktivitete</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = "
<script>
// Grade Distribution Chart
const gradeData = " . json_encode($grade_distribution) . ";
const gradeLabels = gradeData.map(item => 'Nota ' + item.grade_value);
const gradeCounts = gradeData.map(item => parseInt(item.count));

const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
new Chart(gradeCtx, {
    type: 'bar',
    data: {
        labels: gradeLabels,
        datasets: [{
            label: 'Numri i Notave',
            data: gradeCounts,
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Absence Trends Chart
const absenceData = " . json_encode($absence_trends) . ";
const absenceLabels = absenceData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('sq-AL', { month: 'short', day: 'numeric' });
});
const absenceCounts = absenceData.map(item => parseInt(item.count));

const absenceCtx = document.getElementById('absenceTrendsChart').getContext('2d');
new Chart(absenceCtx, {
    type: 'line',
    data: {
        labels: absenceLabels,
        datasets: [{
            label: 'Mungesa',
            data: absenceCounts,
            borderColor: 'rgba(245, 87, 108, 1)',
            backgroundColor: 'rgba(245, 87, 108, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
";

include '../includes/footer.php';
?>
