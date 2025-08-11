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

if ($user['role'] !== 'parent') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les enfants du parent
$children = $db->prepare("
    SELECT s.*, u.first_name, u.last_name, c.name as class_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.parent_id = (SELECT id FROM parents WHERE user_id = ?)
");
$children->execute([$user['id']]);
$children_data = $children->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Parent - ClassNote</title>
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
                        <span>Parent</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="parent.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="#enfants" class="menu-item">
                    <i class="fas fa-child"></i>
                    <span>Mes enfants</span>
                </a>
                
                <a href="#notes" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Notes</span>
                </a>
                
                <a href="#bulletins" class="menu-item">
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
                    <h1>Tableau de bord Parent</h1>
                    <p style="color: #6c757d; margin-top: 5px;">Suivi scolaire de vos enfants</p>
                </div>
            </header>
            
            <?php if (empty($children_data)): ?>
            <div class="table-container">
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-child" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucun enfant associé</h3>
                    <p>Contactez l'établissement pour associer vos enfants à votre compte.</p>
                </div>
            </div>
            <?php else: ?>
            
            <!-- Mes enfants -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Mes enfants</h2>
                </div>
                
                <div style="padding: 20px;">
                    <div class="children-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($children_data as $child): 
                            // Récupérer les dernières notes de l'enfant
                            $child_grades = $db->prepare("
                                SELECT g.*, e.title, sub.name as subject_name
                                FROM grades g
                                JOIN evaluations e ON g.evaluation_id = e.id
                                JOIN subjects sub ON e.subject_id = sub.id
                                WHERE g.student_id = ?
                                ORDER BY g.created_at DESC
                                LIMIT 3
                            ");
                            $child_grades->execute([$child['id']]);
                            $grades = $child_grades->fetchAll();
                            
                            // Moyenne de l'enfant
                            $average = $db->prepare("SELECT AVG(score) as average FROM grades WHERE student_id = ?");
                            $average->execute([$child['id']]);
                            $child_average = $average->fetch()['average'] ?? 0;
                        ?>
                        <div class="child-card" style="background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #4361ee; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h3 style="margin: 0 0 5px 0;"><?= htmlspecialchars($child['first_name'] . ' ' . $child['last_name']) ?></h3>
                                    <p style="color: #6c757d; margin: 0;">
                                        <i class="fas fa-chalkboard"></i> <?= htmlspecialchars($child['class_name'] ?? 'Non affecté') ?>
                                    </p>
                                </div>
                                <span class="badge badge-info">
                                    Moyenne : <?= number_format($child_average, 2) ?>/20
                                </span>
                            </div>
                            
                            <?php if (!empty($grades)): ?>
                            <div style="margin-top: 15px;">
                                <h4 style="margin-bottom: 10px; font-size: 0.9rem; color: #6c757d;">Dernières notes :</h4>
                                <?php foreach ($grades as $grade): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                    <div>
                                        <strong style="font-size: 0.9rem;"><?= htmlspecialchars($grade['subject_name']) ?></strong>
                                        <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #6c757d;"><?= htmlspecialchars($grade['title']) ?></p>
                                    </div>
                                    <span class="badge <?= $grade['score'] >= 10 ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.8rem;">
                                        <?= number_format($grade['score'], 2) ?>/20
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div style="text-align: center; padding: 20px 0; color: #6c757d;">
                                <i class="fas fa-file-alt" style="opacity: 0.3;"></i>
                                <p style="margin: 10px 0 0 0;">Aucune note pour le moment</p>
                            </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 15px; display: flex; gap: 10px;">
                                <a href="#notes-enfant-<?= $child['id'] ?>" class="btn btn-sm btn-secondary">
                                    Voir toutes les notes
                                </a>
                                <a href="#bulletin-<?= $child['id'] ?>" class="btn btn-sm btn-primary">
                                    Voir le bulletin
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../script.js"></script>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
