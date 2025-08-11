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

if ($user['role'] !== 'teacher') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les statistiques pour l'enseignant
$teacher_id = $user['id'];
$teacher_info = $db->prepare("SELECT t.* FROM teachers t WHERE t.user_id = ?");
$teacher_info->execute([$teacher_id]);
$teacher = $teacher_info->fetch();

// Récupérer les classes de l'enseignant
$classes = $db->prepare("
    SELECT c.*, COUNT(s.id) as student_count 
    FROM classes c 
    LEFT JOIN students s ON c.id = s.class_id 
    WHERE c.teacher_id = ? 
    GROUP BY c.id
");
$classes->execute([$teacher['id']]);
$teacher_classes = $classes->fetchAll();

// Récupérer les notes récentes
$recent_grades = $db->prepare("
    SELECT g.*, a.title as evaluation_title, s.user_id as student_user_id,
           u.first_name, u.last_name, sub.name as subject_name
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    JOIN students s ON g.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.teacher_id = ?
    ORDER BY g.created_at DESC
    LIMIT 10
");
$recent_grades->execute([$teacher['id']]);
$grades = $recent_grades->fetchAll();

// Statistiques
$stats = [
    'classes' => count($teacher_classes),
    'students' => 0,
    'grades' => 0,
    'average' => 0
];

foreach ($teacher_classes as $class) {
    $stats['students'] += $class['student_count'];
}

$grade_stats = $db->prepare("
    SELECT COUNT(*) as count, AVG(g.score) as average 
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    WHERE a.teacher_id = ?
");
$grade_stats->execute([$teacher['id']]);
$grade_data = $grade_stats->fetch();
$stats['grades'] = $grade_data['count'];
$stats['average'] = $grade_data['average'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Enseignant - ClassNote</title>
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
                        <span>Enseignant</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="teacher.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../modules/eleves.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Mes élèves</span>
                </a>
                
                <a href="../modules/classes.php" class="menu-item">
                    <i class="fas fa-chalkboard"></i>
                    <span>Mes classes</span>
                </a>
                
                <a href="../modules/notes.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Saisir des notes</span>
                </a>
                
                <a href="../modules/devoirs.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Devoirs</span>
                </a>
                
                <a href="../modules/competences.php" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Compétences</span>
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
            <!-- Header -->
            <header class="header">
                <div>
                    <h1>Tableau de bord Enseignant</h1>
                    <p style="color: #6c757d; margin-top: 5px;"><?= date('l d F Y') ?></p>
                </div>
                
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">2</span>
                    </button>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Classes</h3>
                        <div class="stat-number"><?= $stats['classes'] ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-chalkboard-teacher"></i> Vos classes
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f72585 0%, #b5179e 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Élèves</h3>
                        <div class="stat-number"><?= $stats['students'] ?></div>
                        <div class="stat-change">
                            Élèves total
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Notes saisies</h3>
                        <div class="stat-number"><?= $stats['grades'] ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> Ce trimestre
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f8961e 0%, #f3722c 100%);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Moyenne générale</h3>
                        <div class="stat-number"><?= number_format($stats['average'], 2) ?>/20</div>
                        <div class="stat-change">
                            Vos évaluations
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mes classes -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Mes classes</h2>
                    <a href="../modules/classes.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Voir toutes
                    </a>
                </div>
                
                <?php if (empty($teacher_classes)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-chalkboard" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune classe assignée</h3>
                    <p>Contactez l'administration pour vous assigner une classe.</p>
                </div>
                <?php else: ?>
                <div style="padding: 20px;">
                    <div class="classes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($teacher_classes as $class): ?>
                        <div class="class-card" style="background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #4361ee; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0;"><?= htmlspecialchars($class['name']) ?></h3>
                                <span class="badge badge-info"><?= $class['level'] ?></span>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 5px 0; color: #6c757d;">
                                    <i class="fas fa-users"></i> 
                                    <?= $class['student_count'] ?> élève(s)
                                </p>
                                <p style="margin: 5px 0; color: #6c757d;">
                                    <i class="fas fa-calendar"></i> 
                                    <?= $class['school_year'] ?>
                                </p>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <a href="../modules/eleves.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-secondary">
                                    Voir les élèves
                                </a>
                                <a href="../modules/notes.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-primary">
                                    Saisir des notes
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Dernières notes saisies -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Dernières notes saisies</h2>
                    <a href="../modules/notes.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter une note
                    </a>
                </div>
                
                <?php if (empty($grades)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune note saisie</h3>
                    <p>Commencez à saisir des notes pour vos élèves.</p>
                    <a href="../modules/notes.php" class="btn btn-primary">Saisir ma première note</a>
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
                            <th>Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($grade['evaluation_title']) ?></td>
                            <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                            <td>
                                <span class="badge <?= $grade['score'] >= 10 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= number_format($grade['score'], 2) ?>/20
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($grade['created_at'])) ?></td>
                            <td>
                                <?php if ($grade['appreciation']): ?>
                                <small><?= htmlspecialchars(substr($grade['appreciation'], 0, 50)) ?>...</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Actions rapides -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Actions rapides</h2>
                </div>
                
                <div style="padding: 20px;">
                    <div class="quick-actions" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                        <a href="../modules/notes.php?action=add" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                            <i class="fas fa-edit" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            Saisir une note
                        </a>
                        
                        <a href="../modules/devoirs.php?action=create" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                            <i class="fas fa-book" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            Créer un devoir
                        </a>
                        
                        <a href="../modules/competences.php?action=evaluate" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                            <i class="fas fa-tasks" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            Évaluer compétences
                        </a>
                        
                        <a href="../modules/bulletins.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                            <i class="fas fa-file-pdf" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            Générer bulletins
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Devoirs à corriger -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Devoirs à corriger</h2>
                    <a href="../modules/devoirs.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> Voir tous
                    </a>
                </div>
                
                <div style="padding: 20px; text-align: center; color: #6c757d;">
                    <i class="fas fa-clipboard-check" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Fonctionnalité en développement</h3>
                    <p>Cette section affichera bientôt les devoirs à corriger.</p>
                </div>
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
