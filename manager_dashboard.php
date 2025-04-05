<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'manager') {
    header("Location: login.php");
    exit;
}

// Get the manager's name from session
$manager_name = $_SESSION['user_name'] ?? 'Administrador';

// Get current date and time
$current_date = date('d/m/Y');
$current_day = date('l'); // Day of the week

// Get stats for dashboard
// Count schools
$stmt = $pdo->query("SELECT COUNT(*) as count FROM schools");
$schools_count = $stmt->fetch()['count'];

// Count classes
$stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
$classes_count = $stmt->fetch()['count'];

// Count students
$stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
$students_count = $stmt->fetch()['count'];

// Count professors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM professors");
$professors_count = $stmt->fetch()['count'];

// Recent enrollments
$stmt = $pdo->query("
    SELECT s.name as student_name, c.name as class_name, sc.name as school_name, ce.enrollment_date
    FROM class_enrollments ce
    JOIN students s ON ce.student_id = s.id
    JOIN classes c ON ce.class_id = c.id
    JOIN schools sc ON c.school_id = sc.id
    ORDER BY ce.enrollment_date DESC
    LIMIT 6
");
$recent_enrollments = $stmt->fetchAll();

// Get students per school (for chart)
$stmt = $pdo->query("
    SELECT s.name, COUNT(ce.student_id) as student_count
    FROM schools s
    LEFT JOIN classes c ON s.id = c.school_id
    LEFT JOIN class_enrollments ce ON c.id = ce.class_id
    GROUP BY s.id
    ORDER BY student_count DESC
    LIMIT 5
");
$students_per_school = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Gestor - Sistema Escolar</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="manager-dashboard">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="app-logo">SGE</div>
                <h3>Sistema de<br>Gestão Escolar</h3>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="manager_dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_schools.php">
                            <i class="fas fa-school"></i>
                            <span>Escolas</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_professors.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Professores</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_classes.php">
                            <i class="fas fa-users-class"></i>
                            <span>Turmas</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_students.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Alunos</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Relatórios</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-button">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="topbar">
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="date-display">
                    <i class="far fa-calendar-alt"></i>
                    <span><?php echo $current_day . ', ' . $current_date; ?></span>
                </div>

                <div class="user-info">
                    <div class="user-greeting">
                        <span>Bem-vindo,</span>
                        <h4><?php echo htmlspecialchars($manager_name); ?></h4>
                    </div>
                    <div class="user-avatar">
                        <?php echo substr($manager_name, 0, 1); ?>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <div class="welcome-card">
                        <div class="welcome-text">
                            <h1>Olá, <?php echo htmlspecialchars($manager_name); ?>!</h1>
                            <p>Confira as estatísticas e atividades recentes do sistema escolar.</p>
                        </div>
                        <div class="welcome-actions">
                            <a href="reports.php" class="welcome-btn">
                                <i class="fas fa-file-alt"></i>
                                Ver Relatórios
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Stat Cards -->
                <section class="stat-cards">
                    <div class="stat-card schools">
                        <div class="stat-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $schools_count; ?></h3>
                            <span>Escolas</span>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, $schools_count * 5); ?>%"></div>
                        </div>
                        <a href="manage_schools.php" class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="stat-card professors">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $professors_count; ?></h3>
                            <span>Professores</span>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, $professors_count * 2); ?>%"></div>
                        </div>
                        <a href="manage_professors.php" class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="stat-card classes">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $classes_count; ?></h3>
                            <span>Turmas</span>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, $classes_count * 2); ?>%"></div>
                        </div>
                        <a href="manage_classes.php" class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="stat-card students">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $students_count; ?></h3>
                            <span>Alunos</span>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, $students_count * 0.5); ?>%"></div>
                        </div>
                        <a href="manage_students.php" class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </section>

                <!-- Charts and Recent Data -->
                <section class="dashboard-widgets">
                    <!-- Students Per School Chart -->
                    <div class="widget chart-widget">
                        <div class="widget-header">
                            <h3>Alunos por Escola</h3>
                            <div class="widget-actions">
                                <button class="widget-action refresh">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="widget-action expand">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="widget-body">
                            <canvas id="schoolChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Enrollments -->
                    <div class="widget enrollments-widget">
                        <div class="widget-header">
                            <h3>Matrículas Recentes</h3>
                            <a href="all_enrollments.php" class="view-all">Ver todas</a>
                        </div>
                        <div class="widget-body">
                            <?php if (empty($recent_enrollments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-user-plus empty-icon"></i>
                                    <p>Nenhuma matrícula recente.</p>
                                </div>
                            <?php else: ?>
                                <div class="enrollment-list">
                                    <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <div class="enrollment-item">
                                            <div class="enrollment-student">
                                                <div class="student-avatar">
                                                    <?php echo substr($enrollment['student_name'], 0, 1); ?>
                                                </div>
                                                <div class="student-details">
                                                    <h4><?php echo htmlspecialchars($enrollment['student_name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($enrollment['class_name']); ?> - <?php echo htmlspecialchars($enrollment['school_name']); ?></p>
                                                </div>
                                            </div>
                                            <div class="enrollment-date">
                                                <span><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Quick Actions -->
                <section class="quick-actions-section">
                    <h3>Ações Rápidas</h3>
                    <div class="quick-actions-grid">
                        <a href="add_student.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4>Adicionar Aluno</h4>
                            <p>Cadastre um novo aluno no sistema</p>
                        </a>

                        <a href="add_class.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h4>Adicionar Turma</h4>
                            <p>Crie uma nova turma para uma escola</p>
                        </a>

                        <a href="add_professor.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h4>Adicionar Professor</h4>
                            <p>Registre um novo professor no sistema</p>
                        </a>

                        <a href="reports.php" class="quick-action-card">
                            <div class="action-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h4>Gerar Relatórios</h4>
                            <p>Crie relatórios e análises estatísticas</p>
                        </a>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            // Chart for schools/students
            const schoolCtx = document.getElementById('schoolChart').getContext('2d');

            const schoolChart = new Chart(schoolCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php foreach ($students_per_school as $school): ?> '<?php echo addslashes($school['name']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Quantidade de Alunos',
                        data: [
                            <?php foreach ($students_per_school as $school): ?>
                                <?php echo $school['student_count']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(255, 99, 132, 0.6)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const dashboardContainer = document.querySelector('.dashboard-container');

            sidebarToggle.addEventListener('click', function() {
                dashboardContainer.classList.toggle('sidebar-collapsed');
            });
        });
    </script>
</body>

</html>