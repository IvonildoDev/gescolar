<?php
session_start();
require_once 'config.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'manager') {
        header("Location: manager_dashboard.php");
    } elseif ($_SESSION['user_type'] == 'professor') {
        header("Location: professor_dashboard.php");
    } elseif ($_SESSION['user_type'] == 'student') {
        header("Location: student_dashboard.php");
    }
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verify username exists in users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Check if user exists and password matches
    if ($user) {
        // For simplicity, we're using plain text password comparison
        // In a production environment, you should use password_hash and password_verify
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];

            // Update last login time
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Redirect based on user type
            if ($user['user_type'] == 'manager') {
                header("Location: manager_dashboard.php");
            } elseif ($user['user_type'] == 'professor') {
                header("Location: professor_dashboard.php");
            } elseif ($user['user_type'] == 'student') {
                header("Location: student_dashboard.php");
            }
            exit;
        } else {
            $error = "Senha incorreta.";
        }
    } else {
        $error = "Usuário não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login - Sistema de Gestão Escolar</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <div class="text-logo"><span>SGE</span></div>
        </div>

        <div class="login-header">
            <h2>Bem-vindo</h2>
            <p class="system-title">Sistema de Gestão Escolar</p>
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

        <div class="login-footer">
            <p>© <?php echo date('Y'); ?> Sistema de Gestão Escolar Desenvolvido Por: Ivonildo Lima</p>
        </div>
    </div>
</body>

</html>