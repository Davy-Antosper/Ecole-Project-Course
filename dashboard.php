<?php
require 'config_new.php';
requireLogin();

$user = getUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Suivi Scolaire</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 24px; }
        .navbar a { color: white; text-decoration: none; background: #667eea; padding: 8px 16px; border-radius: 5px; }
        .navbar a:hover { background: #764ba2; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; color: #667eea; font-weight: bold; }
        .stat-label { color: #999; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; font-weight: 600; color: #333; }
        tr:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 Suivi Scolaire</h1>
        <div>
            <span style="margin-right: 20px;">👤 <?= escape($user['first_name'] . ' ' . $user['last_name']) ?> (<?= escape($user['role']) ?>)</span>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
    
    <div class="container">
        <h2>Bienvenue <?= escape($user['first_name']) ?>!</h2>
        
        <?php if ($user['role'] === 'admin'): ?>
            <div class="grid">
                <div class="stat">
                    <div class="stat-number"><?php 
                        $pdo = getPDO();
                        echo $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
                    ?></div>
                    <div class="stat-label">Élèves</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php 
                        echo $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
                    ?></div>
                    <div class="stat-label">Enseignants</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php 
                        echo $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
                    ?></div>
                    <div class="stat-label">Classes</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php 
                        echo $pdo->query('SELECT COUNT(*) FROM grades')->fetchColumn();
                    ?></div>
                    <div class="stat-label">Notes</div>
                </div>
            </div>
            
            <div class="card">
                <h3>Gestion</h3>
                <p>Panel d'administration en développement...</p>
                <ul style="margin-top: 15px;">
                    <li><a href="#">Gérer les utilisateurs</a></li>
                    <li><a href="#">Gérer les classes</a></li>
                    <li><a href="#">Gérer les matières</a></li>
                </ul>
            </div>
        
        <?php elseif ($user['role'] === 'teacher'): ?>
            <div class="card">
                <h3>Mes Classes</h3>
                <table>
                    <thead>
                        <tr><th>Classe</th><th>Élèves</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pdo = getPDO();
                        $stmt = $pdo->query('
                            SELECT c.name, COUNT(s.id) as count 
                            FROM classes c 
                            LEFT JOIN students s ON c.id = s.class_id 
                            GROUP BY c.id
                        ');
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $class):
                        ?>
                            <tr>
                                <td><?= escape($class['name']) ?></td>
                                <td><?= $class['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h3>Récents Devoirs</h3>
                <table>
                    <thead>
                        <tr><th>Titre</th><th>Classe</th><th>Date limite</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $assignments = getAssignments();
                        foreach (array_slice($assignments, 0, 5) as $a):
                        ?>
                            <tr>
                                <td><?= escape($a['title']) ?></td>
                                <td><?= escape($a['class']) ?></td>
                                <td><?= $a['due_date'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        
        <?php elseif ($user['role'] === 'student'): ?>
            <div class="grid">
                <div class="stat">
                    <div class="stat-number"><?php 
                        $pdo = getPDO();
                        $count = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE student_id = (SELECT id FROM students WHERE user_id = ?)');
                        $count->execute([$user['id']]);
                        echo $count->fetchColumn();
                    ?></div>
                    <div class="stat-label">Notes reçues</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php 
                        $avg = $pdo->prepare('SELECT AVG(score) as moyenne FROM grades WHERE student_id = (SELECT id FROM students WHERE user_id = ?)');
                        $avg->execute([$user['id']]);
                        $result = $avg->fetch(PDO::FETCH_ASSOC);
                        echo $result['moyenne'] ? round($result['moyenne'], 1) : '-';
                    ?></div>
                    <div class="stat-label">Moyenne</div>
                </div>
            </div>
            
            <div class="card">
                <h3>Mes Notes</h3>
                <table>
                    <thead>
                        <tr><th>Devoir</th><th>Matière</th><th>Note</th><th>Commentaire</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->prepare('
                            SELECT g.score, g.appreciation, a.title, s.name 
                            FROM grades g
                            JOIN assignments a ON g.assignment_id = a.id
                            JOIN subjects s ON a.subject_id = s.id
                            WHERE g.student_id = (SELECT id FROM students WHERE user_id = ?)
                            ORDER BY g.created_at DESC
                        ');
                        $stmt->execute([$user['id']]);
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $grade):
                        ?>
                            <tr>
                                <td><?= escape($grade['title']) ?></td>
                                <td><?= escape($grade['name']) ?></td>
                                <td><?= $grade['score'] ?? '-' ?>/20</td>
                                <td><?= escape($grade['appreciation'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        
        <?php elseif ($user['role'] === 'parent'): ?>
            <div class="card">
                <h3>Suivi des enfants</h3>
                <p>Page parent en développement...</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
