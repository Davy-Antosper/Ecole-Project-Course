<?php
require 'config_new.php';
requireLogin();

$user = getUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Suivi Scolaire</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 24px; }
        .navbar a { color: white; text-decoration: none; background: #667eea; padding: 8px 16px; border-radius: 5px; }
        .navbar a:hover { background: #764ba2; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; color: #667eea; font-weight: bold; }
        .stat-label { color: #999; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; font-weight: 600; color: #333; }
        tr:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 Suivi Scolaire</h1>
        <div>
            <span style="margin-right: 20px;">👤 <?= escape($user['first_name'] . ' ' . $user['last_name']) ?> (<?= escape($user['role']) ?>)</span>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
    
    <div class="container">
        <h2>Bienvenue <?= escape($user['first_name']) ?>!</h2>
        
        <?php if ($user['role'] === 'admin'): ?>
            <div class="grid">
                <div class="stat">
                    <div class="stat-number"><?php 
                        $pdo = getPDO();
                        echo $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
                    ?></div>
                    <div class="stat-label">Élèves</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php 
                        echo $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
                    ?></div>
                    <div class="stat-label">Enseignants</div>
                </div>
                <div class="stat">
