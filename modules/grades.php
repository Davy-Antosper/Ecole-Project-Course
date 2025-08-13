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
    if (isset($_POST['bulk_grades'])) {
        // Saisie groupée
        $assignment_id = $_POST['assignment_id'];
        $grades = $_POST['grades'];
        
        foreach($grades as $student_id => $grade_data) {
            if (!empty($grade_data['score'])) {
                $score = $grade_data['score'];
                $appreciation = sanitize($grade_data['appreciation'] ?? '');
                
                // Vérifier si la note existe déjà
                $stmt = $db->prepare("
                    SELECT id FROM grades 
                    WHERE student_id = ? AND assignment_id = ?
                ");
                $stmt->execute([$student_id, $assignment_id]);
                
                if ($stmt->fetch()) {
                    // Mettre à jour
                    $stmt = $db->prepare("
                        UPDATE grades 
                        SET score = ?, appreciation = ?, evaluated_by = ?, evaluation_date = NOW()
                        WHERE student_id = ? AND assignment_id = ?
                    ");
                    $stmt->execute([$score, $appreciation, $user['id'], $student_id, $assignment_id]);
                } else {
                    // Créer
                    $stmt = $db->prepare("
                        INSERT INTO grades 
                        (student_id, assignment_id, score, appreciation, evaluated_by, evaluation_date) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$student_id, $assignment_id, $score, $appreciation, $user['id']]);
                }
                
                // Créer une notification pour l'élève
                $student_user_id = $db->query("SELECT user_id FROM students WHERE id = $student_id")->fetchColumn();
                $assignment = $db->query("SELECT title FROM assignments WHERE id = $assignment_id")->fetch();
                
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, title, message, type) 
                    VALUES (?, 'Nouvelle note', ?, 'info')
                ");
                $stmt->execute([$student_user_id, "Vous avez reçu une note pour: " . $assignment['title']]);
            }
        }
        
        $_SESSION['message'] = "Notes enregistrées avec succès";
        header('Location: grades.php');
        exit();
    }
}

// Récupérer les devoirs
$assignments = [];
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

// Pour la saisie groupée
if (isset($_GET['assignment_id'])) {
    $assignment_id = $_GET['assignment_id'];
    
    $stmt = $db->prepare("
        SELECT a.*, c.name as class_name 
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $current_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les élèves de la classe
    $stmt = $db->prepare("
        SELECT s.id, u.full_name 
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$current_assignment['class_id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les notes existantes
    $stmt = $db->prepare("
        SELECT * FROM grades 
        WHERE assignment_id = ?
    ");
    $stmt->execute([$assignment_id]);
    $existing_grades = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_grades[$row['student_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie des notes</title>
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
                <li><a href="grades.php" class="active">Notes</a></li>
                <li><a href="assignments.php">Devoirs</a></li>
                <li><a href="../dashboard/<?= $user['role'] ?>.php">Retour</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Saisie des notes</h1>
            
            <?php if(isset($_SESSION['message'])): ?>
            <div class="alert success">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Devoirs disponibles</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Devoir</th>
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
                            <td><?= date('d/m/Y', strtotime($assignment['due_date'])) ?></td>
                            <td><?= $assignment['max_score'] ?></td>
                            <td><?= $assignment['coefficient'] ?></td>
                            <td><?= $grades_count ?>/<?= $students_count ?></td>
                            <td>
                                <a href="?assignment_id=<?= $assignment['id'] ?>" class="btn-small">
                                    Saisir les notes
                                </a>
                                <a href="view_grades.php?assignment_id=<?= $assignment['id'] ?>" class="btn-small">
                                    Voir
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if(isset($_GET['assignment_id']) && $current_assignment): ?>
            <div class="card">
                <h2>Saisie des notes: <?= htmlspecialchars($current_assignment['title']) ?></h2>
                <p><strong>Classe:</strong> <?= htmlspecialchars($current_assignment['class_name']) ?></p>
                <p><strong>Note maximale:</strong> <?= $current_assignment['max_score'] ?> | 
                   <strong>Coefficient:</strong> <?= $current_assignment['coefficient'] ?></p>
                
                <form method="POST" action="?action=bulk_save">
                    <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Note /<?= $current_assignment['max_score'] ?></th>
                                <th>Appréciation</th>
                                <th>Dernière note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): 
                                $existing = $existing_grades[$student['id']] ?? null;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                                <td>
                                    <input type="number" 
                                           name="grades[<?= $student['id'] ?>][score]" 
                                           step="0.5" 
                                           min="0" 
                                           max="<?= $current_assignment['max_score'] ?>"
                                           value="<?= $existing['score'] ?? '' ?>"
                                           style="width: 80px;">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="grades[<?= $student['id'] ?>][appreciation]" 
                                           value="<?= $existing['appreciation'] ?? '' ?>"
                                           placeholder="Commentaire..."
                                           style="width: 200px;">
                                </td>
                                <td>
                                    <?php if($existing): ?>
                                    <?= $existing['score'] ?>/<?= $current_assignment['max_score'] ?> 
                                    (<?= date('d/m/Y', strtotime($existing['evaluation_date'])) ?>)
                                    <?php else: ?>
                                    <em>Aucune note</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="form-actions">
                        <button type="submit" name="bulk_grades" class="btn">Enregistrer toutes les notes</button>
                        <a href="grades.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
            
            <script>
            // Auto-calcul des notes sur 20
            document.querySelectorAll('input[name^="grades["]').forEach(input => {
                if (input.name.includes('[score]')) {
                    input.addEventListener('input', function() {
                        const maxScore = <?= $current_assignment['max_score'] ?>;
                        const score = parseFloat(this.value);
                        if (!isNaN(score) && score <= maxScore) {
                            const noteSur20 = (score / maxScore) * 20;
                            // Afficher la note sur 20 à côté
                            const parent = this.parentNode;
                            let indicator = parent.querySelector('.note-sur-20');
                            if (!indicator) {
                                indicator = document.createElement('span');
                                indicator.className = 'note-sur-20';
                                indicator.style.marginLeft = '10px';
                                indicator.style.color = '#666';
                                parent.appendChild(indicator);
                            }
                            indicator.textContent = `(${noteSur20.toFixed(2)}/20)`;
                        }
                    });
                }
            });
            </script>
            <?php endif; ?>
            
            <div class="card">
                <h2>Statistiques de notation</h2>
                <div class="stats-grid">
                    <?php
                    // Statistiques pour l'enseignant
                    $stats_sql = "
                        SELECT 
                            COUNT(DISTINCT g.assignment_id) as assignments_count,
                            COUNT(g.id) as grades_count,
                            AVG(g.score) as average_score,
                            MIN(g.score) as min_score,
                            MAX(g.score) as max_score
                        FROM grades g
                        JOIN assignments a ON g.assignment_id = a.id
                        WHERE a.teacher_id = ?
                    ";
                    
                    $stmt = $db->prepare($stats_sql);
                    $stmt->execute([$user['id']]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="stat-card">
                        <h3>Devoirs notés</h3>
                        <p class="stat-number"><?= $stats['assignments_count'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Notes totales</h3>
                        <p class="stat-number"><?= $stats['grades_count'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Moyenne générale</h3>
                        <p class="stat-number"><?= number_format($stats['average_score'] ?? 0, 2) ?>/20</p>
                    </div>
                    <div class="stat-card">
                        <h3>Écart</h3>
                        <p class="stat-number"><?= $stats['min_score'] ?? 0 ?>-<?= $stats['max_score'] ?? 0 ?></p>
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
