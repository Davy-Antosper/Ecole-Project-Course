<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser();
if (!in_array($user['role'], ['admin', 'teacher'])) {
    header('Location: ../index.php');
    exit();
}

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $subject = sanitize($_POST['subject']);
        $class_id = $_POST['class_id'];
        $max_score = $_POST['max_score'];
        $coefficient = $_POST['coefficient'];
        $due_date = $_POST['due_date'];
        
        $stmt = $db->prepare("
            INSERT INTO assignments 
            (title, description, subject, class_id, teacher_id, max_score, coefficient, due_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $subject, $class_id, $user['id'], $max_score, $coefficient, $due_date]);
        
        $assignment_id = $db->lastInsertId();
        
        // Créer des notifications pour les élèves
        $stmt = $db->prepare("
            SELECT user_id FROM students WHERE class_id = ?
        ");
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach($students as $student_id) {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, 'Nouveau devoir', ?, 'assignment')
            ");
            $stmt->execute([$student_id, "Un nouveau devoir a été ajouté: $title. Date limite: " . date('d/m/Y', strtotime($due_date))]);
        }
        
        $_SESSION['message'] = "Devoir créé avec succès";
        header('Location: assignments.php');
        exit();
    }
    
    if ($action === 'delete') {
        $assignment_id = $_POST['id'];
        
        // Supprimer d'abord les notes associées
        $stmt = $db->prepare("DELETE FROM grades WHERE assignment_id = ?");
        $stmt->execute([$assignment_id]);
        
        // Supprimer le devoir
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        
        $_SESSION['message'] = "Devoir supprimé avec succès";
        header('Location: assignments.php');
        exit();
    }
}

// Récupérer les devoirs
if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("
        SELECT a.*, c.name as class_name 
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.teacher_id = ?
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$user['id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("
        SELECT a.*, c.name as class_name, u.full_name as teacher_name
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        LEFT JOIN users u ON a.teacher_id = u.id
        ORDER BY a.due_date DESC
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les classes
$classes = [];
if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
    $stmt->execute([$user['id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->query("SELECT * FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
