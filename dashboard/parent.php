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

if ($user['role'] !== 'parent') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les enfants du parent
$children = $db->prepare("
    SELECT s.*, u.first_name, u.last_name, c.name as class_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.parent_id = (SELECT id FROM parents WHERE user_id = ?)
");
$children->execute([$user['id']]);
$children_data = $children->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Parent - ClassNote</title>
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
                        <span>Parent</span>
