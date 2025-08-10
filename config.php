<?php
session_start();

// Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'suivi_scolaire');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL de base
define('BASE_URL', 'http://localhost/classnote/');

// Connexion à la base de données
function connectDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Sécurisation des données
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction checkAuth
function checkAuth() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est connecté (alias)
function isLoggedIn() {
    return checkAuth();
}

// Rediriger si non connecté
function requireLogin() {
    if (!checkAuth()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

// Récupérer l'utilisateur connecté
function getCurrentUser() {
    if (!checkAuth()) return null;
    
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Vérifier le rôle
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

// Requérir un rôle spécifique
function requireRole($role) {
    requireLogin();
    $user = getCurrentUser();
    
    if ($user['role'] !== $role) {
        header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
        exit();
    }
}

// Créer une notification
function createNotification($userId, $title, $message) {
    $db = connectDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    return $stmt->execute([$userId, $title, $message]);
}

// Redirection
function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit();
}

// Journalisation des actions
function logAction($action, $details = null) {
    if (!checkAuth()) return;
    
    $db = connectDB();
    $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $action, $details]);
}
?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
