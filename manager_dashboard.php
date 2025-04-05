<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'manager') {
    header("Location: login.php");
    exit;
}

$manager_id = $_SESSION['user_id'];
$manager_name = $_SESSION['name'];

// Get count of professors
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM professors");
$stmt->execute();
$professors_count = $stmt->fetch()['count'];

// Get count of schools
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM schools");
$stmt->execute();
$schools_count = $stmt->fetch()['count'];

// Get count of classes
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes");
$stmt->execute();
$classes_count = $stmt->fetch()['count'];

// Get count of students
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students");
$stmt->execute();
$students_count = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Coordenador - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="manager-dashboard">
    <div class="container">
        <header>
            <h1>Painel do Coordenador</h1>
            <div class="user-info">
                <p>Bem-vindo, <?php echo htmlspecialchars($manager_name); ?></p>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <div class="stats-cards">
            <div class="stat-card">
                <h3>Professores</h3>
                <div class="stat-value"><?php echo $professors_count; ?></div>
                <a href="manage_professors.php" class="card-link">Gerenciar</a>
            </div>
            <div class="stat-card">
                <h3>Escolas</h3>
                <div class="stat-value"><?php echo $schools_count; ?></div>
                <a href="manage_schools.php" class="card-link">Gerenciar</a>
            </div>
            <div class="stat-card">
                <h3>Turmas</h3>
                <div class="stat-value"><?php echo $classes_count; ?></div>
                <a href="manage_classes.php" class="card-link">Gerenciar</a>
            </div>
            <div class="stat-card">
                <h3>Alunos</h3>
                <div class="stat-value"><?php echo $students_count; ?></div>
                <a href="manage_students.php" class="card-link">Gerenciar</a>
            </div>
        </div>

        <div class="management-menu">
            <h2>Gerenciamento</h2>
            <div class="menu-cards">
                <a href="manage_professors.php" class="menu-card">
                    <h3>Gerenciar Professores</h3>
                    <p>Cadastrar, editar e atribuir matérias aos professores</p>
                </a>
                <a href="assign_classes.php" class="menu-card">
                    <h3>Atribuir Aulas</h3>
                    <p>Designar horários e turmas para os professores</p>
                </a>
                <a href="manage_curriculum.php" class="menu-card">
                    <h3>Cronograma de Ensino</h3>
                    <p>Definir conteúdo programático para cada disciplina</p>
                </a>
                <a href="professor_performance.php" class="menu-card">
                    <h3>Desempenho</h3>
                    <p>Acompanhar percentual de aulas dadas pelos professores</p>
                </a>
            </div>
        </div>
    </div>
</body>

</html>