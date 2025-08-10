<?php
require_once 'config.php';
checkAuth();

if (!isset($_GET['student_id'])) {
    die(json_encode([]));
}

$db = getDB();
$stmt = $db->prepare("
    SELECT sc.*, c.code, c.description 
    FROM student_competences sc
    JOIN competences c ON sc.competence_id = c.id
       WHERE sc.student_id = ?
    ORDER BY c.subject, c.code
");
$stmt->execute([$_GET['student_id']]);
$competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($competences);
?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
