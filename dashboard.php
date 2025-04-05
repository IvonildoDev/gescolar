<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$professor_name = $_SESSION['name'];

// Get schools where professor teaches
$stmt = $pdo->prepare("
    SELECT s.* FROM schools s
    JOIN professor_schools ps ON s.id = ps.school_id
    WHERE ps.professor_id = ?
");
$stmt->execute([$professor_id]);
$schools = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Professor - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="dashboard-page">
    <div class="container">
        <header>
            <h1>Sistema Escolar</h1>
            <div class="user-info">
                <p>Bem-vindo, Professor <?php echo htmlspecialchars($professor_name); ?></p>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <main>
            <h2>Minhas Escolas</h2>

            <div class="schools-list">
                <?php if (empty($schools)): ?>
                    <p>Você não possui escolas associadas.</p>
                <?php else: ?>
                    <?php foreach ($schools as $school): ?>
                        <div class="school-card">
                            <h3><?php echo htmlspecialchars($school['name']); ?></h3>
                            <p><?php echo htmlspecialchars($school['address']); ?></p>
                            <p>Telefone: <?php echo htmlspecialchars($school['phone']); ?></p>
                            <a href="school_classes.php?school_id=<?php echo $school['id']; ?>" class="btn">Ver Turmas</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>