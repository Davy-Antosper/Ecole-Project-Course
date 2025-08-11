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
