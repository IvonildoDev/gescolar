<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'manager') {
    header("Location: login.php");
    exit;
}

// Check if class_id is provided
if (!isset($_GET['class_id'])) {
    header("Location: manage_curriculum.php");
    exit;
}

$class_id = $_GET['class_id'];

// Get class information with professor
$stmt = $pdo->prepare("
    SELECT c.*, s.name as school_name, p.name as professor_name, p.specialty 
    FROM classes c
    JOIN schools s ON c.school_id = s.id
    JOIN professor_classes pc ON c.id = pc.class_id
    JOIN professors p ON pc.professor_id = p.id
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    header("Location: manage_curriculum.php");
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

// Process delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $stmt = $pdo->prepare("DELETE FROM curriculum WHERE id = ? AND class_id = ?");
    $stmt->execute([$delete_id, $class_id]);

    header("Location: view_curriculum.php?class_id=$class_id&deleted=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronograma - <?php echo htmlspecialchars($class['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="view-curriculum">
    <div class="container">
        <header>
            <h1>Cronograma: <?php echo htmlspecialchars($class['name']); ?></h1>
            <div class="class-info">
                <p><strong>Escola:</strong> <?php echo htmlspecialchars($class['school_name']); ?></p>
                <p><strong>Professor:</strong> <?php echo htmlspecialchars($class['professor_name']); ?> (<?php echo htmlspecialchars($class['specialty']); ?>)</p>
                <p><strong>Dia:</strong> <?php echo htmlspecialchars($class['weekday']); ?>,
                    <strong>Horário:</strong> <?php echo substr($class['start_time'], 0, 5); ?> - <?php echo substr($class['end_time'], 0, 5); ?>
                </p>
            </div>
            <div class="navigation">
                <a href="manage_curriculum.php">← Voltar para Gerenciamento</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success">Item do cronograma removido com sucesso!</div>
        <?php endif; ?>

        <main>
            <h2>Plano de Aulas</h2>

            <?php if (empty($curriculum)): ?>
                <p>Não há aulas planejadas para esta turma.</p>
                <a href="manage_curriculum.php" class="btn">Adicionar Plano de Aula</a>
            <?php else: ?>
                <table class="curriculum-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($curriculum as $lesson): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($lesson['lesson_date'])); ?></td>
                                <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['description']); ?></td>
                                <td>
                                    <a href="edit_curriculum.php?id=<?php echo $lesson['id']; ?>" class="btn-small">Editar</a>
                                    <a href="view_curriculum.php?class_id=<?php echo $class_id; ?>&delete_id=<?php echo $lesson['id']; ?>"
                                        class="btn-small btn-danger"
                                        onclick="return confirm('Tem certeza que deseja remover este item?')">Remover</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="action-buttons">
                    <a href="manage_curriculum.php" class="btn">Adicionar Mais Aulas</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>