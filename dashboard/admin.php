<?php
require_once '../config.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Vérifier le rôle
$user = getCurrentUser();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

if ($user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les statistiques
$stats = [
    'students' => $db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'teachers' => $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'classes' => $db->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
    'parents' => $db->query("SELECT COUNT(*) FROM parents")->fetchColumn(),
    'grades' => $db->query("SELECT COUNT(*) FROM grades")->fetchColumn()
];

// Récupérer les dernières activités
$activities = $db->query("
    SELECT 
        g.*,
        u.first_name,
        u.last_name,
        a.title as evaluation_title,
        sub.name as subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN assignments a ON g.assignment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    ORDER BY g.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - ClassNote</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-book-open"></i>
                    <h2>ClassNote</h2>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <span>Administrateur</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="admin.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../modules/eleves.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Gestion élèves</span>
                </a>
                
                <a href="../modules/classes.php" class="menu-item">
                    <i class="fas fa-chalkboard"></i>
                    <span>Gestion classes</span>
                </a>
                
                <a href="../modules/competences.php" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Compétences</span>
                </a>
                
                <a href="../modules/notes.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Notes</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../modules/bulletins.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Bulletins</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <div>
                    <h1>Tableau de bord Administrateur</h1>
                    <p style="color: #6c757d; margin-top: 5px;"><?= date('l d F Y') ?></p>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Élèves</h3>
                        <div class="stat-number"><?= $stats['students'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f72585 0%, #b5179e 100%);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Enseignants</h3>
                        <div class="stat-number"><?= $stats['teachers'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Classes</h3>
                        <div class="stat-number"><?= $stats['classes'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f8961e 0%, #f3722c 100%);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Notes</h3>
                        <div class="stat-number"><?= $stats['grades'] ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Dernières activités -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Dernières activités</h2>
                </div>
                
                <?php if (empty($activities)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune activité récente</h3>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Évaluation</th>
                            <th>Matière</th>
                            <th>Note</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></td>
                            <td><?= htmlspecialchars($activity['evaluation_title']) ?></td>
                            <td><?= htmlspecialchars($activity['subject_name']) ?></td>
                            <td><?= number_format($activity['score'], 2) ?>/20</td>
                            <td><?= date('d/m/Y', strtotime($activity['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Actions rapides -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                <a href="../modules/eleves.php?action=create" class="btn btn-primary" style="padding: 20px; text-align: center;">
                    <i class="fas fa-user-plus" style="font-size: 32px; margin-bottom: 10px;"></i><br>
                    Ajouter un élève
                </a>
                
                <a href="../modules/classes.php?action=create" class="btn btn-primary" style="padding: 20px; text-align: center;">
                    <i class="fas fa-chalkboard" style="font-size: 32px; margin-bottom: 10px;"></i><br>
                    Créer une classe
                </a>
                
                <a href="../modules/competences.php?action=create" class="btn btn-primary" style="padding: 20px; text-align: center;">
                    <i class="fas fa-tasks" style="font-size: 32px; margin-bottom: 10px;"></i><br>
                    Gérer compétences
                </a>
            </div>
        </div>
    </div>
    
    <script src="../script.js"></script>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
