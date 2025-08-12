<?php
require_once '../config.php';

// Vérifier l'authentification
if (!checkAuth()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$user = getCurrentUser();
$db = connectDB();

// Récupérer les données selon le rôle
if ($user['role'] === 'admin') {
    // Admin peut voir tous les élèves
    $students_stmt = $db->query("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY u.last_name, u.first_name
    ");
    $students = $students_stmt->fetchAll();
    
    $classes_stmt = $db->query("SELECT id, name FROM classes ORDER BY name");
    $classes = $classes_stmt->fetchAll();
    
} elseif ($user['role'] === 'teacher') {
    // Enseignant voit ses élèves
    $students_stmt = $db->prepare("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN classes c ON s.class_id = c.id
        WHERE c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
        ORDER BY u.last_name, u.first_name
    ");
    $students_stmt->execute([$user['id']]);
    $students = $students_stmt->fetchAll();
    
    $classes_stmt = $db->prepare("
        SELECT id, name FROM classes 
        WHERE teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
        ORDER BY name
    ");
    $classes_stmt->execute([$user['id']]);
    $classes = $classes_stmt->fetchAll();
    
} elseif ($user['role'] === 'student') {
    // Élève se voit lui-même
    $students_stmt = $db->prepare("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.user_id = ?
    ");
    $students_stmt->execute([$user['id']]);
    $students = $students_stmt->fetchAll();
    
    $classes = [];
    
} elseif ($user['role'] === 'parent') {
    // Parent voit ses enfants
    $students_stmt = $db->prepare("
        SELECT s.id, u.first_name, u.last_name, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.parent_id = (SELECT id FROM parents WHERE user_id = ?)
        ORDER BY u.last_name, u.first_name
    ");
    $students_stmt->execute([$user['id']]);
    $students = $students_stmt->fetchAll();
    
    $classes = [];
}

// Générer un bulletin (simulé)
if (isset($_GET['generate']) && isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
    $period = isset($_GET['period']) ? cleanInput($_GET['period']) : 'trimestre1';
    
    // Vérifier les permissions
    if ($user['role'] === 'teacher') {
        // Vérifier que l'élève est dans une classe de l'enseignant
        $check_stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM students s
            JOIN classes c ON s.class_id = c.id
            WHERE s.id = ? AND c.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
        ");
        $check_stmt->execute([$student_id, $user['id']]);
        if (!$check_stmt->fetchColumn()) {
            die("Accès non autorisé");
        }
    } elseif ($user['role'] === 'student') {
        // Vérifier que c'est son propre bulletin
        $check_stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
        $check_stmt->execute([$user['id']]);
        $student = $check_stmt->fetch();
        if ($student['id'] != $student_id) {
            die("Accès non autorisé");
        }
    } elseif ($user['role'] === 'parent') {
        // Vérifier que c'est son enfant
        $check_stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM students s
            JOIN parents p ON s.parent_id = p.id
            WHERE s.id = ? AND p.user_id = ?
        ");
        $check_stmt->execute([$student_id, $user['id']]);
        if (!$check_stmt->fetchColumn()) {
            die("Accès non autorisé");
        }
    }
    
    // Récupérer les informations de l'élève
    $student_info = $db->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email, c.name as class_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.id = ?
    ");
    $student_info->execute([$student_id]);
    $student = $student_info->fetch();
    
    // Récupérer les notes
    $grades_stmt = $db->prepare("
        SELECT 
            g.*,
            e.title as evaluation_title,
            e.type as evaluation_type,
            e.date as evaluation_date,
            e.max_score,
            e.coefficient,
            sub.name as subject_name,
            sub.code as subject_code,
            CONCAT(teach.first_name, ' ', teach.last_name) as teacher_name
        FROM grades g
        JOIN evaluations e ON g.evaluation_id = e.id
        JOIN subjects sub ON e.subject_id = sub.id
        JOIN teachers t ON e.teacher_id = t.id
        JOIN users teach ON t.user_id = teach.id
        WHERE g.student_id = ?
        ORDER BY sub.name, e.date
    ");
    $grades_stmt->execute([$student_id]);
    $grades = $grades_stmt->fetchAll();
    
    // Calculer les moyennes par matière
    $subjects = [];
    foreach ($grades as $grade) {
        $subject = $grade['subject_name'];
        if (!isset($subjects[$subject])) {
            $subjects[$subject] = [
                'grades' => [],
                'total_weighted' => 0,
                'total_coefficient' => 0,
