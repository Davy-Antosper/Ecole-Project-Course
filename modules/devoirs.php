<?php
require_once '../config.php';

// Vérifier l'authentification
if (!checkAuth()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Seuls admin et enseignants peuvent accéder
$user = getCurrentUser();
if (!in_array($user['role'], ['admin', 'teacher'])) {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB(); // CHANGÉ
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des devoirs - ClassNote</title>
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
                        <span><?= ucfirst($user['role']) ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="../dashboard/<?= $user['role'] ?>.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="eleves.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Élèves</span>
                </a>
                
                <a href="devoirs.php" class="menu-item active">
                    <i class="fas fa-book"></i>
                    <span>Devoirs</span>
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
                    <h1>Gestion des devoirs</h1>
                    <p style="color: #6c757d; margin-top: 5px;">Créez et gérez les devoirs</p>
                </div>
                
                <div class="header-actions">
                    <a href="?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouveau devoir
                    </a>
                </div>
            </header>
            
            <div class="table-container">
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-book" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Fonctionnalité en développement</h3>
                    <p>Cette section permettra bientôt de créer et gérer les devoirs.</p>
                    
                    <div style="margin-top: 30px;">
                        <a href="notes.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Saisir des notes
                        </a>
                    </div>
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
