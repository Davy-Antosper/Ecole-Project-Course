<?php
require_once 'config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    $user = getCurrentUser();
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Récupération et validation des données
    $firstName = cleanInput($_POST['first_name']);
    $lastName = cleanInput($_POST['last_name']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = cleanInput($_POST['role']);
    $phone = cleanInput($_POST['phone'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($firstName) || empty($lastName)) {
        $errors[] = "Le nom et prénom sont obligatoires";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (!in_array($role, ['student', 'parent', 'teacher'])) {
        $errors[] = "Rôle invalide";
    }
    
    // Vérifier si l'email existe déjà
    $db = connectDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $errors[] = "Cette adresse email est déjà utilisée";
    }
    
    if (empty($errors)) {
        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Créer un username à partir de l'email
        $username = strtolower(explode('@', $email)[0]) . uniqid();
        $fullName = $firstName . ' ' . $lastName;
        
        // Insérer l'utilisateur
        $stmt = $db->prepare("
            INSERT INTO users (username, first_name, last_name, email, password, role, phone, full_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$username, $firstName, $lastName, $email, $hashedPassword, $role, $phone, $fullName])) {
            $userId = $db->lastInsertId();
            
            // Si c'est un élève, créer un profil étudiant
            if ($role === 'student') {
                $stmt = $db->prepare("
                    INSERT INTO students (user_id) 
                    VALUES (?)
                ");
                $stmt->execute([$userId]);
            }
            
            // Si c'est un parent, créer un profil parent
            if ($role === 'parent') {
                $stmt = $db->prepare("
                    INSERT INTO parents (user_id) 
                    VALUES (?)
                ");
                $stmt->execute([$userId]);
            }
            
            // Si c'est un enseignant, créer un profil enseignant
            if ($role === 'teacher') {
                $stmt = $db->prepare("
                    INSERT INTO teachers (user_id) 
