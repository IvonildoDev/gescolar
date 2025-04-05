<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professor') {
    header("Location: login.php");
    exit;
}

// Check if class_id is provided
if (!isset($_GET['class_id'])) {
    header("Location: dashboard.php");
    exit;
}

$class_id = $_GET['class_id'];
$professor_id = $_SESSION['user_id'];

// Get class information
$stmt = $pdo->prepare("
    SELECT c.*, s.name as school_name, s.id as school_id 
    FROM classes c
    JOIN schools s ON c.school_id = s.id
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    header("Location: dashboard.php");
    exit;
}

// Process attendance form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = date('Y-m-d');

    // Get array of present students
    $presentStudents = isset($_POST['present']) ? $_POST['present'] : [];

    // Get all students in class
    $stmt = $pdo->prepare("
        SELECT student_id FROM class_enrollments 
        WHERE class_id = ?
    ");
    $stmt->execute([$class_id]);
    $allStudents = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Process each student
    foreach ($allStudents as $studentId) {
        $present = in_array($studentId, $presentStudents) ? 1 : 0;

        // Insert or update attendance
        $stmt = $pdo->prepare("
            INSERT INTO attendance (class_id, student_id, date, present)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE present = ?
        ");
        $stmt->execute([$class_id, $studentId, $date, $present, $present]);
    }

    $success = "Chamada registrada com sucesso!";
}

// Get students enrolled in this class
$stmt = $pdo->prepare("
    SELECT s.* FROM students s
    JOIN class_enrollments ce ON s.id = ce.student_id
    WHERE ce.class_id = ?
    ORDER BY s.name
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll();

// Get today's date
$today = date('Y-m-d');

// Check if attendance was already taken today
$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE class_id = ? AND date = ? 
    LIMIT 1
");
$stmt->execute([$class_id, $today]);
$attendanceExists = $stmt->fetch();

// If attendance exists, get present students
$presentStudents = [];
if ($attendanceExists) {
    $stmt = $pdo->prepare("
        SELECT student_id FROM attendance 
        WHERE class_id = ? AND date = ? AND present = 1
    ");
    $stmt->execute([$class_id, $today]);
    $presentStudents = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada - <?php echo htmlspecialchars($class['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="attendance-page">
    <div class="container">
        <header>
            <h1>Chamada: <?php echo htmlspecialchars($class['name']); ?></h1>
            <p>Escola: <?php echo htmlspecialchars($class['school_name']); ?></p>
            <p>Data: <?php echo date('d/m/Y'); ?></p>
            <div class="navigation">
                <a href="school_classes.php?school_id=<?php echo $class['school_id']; ?>">← Voltar para Turmas</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <main>
            <h2>Registro de Presença</h2>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (empty($students)): ?>
                <p>Não há alunos matriculados nesta turma.</p>
            <?php else: ?>
                <form method="post">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Nome do Aluno</th>
                                <th>Presente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td>
                                        <input type="checkbox" name="present[]" value="<?php echo $student['id']; ?>"
                                            <?php echo in_array($student['id'], $presentStudents) ? 'checked' : ''; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" class="btn">Salvar Chamada</button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>