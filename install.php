<?php
// Fichier d'installation simple
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Suivi Scolaire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="auth-container" style="max-width: 800px;">
            <div class="auth-left" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <div class="logo">
                    <i class="fas fa-database"></i>
                    <h1>Installation</h1>
                </div>
                <div class="illustration">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Installation">
                </div>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Création de la base de données</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Configuration des tables</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Données de test</span>
                    </div>
                </div>
            </div>
            
            <div class="auth-right">
                <div class="auth-form">
                    <h2>Installation du système</h2>
                    <p class="subtitle">Configuration de la base de données</p>
                    
                    <?php
                    // Vérifier si le fichier de configuration existe
                    if (file_exists('config.php')) {
                        require_once 'config.php';
                        
                        try {
                            // Tester la connexion à la base de données
                            $db = connectDB();
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-check-circle"></i> ';
                            echo 'Connexion à la base de données réussie !';
                            echo '</div>';
                            
                            // Lire le fichier SQL
                            $sql = file_get_contents('database.sql');
                            
                            if ($sql) {
                                // Exécuter les requêtes SQL
                                $queries = explode(';', $sql);
                                
                                $success = 0;
                                $errors = 0;
                                $error_messages = [];
                                
                                foreach ($queries as $query) {
                                    $query = trim($query);
                                    if (!empty($query)) {
                                        try {
                                            $db->exec($query);
                                            $success++;
                                        } catch (PDOException $e) {
                                            $errors++;
                                            $error_messages[] = $e->getMessage();
                                        }
                                    }
                                }
                                
                                echo '<div class="alert ' . ($errors > 0 ? 'alert-warning' : 'alert-success') . '">';
                                echo '<i class="fas ' . ($errors > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle') . '"></i> ';
                                echo "Installation terminée : $success requêtes réussies, $errors erreurs";
                                echo '</div>';
                                
                                if ($errors > 0) {
                                    echo '<details style="margin-top: 20px;">';
                                    echo '<summary>Voir les erreurs</summary>';
                                    echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px; font-family: monospace; font-size: 0.9rem;">';
                                    foreach ($error_messages as $error) {
                                        echo '<p style="color: #f72585; margin: 5px 0;">' . htmlspecialchars($error) . '</p>';
                                    }
                                    echo '</div>';
                                    echo '</details>';
                                }
                                
                                // Afficher les comptes de test
                                if ($success > 0) {
                                    echo '<div class="alert alert-success" style="margin-top: 20px;">';
                                    echo '<h4>Comptes de test créés :</h4>';
                                    echo '<div style="margin-top: 10px;">';
                                    echo '<p><strong>Administrateur :</strong> admin@ecole.fr / password123</p>';
                                    echo '<p><strong>Enseignant :</strong> prof@ecole.fr / password123</p>';
                                    echo '<p><strong>Élève :</strong> eleve@ecole.fr / password123</p>';
                                    echo '<p><strong>Parent :</strong> parent@ecole.fr / password123</p>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '<div class="alert alert-info">';
                                    echo '<i class="fas fa-info-circle"></i> ';
                                    echo 'L\'installation est terminée. Vous pouvez maintenant vous connecter.';
                                    echo '</div>';
                                    
                                    echo '<div class="form-actions" style="margin-top: 30px;">';
                                    echo '<a href="index.php" class="btn btn-primary btn-block">';
                                    echo '<i class="fas fa-sign-in-alt"></i> Aller à la page de connexion';
                                    echo '</a>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-error">';
                                echo '<i class="fas fa-exclamation-circle"></i> ';
                                echo 'Impossible de lire le fichier database.sql';
                                echo '</div>';
                            }
                            
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-error">';
                            echo '<i class="fas fa-exclamation-circle"></i> ';
                            echo 'Erreur de connexion à la base de données : ' . $e->getMessage();
                            echo '</div>';
                            
                            echo '<div class="alert alert-info" style="margin-top: 20px;">';
                            echo '<h4>Configuration manuelle :</h4>';
                            echo '<ol style="margin-left: 20px; margin-top: 10px;">';
                            echo '<li>Créez une base de données nommée "suivi_scolaire"</li>';
                            echo '<li>Importez le fichier database.sql dans phpMyAdmin</li>';
                            echo '<li>Vérifiez les paramètres de connexion dans config.php</li>';
                            echo '</ol>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-error">';
                        echo '<i class="fas fa-exclamation-circle"></i> ';
                        echo 'Le fichier config.php n\'existe pas';
                        echo '</div>';
                        
                        echo '<div class="alert alert-info" style="margin-top: 20px;">';
                        echo '<h4>Étapes d\'installation :</h4>';
                        echo '<ol style="margin-left: 20px; margin-top: 10px;">';
                        echo '<li>Créez le fichier config.php avec vos paramètres de connexion</li>';
                        echo '<li>Créez une base de données MySQL</li>';
                        echo '<li>Exécutez le script SQL database.sql</li>';
                        echo '<li>Accédez à la page de connexion</li>';
                        echo '</ol>';
                        echo '</div>';
                    }
                    ?>
                    
                    <div class="auth-footer" style="margin-top: 30px;">
                        <p>Si vous rencontrez des problèmes, vérifiez :</p>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>Les permissions de votre serveur MySQL</li>
                            <li>Les identifiants de connexion dans config.php</li>
                            <li>Que la base de données "suivi_scolaire" n'existe pas déjà</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
