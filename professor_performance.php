<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'manager') {
    header("Location: login.php");
    exit;
}

// Get all professors with their performance data
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.specialty,
           COUNT(DISTINCT pc.class_id) as total_classes,
           COUNT(DISTINCT a.date) as classes_given
    FROM professors p
    LEFT JOIN professor_classes pc ON p.id = pc.professor_id
    LEFT JOIN attendance a ON pc.class_id = a.class_id
    GROUP BY p.id
    ORDER BY p.name
");
$stmt->execute();
$professors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desempenho dos Professores - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="performance-page">
    <div class="container">
        <header>
            <h1>Desempenho dos Professores</h1>
            <div class="navigation">
                <a href="manager_dashboard.php" class="back-btn">Voltar para Dashboard</a>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>

        <main>
            <h2>Desempenho por Professor</h2>

            <?php if (empty($professors)): ?>
                <p>Nenhum professor cadastrado.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Professor</th>
                            <th>Especialidade</th>
                            <th>Total de Turmas</th>
                            <th>Aulas Dadas</th>
                            <th>% de Aulas Dadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($professors as $professor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($professor['name']); ?></td>
                                <td><?php echo htmlspecialchars($professor['specialty']); ?></td>
                                <td><?php echo $professor['total_classes']; ?></td>
                                <td><?php echo $professor['classes_given']; ?></td>
                                <td>
                                    <?php
                                    $totalExpectedClasses = $professor['total_classes'] * 10; // Assuming 10 classes per course
                                    $percentage = ($totalExpectedClasses > 0)
                                        ? ($professor['classes_given'] / $totalExpectedClasses) * 100
                                        : 0;
                                    $roundedPercentage = round($percentage);

                                    // Determine the CSS class for the progress bar
                                    $progressClass = 'progress-low';
                                    if ($roundedPercentage >= 70) {
                                        $progressClass = 'progress-high';
                                    } else if ($roundedPercentage >= 40) {
                                        $progressClass = 'progress-medium';
                                    }

                                    echo $roundedPercentage . '%';
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress <?php echo $progressClass; ?>" style="width: <?php echo $roundedPercentage; ?>%;">
                                            <?php echo $roundedPercentage; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>Detalhes por Professor</h3>
                <?php foreach ($professors as $professor): ?>
                    <?php if ($professor['total_classes'] > 0): ?>
                        <div class="professor-details">
                            <h4><?php echo htmlspecialchars($professor['name']); ?> - <?php echo htmlspecialchars($professor['specialty']); ?></h4>

                            <?php
                            // Get classes taught by this professor
                            $stmt = $pdo->prepare("
                                    SELECT c.id, c.name, s.name as school_name,
                                           COUNT(DISTINCT a.date) as classes_given
                                    FROM classes c
                                    JOIN schools s ON c.school_id = s.id
                                    JOIN professor_classes pc ON c.id = pc.class_id
                                    LEFT JOIN attendance a ON c.id = a.class_id
                                    WHERE pc.professor_id = ?
                                    GROUP BY c.id
                                    ORDER BY s.name, c.name
                                ");
                            $stmt->execute([$professor['id']]);
                            $classes = $stmt->fetchAll();
                            ?>

                            <table class="sub-table">
                                <thead>
                                    <tr>
                                        <th>Turma</th>
                                        <th>Escola</th>
                                        <th>Aulas Dadas</th>
                                        <th>% Concluído</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['school_name']); ?></td>
                                            <td><?php echo $class['classes_given']; ?></td>
                                            <td>
                                                <?php
                                                $expectedClasses = 10; // Assuming 10 classes expected per course
                                                $classPercentage = ($class['classes_given'] / $expectedClasses) * 100;
                                                $roundedClassPercentage = round($classPercentage);

                                                // Determine the CSS class for the progress bar
                                                $classProgressClass = 'progress-low';
                                                if ($roundedClassPercentage >= 70) {
                                                    $classProgressClass = 'progress-high';
                                                } else if ($roundedClassPercentage >= 40) {
                                                    $classProgressClass = 'progress-medium';
                                                }
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress <?php echo $classProgressClass; ?>" style="width: <?php echo $roundedClassPercentage; ?>%;">
                                                        <?php echo $roundedClassPercentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>