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
                    VALUES (?)
                ");
                $stmt->execute([$userId]);
            }
            
            $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            
            // Réinitialiser le formulaire
            $_POST = [];
        } else {
            $error = "Une erreur est survenue lors de la création du compte";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Scolaire - Inscription</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="auth-container">
            <!-- Partie gauche - Illustration -->
            <div class="auth-left">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1>Suivi Scolaire</h1>
                </div>
                <div class="illustration">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Éducation">
                </div>
                <div class="benefits">
                    <h3>Pourquoi s'inscrire ?</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Suivi en temps réel des résultats</li>
                        <li><i class="fas fa-check-circle"></i> Communication directe avec les enseignants</li>
                        <li><i class="fas fa-check-circle"></i> Accès aux bulletins et rapports</li>
                        <li><i class="fas fa-check-circle"></i> Notifications instantanées</li>
                    </ul>
                </div>
            </div>
            
            <!-- Partie droite - Formulaire -->
            <div class="auth-right">
                <div class="auth-form">
                    <h2>Créer un compte</h2>
                    <p class="subtitle">Rejoignez notre plateforme éducative</p>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $error ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $success ?>
                        <p><a href="index.php" class="btn-link">Se connecter</a></p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">
                                    <i class="fas fa-user"></i> Prénom *
                                </label>
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?= $_POST['first_name'] ?? '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">
                                    <i class="fas fa-user"></i> Nom *
                                </label>
                                <input type="text" id="last_name" name="last_name" required 
                                       value="<?= $_POST['last_name'] ?? '' ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Adresse email *
                            </label>
                            <input type="email" id="email" name="email" required 
                                   value="<?= $_POST['email'] ?? '' ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i> Mot de passe *
                                </label>
                                <input type="password" id="password" name="password" required>
                                <small>Minimum 6 caractères</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> Confirmer le mot de passe *
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">
                                <i class="fas fa-user-tag"></i> Vous êtes *
                            </label>
                            <div class="role-selector">
                                <label class="role-option">
                                    <input type="radio" name="role" value="student" required 
                                           <?= ($_POST['role'] ?? '') === 'student' ? 'checked' : '' ?>>
                                    <div class="role-card">
                                        <i class="fas fa-user-graduate"></i>
                                        <span>Élève</span>
                                    </div>
                                </label>
                                
                                <label class="role-option">
                                    <input type="radio" name="role" value="parent" 
                                           <?= ($_POST['role'] ?? '') === 'parent' ? 'checked' : '' ?>>
                                    <div class="role-card">
                                        <i class="fas fa-users"></i>
                                        <span>Parent</span>
                                    </div>
                                </label>
                                
                                <label class="role-option">
                                    <input type="radio" name="role" value="teacher" 
                                           <?= ($_POST['role'] ?? '') === 'teacher' ? 'checked' : '' ?>>
                                    <div class="role-card">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Enseignant</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Téléphone (optionnel)
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?= $_POST['phone'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox">
                                <input type="checkbox" name="terms" required>
                                <span>J'accepte les <a href="#" class="btn-link">conditions d'utilisation</a> et la <a href="#" class="btn-link">politique de confidentialité</a> *</span>
                            </label>
                        </div>
                        
                        <button type="submit" name="register" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> Créer mon compte
                        </button>
                        
                        <div class="auth-links">
                            <p>Déjà un compte ? <a href="index.php" class="btn-link">Se connecter</a></p>
                        </div>
                    </form>
                </div>
                
                <div class="auth-footer">
                    <p>&copy; 2024 Suivi Scolaire. Tous droits réservés.</p>
                    <p>
                        <a href="#">Mentions légales</a> | 
                        <a href="#">Confidentialité</a> | 
                        <a href="#">Contact</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas !');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères !');
            return false;
        }
        
        const roleSelected = document.querySelector('input[name="role"]:checked');
        if (!roleSelected) {
            e.preventDefault();
            alert('Veuillez sélectionner un rôle !');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
