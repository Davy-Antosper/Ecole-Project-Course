<?php
require_once 'config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user && !empty($user['role'])) {
        header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
        exit();
    } else {
        // Utilisateur invalide, déconnecter
        session_destroy();
    }
}

$error = '';
$success = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'] ?? ($user['first_name'] . ' ' . $user['last_name']);
        
        if (!empty($user['role'])) {
            header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
            exit();
        } else {
            $error = "Rôle utilisateur invalide";
        }
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Scolaire - Connexion</title>
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
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Suivi personnalisé</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bell"></i>
                        <span>Notifications en temps réel</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-file-pdf"></i>
                        <span>Bulletins automatiques</span>
                    </div>
                </div>
            </div>
            
            <!-- Partie droite - Formulaire -->
            <div class="auth-right">
                <div class="auth-form">
                    <h2>Connexion</h2>
                    <p class="subtitle">Accédez à votre espace personnalisé</p>
                    
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
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Adresse email
                            </label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="votre@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Votre mot de passe">
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox">
                                <input type="checkbox" name="remember">
                                <span>Se souvenir de moi</span>
                            </label>
                            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                        
                        <div class="divider">
                            <span>ou</span>
                        </div>
                        
                        <a href="register.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-user-plus"></i> Créer un compte
                        </a>
                    </form>
                    
                    <div class="demo-accounts">
                        <h4>Comptes de démonstration :</h4>
                        <div class="demo-grid">
                            <div class="demo-account">
                                <strong>Administrateur</strong>
                                <p>admin@ecole.fr</p>
                                <p>test123</p>
                            </div>
                            <div class="demo-account">
                                <strong>Enseignant</strong>
                                <p>prof.math@ecole.fr</p>
                                <p>test123</p>
                            </div>
                            <div class="demo-account">
                                <strong>Élève</strong>
                                <p>eleve1@ecole.fr</p>
                                <p>test123</p>
                            </div>
                            <div class="demo-account">
                                <strong>Parent</strong>
                                <p>parent1@ecole.fr</p>
                                <p>test123</p>
                            </div>
                        </div>
                    </div>
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
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
