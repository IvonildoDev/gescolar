<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $loginSuccess = false;

    // First check if it's a professor
    $stmt = $pdo->prepare("SELECT * FROM professors WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $password === $user['password']) { // In production, use password_hash
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = 'professor';
        $_SESSION['name'] = $user['name'];
        header("Location: dashboard.php");
        exit;
    }

    // If not found in professors, check managers
    $stmt = $pdo->prepare("SELECT * FROM managers WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $password === $user['password']) { // In production, use password_hash
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = 'manager';
        $_SESSION['name'] = $user['name'];
        header("Location: manager_dashboard.php");
        exit;
    }

    // If we get here, login failed
    $error = "Usuário ou senha inválidos!";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login - Sistema Escolar</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Sistema Escolar</h2>
            <p>Entre com suas credenciais</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="" class="login-form">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Entrar</button>
        </form>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>