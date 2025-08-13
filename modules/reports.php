<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser();
$db = getDB();

$action = $_GET['action'] ?? 'list';
$student_id = $_GET['student_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;

if ($action === 'generate_pdf' && isset($_GET['student_id'])) {
    // Génération de bulletin (version simplifiée sans bibliothèque PDF)
    $stmt = $db->prepare("
        SELECT s.*, u.full_name as student_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer toutes les notes
    $stmt = $db->prepare("
        SELECT g.*, a.title, a.subject, a.coefficient, a.max_score
        FROM grades g
        JOIN assignments a ON g.assignment_id = a.id
        WHERE g.student_id = ?
        ORDER BY a.subject, g.created_at
    ");
    $stmt->execute([$student_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer les moyennes par matière
    $subjects = [];
    foreach($grades as $grade) {
        $subject = $grade['subject'];
        $score = $grade['score'];
        $coef = $grade['coefficient'];
        
        if(!isset($subjects[$subject])) {
            $subjects[$subject] = [
                'grades' => [],
                'total_weighted' => 0,
                'total_coef' => 0,
                'average' => 0
            ];
        }
        
        $subjects[$subject]['grades'][] = $grade;
        $subjects[$subject]['total_weighted'] += $score * $coef;
        $subjects[$subject]['total_coef'] += $coef;
    }
    
    // Calculer les moyennes
    foreach($subjects as $subject => &$data) {
        if ($data['total_coef'] > 0) {
            $data['average'] = $data['total_weighted'] / $data['total_coef'];
        }
    }
    
    // Moyenne générale
    $general_average = 0;
    $total_all_weighted = 0;
    $total_all_coef = 0;
    
    foreach($subjects as $data) {
        $total_all_weighted += $data['total_weighted'];
        $total_all_coef += $data['total_coef'];
    }
    
    if ($total_all_coef > 0) {
        $general_average = $total_all_weighted / $total_all_coef;
    }
    
    // Récupérer les compétences
    $stmt = $db->prepare("
        SELECT sc.*, c.code, c.description 
        FROM student_competences sc
        JOIN competences c ON sc.competence_id = c.id
        WHERE sc.student_id = ?
        ORDER BY c.subject, c.code
    ");
    $stmt->execute([$student_id]);
    $competences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer le contenu HTML du bulletin
    $html = generateReportHTML($student, $subjects, $general_average, $competences);
    
    // Pour une vraie génération PDF, vous utiliseriez une bibliothèque comme TCPDF ou Dompdf
    // Ici, nous allons juste afficher un aperçu HTML
    echo $html;
    exit();
}

function generateReportHTML($student, $subjects, $general_average, $competences) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bulletin scolaire - <?= htmlspecialchars($student['student_name']) ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .student-info { margin-bottom: 30px; }
            .subject-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .subject-table th, .subject-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            .subject-table th { background-color: #f4f4f4; }
            .average { font-weight: bold; color: #667eea; }
            .competence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 20px; }
            .competence-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
            .level-badge { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; margin-left: 10px; }
            .non-acquis { background: #f56565; color: white; }
            .en-cours { background: #ed8936; color: white; }
            .acquis { background: #38b2ac; color: white; }
            .expert { background: #667eea; color: white; }
            .summary { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 30px; }
            @media print {
                button { display: none; }
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Bulletin Scolaire</h1>
            <h2>Année scolaire 2024-2025</h2>
        </div>
        
        <div class="student-info">
            <h3>Informations de l'élève</h3>
            <p><strong>Nom :</strong> <?= htmlspecialchars($student['student_name']) ?></p>
            <p><strong>Classe :</strong> <?= htmlspecialchars($student['class_name'] ?? 'Non affecté') ?></p>
            <p><strong>Date d'émission :</strong> <?= date('d/m/Y') ?></p>
        </div>
        
        <h3>Notes par matière</h3>
        <?php foreach($subjects as $subject_name => $subject_data): ?>
        <h4><?= htmlspecialchars($subject_name) ?> - Moyenne: <?= number_format($subject_data['average'], 2) ?>/20</h4>
        <table class="subject-table">
            <thead>
                <tr>
                    <th>Devoir</th>
                    <th>Date</th>
                    <th>Note</th>
                    <th>Coefficient</th>
                    <th>Note sur 20</th>
                    <th>Appréciation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($subject_data['grades'] as $grade): 
                    $note_sur_20 = ($grade['score'] / $grade['max_score']) * 20;
                ?>
                <tr>
                    <td><?= htmlspecialchars($grade['title']) ?></td>
                    <td><?= date('d/m/Y', strtotime($grade['evaluation_date'])) ?></td>
                    <td><?= number_format($grade['score'], 2) ?>/<?= $grade['max_score'] ?></td>
                    <td><?= $grade['coefficient'] ?></td>
                    <td><?= number_format($note_sur_20, 2) ?>/20</td>
                    <td><?= htmlspecialchars($grade['appreciation'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
        
        <div class="summary">
            <h3>Résumé</h3>
            <p><strong>Moyenne générale :</strong> <span class="average"><?= number_format($general_average, 2) ?>/20</span></p>
            <p><strong>Nombre de matières :</strong> <?= count($subjects) ?></p>
            <p><strong>Nombre total de notes :</strong> <?= array_sum(array_map(function($s) { return count($s['grades']); }, $subjects)) ?></p>
        </div>
        
        <?php if(!empty($competences)): ?>
        <h3>Compétences évaluées</h3>
        <div class="competence-grid">
            <?php foreach($competences as $comp): ?>
            <div class="competence-card">
                <p><strong><?= htmlspecialchars($comp['code']) ?></strong></p>
                <p><?= htmlspecialchars($comp['description']) ?></p>
                <p>Niveau: 
                    <span class="level-badge <?= $comp['level'] ?>">
                        <?= ucfirst(str_replace('-', ' ', $comp['level'])) ?>
                    </span>
                </p>
                <?php if($comp['evaluation']): ?>
                <p><em><?= htmlspecialchars($comp['evaluation']) ?></em></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; text-align: center;">
            <button onclick="window.print()" class="btn">Imprimer le bulletin</button>
            <button onclick="window.close()" class="btn btn-secondary">Fermer</button>
        </div>
        
        <script>
        function generatePDF() {
            // Pour une vraie génération PDF, vous utiliseriez une bibliothèque JavaScript
            alert("Fonctionnalité PDF à implémenter avec une bibliothèque comme jsPDF");
        }
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Récupérer les élèves pour les rapports
$students = [];
$classes = [];

if ($user['role'] === 'admin') {
    // Admin voit tout
    $stmt = $db->query("
        SELECT s.id, u.full_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY u.full_name
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT * FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($user['role'] === 'teacher') {
    // Enseignant voit ses classes
    $stmt = $db->prepare("
        SELECT s.id, u.full_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN classes c ON s.class_id = c.id
        WHERE c.teacher_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$user['id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
    $stmt->execute([$user['id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($user['role'] === 'parent') {
    // Parent voit ses enfants
    $stmt = $db->prepare("
        SELECT s.id, u.full_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.parent_id = (SELECT id FROM parents WHERE user_id = ?)
        ORDER BY u.full_name
    ");
    $stmt->execute([$user['id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($user['role'] === 'student') {
    // Élève se voit lui-même
    $stmt = $db->prepare("
        SELECT s.id, u.full_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération de rapports</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard">
        <div class="sidebar">
            <h3>Rapports</h3>
            <ul>
                <li><a href="reports.php" class="active">Bulletins</a></li>
                <li><a href="#statistiques">Statistiques</a></li>
                <li><a href="#export">Export</a></li>
                <li><a href="../dashboard/<?= $user['role'] ?>.php">Retour</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Génération de rapports et bulletins</h1>
            
            <div class="card">
                <h2>Générer un bulletin</h2>
                
                <form method="GET" action="reports.php" target="_blank">
                    <input type="hidden" name="action" value="generate_pdf">
                    
                    <div class="form-group">
                        <label for="student_id">Sélectionner un élève</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Sélectionner un élève</option>
                            <?php foreach($students as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= $student_id == $student['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($student['full_name']) ?> - <?= $student['class_name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="period">Période</label>
                            <select id="period" name="period">
                                <option value="trimestre1">1er trimestre</option>
                                <option value="trimestre2">2ème trimestre</option>
                                <option value="trimestre3">3ème trimestre</option>
                                <option value="annuel">Annuel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="format">Format</label>
                            <select id="format" name="format">
                                <option value="html">Aperçu HTML</option>
                                <option value="pdf" disabled>PDF (à venir)</option>
                                <option value="excel" disabled>Excel (à venir)</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Générer le bulletin</button>
                </form>
            </div>
            
            <?php if(in_array($user['role'], ['admin', 'teacher'])): ?>
            <div class="card">
                <h2>Rapports de classe</h2>
                
                <form method="GET" action="class_report.php">
                    <div class="form-group">
                        <label for="class_id">Sélectionner une classe</label>
                        <select id="class_id" name="class_id">
                            <option value="">Sélectionner une classe</option>
                            <?php foreach($classes as $class): ?>
                            <option value="<?= $class['id'] ?>" <?= $class_id == $class['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_type">Type de rapport</label>
                        <select id="report_type" name="report_type">
                            <option value="moyennes">Moyennes de classe</option>
                            <option value="progression">Progression des élèves</option>
                            <option value="comparaison">Comparaison entre classes</option>
                            <option value="competences">Compétences acquises</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Générer le rapport</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Statistiques globales</h2>
                
                <div class="stats-grid">
                    <?php
                    // Calculer des statistiques
                    if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                        $total_students = count($students);
                        
                        // Moyenne générale de tous les élèves
                        $stmt = $db->query("
                            SELECT AVG(g.score) as global_average
                            FROM grades g
                        ");
                        $global_average = $stmt->fetchColumn();
                        
                        // Nombre de compétences évaluées
                        $stmt = $db->query("SELECT COUNT(*) FROM student_competences");
                        $total_competences = $stmt->fetchColumn();
                        
                        // Distribution des niveaux de compétences
                        $stmt = $db->query("
                            SELECT level, COUNT(*) as count 
                            FROM student_competences 
                            GROUP BY level
                        ");
                        $competence_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="stat-card">
                        <h3>Moyenne générale</h3>
                        <p class="stat-number"><?= number_format($global_average, 2) ?>/20</p>
                    </div>
                    <div class="stat-card">
                        <h3>Élèves suivis</h3>
                        <p class="stat-number"><?= $total_students ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Compétences évaluées</h3>
                        <p class="stat-number"><?= $total_competences ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Réussite</h3>
                        <p class="stat-number"><?= $global_average >= 10 ? '✓' : '✗' ?></p>
                    </div>
                    
                    <?php } else { 
                        // Pour élèves et parents
                        if (!empty($students)) {
                            $child_id = $students[0]['id'];
                            
                            $stmt = $db->prepare("
                                SELECT AVG(g.score) as average 
                                FROM grades g
                                WHERE g.student_id = ?
                            ");
                            $stmt->execute([$child_id]);
                            $child_average = $stmt->fetchColumn();
                            
                            $stmt = $db->prepare("
                                SELECT COUNT(DISTINCT g.assignment_id) as assignments_count
                                FROM grades g
                                WHERE g.student_id = ?
                            ");
                            $stmt->execute([$child_id]);
                            $assignments_count = $stmt->fetchColumn();
                            
                            $stmt = $db->prepare("
                                SELECT COUNT(*) as competences_count
                                FROM student_competences
                                WHERE student_id = ?
                            ");
                            $stmt->execute([$child_id]);
                            $competences_count = $stmt->fetchColumn();
                    ?>
                    
                    <div class="stat-card">
                        <h3>Votre moyenne</h3>
                        <p class="stat-number"><?= number_format($child_average, 2) ?>/20</p>
                    </div>
                    <div class="stat-card">
                        <h3>Devoirs notés</h3>
                        <p class="stat-number"><?= $assignments_count ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Compétences</h3>
                        <p class="stat-number"><?= $competences_count ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Classement</h3>
                        <p class="stat-number">15ème/30</p>
                    </div>
                    
                    <?php } } ?>
                </div>
                
                <?php if(isset($competence_levels) && !empty($competence_levels)): ?>
                <h3 style="margin-top: 20px;">Répartition des compétences</h3>
                <div class="competence-distribution">
                    <?php foreach($competence_levels as $level): 
                        $percentage = ($level['count'] / $total_competences) * 100;
                        $level_name = ucfirst(str_replace('-', ' ', $level['level']));
                    ?>
                    <div class="distribution-item">
                        <div class="distribution-label">
                            <span><?= $level_name ?></span>
                            <span><?= $level['count'] ?> (<?= number_format($percentage, 1) ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $percentage ?>%; background-color: 
                                <?= $level['level'] == 'non-acquis' ? '#f56565' : 
                                   ($level['level'] == 'en-cours' ? '#ed8936' : 
                                   ($level['level'] == 'acquis' ? '#38b2ac' : '#667eea')) ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Export de données</h2>
                <p>Exportez les données pour une analyse externe ou pour les archives.</p>
                
                <div class="export-options">
                    <a href="export.php?type=grades&format=csv" class="btn-small">Notes (CSV)</a>
                    <a href="export.php?type=competences&format=csv" class="btn-small">Compétences (CSV)</a>
                    <a href="export.php?type=students&format=excel" class="btn-small" disabled>Élèves (Excel)</a>
                    <a href="export.php?type=all&format=backup" class="btn-small">Sauvegarde complète</a>
                </div>
                
                <div class="alert" style="margin-top: 15px;">
                    <p><small>Les exports Excel et les sauvegardes nécessitent des bibliothèques supplémentaires.</small></p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .competence-distribution {
        margin-top: 20px;
    }
    .distribution-item {
        margin-bottom: 10px;
    }
    .distribution-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 14px;
    }
    .export-options {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 15px;
    }
    a[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    </style>
    
    <script src="../script.js"></script>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
