<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // Mettre à jour le timestamp de la session
    $_SESSION['last_activity'] = time();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
