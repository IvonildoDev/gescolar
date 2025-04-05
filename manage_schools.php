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
    // Handle school registration
    if (isset($_POST['action']) && $_POST['action'] == 'add_school') {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];

        // Insert new school
        $stmt = $pdo->prepare("INSERT INTO schools (name, address, phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $address, $phone]);
        $success = "Escola cadastrada com sucesso!";
    }

    // Handle school deletion
    if (isset($_POST['action']) && $_POST['action'] == 'delete_school') {
        $school_id = $_POST['school_id'];

        // Check if school has classes
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            $error = "Não é possível excluir esta escola pois existem turmas associadas a ela.";
        } else {
            // Delete school-professor associations first
            $stmt = $pdo->prepare("DELETE FROM professor_schools WHERE school_id = ?");
            $stmt->execute([$school_id]);

            // Delete school
            $stmt = $pdo->prepare("DELETE FROM schools WHERE id = ?");
            $stmt->execute([$school_id]);
            $success = "Escola excluída com sucesso!";
        }
    }

    // Handle school update
    if (isset($_POST['action']) && $_POST['action'] == 'edit_school') {
        $school_id = $_POST['school_id'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];

        // Update school
        $stmt = $pdo->prepare("UPDATE schools SET name = ?, address = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $address, $phone, $school_id]);
        $success = "Escola atualizada com sucesso!";
    }
}

// Initialize edit mode variables
$edit_mode = false;
$school_to_edit = null;

// Check if we're in edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $school_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school_to_edit = $stmt->fetch();

    if ($school_to_edit) {
        $edit_mode = true;
    }
}

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
    <title>Gerenciamento de Escolas - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="manager-dashboard">
    <div class="container">
        <header>
            <h1>Gerenciamento de Escolas</h1>
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
                <h2><?php echo $edit_mode ? 'Editar Escola' : 'Cadastrar Nova Escola'; ?></h2>
                <form method="post" class="standard-form">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit_school' : 'add_school'; ?>">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="school_id" value="<?php echo $school_to_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name">Nome da Escola:</label>
                        <input type="text" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($school_to_edit['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Endereço:</label>
                        <input type="text" id="address" name="address" value="<?php echo $edit_mode ? htmlspecialchars($school_to_edit['address']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefone:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo $edit_mode ? htmlspecialchars($school_to_edit['phone']) : ''; ?>" required>
                    </div>

                    <button type="submit" class="btn"><?php echo $edit_mode ? 'Atualizar Escola' : 'Cadastrar Escola'; ?></button>

                    <?php if ($edit_mode): ?>
                        <a href="manage_schools.php" class="btn btn-cancel">Cancelar Edição</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="right-panel">
                <h2>Escolas Cadastradas</h2>
                <?php if (empty($schools)): ?>
                    <p>Nenhuma escola cadastrada.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Endereço</th>
                                <th>Telefone</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schools as $school): ?>
                                <tr>
                                    <td><?php echo $school['id']; ?></td>
                                    <td><?php echo htmlspecialchars($school['name']); ?></td>
                                    <td><?php echo htmlspecialchars($school['address']); ?></td>
                                    <td><?php echo htmlspecialchars($school['phone']); ?></td>
                                    <td>
                                        <a href="manage_schools.php?edit=<?php echo $school['id']; ?>" class="btn-small">Editar</a>
                                        <form method="post" style="display: inline">
                                            <input type="hidden" name="action" value="delete_school">
                                            <input type="hidden" name="school_id" value="<?php echo $school['id']; ?>">
                                            <button type="submit" class="btn-small btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta escola?')">Excluir</button>
                                        </form>
                                        <a href="school_details.php?id=<?php echo $school['id']; ?>" class="btn-small">Detalhes</a>
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