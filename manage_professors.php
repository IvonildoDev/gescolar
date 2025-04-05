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
    // Handle professor registration
    if (isset($_POST['action']) && $_POST['action'] == 'add_professor') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = $_POST['password']; // In production, use password_hash
        $specialty = $_POST['specialty'];

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM professors WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            $error = "Nome de usuário já existe!";
        } else {
            // Insert new professor
            $stmt = $pdo->prepare("INSERT INTO professors (name, username, password, specialty) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $password, $specialty]);
            $success = "Professor cadastrado com sucesso!";
        }
    }
    // Handle professor-school assignment
    else if (isset($_POST['action']) && $_POST['action'] == 'assign_school') {
        $professor_id = $_POST['professor_id'];
        $school_id = $_POST['school_id'];

        // Check if assignment already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM professor_schools WHERE professor_id = ? AND school_id = ?");
        $stmt->execute([$professor_id, $school_id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            $error = "Professor já está vinculado a esta escola!";
        } else {
            // Create assignment
            $stmt = $pdo->prepare("INSERT INTO professor_schools (professor_id, school_id) VALUES (?, ?)");
            $stmt->execute([$professor_id, $school_id]);
            $success = "Professor vinculado à escola com sucesso!";
        }
    }
}

// Get all professors
$stmt = $pdo->prepare("SELECT * FROM professors ORDER BY name");
$stmt->execute();
$professors = $stmt->fetchAll();

// Get all schools
$stmt = $pdo->prepare("SELECT * FROM schools ORDER BY name");
$stmt->execute();
$schools = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Professores - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="manage-professors">
    <div class="container">
        <header>
            <h1>Gerenciamento de Professores</h1>
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
                <h2>Cadastrar Novo Professor</h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="add_professor">

                    <div class="form-group">
                        <label for="name">Nome Completo:</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Nome de Usuário:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="specialty">Especialidade/Matéria:</label>
                        <input type="text" id="specialty" name="specialty" required>
                    </div>

                    <button type="submit" class="btn">Cadastrar Professor</button>
                </form>

                <h2>Vincular Professor a Escola</h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="assign_school">

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
                        <label for="school_id">Escola:</label>
                        <select id="school_id" name="school_id" required>
                            <option value="">Selecione uma escola</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn">Vincular</button>
                </form>
            </div>

            <div class="right-panel">
                <h2>Professores Cadastrados</h2>
                <?php if (empty($professors)): ?>
                    <p>Nenhum professor cadastrado.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Usuário</th>
                                <th>Especialidade</th>
                                <th>Escolas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professors as $professor): ?>
                                <tr>
                                    <td><?php echo $professor['id']; ?></td>
                                    <td><?php echo htmlspecialchars($professor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($professor['username']); ?></td>
                                    <td><?php echo htmlspecialchars($professor['specialty']); ?></td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("
                                                SELECT s.name FROM schools s
                                                JOIN professor_schools ps ON s.id = ps.school_id
                                                WHERE ps.professor_id = ?
                                            ");
                                        $stmt->execute([$professor['id']]);
                                        $professorSchools = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                        if (empty($professorSchools)) {
                                            echo "Nenhuma escola";
                                        } else {
                                            echo implode(", ", array_map('htmlspecialchars', $professorSchools));
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_professor.php?id=<?php echo $professor['id']; ?>" class="btn-small">Editar</a>
                                        <a href="professor_classes.php?id=<?php echo $professor['id']; ?>" class="btn-small">Ver Aulas</a>
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