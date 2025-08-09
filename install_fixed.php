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
