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
    if (isset($_POST['bulk_grades'])) {
        // Saisie groupée
        $assignment_id = $_POST['assignment_id'];
        $grades = $_POST['grades'];
        
        foreach($grades as $student_id => $grade_data) {
            if (!empty($grade_data['score'])) {
                $score = $grade_data['score'];
                $appreciation = sanitize($grade_data['appreciation'] ?? '');
                
                // Vérifier si la note existe déjà
                $stmt = $db->prepare("
                    SELECT id FROM grades 
                    WHERE student_id = ? AND assignment_id = ?
                ");
                $stmt->execute([$student_id, $assignment_id]);
                
                if ($stmt->fetch()) {
                    // Mettre à jour
                    $stmt = $db->prepare("
                        UPDATE grades 
                        SET score = ?, appreciation = ?, evaluated_by = ?, evaluation_date = NOW()
                        WHERE student_id = ? AND assignment_id = ?
                    ");
                    $stmt->execute([$score, $appreciation, $user['id'], $student_id, $assignment_id]);
                } else {
                    // Créer
                    $stmt = $db->prepare("
                        INSERT INTO grades 
                        (student_id, assignment_id, score, appreciation, evaluated_by, evaluation_date) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$student_id, $assignment_id, $score, $appreciation, $user['id']]);
                }
                
                // Créer une notification pour l'élève
                $student_user_id = $db->query("SELECT user_id FROM students WHERE id = $student_id")->fetchColumn();
                $assignment = $db->query("SELECT title FROM assignments WHERE id = $assignment_id")->fetch();
                
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, title, message, type) 
                    VALUES (?, 'Nouvelle note', ?, 'info')
                ");
                $stmt->execute([$student_user_id, "Vous avez reçu une note pour: " . $assignment['title']]);
            }
        }
        
        $_SESSION['message'] = "Notes enregistrées avec succès";
        header('Location: grades.php');
        exit();
    }
}

// Récupérer les devoirs
$assignments = [];
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

// Pour la saisie groupée
if (isset($_GET['assignment_id'])) {
    $assignment_id = $_GET['assignment_id'];
    
    $stmt = $db->prepare("
        SELECT a.*, c.name as class_name 
        FROM assignments a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $current_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les élèves de la classe
