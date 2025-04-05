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

// Get curriculum for this class
$stmt = $pdo->prepare("
    SELECT * FROM curriculum 
    WHERE class_id = ?
    ORDER BY lesson_date
");
$stmt->execute([$class_id]);
$curriculum = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronograma - <?php echo htmlspecialchars($class['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="curriculum-page">
    <div class="container">
        <header>
            <h1>Cronograma: <?php echo htmlspecialchars($class['name']); ?></h1>
            <p>Escola: <?php echo htmlspecialchars($class['school_name']); ?></p>
            <div class="navigation">
                <a href="school_classes.php?school_id=<?php echo $class['school_id']; ?>">← Voltar para Turmas</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <main>
            <h2>Cronograma de Aulas</h2>

            <?php if (empty($curriculum)): ?>
                <p>Não há aulas planejadas para esta turma.</p>
            <?php else: ?>
                <table class="curriculum-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Título</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($curriculum as $lesson): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($lesson['lesson_date'])); ?></td>
                                <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>