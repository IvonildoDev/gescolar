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

// Get enrolled students for this class
$stmt = $pdo->prepare("
    SELECT s.* FROM students s
    JOIN class_enrollments ce ON s.id = ce.student_id
    WHERE ce.class_id = ?
    ORDER BY s.name
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos - <?php echo htmlspecialchars($class['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="students-page">
    <div class="container">
        <header>
            <h1>Turma: <?php echo htmlspecialchars($class['name']); ?></h1>
            <p>Escola: <?php echo htmlspecialchars($class['school_name']); ?></p>
            <div class="navigation">
                <a href="school_classes.php?school_id=<?php echo $class['school_id']; ?>">← Voltar para Turmas</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <main>
            <h2>Alunos Matriculados</h2>

            <?php if (empty($students)): ?>
                <p>Não há alunos matriculados nesta turma.</p>
            <?php else: ?>
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data de Nascimento</th>
                            <th>Idade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($student['birth_date'])); ?></td>
                                <td>
                                    <?php
                                    $birthDate = new DateTime($student['birth_date']);
                                    $today = new DateTime();
                                    $age = $today->diff($birthDate)->y;
                                    echo $age . ' anos';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="attendance.php?class_id=<?php echo $class_id; ?>" class="btn">Fazer Chamada</a>
            </div>
        </main>
    </div>
</body>

</html>