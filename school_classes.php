<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];

// Check if school_id is provided
if (!isset($_GET['school_id'])) {
    header("Location: dashboard.php");
    exit;
}

$school_id = $_GET['school_id'];

// Get school information
$stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch();

if (!$school) {
    header("Location: dashboard.php");
    exit;
}

// Get classes for this professor at this school
$stmt = $pdo->prepare("
    SELECT c.* FROM classes c
    JOIN professor_classes pc ON c.id = pc.class_id
    WHERE pc.professor_id = ? AND c.school_id = ?
");
$stmt->execute([$professor_id, $school_id]);
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turmas - <?php echo htmlspecialchars($school['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="classes-page">
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($school['name']); ?></h1>
            <div class="navigation">
                <a href="dashboard.php">← Voltar para Escolas</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <main>
            <h2>Minhas Turmas</h2>

            <div class="classes-list">
                <?php if (empty($classes)): ?>
                    <p>Você não possui turmas nesta escola.</p>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                            <p>Ano Letivo: <?php echo htmlspecialchars($class['school_year']); ?></p>
                            <p>Dia: <?php echo htmlspecialchars($class['weekday']); ?></p>
                            <p>Horário: <?php echo substr($class['start_time'], 0, 5); ?> - <?php echo substr($class['end_time'], 0, 5); ?></p>
                            <div class="class-actions">
                                <a href="class_students.php?class_id=<?php echo $class['id']; ?>" class="btn">Ver Alunos</a>
                                <a href="class_curriculum.php?class_id=<?php echo $class['id']; ?>" class="btn">Cronograma</a>
                                <a href="attendance.php?class_id=<?php echo $class['id']; ?>" class="btn">Fazer Chamada</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>