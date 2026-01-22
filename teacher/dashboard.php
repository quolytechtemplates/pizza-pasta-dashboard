<?php
require_once '../config/database.php';
checkRole(['teacher']);

$page_title = 'Dashboard Mësuesi';
$teacher_user_id = $_SESSION['user_id'];

// Get teacher's classes and subjects
$classes_query = $conn->prepare("
    SELECT 
        cs.id as class_subject_id,
        c.class_name,
        s.subject_name,
        COUNT(DISTINCT st.id) as student_count
    FROM class_subjects cs
    JOIN classes c ON cs.class_id = c.id
    JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN students st ON st.class_id = c.id
    WHERE cs.teacher_id = ? AND cs.semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)
    GROUP BY cs.id, c.class_name, s.subject_name
");
$classes_query->bind_param("i", $teacher_user_id);
$classes_query->execute();
$classes_result = $classes_query->get_result();
$my_classes = $classes_result->fetch_all(MYSQLI_ASSOC);
$classes_query->close();

// Get statistics
$stats_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(DISTINCT cs.class_id) FROM class_subjects cs WHERE cs.teacher_id = ? AND cs.semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as total_classes,
        (SELECT COUNT(DISTINCT st.id) FROM students st JOIN classes c ON st.class_id = c.id JOIN class_subjects cs ON c.id = cs.class_id WHERE cs.teacher_id = ? AND cs.semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as total_students,
        (SELECT COUNT(*) FROM absences a JOIN class_subjects cs ON a.class_subject_id = cs.id WHERE cs.teacher_id = ? AND a.absence_date = CURDATE()) as today_absences,
        (SELECT COUNT(*) FROM grades g JOIN class_subjects cs ON g.class_subject_id = cs.id WHERE cs.teacher_id = ? AND g.semester_id = (SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1)) as total_grades
");
$stats_query->bind_param("iiii", $teacher_user_id, $teacher_user_id, $teacher_user_id, $teacher_user_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();
$stats_query->close();

// Get today's schedule
$today = date('l'); // Monday, Tuesday, etc.
$schedule_query = $conn->prepare("
    SELECT 
        sch.start_time,
        sch.end_time,
        sch.room,
        c.class_name,
        s.subject_name
    FROM schedules sch
    JOIN class_subjects cs ON sch.class_subject_id = cs.id
    JOIN classes c ON cs.class_id = c.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE cs.teacher_id = ? AND sch.day_of_week = ?
    ORDER BY sch.start_time
");
$schedule_query->bind_param("is", $teacher_user_id, $today);
$schedule_query->execute();
$schedule_result = $schedule_query->get_result();
$today_schedule = $schedule_result->fetch_all(MYSQLI_ASSOC);
$schedule_query->close();

// Get recent activities
$activities_query = $conn->prepare("
    SELECT 
        'Notë e re' as type,
        CONCAT(u.full_name, ' - ', s.subject_name, ': ', g.grade) as description,
        g.created_at as date
    FROM grades g
    JOIN students st ON g.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN class_subjects cs ON g.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE g.created_by = ?
    UNION ALL
    SELECT 
        'Mungesë e re' as type,
        CONCAT(u.full_name, ' - ', s.subject_name, ' (Ora ', a.hour_number, ')') as description,
        a.created_at as date
    FROM absences a
    JOIN students st ON a.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN class_subjects cs ON a.class_subject_id = cs.id
    JOIN subjects s ON cs.subject_id = s.id
    WHERE a.marked_by = ?
    ORDER BY date DESC
    LIMIT 10
");
$activities_query->bind_param("ii", $teacher_user_id, $teacher_user_id);
$activities_query->execute();
$activities_result = $activities_query->get_result();
$activities = $activities_result->fetch_all(MYSQLI_ASSOC);
$activities_query->close();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <h3>Mirësevini, Prof. <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
                <p class="mb-0">Sot është <?php echo date('l, d F Y'); ?></p>
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
                    <i class="ti ti-building text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['total_classes']; ?></h3>
                <p class="mb-0 text-muted">Klasa</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #f093fb, #f5576c); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-users text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                <p class="mb-0 text-muted">Studentë</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #4facfe, #00f2fe); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-clipboard-x text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['today_absences']; ?></h3>
                <p class="mb-0 text-muted">Mungesa sot</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="rounded-circle mx-auto mb-3" style="background: linear-gradient(135deg, #fa709a, #fee140); width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-award text-white" style="font-size: 28px;"></i>
                </div>
                <h3 class="mb-0"><?php echo $stats['total_grades']; ?></h3>
                <p class="mb-0 text-muted">Nota totale</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Schedule -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Orari i Sotëm</h4>
                <?php if (count($today_schedule) > 0): ?>
                    <?php foreach ($today_schedule as $class): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <div class="badge" style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 10px; font-size: 12px;">
                                    <?php echo date('H:i', strtotime($class['start_time'])); ?><br>
                                    <?php echo date('H:i', strtotime($class['end_time'])); ?>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?php echo htmlspecialchars($class['subject_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($class['class_name']); ?></small>
                                <?php if ($class['room']): ?>
                                    <br><small class="text-muted"><i class="ti ti-door"></i> <?php echo htmlspecialchars($class['room']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">Nuk keni orë sot</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- My Classes -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Klasat dhe Lëndët</h4>
                <?php if (count($my_classes) > 0): ?>
                    <?php foreach ($my_classes as $class): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($class['subject_name']); ?></small>
                            </div>
                            <div>
                                <span class="badge bg-primary"><?php echo $class['student_count']; ?> studentë</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="my_classes.php" class="btn btn-primary btn-sm w-100">Shiko detajet</a>
                <?php else: ?>
                    <p class="text-muted text-center">Nuk keni klasa të caktuara</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Veprime të Shpejta</h4>
                <div class="d-grid gap-2">
                    <a href="absences.php" class="btn btn-outline-primary">
                        <i class="ti ti-clipboard-check"></i> Shëno Mungesa
                    </a>
                    <a href="grades.php" class="btn btn-outline-primary">
                        <i class="ti ti-award"></i> Vendos Nota
                    </a>
                    <a href="students.php" class="btn btn-outline-primary">
                        <i class="ti ti-users"></i> Shiko Studentët
                    </a>
                    <a href="schedule.php" class="btn btn-outline-primary">
                        <i class="ti ti-calendar"></i> Shiko Orarin
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Aktivitetet e Fundit</h4>
                <div style="max-height: 350px; overflow-y: auto;">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                <div class="me-3">
                                    <?php if ($activity['type'] == 'Notë e re'): ?>
                                        <i class="ti ti-award" style="color: var(--primary-color);"></i>
                                    <?php else: ?>
                                        <i class="ti ti-clipboard-x" style="color: #dc3545;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <strong><?php echo $activity['type']; ?></strong>
                                    <p class="mb-0 small"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?></small>
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

<?php include '../includes/footer.php'; ?>
