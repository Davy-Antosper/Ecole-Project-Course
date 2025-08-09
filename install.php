<?php
// Fichier d'installation simple
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Suivi Scolaire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="auth-container" style="max-width: 800px;">
            <div class="auth-left" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="logo">
                    <i class="fas fa-database"></i>
                    <h1>Installation</h1>
                </div>
                <div class="illustration">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Installation">
                </div>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Création de la base de données</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Configuration des tables</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Données de test</span>
                    </div>
                </div>
            </div>
            
            <div class="auth-right">
                <div class="auth-form">
                    <h2>Installation du système</h2>
                    <p class="subtitle">Configuration de la base de données</p>
                    
                    <?php
                    // Vérifier si le fichier de configuration existe
                    if (file_exists('config.php')) {
                        require_once 'config.php';
                        
                        try {
                            // Tester la connexion à la base de données
                            $db = connectDB();
                            echo '<div class="alert alert-success">';
