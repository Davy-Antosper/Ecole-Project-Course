<?php
/**
 * Configuration Suivi Scolaire
 * Fichier central de configuration et fonctions
 */

session_start();

// Configuration Base de Données
define('DB_HOST', 'localhost');
define('DB_NAME', 'suivi_scolaire');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO
$pdo = null;

function getPDO() {
    global $pdo;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die('Erreur BD: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// ====== AUTHENTIFICATION ======

function login($email, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
