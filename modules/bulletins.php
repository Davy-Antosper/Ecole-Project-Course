<?php
require_once '../config.php';

// Vérifier l'authentification
if (!checkAuth()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$user = getCurrentUser();
$db = connectDB();

// Récupérer les données selon le rôle
if ($user['role'] === 'admin') {
    // Admin peut voir tous les élèves
    $students_stmt = $db->query("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY u.last_name, u.first_name
    ");
    $students = $students_stmt->fetchAll();
    
    $classes_stmt = $db->query("SELECT id, name FROM classes ORDER BY name");
    $classes = $classes_stmt->fetchAll();
    
} elseif ($user['role'] === 'teacher') {
    // Enseignant voit ses élèves
    $students_stmt = $db->prepare("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN classes c ON s.class_id = c.id
        WHERE c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
        ORDER BY u.last_name, u.first_name
    ");
    $students_stmt->execute([$user['id']]);
    $students = $students_stmt->fetchAll();
    
    $classes_stmt = $db->prepare("
        SELECT id, name FROM classes 
        WHERE teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
        ORDER BY name
    ");
    $classes_stmt->execute([$user['id']]);
    $classes = $classes_stmt->fetchAll();
    
} elseif ($user['role'] === 'student') {
    // Élève se voit lui-même
    $students_stmt = $db->prepare("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.user_id = ?
    ");
    $students_stmt->execute([$user['id']]);
    $students = $students_stmt->fetchAll();
    
    $classes = [];
    
} elseif ($user['role'] === 'parent') {
    // Parent voit ses enfants
    $students_stmt = $db->prepare("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.parent_id = (SELECT id FROM parents WHERE user_id = ?)
        ORDER BY u.last_name, u.first_name
    ");
    $students_stmt->execute([$user['id']]);
    $students = $students_stmt->fetchAll();
    
    $classes = [];
}

// Générer un bulletin (simulé)
if (isset($_GET['generate']) && isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
    $period = isset($_GET['period']) ? cleanInput($_GET['period']) : 'trimestre1';
    
    // Vérifier les permissions
    if ($user['role'] === 'teacher') {
        // Vérifier que l'élève est dans une classe de l'enseignant
        $check_stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM students s
            JOIN classes c ON s.class_id = c.id
            WHERE s.id = ? AND c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
        ");
        $check_stmt->execute([$student_id, $user['id']]);
        if (!$check_stmt->fetchColumn()) {
            die("Accès non autorisé");
        }
    } elseif ($user['role'] === 'student') {
        // Vérifier que c'est son propre bulletin
        $check_stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
        $check_stmt->execute([$user['id']]);
        $student = $check_stmt->fetch();
        if ($student['id'] != $student_id) {
            die("Accès non autorisé");
        }
    } elseif ($user['role'] === 'parent') {
        // Vérifier que c'est son enfant
        $check_stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM students s
            JOIN parents p ON s.parent_id = p.id
            WHERE s.id = ? AND p.user_id = ?
        ");
        $check_stmt->execute([$student_id, $user['id']]);
        if (!$check_stmt->fetchColumn()) {
            die("Accès non autorisé");
        }
    }
    
    // Récupérer les informations de l'élève
    $student_info = $db->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.id = ?
    ");
    $student_info->execute([$student_id]);
    $student = $student_info->fetch();
    
    // Récupérer les notes
    $grades_stmt = $db->prepare("
        SELECT 
            g.*,
            e.title as evaluation_title,
            e.type as evaluation_type,
            e.date as evaluation_date,
            e.max_score,
            e.coefficient,
            sub.name as subject_name,
            sub.code as subject_code,
            CONCAT(teach.first_name, ' ', teach.last_name) as teacher_name
        FROM grades g
        JOIN evaluations e ON g.evaluation_id = e.id
        JOIN subjects sub ON e.subject_id = sub.id
        JOIN teachers t ON e.teacher_id = t.id
        JOIN users teach ON t.user_id = teach.id
        WHERE g.student_id = ?
        ORDER BY sub.name, e.date
    ");
    $grades_stmt->execute([$student_id]);
    $grades = $grades_stmt->fetchAll();
    
    // Calculer les moyennes par matière
    $subjects = [];
    foreach ($grades as $grade) {
        $subject = $grade['subject_name'];
        if (!isset($subjects[$subject])) {
            $subjects[$subject] = [
                'grades' => [],
                'total_weighted' => 0,
                'total_coefficient' => 0,
                'average' => 0
            ];
        }
        $subjects[$subject]['grades'][] = $grade;
        $subjects[$subject]['total_weighted'] += $grade['score'] * $grade['coefficient'];
        $subjects[$subject]['total_coefficient'] += $grade['coefficient'];
    }
    
    // Calculer les moyennes
    foreach ($subjects as &$subject) {
        if ($subject['total_coefficient'] > 0) {
            $subject['average'] = $subject['total_weighted'] / $subject['total_coefficient'];
        }
    }
    
    // Moyenne générale
    $total_weighted = array_sum(array_column($subjects, 'total_weighted'));
    $total_coefficient = array_sum(array_column($subjects, 'total_coefficient'));
    $general_average = $total_coefficient > 0 ? $total_weighted / $total_coefficient : 0;
    
    // Récupérer les compétences
    $competences_stmt = $db->prepare("
        SELECT 
            ce.*,
            c.code as competence_code,
            c.description as competence_description,
            sub.name as subject_name
        FROM competence_evaluations ce
        JOIN competences c ON ce.competence_id = c.id
        JOIN subjects sub ON c.subject_id = sub.id
        WHERE ce.student_id = ?
        ORDER BY sub.name, c.code
    ");
    $competences_stmt->execute([$student_id]);
    $competences = $competences_stmt->fetchAll();
    
    // Grouper les compétences par matière
    $competences_by_subject = [];
    foreach ($competences as $comp) {
        $subject = $comp['subject_name'];
        if (!isset($competences_by_subject[$subject])) {
            $competences_by_subject[$subject] = [];
        }
        $competences_by_subject[$subject][] = $comp;
    }
    
    // Afficher le bulletin HTML
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bulletin - <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .bulletin-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); max-width: 1000px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .student-info { margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px; }
            .subject-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .subject-table th, .subject-table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
            .subject-table th { background: #4361ee; color: white; }
            .average { font-weight: bold; color: #4361ee; font-size: 1.2em; }
            .competence-card { background: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 8px; border-left: 4px solid #4361ee; }
            .level-badge { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; margin-left: 10px; }
            .non-acquis { background: #f72585; color: white; }
            .en-cours { background: #f8961e; color: white; }
            .acquis { background: #4cc9f0; color: white; }
            .expert { background: #2ecc71; color: white; }
            .summary { background: #e9ecef; padding: 20px; border-radius: 8px; margin-top: 30px; }
            @media print {
                .no-print { display: none; }
                body { margin: 0; background: white; }
                .bulletin-container { box-shadow: none; padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="bulletin-container">
            <div class="header">
                <h1>Bulletin Scolaire</h1>
                <h2><?= ucfirst($period) ?> - Année scolaire 2024-2025</h2>
            </div>
            
            <div class="student-info">
                <h3>Informations de l'élève</h3>
                <p><strong>Nom :</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></p>
                <p><strong>Classe :</strong> <?= htmlspecialchars($student['class_name'] ?? 'Non affecté') ?></p>
                <p><strong>Date d'émission :</strong> <?= date('d/m/Y') ?></p>
            </div>
            
            <?php if (!empty($subjects)): ?>
            <h3>Notes par matière</h3>
            <?php foreach ($subjects as $subject_name => $subject_data): ?>
            <h4><?= htmlspecialchars($subject_name) ?> - Moyenne: <span class="average"><?= number_format($subject_data['average'], 2) ?>/20</span></h4>
            <table class="subject-table">
                <thead>
                    <tr>
                        <th>Devoir</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Note</th>
                        <th>Coefficient</th>
                        <th>Note sur 20</th>
                        <th>Professeur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subject_data['grades'] as $grade): 
                        $note_sur_20 = ($grade['score'] / $grade['max_score']) * 20;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($grade['evaluation_title']) ?></td>
                        <td><?= ucfirst($grade['evaluation_type']) ?></td>
                        <td><?= date('d/m/Y', strtotime($grade['evaluation_date'])) ?></td>
                        <td><?= number_format($grade['score'], 2) ?>/<?= $grade['max_score'] ?></td>
                        <td><?= $grade['coefficient'] ?></td>
                        <td><?= number_format($note_sur_20, 2) ?>/20</td>
                        <td><?= htmlspecialchars($grade['teacher_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #6c757d;">
                <i class="fas fa-file-alt" style="font-size: 48px; opacity: 0.5;"></i>
                <h4>Aucune note disponible</h4>
                <p>Les notes apparaîtront ici dès qu'elles seront saisies.</p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($competences_by_subject)): ?>
            <h3>Compétences évaluées</h3>
            <?php foreach ($competences_by_subject as $subject_name => $subject_competences): ?>
            <h4><?= htmlspecialchars($subject_name) ?></h4>
            <?php foreach ($subject_competences as $comp): ?>
            <div class="competence-card">
                <p><strong><?= htmlspecialchars($comp['competence_code']) ?></strong> - 
                   <?= htmlspecialchars($comp['competence_description']) ?></p>
                <p>Niveau: 
                    <span class="level-badge <?= $comp['level'] ?>">
                        <?= ucfirst(str_replace('-', ' ', $comp['level'])) ?>
                    </span>
                </p>
                <?php if ($comp['comment']): ?>
                <p><em><?= htmlspecialchars($comp['comment']) ?></em></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="summary">
                <h3>Résumé</h3>
                <p><strong>Moyenne générale :</strong> <span class="average"><?= number_format($general_average, 2) ?>/20</span></p>
                <p><strong>Nombre de matières :</strong> <?= count($subjects) ?></p>
                <p><strong>Nombre total de notes :</strong> <?= count($grades) ?></p>
                <p><strong>Nombre de compétences évaluées :</strong> <?= count($competences) ?></p>
            </div>
            
            <div class="no-print" style="margin-top: 40px; text-align: center;">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimer le bulletin
                </button>
                <button onclick="window.close()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
        
        <script>
        function generatePDF() {
            alert("Pour une génération PDF réelle, vous pouvez utiliser une bibliothèque comme jsPDF");
        }
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletins - ClassNote</title>
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
                
                <a href="bulletins.php" class="menu-item active">
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
                    <h1>Bulletins scolaires</h1>
                    <p style="color: #6c757d; margin-top: 5px;">Générez et consultez les bulletins</p>
                </div>
            </header>
            
            <div class="table-container">
                <div class="table-header">
                    <h2>Générer un bulletin</h2>
                </div>
                
                <div style="padding: 30px;">
                    <?php if (empty($students)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <i class="fas fa-user-graduate" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>Aucun élève disponible</h3>
                        <p>Vous ne pouvez pas générer de bulletin pour le moment.</p>
                    </div>
                    <?php else: ?>
                    
                    <form method="GET" action="" target="_blank">
                        <input type="hidden" name="generate" value="1">
                        
                        <div class="form-group">
                            <label for="student_id">Sélectionner un élève *</label>
                            <select id="student_id" name="student_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="">Choisir un élève</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> 
                                    - <?= htmlspecialchars($student['class_name'] ?? 'Non classé') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="period">Période *</label>
                            <select id="period" name="period" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="trimestre1">1er trimestre</option>
                                <option value="trimestre2">2ème trimestre</option>
                                <option value="trimestre3">3ème trimestre</option>
                                <option value="annuel">Annuel</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="format">Format</label>
                            <select id="format" name="format" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <option value="html">Aperçu HTML</option>
                                <option value="pdf" disabled>PDF (bientôt disponible)</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Information :</strong> Le bulletin s'ouvrira dans un nouvel onglet. 
                            Vous pourrez l'imprimer depuis votre navigateur.
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> Générer le bulletin
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($user['role'] === 'admin' || $user['role'] === 'teacher'): ?>
            <!-- Statistiques des classes -->
            <div class="table-container" style="margin-top: 30px;">
                <div class="table-header">
                    <h2>Moyennes par classe</h2>
                </div>
                
                <div style="padding: 20px;">
                    <?php if (empty($classes)): ?>
                    <div style="text-align: center; padding: 20px; color: #6c757d;">
                        <p>Aucune classe disponible</p>
                    </div>
                    <?php else: ?>
                    <div class="classes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($classes as $class): 
                            // Calculer la moyenne de la classe
                            $class_average_stmt = $db->prepare("
                                SELECT AVG(g.score) as average
                                FROM grades g
                                JOIN evaluations e ON g.evaluation_id = e.id
                                JOIN students s ON g.student_id = s.id
                                WHERE s.class_id = ?
                            ");
                            $class_average_stmt->execute([$class['id']]);
                            $class_average = $class_average_stmt->fetch()['average'] ?? 0;
                            
                            // Compter les élèves
                            $student_count_stmt = $db->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
                            $student_count_stmt->execute([$class['id']]);
                            $student_count = $student_count_stmt->fetch()['count'];
                        ?>
                        <div class="class-card" style="background: white; padding: 20px; border-radius: 12px; border-left: 4px solid #4361ee; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0;"><?= htmlspecialchars($class['name']) ?></h3>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 5px 0; color: #6c757d;">
                                    <i class="fas fa-users"></i> 
                                    <?= $student_count ?> élève(s)
                                </p>
                                <p style="margin: 5px 0; color: #6c757d;">
                                    <i class="fas fa-chart-line"></i> 
                                    Moyenne de classe : <strong><?= number_format($class_average, 2) ?>/20</strong>
                                </p>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <a href="?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-primary">
                                    Voir les bulletins
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
