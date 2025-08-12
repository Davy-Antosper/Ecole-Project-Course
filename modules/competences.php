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

$db = connectDB(); // CHANGÉ
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compétences - ClassNote</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
