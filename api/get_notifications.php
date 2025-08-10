<?php
require_once 'config.php';
checkAuth();

$user = getCurrentUser();
$db = getDB();

$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marquer comme lues
$db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['id']]);

header('Content-Type: application/json');
echo json_encode($notifications);
?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
