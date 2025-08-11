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

if ($user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les statistiques
$stats = [
    'students' => $db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'teachers' => $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'classes' => $db->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
    'parents' => $db->query("SELECT COUNT(*) FROM parents")->fetchColumn(),
    'grades' => $db->query("SELECT COUNT(*) FROM grades")->fetchColumn()
];

// Récupérer les dernières activités
$activities = $db->query("
    SELECT 
        g.*,
        u.first_name,
        u.last_name,
        a.title as evaluation_title,
        sub.name as subject_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN assignments a ON g.assignment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    ORDER BY g.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - ClassNote</title>
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
