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

$db = connectDB(); // CHANGÉ: connectDB() au lieu de getDB()
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = cleanInput($_POST['name']);
        $level = cleanInput($_POST['level']);
        $school_year = cleanInput($_POST['school_year']);
        $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
        
        $stmt = $db->prepare("
            INSERT INTO classes (name, level, school_year, teacher_id) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $level, $school_year, $teacher_id])) {
            $_SESSION['success'] = "Classe créée avec succès";
            header('Location: classes.php');
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la création";
        }
    }
}

// Récupérer les classes
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$teacher_filter = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : '';

$sql = "
    SELECT 
        c.*,
        CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
        COUNT(DISTINCT s.id) as student_count
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN students s ON c.id = s.class_id
    WHERE 1=1
";

$params = [];

if ($user['role'] === 'teacher') {
    // Enseignant ne voit que ses classes
    $sql .= " AND c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)";
    $params[] = $user['id'];
}

if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.level LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($teacher_filter && $user['role'] === 'admin') {
    $sql .= " AND c.teacher_id = ?";
    $params[] = $teacher_filter;
}

$sql .= " GROUP BY c.id ORDER BY c.name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$classes = $stmt->fetchAll();

// Récupérer les enseignants pour les formulaires
$teachers = [];
if ($user['role'] === 'admin') {
    $teachers_stmt = $db->query("
        SELECT t.id, u.first_name, u.last_name 
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        ORDER BY u.last_name
    ");
    $teachers = $teachers_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des classes - ClassNote</title>
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
                
                <a href="classes.php" class="menu-item active">
                    <i class="fas fa-chalkboard"></i>
                    <span>Classes</span>
                </a>
                
                <a href="notes.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Notes</span>
                </a>
                
                <a href="devoirs.php" class="menu-item">
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
                    <h1>Gestion des classes</h1>
                    <p style="color: #6c757d; margin-top: 5px;"><?= count($classes) ?> classe(s)</p>
                </div>
                
                <div class="header-actions">
                    <a href="?action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle classe
                    </a>
                </div>
            </header>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
            <!-- Liste des classes -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Liste des classes</h2>
                    
                    <form method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Rechercher..." 
                               value="<?= htmlspecialchars($search) ?>" 
                               style="padding: 8px 15px; border-radius: 20px; border: 1px solid #e9ecef;">
                        
                        <?php if ($user['role'] === 'admin' && !empty($teachers)): ?>
                        <select name="teacher_id" style="padding: 8px 15px; border-radius: 20px; border: 1px solid #e9ecef;">
                            <option value="">Tous les enseignants</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>" <?= $teacher_filter == $teacher['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-sm">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                        
                        <?php if ($search || $teacher_filter): ?>
                        <a href="classes.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (empty($classes)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-chalkboard" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune classe</h3>
                    <p>Commencez par créer votre première classe.</p>
                    <a href="?action=create" class="btn btn-primary">Créer une classe</a>
                </div>
                <?php else: ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Niveau</th>
                            <th>Année scolaire</th>
                            <th>Enseignant</th>
                            <th>Élèves</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($class['name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($class['level']) ?></td>
                            <td><?= htmlspecialchars($class['school_year']) ?></td>
                            <td>
                                <?php if ($class['teacher_name']): ?>
                                <?= htmlspecialchars($class['teacher_name']) ?>
                                <?php else: ?>
                                <em style="color: #6c757d;">Non assigné</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $class['student_count'] ?></span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="eleves.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-users"></i> Élèves
                                    </a>
                                    <a href="notes.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Noter
                                    </a>
                                    <?php if ($user['role'] === 'admin'): ?>
                                    <a href="?action=edit&id=<?= $class['id'] ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <?php elseif ($action === 'create'): ?>
            <!-- Formulaire de création -->
            <div class="form-card">
                <div class="form-title">
                    <h2>Créer une nouvelle classe</h2>
                    <p>Remplissez les informations de la classe</p>
                </div>
                
                <form method="POST" action="?action=create">
                    <div class="form-group">
                        <label for="name">Nom de la classe *</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="Ex: CM1 A, 6ème B">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="level">Niveau *</label>
                            <select id="level" name="level" required>
                                <option value="">Sélectionner un niveau</option>
                                <option value="CP">CP</option>
                                <option value="CE1">CE1</option>
                                <option value="CE2">CE2</option>
                                <option value="CM1">CM1</option>
                                <option value="CM2">CM2</option>
                                <option value="6ème">6ème</option>
                                <option value="5ème">5ème</option>
                                <option value="4ème">4ème</option>
                                <option value="3ème">3ème</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="school_year">Année scolaire *</label>
                            <input type="text" id="school_year" name="school_year" required 
                                   value="2024-2025" placeholder="2024-2025">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="teacher_id">Enseignant responsable</label>
                        <select id="teacher_id" name="teacher_id">
                            <option value="">Non assigné</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>">
                                <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Laissez vide si l'enseignant n'est pas encore connu</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Créer la classe
                        </button>
                        <a href="classes.php" class="btn btn-secondary">
                            Annuler
                        </a>
                    </div>
                </form>
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
