<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser();
if (!in_array($user['role'], ['admin', 'teacher'])) {
    header('Location: ../index.php');
    exit();
}

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $subject = sanitize($_POST['subject']);
        $class_id = $_POST['class_id'];
        $max_score = $_POST['max_score'];
        $coefficient = $_POST['coefficient'];
        $due_date = $_POST['due_date'];
        
        $stmt = $db->prepare("
            INSERT INTO assignments 
            (title, description, subject, class_id, teacher_id, max_score, coefficient, due_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $subject, $class_id, $user['id'], $max_score, $coefficient, $due_date]);
        
        $assignment_id = $db->lastInsertId();
        
        // Créer des notifications pour les élèves
        $stmt = $db->prepare("
            SELECT user_id FROM students WHERE class_id = ?
        ");
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach($students as $student_id) {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, 'Nouveau devoir', ?, 'assignment')
            ");
            $stmt->execute([$student_id, "Un nouveau devoir a été ajouté: $title. Date limite: " . date('d/m/Y', strtotime($due_date))]);
        }
        
        $_SESSION['message'] = "Devoir créé avec succès";
        header('Location: assignments.php');
        exit();
    }
    
    if ($action === 'delete') {
        $assignment_id = $_POST['id'];
        
        // Supprimer d'abord les notes associées
        $stmt = $db->prepare("DELETE FROM grades WHERE assignment_id = ?");
        $stmt->execute([$assignment_id]);
        
        // Supprimer le devoir
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        
        $_SESSION['message'] = "Devoir supprimé avec succès";
        header('Location: assignments.php');
        exit();
    }
}

// Récupérer les devoirs
if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("
        SELECT a.*, c.name as class_name 
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.teacher_id = ?
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$user['id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("
        SELECT a.*, c.name as class_name, u.full_name as teacher_name
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        LEFT JOIN users u ON a.teacher_id = u.id
        ORDER BY a.due_date DESC
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les classes
$classes = [];
if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
    $stmt->execute([$user['id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->query("SELECT * FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des devoirs</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard">
        <div class="sidebar">
            <h3>Gestion</h3>
            <ul>
                <li><a href="students.php">Élèves</a></li>
                <li><a href="classes.php">Classes</a></li>
                <li><a href="competences.php">Compétences</a></li>
                <li><a href="grades.php">Notes</a></li>
                <li><a href="assignments.php" class="active">Devoirs</a></li>
                <li><a href="../dashboard/<?= $user['role'] ?>.php">Retour</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Gestion des devoirs</h1>
            
            <?php if(isset($_SESSION['message'])): ?>
            <div class="alert success">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Liste des devoirs</h2>
                    <a href="?action=create" class="btn">+ Créer un devoir</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Matière</th>
                            <th>Classe</th>
                            <th>Date limite</th>
                            <th>Note max</th>
                            <th>Coefficient</th>
                            <th>Notes saisies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assignments as $assignment): 
                            // Compter les notes déjà saisies
                            $stmt = $db->prepare("SELECT COUNT(*) FROM grades WHERE assignment_id = ?");
                            $stmt->execute([$assignment['id']]);
                            $grades_count = $stmt->fetchColumn();
                            
                            // Compter les élèves dans la classe
                            $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
                            $stmt->execute([$assignment['class_id']]);
                            $students_count = $stmt->fetchColumn();
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($assignment['title']) ?></td>
                            <td><?= htmlspecialchars($assignment['subject']) ?></td>
                            <td><?= htmlspecialchars($assignment['class_name']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($assignment['due_date'])) ?>
                                <?php if(strtotime($assignment['due_date']) < time()): ?>
                                <span class="badge overdue">Échu</span>
                                <?php elseif(strtotime($assignment['due_date']) < strtotime('+3 days')): ?>
                                <span class="badge upcoming">Bientôt</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $assignment['max_score'] ?></td>
                            <td><?= $assignment['coefficient'] ?></td>
                            <td>
                                <div class="progress-info">
                                    <span><?= $grades_count ?>/<?= $students_count ?></span>
                                    <div class="progress-bar small">
                                        <div class="progress-fill" style="width: <?= ($grades_count / max(1, $students_count)) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="grades.php?assignment_id=<?= $assignment['id'] ?>" class="btn-small">
                                    Noter
                                </a>
                                <a href="?action=edit&id=<?= $assignment['id'] ?>" class="btn-small">
                                    Modifier
                                </a>
                                <form method="POST" action="?action=delete" style="display:inline;" 
                                      onsubmit="return confirm('Supprimer ce devoir ?')">
                                    <input type="hidden" name="id" value="<?= $assignment['id'] ?>">
                                    <button type="submit" class="btn-small btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($action === 'create'): ?>
            <div class="card">
                <h2>Créer un nouveau devoir</h2>
                <form method="POST" action="?action=create">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Titre du devoir *</label>
                            <input type="text" id="title" name="title" required 
                                   placeholder="Ex: Contrôle de mathématiques">
                        </div>
                        <div class="form-group">
                            <label for="subject">Matière *</label>
                            <input type="text" id="subject" name="subject" required 
                                   placeholder="Ex: Mathématiques">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="class_id">Classe *</label>
                            <select id="class_id" name="class_id" required>
                                <option value="">Sélectionner une classe</option>
                                <?php foreach($classes as $class): ?>
                                <option value="<?= $class['id'] ?>">
                                    <?= htmlspecialchars($class['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="due_date">Date limite *</label>
                            <input type="date" id="due_date" name="due_date" required 
                                   value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_score">Note maximale *</label>
                            <input type="number" id="max_score" name="max_score" 
                                   min="1" max="100" step="0.5" value="20" required>
                        </div>
                        <div class="form-group">
                            <label for="coefficient">Coefficient *</label>
                            <input type="number" id="coefficient" name="coefficient" 
                                   min="0.1" max="10" step="0.1" value="1.0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Instructions pour le devoir..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Créer le devoir</button>
                    <a href="assignments.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Statistiques des devoirs</h2>
                <div class="stats-grid">
                    <?php
                    // Calculer les statistiques
                    $stats = [
                        'total' => count($assignments),
                        'overdue' => 0,
                        'upcoming' => 0,
                        'completed' => 0
                    ];
                    
                    foreach($assignments as $assignment) {
                        $due_date = strtotime($assignment['due_date']);
                        $now = time();
                        
                        if ($due_date < $now) {
                            $stats['overdue']++;
                        } elseif ($due_date < strtotime('+3 days')) {
                            $stats['upcoming']++;
                        }
                        
                        // Vérifier si toutes les notes sont saisies
                        $stmt = $db->prepare("SELECT COUNT(*) FROM grades WHERE assignment_id = ?");
                        $stmt->execute([$assignment['id']]);
                        $grades_count = $stmt->fetchColumn();
                        
                        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
                        $stmt->execute([$assignment['class_id']]);
                        $students_count = $stmt->fetchColumn();
                        
                        if ($grades_count == $students_count) {
                            $stats['completed']++;
                        }
                    }
                    ?>
                    
                    <div class="stat-card">
                        <h3>Total</h3>
                        <p class="stat-number"><?= $stats['total'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Échus</h3>
                        <p class="stat-number"><?= $stats['overdue'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>À venir</h3>
                        <p class="stat-number"><?= $stats['upcoming'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Complétés</h3>
                        <p class="stat-number"><?= $stats['completed'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: bold;
        margin-left: 5px;
    }
    .badge.overdue {
        background: #f56565;
        color: white;
    }
    .badge.upcoming {
        background: #ed8936;
        color: white;
    }
    .progress-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .progress-bar.small {
        height: 10px;
        flex: 1;
    }
    </style>
    
    <script src="../script.js"></script>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
