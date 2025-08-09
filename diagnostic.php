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
