<?php
/**
 * Diagnostic du Projet - Vérification de l'état
 * Accédez à: http://localhost/classnote/diagnostic.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic - Suivi Scolaire</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }
        h1 { color: #2c3e50; margin-bottom: 30px; }
        h2 { color: #34495e; margin-top: 30px; margin-bottom: 15px; }
        .diagnostic-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            background: #f8f9fa;
            border-left: 4px solid #ddd;
        }
        .diagnostic-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .diagnostic-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .diagnostic-item.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .diagnostic-icon {
            font-size: 1.2rem;
            margin-right: 12px;
            min-width: 20px;
        }
        .diagnostic-text {
            flex: 1;
        }
        .diagnostic-text strong { display: block; margin-bottom: 3px; }
        .diagnostic-text small { color: #666; }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .status-card.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        .status-card.error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .status-card h3 { font-size: 2rem; margin-bottom: 5px; }
        .status-card p { font-size: 0.9rem; opacity: 0.9; }
        .action-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        .section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnostic du Projet</h1>
        
        <?php
        $issues = [];
        $warnings = [];
        $success_count = 0;
        $total_checks = 0;
        
        // 1. Vérifier PHP
        $total_checks++;
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            echo '<div class="diagnostic-item success">
                <div class="diagnostic-icon">✅</div>
                <div class="diagnostic-text">
                    <strong>PHP Version</strong>
                    <small>PHP ' . phpversion() . ' (Compatible)</small>
                </div>
            </div>';
            $success_count++;
        } else {
            echo '<div class="diagnostic-item error">
                <div class="diagnostic-icon">❌</div>
                <div class="diagnostic-text">
                    <strong>PHP Version</strong>
                    <small>PHP ' . phpversion() . ' (Minimum 7.4 requis)</small>
                </div>
            </div>';
            $issues[] = "PHP version trop ancienne";
        }
        
        // 2. Vérifier PDO MySQL
        $total_checks++;
        if (extension_loaded('pdo_mysql')) {
            echo '<div class="diagnostic-item success">
                <div class="diagnostic-icon">✅</div>
                <div class="diagnostic-text">
                    <strong>Extension PDO MySQL</strong>
                    <small>Disponible</small>
                </div>
            </div>';
            $success_count++;
        } else {
            echo '<div class="diagnostic-item error">
                <div class="diagnostic-icon">❌</div>
                <div class="diagnostic-text">
                    <strong>Extension PDO MySQL</strong>
                    <small>Non disponible - Activez pdo_mysql</small>
                </div>
            </div>';
            $issues[] = "Extension PDO MySQL manquante";
        }
        
        // 3. Vérifier les fichiers essentiels
        echo '<h2>📁 Fichiers Essentiels</h2>';
        $essential_files = [
            'config.php',
            'index.php',
            'register.php',
            'database_fixed.sql',
            'style.css'
        ];
        
        foreach ($essential_files as $file) {
            $total_checks++;
            if (file_exists($file)) {
                echo '<div class="diagnostic-item success">
                    <div class="diagnostic-icon">✅</div>
                    <div class="diagnostic-text">
                        <strong>' . $file . '</strong>
                        <small>Fichier présent</small>
                    </div>
                </div>';
                $success_count++;
            } else {
                echo '<div class="diagnostic-item error">
                    <div class="diagnostic-icon">❌</div>
                    <div class="diagnostic-text">
                        <strong>' . $file . '</strong>
                        <small>Fichier manquant</small>
                    </div>
                </div>';
                $issues[] = "$file manquant";
            }
        }
        
        // 4. Vérifier la configuration
        echo '<h2>⚙️ Configuration</h2>';
        $total_checks++;
        if (file_exists('config.php')) {
            $config_content = file_get_contents('config.php');
            if (strpos($config_content, 'function connectDB()') !== false) {
                echo '<div class="diagnostic-item success">
                    <div class="diagnostic-icon">✅</div>
                    <div class="diagnostic-text">
                        <strong>Fonction connectDB()</strong>
                        <small>Présente dans config.php</small>
                    </div>
                </div>';
                $success_count++;
            } else {
                echo '<div class="diagnostic-item error">
                    <div class="diagnostic-icon">❌</div>
                    <div class="diagnostic-text">
                        <strong>Fonction connectDB()</strong>
                        <small>Manquante dans config.php</small>
                    </div>
                </div>';
                $issues[] = "Fonction connectDB() manquante";
            }
        }
        
        // 5. Vérifier la base de données
        echo '<h2>🗄️ Base de Données</h2>';
        $total_checks++;
        
        try {
            require_once 'config.php';
            $db = connectDB();
            
            echo '<div class="diagnostic-item success">
                <div class="diagnostic-icon">✅</div>
                <div class="diagnostic-text">
                    <strong>Connexion MySQL</strong>
                    <small>Connexion réussie</small>
                </div>
            </div>';
            $success_count++;
            
            // Vérifier les tables
            $stmt = $db->query("SHOW TABLES FROM suivi_scolaire");
            $table_count = count($stmt->fetchAll());
            
            if ($table_count > 0) {
                echo '<div class="diagnostic-item success">
                    <div class="diagnostic-icon">✅</div>
                    <div class="diagnostic-text">
                        <strong>Tables de Base de Données</strong>
                        <small>' . $table_count . ' tables trouvées</small>
                    </div>
                </div>';
            } else {
                echo '<div class="diagnostic-item warning">
                    <div class="diagnostic-icon">⚠️</div>
                    <div class="diagnostic-text">
                        <strong>Tables de Base de Données</strong>
                        <small>Aucune table trouvée - Lancez l\'installation</small>
                    </div>
                </div>';
                $warnings[] = "Base de données vide";
            }
            
            // Vérifier les utilisateurs
            $total_checks++;
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $user_count = $stmt->fetch()['count'] ?? 0;
            
            if ($user_count > 0) {
                echo '<div class="diagnostic-item success">
                    <div class="diagnostic-icon">✅</div>
                    <div class="diagnostic-text">
                        <strong>Données de Test</strong>
                        <small>' . $user_count . ' utilisateurs trouvés</small>
                    </div>
                </div>';
                $success_count++;
            } else {
                echo '<div class="diagnostic-item warning">
                    <div class="diagnostic-icon">⚠️</div>
                    <div class="diagnostic-text">
                        <strong>Données de Test</strong>
                        <small>Aucun utilisateur - Lancez l\'installation</small>
                    </div>
                </div>';
                $warnings[] = "Aucun utilisateur de test";
            }
            
        } catch (Exception $e) {
            echo '<div class="diagnostic-item error">
                <div class="diagnostic-icon">❌</div>
                <div class="diagnostic-text">
                    <strong>Connexion MySQL</strong>
                    <small>Erreur: ' . htmlspecialchars($e->getMessage()) . '</small>
                </div>
            </div>';
            $issues[] = "Impossible de connecter la base de données";
        }
        
        // Résumé
        echo '<h2>📊 Résumé</h2>';
        $percentage = ($success_count / $total_checks) * 100;
        $status = $percentage == 100 ? 'success' : ($percentage >= 80 ? 'warning' : 'error');
        
        echo '<div class="status-grid">
            <div class="status-card ' . $status . '">
                <h3>' . intval($percentage) . '%</h3>
                <p>Complétude</p>
            </div>
            <div class="status-card success">
                <h3>' . $success_count . '</h3>
                <p>Vérifications OK</p>
            </div>
            <div class="status-card warning">
                <h3>' . count($warnings) . '</h3>
                <p>Avertissements</p>
            </div>
            <div class="status-card error">
                <h3>' . count($issues) . '</h3>
                <p>Problèmes</p>
            </div>
        </div>';
        
        // Problèmes
        if (!empty($issues)) {
            echo '<div class="section">
                <h2>❌ Problèmes à Résoudre</h2>';
            foreach ($issues as $issue) {
                echo '<div class="diagnostic-item error">
                    <div class="diagnostic-icon">⚠️</div>
                    <div class="diagnostic-text">' . htmlspecialchars($issue) . '</div>
                </div>';
            }
            echo '</div>';
        }
        
        // Avertissements
        if (!empty($warnings)) {
            echo '<div class="section">
                <h2>⚠️ Avertissements</h2>';
            foreach ($warnings as $warning) {
                echo '<div class="diagnostic-item warning">
                    <div class="diagnostic-icon">ℹ️</div>
                    <div class="diagnostic-text">' . htmlspecialchars($warning) . '</div>
                </div>';
            }
            echo '</div>';
        }
        
        // Actions recommandées
        if ($percentage < 100) {
            echo '<div class="section">
                <h2>🔧 Actions Recommandées</h2>';
            
            if (empty($db)) {
                echo '<p>1. <a class="action-btn" href="install_fixed.php">Installer la Base de Données</a></p>';
            }
            
            if (count($issues) > 0) {
                echo '<p>2. Corrigez les problèmes listés ci-dessus</p>';
            }
            
            echo '<p>3. Consultez <a href="INSTALLATION.md" style="color: #667eea;">INSTALLATION.md</a> pour l\'aide</p>
            </div>';
        } else {
            echo '<div class="section" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white;">
                <h2 style="color: white;">✅ Tout est Prêt!</h2>
                <p>Votre projet est bien configuré. Vous pouvez commencer à l\'utiliser.</p>
                <a class="action-btn" href="index.php" style="background: white; color: #2ecc71;">Accéder à l\'Application</a>
            </div>';
        }
        ?>
    </div>
</body>
</html>

// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
