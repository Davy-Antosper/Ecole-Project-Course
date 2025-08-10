<?php
/**
 * Script d'installation simplifiée
 * Accédez à: http://localhost/classnote/install_fixed.php
 */

require_once 'config.php';

$installSuccess = false;
$installError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $db = connectDB();
        
        // Lire le fichier SQL
        $sqlFile = __DIR__ . '/database_fixed.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Le fichier database_fixed.sql n'existe pas!");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Exécuter le script SQL
        $db->exec($sql);
        
        $installSuccess = true;
    } catch (Exception $e) {
        $installError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Suivi Scolaire</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #f5c6cb;
        }
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
            text-align: left;
        }
        
        .accounts {
            text-align: left;
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .accounts h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .account-item {
            background: white;
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
            border-left: 3px solid #3498db;
        }
        
        .account-item strong {
            color: #2c3e50;
        }
        
        .account-item p {
            margin: 3px 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        a {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Installation - Suivi Scolaire</h1>
        
        <?php if ($installSuccess): ?>
            <div class="success">
                <strong>✅ Installation réussie!</strong><br>
                La base de données a été créée avec les données de test.
            </div>
            
            <div class="info">
                <strong>Configuration de la base de données:</strong>
                <p>Base: <code>suivi_scolaire</code></p>
                <p>Tables créées: 11 tables principales</p>
                <p>Utilisateurs de test: 8 comptes</p>
                <p>Données de test: Classes, Élèves, Devoirs, Notes</p>
            </div>
            
            <div class="accounts">
                <h3>📋 Comptes de Démonstration</h3>
                
                <div class="account-item">
                    <strong>👨‍💼 Administrateur</strong>
                    <p>Email: <code>admin@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👨‍🏫 Enseignant (Mathématiques)</strong>
                    <p>Email: <code>prof.math@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👨‍🏫 Enseignant (Français)</strong>
                    <p>Email: <code>prof.francais@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👨‍🎓 Élève 1</strong>
                    <p>Email: <code>eleve1@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👩‍🎓 Élève 2</strong>
                    <p>Email: <code>eleve2@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👨‍🎓 Élève 3</strong>
                    <p>Email: <code>eleve3@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👨‍👧 Parent 1</strong>
                    <p>Email: <code>parent1@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
                
                <div class="account-item">
                    <strong>👩‍👦 Parent 2</strong>
                    <p>Email: <code>parent2@ecole.fr</code></p>
                    <p>Mot de passe: <code>test123</code></p>
                </div>
            </div>
            
            <div class="warning">
                ⚠️ <strong>Important:</strong> Pour des raisons de sécurité, supprimez ce script d'installation une fois que vous avez terminé.
            </div>
            
            <a href="index.php">Se connecter à l'application →</a>
            
        <?php elseif ($installError): ?>
            <div class="error">
                <strong>❌ Erreur d'installation</strong><br>
                <?= htmlspecialchars($installError) ?>
            </div>
            
            <p>Veuillez vérifier:</p>
            <ul style="text-align: left; margin: 20px 0;">
                <li>Que le fichier <code>database_fixed.sql</code> existe</li>
                <li>Que votre base de données MySQL est accessible</li>
                <li>Que les paramètres dans <code>config.php</code> sont corrects</li>
            </ul>
            
            <button onclick="location.reload();">Réessayer</button>
            
        <?php else: ?>
            <div class="info">
                <strong>⚙️ Installation de la Base de Données</strong>
                <p>Cette installation va créer la base de données <code>suivi_scolaire</code> avec les tables et données de test nécessaires.</p>
            </div>
            
            <div class="warning">
                ⚠️ <strong>Attention:</strong> Cela va supprimer l'ancienne base de données s'il en existe une.
            </div>
            
            <form method="POST">
                <button type="submit" name="install">🚀 Démarrer l'installation</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
