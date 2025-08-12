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

$db = connectDB(); // CHANGÉ: connectDB() au lieu de getDB()
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = cleanInput($_POST['name']);
        $level = cleanInput($_POST['level']);
        $school_year = cleanInput($_POST['school_year']);
        $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
        
        $stmt = $db->prepare("
            INSERT INTO classes (name, level, school_year, teacher_id) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $level, $school_year, $teacher_id])) {
            $_SESSION['success'] = "Classe créée avec succès";
            header('Location: classes.php');
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la création";
        }
    }
}

// Récupérer les classes
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$teacher_filter = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : '';

$sql = "
    SELECT 
        c.*,
        CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
        COUNT(DISTINCT s.id) as student_count
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN students s ON c.id = s.class_id
    WHERE 1=1
";

$params = [];

if ($user['role'] === 'teacher') {
    // Enseignant ne voit que ses classes
    $sql .= " AND c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)";
    $params[] = $user['id'];
}

if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.level LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($teacher_filter && $user['role'] === 'admin') {
    $sql .= " AND c.teacher_id = ?";
    $params[] = $teacher_filter;
}

$sql .= " GROUP BY c.id ORDER BY c.name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$classes = $stmt->fetchAll();

// Récupérer les enseignants pour les formulaires
$teachers = [];
if ($user['role'] === 'admin') {
    $teachers_stmt = $db->query("
        SELECT t.id, u.first_name, u.last_name 
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        ORDER BY u.last_name
    ");
    $teachers = $teachers_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des classes - ClassNote</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
