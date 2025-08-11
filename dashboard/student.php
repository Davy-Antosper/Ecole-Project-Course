<?php
require_once '../config.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$user = getCurrentUser();

// Rediriger si pas d'utilisateur valide
if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Rediriger si ce n'est pas un élève
if ($user['role'] !== 'student') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les informations de l'élève
$stmt = $db->prepare("
    SELECT s.*, c.name as class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.user_id = ?
");
$stmt->execute([$user['id']]);
$student = $stmt->fetch();

if (!$student) {
    die("Profil élève non trouvé");
}

// Récupérer les notes
$stmt = $db->prepare("
    SELECT 
        g.*,
        a.title as evaluation_title,
        a.max_score,
        sub.name as subject_name,
        u.first_name,
        u.last_name
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN users u ON a.teacher_id = u.id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
");
$stmt->execute([$student['id']]);
$grades = $stmt->fetchAll();

// Calculer les moyennes par matière
$subjects = [];
$totalWeighted = 0;
$totalCoefficient = 0;

foreach ($grades as $grade) {
    $subject = $grade['subject_name'];
    
    if (!isset($subjects[$subject])) {
        $subjects[$subject] = [
            'grades' => [],
            'total' => 0,
            'coefficient' => 0,
            'count' => 0
        ];
    }
    
    $subjects[$subject]['grades'][] = $grade;
    $subjects[$subject]['total'] += $grade['score'] * $grade['coefficient'];
    $subjects[$subject]['coefficient'] += $grade['coefficient'];
    $subjects[$subject]['count']++;
    
    $totalWeighted += $grade['score'] * $grade['coefficient'];
    $totalCoefficient += $grade['coefficient'];
}

// Moyenne générale
$average = $totalCoefficient > 0 ? $totalWeighted / $totalCoefficient : 0;

// Récupérer les compétences évaluées
$stmt = $db->prepare("
    SELECT 
        sc.*,
        c.code as competence_code,
        c.description as competence_description,
        sub.name as subject_name
    FROM student_competences sc
    JOIN competences c ON sc.competence_id = c.id
    JOIN subjects sub ON c.subject_id = sub.id
    WHERE sc.student_id = ?
    ORDER BY sc.evaluation_date DESC
");
$stmt->execute([$student['id']]);
$competences = $stmt->fetchAll();

// Récupérer les devoirs à venir
$stmt = $db->prepare("
    SELECT 
        a.*,
        sub.name as subject_name,
        u.first_name,
        u.last_name
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN users u ON a.teacher_id = u.id
    WHERE a.class_id = ? AND a.due_date >= CURDATE()
    ORDER BY a.due_date ASC
    LIMIT 5
");
$stmt->execute([$student['class_id']]);
$upcomingEvaluations = $stmt->fetchAll();

// Récupérer les notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Élève</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h2>Suivi Scolaire</h2>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <span>Élève - <?= htmlspecialchars($student['class_name'] ?? 'Non affecté') ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="student.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="#notes" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Mes notes</span>
                </a>
                
                <a href="#competences" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Mes compétences</span>
                </a>
                
                <a href="#devoirs" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Mes devoirs</span>
                </a>
                
                <a href="#bulletins" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Mes bulletins</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="#messages" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if (count($notifications) > 0): ?>
                    <span style="background: #f72585; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; margin-left: auto;">
                        <?= count($notifications) ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
