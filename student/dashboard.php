<?php
require_once '../config/database.php';
checkRole(['student']);

$page_title = 'Dashboard Studenti';
$student_user_id = $_SESSION['user_id'];

// Get student ID
$student_query = $conn->prepare("SELECT id, class_id FROM students WHERE user_id = ?");
$student_query->bind_param("i", $student_user_id);
$student_query->execute();
$student_result = $student_query->get_result();
$student_data = $student_result->fetch_assoc();
$student_id = $student_data['id'];
$class_id = $student_data['class_id'];
$student_query->close();

// Get class name
$class_name = 'N/A';
if ($class_id) {
    $class_query = $conn->prepare("SELECT class_name FROM classes WHERE id = ?");
    $class_query->bind_param("i", $class_id);
    $class_query->execute();
    $class_result = $class_query->get_result();
    $class_row = $class_result->fetch_assoc();
    $class_name = $class_row['class_name'];
    $class_query->close();
}

// Get statistics
$stats_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM absences WHERE student_id = ? AND is_excused = FALSE) as total_unexcused_absences,
        (SELECT COUNT(*) FROM absences WHERE student_id = ? AND is_excused = TRUE) as total_excused_absences,
        (SELECT AVG(grade) FROM grades WHERE student_id = ? AND semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as avg_grade,
        (SELECT COUNT(DISTINCT class_subject_id) FROM grades WHERE student_id = ? AND semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as subjects_with_grades
");
$stats_query->bind_param("iiii", $student_id, $student_id, $student_id, $student_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();
$stats_query->close();

// Get absence calendar data (last 30 days)
$absence_calendar_query = $conn->prepare("
    SELECT 
        absence_date,
        COUNT(*) as hours_missed,
        SUM(CASE WHEN is_excused = TRUE THEN 1 ELSE 0 END) as excused_hours
    FROM absences
    WHERE student_id = ? AND absence_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY absence_date
    ORDER BY absence_date DESC
");
$absence_calendar_query->bind_param("i", $student_id);
$absence_calendar_query->execute();
$absence_calendar_result = $absence_calendar_query->get_result();
$absence_calendar = $absence_calendar_result->fetch_all(MYSQLI_ASSOC);
$absence_calendar_query->close();

// Get recent absences with details
$recent_absences_query = $conn->prepare("
    SELECT 
        a.absence_date,
        a.hour_number,
        a.is_excused,
        a.excuse_reason,
        s.subject_name,
        u.full_name as teacher_name
    FROM absences a
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN users u ON cs.teacher_id = u.id
    WHERE a.student_id = ?
    ORDER BY a.absence_date DESC, a.hour_number DESC
    LIMIT 10
");
$recent_absences_query->bind_param("i", $student_id);
$recent_absences_query->execute();
$recent_absences_result = $recent_absences_query->get_result();
$recent_absences = $recent_absences_result->fetch_all(MYSQLI_ASSOC);
$recent_absences_query->close();

// Get recent grades
$recent_grades_query = $conn->prepare("
    SELECT 
        g.grade,
        g.grade_type,
        g.grade_date,
        s.subject_name,
        u.full_name as teacher_name
    FROM grades g
    JOIN class_subjects cs ON g.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN users u ON cs.teacher_id = u.id
    WHERE g.student_id = ?
    ORDER BY g.grade_date DESC
    LIMIT 5
");
$recent_grades_query->bind_param("i", $student_id);
$recent_grades_query->execute();
$recent_grades_result = $recent_grades_query->get_result();
$recent_grades = $recent_grades_result->fetch_all(MYSQLI_ASSOC);
$recent_grades_query->close();

// Get grade type names in Albanian
function getGradeTypeName($type) {
    $types = [
        'vleresim_vazhduar' => 'Vlerësim i Vazhduar',
        'projekti_final' => 'Projekti Final',
        'testi_final' => 'Testi Final'
    ];
    return $types[$type] ?? $type;
}

include '../includes/header.php';
?>

<div class="row">
    <!-- Welcome Card -->
    <div class="col-12">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <h3>Mirësevini, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
                <p class="mb-0">Klasa: <?php echo htmlspecialchars($class_name); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #667eea, #764ba2); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-award text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['avg_grade'] ? number_format($stats['avg_grade'], 2) : 'N/A'; ?></h3>
                <p class="mb-0 text-muted">Mesatarja</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #f093fb, #f5576c); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-clipboard-x text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['total_unexcused_absences']; ?></h3>
                <p class="mb-0 text-muted">Mungesa të pajustifikuara</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #4facfe, #00f2fe); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-clipboard-check text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['total_excused_absences']; ?></h3>
                <p class="mb-0 text-muted">Mungesa të justifikuara</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #fa709a, #fee140); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-books text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['subjects_with_grades']; ?></h3>
                <p class="mb-0 text-muted">Lëndë me nota</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Absence Calendar -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Kalendari i Mungesave (30 ditë të fundit)</h4>
                <div class="row g-2">
                    <?php
                    // Create array of last 30 days
                    $days = [];
                    for ($i = 29; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $days[$date] = ['hours' => 0, 'excused' => 0];
                    }
                    
                    // Fill in absence data
                    foreach ($absence_calendar as $absence) {
                        $days[$absence['absence_date']] = [
                            'hours' => $absence['hours_missed'],
                            'excused' => $absence['excused_hours']
                        ];
                    }
                    
                    foreach ($days as $date => $data):
                        $day_name = date('D', strtotime($date));
                        $day_number = date('d', strtotime($date));
                        $is_weekend = in_array($day_name, ['Sat', 'Sun']);
                        
                        $bg_color = '#f8f9fa';
                        $text_color = '#6c757d';
                        $border = '';
                        
                        if (!$is_weekend && $data['hours'] > 0) {
                            if ($data['hours'] == $data['excused']) {
                                $bg_color = '#d1ecf1';
                                $text_color = '#0c5460';
                            } else {
                                $bg_color = '#f8d7da';
                                $text_color = '#721c24';
                                $border = 'border: 2px solid #dc3545;';
                            }
                        }
                        
                        if ($date == date('Y-m-d')) {
                            $border = 'border: 2px solid #667eea;';
                        }
                    ?>
                        <div class="col" style="min-width: 50px;">
                            <div class="text-center p-2 rounded" style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; <?php echo $border; ?>">
                                <small style="font-size: 10px;"><?php echo $day_name; ?></small><br>
                                <strong><?php echo $day_number; ?></strong>
                                <?php if ($data['hours'] > 0): ?>
                                    <br><small style="font-size: 10px;"><?php echo $data['hours']; ?>h</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <small>
                        <span class="badge me-2" style="background: #f8d7da; color: #721c24;">■</span> Mungesa të pajustifikuara
                        <span class="badge me-2 ms-3" style="background: #d1ecf1; color: #0c5460;">■</span> Mungesa të justifikuara
                        <span class="badge ms-3" style="border: 2px solid #667eea; background: transparent; color: #667eea;">□</span> Sot
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Grades -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Notat e Fundit</h4>
                <?php if (count($recent_grades) > 0): ?>
                    <?php foreach ($recent_grades as $grade): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($grade['subject_name']); ?></h6>
                                    <small class="text-muted"><?php echo getGradeTypeName($grade['grade_type']); ?></small>
                                </div>
                                <div>
                                    <span class="badge" style="background: linear-gradient(135deg, #667eea, #764ba2); font-size: 16px;">
                                        <?php echo number_format($grade['grade'], 1); ?>
                                    </span>
                                </div>
                            </div>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($grade['grade_date'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                    <a href="grades.php" class="btn btn-primary btn-sm w-100">Shiko të gjitha notat</a>
                <?php else: ?>
                    <p class="text-muted text-center">Nuk ka nota akoma</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Absences Details -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Mungesat e Fundit</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Ora</th>
                                <th>Lënda</th>
                                <th>Mësuesi</th>
                                <th>Statusi</th>
                                <th>Arsyeja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_absences) > 0): ?>
                                <?php foreach ($recent_absences as $absence): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($absence['absence_date'])); ?></td>
                                        <td>Ora <?php echo $absence['hour_number']; ?></td>
                                        <td><?php echo htmlspecialchars($absence['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($absence['teacher_name']); ?></td>
                                        <td>
                                            <?php if ($absence['is_excused']): ?>
                                                <span class="badge bg-info">E justifikuar</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">E pajustifikuar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $absence['excuse_reason'] ? htmlspecialchars($absence['excuse_reason']) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Nuk ka mungesa</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($recent_absences) > 0): ?>
                    <a href="absences.php" class="btn btn-primary btn-sm">Shiko të gjitha mungesat</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
