<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'manager') {
    header("Location: login.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add_curriculum') {
        $class_id = $_POST['class_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $lesson_date = $_POST['lesson_date'];

        // Insert new curriculum item
        $stmt = $pdo->prepare("
            INSERT INTO curriculum (class_id, title, description, lesson_date) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$class_id, $title, $description, $lesson_date]);

        $success = "Plano de aula adicionado com sucesso!";
    }
}

// Get all classes with professor info
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.school_year, s.name as school_name, p.name as professor_name, p.specialty
    FROM classes c
    JOIN schools s ON c.school_id = s.id
    JOIN professor_classes pc ON c.id = pc.class_id
    JOIN professors p ON pc.professor_id = p.id
    ORDER BY s.name, c.name
");
$stmt->execute();
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Cronograma - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="manage-curriculum">
    <div class="container">
        <header>
            <h1>Gerenciamento de Cronograma</h1>
            <div class="navigation">
                <a href="manager_dashboard.php" class="back-btn">Voltar para Dashboard</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="content-area">
            <div class="left-panel">
                <h2>Adicionar Plano de Aula</h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="add_curriculum">

                    <div class="form-group">
                        <label for="class_id">Turma:</label>
                        <select id="class_id" name="class_id" required>
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name']); ?> -
                                    <?php echo htmlspecialchars($class['school_name']); ?> -
                                    Prof. <?php echo htmlspecialchars($class['professor_name']); ?> (<?php echo htmlspecialchars($class['specialty']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lesson_date">Data da Aula:</label>
                        <input type="date" id="lesson_date" name="lesson_date" required>
                    </div>

                    <div class="form-group">
                        <label for="title">Título:</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Descrição/Conteúdo:</label>
                        <textarea id="description" name="description" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn">Adicionar Plano</button>
                </form>
            </div>

            <div class="right-panel">
                <h2>Selecione uma turma para ver o cronograma</h2>
                <form method="get" action="view_curriculum.php">
                    <div class="form-group">
                        <select name="class_id" onchange="this.form.submit()">
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name']); ?> -
                                    <?php echo htmlspecialchars($class['school_name']); ?> -
                                    Prof. <?php echo htmlspecialchars($class['professor_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>