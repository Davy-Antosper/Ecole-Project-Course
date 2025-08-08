<?php
require_once 'config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user && !empty($user['role'])) {
        header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
        exit();
    } else {
        // Utilisateur invalide, déconnecter
        session_destroy();
    }
}

$error = '';
$success = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'] ?? ($user['first_name'] . ' ' . $user['last_name']);
        
        if (!empty($user['role'])) {
            header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
            exit();
        } else {
            $error = "Rôle utilisateur invalide";
        }
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Scolaire - Connexion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="container">
