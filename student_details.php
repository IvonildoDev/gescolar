<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a manager or professor
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'manager' && $_SESSION['user_type'] != 'professor')) {
    header("Location: login.php");
    exit;
}

// Check if student ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_students.php");
    exit;
}

$student_id = $_GET['id'];

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: manage_students.php");
    exit;
}

// Calculate student age
$birth_date = new DateTime($student['birth_date']);
$today = new DateTime();
$age = $birth_date->diff($today)->y;

// Get all classes this student is enrolled in
$stmt = $pdo->prepare("
    SELECT c.id, c.name, s.name as school_name, ce.enrollment_date 
    FROM class_enrollments ce
    JOIN classes c ON ce.class_id = c.id
    JOIN schools s ON c.school_id = s.id
    WHERE ce.student_id = ?
    ORDER BY ce.enrollment_date DESC
");
$stmt->execute([$student_id]);
$enrollments = $stmt->fetchAll();

// Check if attendance table exists to avoid fatal error
$stmt = $pdo->query("SHOW TABLES LIKE 'attendance'");
$attendance_table_exists = $stmt->rowCount() > 0;

// Get attendance summary if table exists
$attendance_records = [];
$attendance_rate = 0;
$present_count = 0;
$total_classes = 0;

if ($attendance_table_exists) {
    $stmt = $pdo->prepare("
        SELECT date, status, class_id
        FROM attendance
        WHERE student_id = ?
        ORDER BY date DESC
    ");
    $stmt->execute([$student_id]);
    $attendance_records = $stmt->fetchAll();

    $total_classes = count($attendance_records);

    foreach ($attendance_records as $record) {
        if ($record['status'] == 'present') {
            $present_count++;
        }
    }

    $attendance_rate = $total_classes > 0 ? round(($present_count / $total_classes) * 100) : 0;
}

// Check if grades table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'grades'");
$grades_table_exists = $stmt->rowCount() > 0;

// Get grades if table exists
$grades = [];
$overall_average = 0;

if ($grades_table_exists) {
    $stmt = $pdo->prepare("
        SELECT grade, evaluation_date, class_id
        FROM grades
        WHERE student_id = ?
        ORDER BY evaluation_date DESC
    ");
    $stmt->execute([$student_id]);
    $grades = $stmt->fetchAll();

    // Calculate overall average if grades exist
    if (count($grades) > 0) {
        $sum = 0;
        foreach ($grades as $grade) {
            $sum += $grade['grade'];
        }
        $overall_average = round($sum / count($grades), 1);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Aluno - <?php echo htmlspecialchars($student['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="student-details-page">
    <div class="container">
        <header>
            <h1>Detalhes do Aluno</h1>
            <div class="navigation">
                <a href="manage_students.php" class="back-btn">Voltar para Alunos</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <div class="student-profile">
            <div class="student-header">
                <div class="student-avatar">
                    <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="student-header-info">
                    <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                    <div class="student-meta">
                        <div class="meta-item">
                            <i class="meta-icon">📅</i>
                            <span>Nascimento: <?php echo date('d/m/Y', strtotime($student['birth_date'])); ?> (<?php echo $age; ?> anos)</span>
                        </div>
                        <?php if (isset($student['parent_name']) && !empty($student['parent_name'])): ?>
                            <div class="meta-item">
                                <i class="meta-icon">👪</i>
                                <span>Responsável: <?php echo htmlspecialchars($student['parent_name']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($student['contact_phone']) && !empty($student['contact_phone'])): ?>
                            <div class="meta-item">
                                <i class="meta-icon">📞</i>
                                <span>Contato: <?php echo htmlspecialchars($student['contact_phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($student['address']) && !empty($student['address'])): ?>
                            <div class="meta-item">
                                <i class="meta-icon">🏠</i>
                                <span>Endereço: <?php echo htmlspecialchars($student['address']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($attendance_table_exists || $grades_table_exists): ?>
                <div class="performance-summary">
                    <?php if ($attendance_table_exists): ?>
                        <div class="summary-card">
                            <div class="summary-title">Frequência Geral</div>
                            <div class="summary-value"><?php echo $attendance_rate; ?>%</div>
                            <div class="progress-bar">
                                <div class="progress <?php
                                                        if ($attendance_rate < 70) echo 'progress-low';
                                                        else if ($attendance_rate < 85) echo 'progress-medium';
                                                        else echo 'progress-high';
                                                        ?>" style="width: <?php echo $attendance_rate; ?>%"><?php echo $attendance_rate; ?>%</div>
                            </div>
                            <div class="summary-details">
                                <span>Presente: <?php echo $present_count; ?> aulas</span>
                                <span>Ausente: <?php echo $total_classes - $present_count; ?> aulas</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($grades_table_exists && count($grades) > 0): ?>
                        <div class="summary-card">
                            <div class="summary-title">Média Geral</div>
                            <div class="summary-value"><?php echo $overall_average; ?></div>
                            <div class="progress-bar">
                                <div class="progress <?php
                                                        if ($overall_average < 5) echo 'progress-low';
                                                        else if ($overall_average < 7) echo 'progress-medium';
                                                        else echo 'progress-high';
                                                        ?>" style="width: <?php echo ($overall_average * 10); ?>%"><?php echo $overall_average; ?></div>
                            </div>
                            <div class="summary-details">
                                <span>Total de avaliações: <?php echo count($grades); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="content-tabs">
                <div class="tab-header">
                    <button class="tab-button active" onclick="openTab(event, 'enrollments')">Matrículas</button>
                    <?php if ($attendance_table_exists): ?>
                        <button class="tab-button" onclick="openTab(event, 'attendance')">Frequência</button>
                    <?php endif; ?>
                    <?php if ($grades_table_exists && count($grades) > 0): ?>
                        <button class="tab-button" onclick="openTab(event, 'grades')">Notas</button>
                    <?php endif; ?>
                </div>

                <div id="enrollments" class="tab-content active">
                    <h3>Matrículas do Aluno</h3>
                    <?php if (empty($enrollments)): ?>
                        <p>Nenhuma matrícula encontrada para este aluno.</p>
                    <?php else: ?>
                        <div class="enrollment-cards">
                            <?php foreach ($enrollments as $enrollment): ?>
                                <div class="enrollment-card">
                                    <div class="enrollment-header">
                                        <h4><?php echo htmlspecialchars($enrollment['name']); ?></h4>
                                    </div>
                                    <div class="enrollment-body">
                                        <p><i class="school-icon">🏫</i> <?php echo htmlspecialchars($enrollment['school_name']); ?></p>
                                        <p><i class="calendar-icon">📆</i> Data de matrícula: <?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></p>
                                    </div>
                                    <div class="enrollment-footer">
                                        <a href="class_details.php?id=<?php echo $enrollment['id']; ?>" class="btn-small">Ver Turma</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($attendance_table_exists): ?>
                    <div id="attendance" class="tab-content">
                        <h3>Frequência Escolar</h3>
                        <?php if (empty($attendance_records)): ?>
                            <p>Nenhum registro de frequência encontrado para este aluno.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Turma</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record):
                                        // Get class name
                                        $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
                                        $stmt->execute([$record['class_id']]);
                                        $class_name = $stmt->fetch()['name'] ?? 'N/A';
                                    ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($class_name); ?></td>
                                            <td>
                                                <span class="attendance-badge <?php echo $record['status'] == 'present' ? 'present' : 'absent'; ?>">
                                                    <?php echo $record['status'] == 'present' ? 'Presente' : 'Ausente'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($grades_table_exists && count($grades) > 0): ?>
                    <div id="grades" class="tab-content">
                        <h3>Desempenho Acadêmico</h3>
                        <div class="detailed-grades">
                            <h4>Notas</h4>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Turma</th>
                                        <th>Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade):
                                        // Get class name
                                        $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
                                        $stmt->execute([$grade['class_id']]);
                                        $class_name = $stmt->fetch()['name'] ?? 'N/A';
                                    ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($grade['evaluation_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($class_name); ?></td>
                                            <td>
                                                <span class="grade-badge <?php
                                                                            if ($grade['grade'] < 5) echo 'low-grade';
                                                                            else if ($grade['grade'] < 7) echo 'medium-grade';
                                                                            else echo 'high-grade';
                                                                            ?>">
                                                    <?php echo $grade['grade']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            // Hide all tab content
            var tabcontent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }

            // Remove active class from all tab buttons
            var tablinks = document.getElementsByClassName("tab-button");
            for (var i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }

            // Show the specific tab content
            document.getElementById(tabName).classList.add("active");

            // Add active class to the button that opened the tab
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>

</html>