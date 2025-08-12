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
