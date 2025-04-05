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
    // Handle class registration
    if (isset($_POST['action']) && $_POST['action'] == 'add_class') {
        $name = $_POST['name'];
        $school_year = $_POST['school_year'];
        $school_id = $_POST['school_id'];

        // Insert new class
        $stmt = $pdo->prepare("INSERT INTO classes (name, school_year, school_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $school_year, $school_id]);
        $success = "Turma cadastrada com sucesso!";
    }

    // Handle class deletion
    if (isset($_POST['action']) && $_POST['action'] == 'delete_class') {
        $class_id = $_POST['class_id'];

        // Check if class has enrollments
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_enrollments WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $has_enrollments = $stmt->fetch()['count'] > 0;

        // Check if class has professor assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM professor_classes WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $has_assignments = $stmt->fetch()['count'] > 0;

        // Check if class has curriculum
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM curriculum WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $has_curriculum = $stmt->fetch()['count'] > 0;

        if ($has_enrollments || $has_assignments || $has_curriculum) {
            $error = "Não é possível excluir esta turma pois existem dados associados a ela.";
        } else {
            // Delete class
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->execute([$class_id]);
            $success = "Turma excluída com sucesso!";
        }
    }

    // Handle class update
    if (isset($_POST['action']) && $_POST['action'] == 'edit_class') {
        $class_id = $_POST['class_id'];
        $name = $_POST['name'];
        $school_year = $_POST['school_year'];
        $school_id = $_POST['school_id'];

        // Update class
        $stmt = $pdo->prepare("UPDATE classes SET name = ?, school_year = ?, school_id = ? WHERE id = ?");
        $stmt->execute([$name, $school_year, $school_id, $class_id]);
        $success = "Turma atualizada com sucesso!";
    }
}

// Initialize edit mode variables
$edit_mode = false;
$class_to_edit = null;

// Check if we're in edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $class_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class_to_edit = $stmt->fetch();

    if ($class_to_edit) {
        $edit_mode = true;
    }
}

// Get all schools for dropdown
$stmt = $pdo->prepare("SELECT id, name FROM schools ORDER BY name");
$stmt->execute();
$schools = $stmt->fetchAll();

// Get all classes with school info
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
    <title>Gerenciamento de Turmas - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="manager-dashboard">
    <div class="container">
        <header>
            <h1>Gerenciamento de Turmas</h1>
            <div class="navigation">
                <a href="manager_dashboard.php" class="back-btn">Voltar para Dashboard</a>
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
                <h2><?php echo $edit_mode ? 'Editar Turma' : 'Cadastrar Nova Turma'; ?></h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit_class' : 'add_class'; ?>">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="class_id" value="<?php echo $class_to_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="school_id">Escola:</label>
                        <select id="school_id" name="school_id" required>
                            <option value="">Selecione uma escola</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['id']; ?>" <?php echo ($edit_mode && $class_to_edit['school_id'] == $school['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($school['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="name">Nome da Turma:</label>
                        <input type="text" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($class_to_edit['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="school_year">Ano Letivo:</label>
                        <input type="text" id="school_year" name="school_year" value="<?php echo $edit_mode ? htmlspecialchars($class_to_edit['school_year']) : date('Y'); ?>" required>
                    </div>

                    <button type="submit" class="btn"><?php echo $edit_mode ? 'Atualizar Turma' : 'Cadastrar Turma'; ?></button>

                    <?php if ($edit_mode): ?>
                        <a href="manage_classes.php" class="btn btn-cancel">Cancelar Edição</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="right-panel">
                <div class="panel-header">
                    <h2>Turmas Cadastradas</h2>
                    <div class="panel-tools">
                        <div class="search-container">
                            <input type="text" id="classSearch" placeholder="Buscar turma..." onkeyup="filterClasses()">
                            <i class="search-icon">🔍</i>
                        </div>
                    </div>
                </div>

                <?php if (empty($classes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📚</div>
                        <p>Nenhuma turma cadastrada.</p>
                        <p class="empty-hint">Utilize o formulário ao lado para adicionar sua primeira turma.</p>
                    </div>
                <?php else: ?>
                    <div class="class-count">
                        <span><?php echo count($classes); ?> turma<?php echo count($classes) > 1 ? 's' : ''; ?> encontrada<?php echo count($classes) > 1 ? 's' : ''; ?></span>
                    </div>

                    <div class="class-cards">
                        <?php foreach ($classes as $class): ?>
                            <div class="class-card" data-name="<?php echo strtolower(htmlspecialchars($class['name'])); ?>" data-school="<?php echo strtolower(htmlspecialchars($class['school_name'])); ?>">
                                <div class="class-card-header">
                                    <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                                    <span class="school-year-badge"><?php echo htmlspecialchars($class['school_year']); ?></span>
                                </div>

                                <div class="class-card-body">
                                    <p class="school-name">
                                        <i class="school-icon">🏫</i>
                                        <?php echo htmlspecialchars($class['school_name']); ?>
                                    </p>

                                    <div class="class-stats">
                                        <?php
                                        // Get student count for this class
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_enrollments WHERE class_id = ?");
                                        $stmt->execute([$class['id']]);
                                        $student_count = $stmt->fetch()['count'];

                                        // Get professor count for this class
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM professor_classes WHERE class_id = ?");
                                        $stmt->execute([$class['id']]);
                                        $professor_count = $stmt->fetch()['count'];
                                        ?>
                                        <div class="stat-item">
                                            <i class="student-icon">👨‍🎓</i>
                                            <span><?php echo $student_count; ?> aluno<?php echo $student_count != 1 ? 's' : ''; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="professor-icon">👨‍🏫</i>
                                            <span><?php echo $professor_count; ?> professor<?php echo $professor_count != 1 ? 'es' : ''; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="class-card-footer">
                                    <div class="button-group">
                                        <a href="manage_classes.php?edit=<?php echo $class['id']; ?>" class="btn-icon btn-edit" title="Editar turma">
                                            <i class="icon-edit"></i>
                                            <span>Editar</span>
                                        </a>
                                        <a href="class_details.php?id=<?php echo $class['id']; ?>" class="btn-icon btn-details" title="Ver detalhes">
                                            <i class="icon-details"></i>
                                            <span>Detalhes</span>
                                        </a>
                                        <a href="class_students.php?class_id=<?php echo $class['id']; ?>" class="btn-icon btn-students" title="Alunos da turma">
                                            <i class="icon-students"></i>
                                            <span>Alunos</span>
                                        </a>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="action" value="delete_class">
                                            <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="Excluir turma" onclick="return confirm('Tem certeza que deseja excluir esta turma?')">
                                                <i class="icon-delete"></i>
                                                <span>Excluir</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterClasses() {
            const input = document.getElementById('classSearch');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.class-card');

            cards.forEach(card => {
                const className = card.getAttribute('data-name');
                const schoolName = card.getAttribute('data-school');

                if (className.includes(filter) || schoolName.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Update count
            const visibleCards = document.querySelectorAll('.class-card[style=""]').length;
            document.querySelector('.class-count span').textContent =
                `${visibleCards} turma${visibleCards > 1 ? 's' : ''} encontrada${visibleCards > 1 ? 's' : ''}`;
        }
    </script>
</body>

</html>