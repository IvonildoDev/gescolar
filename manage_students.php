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
    // Handle student registration
    if (isset($_POST['action']) && $_POST['action'] == 'add_student') {
        $name = $_POST['name'];
        $birth_date = $_POST['birth_date'];
        $parent_name = $_POST['parent_name'] ?? null;
        $contact_phone = $_POST['contact_phone'] ?? null;
        $address = $_POST['address'] ?? null;

        // Insert new student
        $stmt = $pdo->prepare("
            INSERT INTO students (name, birth_date, parent_name, contact_phone, address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $birth_date, $parent_name, $contact_phone, $address]);
        $success = "Aluno cadastrado com sucesso!";
    }

    // Handle class enrollment
    if (isset($_POST['action']) && $_POST['action'] == 'enroll_student') {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        $enrollment_date = date('Y-m-d');

        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_enrollments WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$student_id, $class_id]);
        $already_enrolled = $stmt->fetch()['count'] > 0;

        if ($already_enrolled) {
            $error = "O aluno já está matriculado nesta turma.";
        } else {
            // Create enrollment
            $stmt = $pdo->prepare("INSERT INTO class_enrollments (student_id, class_id, enrollment_date) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $class_id, $enrollment_date]);
            $success = "Aluno matriculado com sucesso!";
        }
    }

    // Handle student deletion
    if (isset($_POST['action']) && $_POST['action'] == 'delete_student') {
        $student_id = $_POST['student_id'];

        // Check if student has enrollments
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_enrollments WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $has_enrollments = $stmt->fetch()['count'] > 0;

        if ($has_enrollments) {
            $error = "Não é possível excluir este aluno pois ele possui matrículas ativas.";
        } else {
            // Delete student
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $success = "Aluno excluído com sucesso!";
        }
    }

    // Handle student update
    if (isset($_POST['action']) && $_POST['action'] == 'edit_student') {
        $student_id = $_POST['student_id'];
        $name = $_POST['name'];
        $birth_date = $_POST['birth_date'];
        $parent_name = $_POST['parent_name'] ?? null;
        $contact_phone = $_POST['contact_phone'] ?? null;
        $address = $_POST['address'] ?? null;

        // Update student
        $stmt = $pdo->prepare("
            UPDATE students 
            SET name = ?, birth_date = ?, parent_name = ?, contact_phone = ?, address = ? 
            WHERE id = ?
        ");
        $stmt->execute([$name, $birth_date, $parent_name, $contact_phone, $address, $student_id]);
        $success = "Aluno atualizado com sucesso!";
    }
}

// Initialize edit mode variables
$edit_mode = false;
$student_to_edit = null;

// Check if we're in edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $student_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student_to_edit = $stmt->fetch();

    if ($student_to_edit) {
        $edit_mode = true;
    }
}

// Get all students
$stmt = $pdo->prepare("SELECT * FROM students ORDER BY name");
$stmt->execute();
$students = $stmt->fetchAll();

// Get all classes with school info for enrollment form
$stmt = $pdo->prepare("
    SELECT c.id, c.name, s.name as school_name 
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
    <title>Gerenciamento de Alunos - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="manager-dashboard">
    <div class="container">
        <header>
            <h1>Gerenciamento de Alunos</h1>
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
                <h2><?php echo $edit_mode ? 'Editar Aluno' : 'Cadastrar Novo Aluno'; ?></h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit_student' : 'add_student'; ?>">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="student_id" value="<?php echo $student_to_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name">Nome Completo:</label>
                        <input type="text" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($student_to_edit['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Data de Nascimento:</label>
                        <input type="date" id="birth_date" name="birth_date" value="<?php echo $edit_mode ? $student_to_edit['birth_date'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="parent_name">Nome do Responsável:</label>
                        <input type="text" id="parent_name" name="parent_name" value="<?php echo $edit_mode && $student_to_edit['parent_name'] ? htmlspecialchars($student_to_edit['parent_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Telefone de Contato:</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="<?php echo $edit_mode && $student_to_edit['contact_phone'] ? htmlspecialchars($student_to_edit['contact_phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Endereço:</label>
                        <input type="text" id="address" name="address" value="<?php echo $edit_mode && $student_to_edit['address'] ? htmlspecialchars($student_to_edit['address']) : ''; ?>">
                    </div>

                    <button type="submit" class="btn"><?php echo $edit_mode ? 'Atualizar Aluno' : 'Cadastrar Aluno'; ?></button>

                    <?php if ($edit_mode): ?>
                        <a href="manage_students.php" class="btn btn-cancel">Cancelar Edição</a>
                    <?php endif; ?>
                </form>

                <?php if (!$edit_mode && !empty($students) && !empty($classes)): ?>
                    <h2>Matricular Aluno em Turma</h2>
                    <form method="post" class="standard-form">
                        <input type="hidden" name="action" value="enroll_student">

                        <div class="form-group">
                            <label for="student_id">Aluno:</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">Selecione um aluno</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="class_id">Turma:</label>
                            <select id="class_id" name="class_id" required>
                                <option value="">Selecione uma turma</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['name']); ?> -
                                        <?php echo htmlspecialchars($class['school_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn">Matricular</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="right-panel">
                <div class="panel-header">
                    <h2>Alunos Cadastrados</h2>
                    <div class="panel-tools">
                        <div class="search-container">
                            <input type="text" id="studentSearch" placeholder="Buscar aluno..." onkeyup="filterStudents()">
                            <i class="search-icon">🔍</i>
                        </div>
                    </div>
                </div>

                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👨‍🎓</div>
                        <p>Nenhum aluno cadastrado.</p>
                        <p class="empty-hint">Utilize o formulário ao lado para adicionar seu primeiro aluno.</p>
                    </div>
                <?php else: ?>
                    <div class="student-count">
                        <span><?php echo count($students); ?> aluno<?php echo count($students) > 1 ? 's' : ''; ?> encontrado<?php echo count($students) > 1 ? 's' : ''; ?></span>
                    </div>

                    <div class="student-cards">
                        <?php foreach ($students as $student): ?>
                            <?php
                            // Calculate age
                            $birth_date = new DateTime($student['birth_date']);
                            $today = new DateTime();
                            $age = $birth_date->diff($today)->y;

                            // Get classes for this student
                            $stmt = $pdo->prepare("
                                    SELECT c.id, c.name, s.name as school_name
                                    FROM class_enrollments ce
                                    JOIN classes c ON ce.class_id = c.id
                                    JOIN schools s ON c.school_id = s.id
                                    WHERE ce.student_id = ?
                                ");
                            $stmt->execute([$student['id']]);
                            $student_classes = $stmt->fetchAll();
                            ?>
                            <div class="student-card" data-name="<?php echo strtolower(htmlspecialchars($student['name'])); ?>">
                                <div class="student-card-header">
                                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                                    <span class="age-badge"><?php echo $age; ?> anos</span>
                                </div>

                                <div class="student-card-body">
                                    <div class="student-info">
                                        <div class="info-row">
                                            <i class="calendar-icon">📅</i>
                                            <span>Nascimento: <?php echo date('d/m/Y', strtotime($student['birth_date'])); ?></span>
                                        </div>

                                        <?php if (isset($student['parent_name']) && !empty($student['parent_name'])): ?>
                                            <div class="info-row">
                                                <i class="parent-icon">👪</i>
                                                <span>Responsável: <?php echo htmlspecialchars($student['parent_name']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($student['contact_phone']) && !empty($student['contact_phone'])): ?>
                                            <div class="info-row">
                                                <i class="phone-icon">📞</i>
                                                <span>Contato: <?php echo htmlspecialchars($student['contact_phone']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($student['address']) && !empty($student['address'])): ?>
                                            <div class="info-row">
                                                <i class="address-icon">🏠</i>
                                                <span>Endereço: <?php echo htmlspecialchars($student['address']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="enrollment-info">
                                        <h4>
                                            <i class="class-icon">🏫</i>
                                            Turmas Matriculadas (<?php echo count($student_classes); ?>)
                                        </h4>

                                        <?php if (empty($student_classes)): ?>
                                            <p class="no-enrollments">Nenhuma matrícula</p>
                                        <?php else: ?>
                                            <ul class="enrollment-list">
                                                <?php foreach ($student_classes as $class): ?>
                                                    <li>
                                                        <a href="class_details.php?id=<?php echo $class['id']; ?>">
                                                            <?php echo htmlspecialchars($class['name']); ?> -
                                                            <?php echo htmlspecialchars($class['school_name']); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="student-card-footer">
                                    <div class="button-group">
                                        <a href="manage_students.php?edit=<?php echo $student['id']; ?>" class="btn-icon btn-edit" title="Editar aluno">
                                            <i class="icon-edit"></i>
                                            <span>Editar</span>
                                        </a>
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn-icon btn-details" title="Ver detalhes">
                                            <i class="icon-details"></i>
                                            <span>Detalhes</span>
                                        </a>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="action" value="delete_student">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" class="btn-icon btn-delete" title="Excluir aluno" onclick="return confirm('Tem certeza que deseja excluir este aluno?')">
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
        function filterStudents() {
            const input = document.getElementById('studentSearch');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.student-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const studentName = card.getAttribute('data-name');

                if (studentName.includes(filter)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Update count
            document.querySelector('.student-count span').textContent =
                `${visibleCount} aluno${visibleCount > 1 ? 's' : ''} encontrado${visibleCount > 1 ? 's' : ''}`;
        }
    </script>
</body>

</html>