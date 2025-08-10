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
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: dashboard.php');
        exit;
    }
}

// ====== FONCTIONS UTILES ======

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function getStudents() {
    $pdo = getPDO();
    $stmt = $pdo->query('
        SELECT s.id, u.first_name, u.last_name, u.email, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY u.first_name
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClasses() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, name FROM classes ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSubjects() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, name FROM subjects ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGrades($student_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('
        SELECT g.id, g.score, a.title, s.name as subject
        FROM grades g
        JOIN assignments a ON g.assignment_id = a.id
        JOIN subjects s ON a.subject_id = s.id
        WHERE g.student_id = ?
        ORDER BY g.created_at DESC
    ');
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAssignments($class_id = null) {
    $pdo = getPDO();
    if ($class_id) {
        $stmt = $pdo->prepare('
            SELECT a.id, a.title, s.name as subject, c.name as class, a.due_date
            FROM assignments a
            JOIN subjects s ON a.subject_id = s.id
            JOIN classes c ON a.class_id = c.id
            WHERE a.class_id = ?
            ORDER BY a.due_date DESC
        ');
        $stmt->execute([$class_id]);
    } else {
        $stmt = $pdo->query('
            SELECT a.id, a.title, s.name as subject, c.name as class, a.due_date
            FROM assignments a
            JOIN subjects s ON a.subject_id = s.id
            JOIN classes c ON a.class_id = c.id
            ORDER BY a.due_date DESC
        ');
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
