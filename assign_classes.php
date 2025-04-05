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
    // Handle class assignment
    if (isset($_POST['action']) && $_POST['action'] == 'assign_class') {
        $professor_id = $_POST['professor_id'];
        $class_id = $_POST['class_id'];
        $weekday = $_POST['weekday'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        // Check if the professor is already assigned to this class
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM professor_classes WHERE professor_id = ? AND class_id = ?");
        $stmt->execute([$professor_id, $class_id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            $error = "Professor já está vinculado a esta turma!";
        } else {
            // Update class schedule
            $stmt = $pdo->prepare("
                UPDATE classes SET 
                weekday = ?, 
                start_time = ?, 
                end_time = ? 
                WHERE id = ?
            ");
            $stmt->execute([$weekday, $start_time, $end_time, $class_id]);

            // Create assignment
            $stmt = $pdo->prepare("INSERT INTO professor_classes (professor_id, class_id) VALUES (?, ?)");
            $stmt->execute([$professor_id, $class_id]);

            $success = "Aula atribuída com sucesso!";
        }
    }
}

// Get all professors
$stmt = $pdo->prepare("SELECT * FROM professors ORDER BY name");
$stmt->execute();
$professors = $stmt->fetchAll();

// Get all classes
$stmt = $pdo->prepare("
    SELECT c.*, s.name as school_name 
    FROM classes c
    JOIN schools s ON c.school_id = s.id
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
    <title>Atribuição de Aulas - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="assign-classes">
    <div class="container">
        <header>
            <h1>Atribuição de Aulas</h1>
            <div class="navigation">
                <a href="manager_dashboard.php">← Voltar para Dashboard</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-area">
            <div class="left-panel">
                <h2>Atribuir Aula</h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="assign_class">

                    <div class="form-group">
                        <label for="professor_id">Professor:</label>
                        <select id="professor_id" name="professor_id" required>
                            <option value="">Selecione um professor</option>
                            <?php foreach ($professors as $professor): ?>
                                <option value="<?php echo $professor['id']; ?>"><?php echo htmlspecialchars($professor['name']); ?> (<?php echo htmlspecialchars($professor['specialty']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_id">Turma:</label>
                        <select id="class_id" name="class_id" required>
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?> - <?php echo htmlspecialchars($class['school_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="weekday">Dia da Semana:</label>
                        <select id="weekday" name="weekday" required>
                            <option value="">Selecione</option>
                            <option value="Segunda-feira">Segunda-feira</option>
                            <option value="Terça-feira">Terça-feira</option>
                            <option value="Quarta-feira">Quarta-feira</option>
                            <option value="Quinta-feira">Quinta-feira</option>
                            <option value="Sexta-feira">Sexta-feira</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_time">Hora de Início:</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>

                    <div class="form-group">
                        <label for="end_time">Hora de Término:</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>

                    <button type="submit" class="btn">Atribuir Aula</button>
                </form>
            </div>

            <div class="right-panel">
                <h2>Atribuições Atuais</h2>
                <?php
                // Get all professor class assignments
                $stmt = $pdo->prepare("
                    SELECT pc.id, p.name as professor_name, p.specialty, 
                           c.name as class_name, c.weekday, c.start_time, c.end_time,
                           s.name as school_name
                    FROM professor_classes pc
                    JOIN professors p ON pc.professor_id = p.id
                    JOIN classes c ON pc.class_id = c.id
                    JOIN schools s ON c.school_id = s.id
                    ORDER BY p.name, s.name, c.name
                ");
                $stmt->execute();
                $assignments = $stmt->fetchAll();
                ?>

                <?php if (empty($assignments)): ?>
                    <p>Nenhuma atribuição de aula.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Especialidade</th>
                                <th>Escola</th>
                                <th>Turma</th>
                                <th>Dia</th>
                                <th>Horário</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['professor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['specialty']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['school_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['weekday']); ?></td>
                                    <td>
                                        <?php
                                        echo substr($assignment['start_time'], 0, 5) . ' - ' .
                                            substr($assignment['end_time'], 0, 5);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-small">Editar</a>
                                        <a href="delete_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Tem certeza que deseja remover esta atribuição?')">Remover</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>