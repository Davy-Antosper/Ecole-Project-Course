<?php
require_once '../config.php';

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$user = getCurrentUser();

// Rediriger si pas d'utilisateur valide
if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Rediriger si ce n'est pas un élève
if ($user['role'] !== 'student') {
    header('Location: ' . BASE_URL . 'dashboard/' . $user['role'] . '.php');
    exit();
}

$db = connectDB();

// Récupérer les informations de l'élève
$stmt = $db->prepare("
    SELECT s.*, c.name as class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.user_id = ?
");
$stmt->execute([$user['id']]);
$student = $stmt->fetch();

if (!$student) {
    die("Profil élève non trouvé");
}

// Récupérer les notes
$stmt = $db->prepare("
    SELECT 
        g.*,
        a.title as evaluation_title,
        a.max_score,
        sub.name as subject_name,
        u.first_name,
        u.last_name
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN users u ON a.teacher_id = u.id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
");
$stmt->execute([$student['id']]);
$grades = $stmt->fetchAll();

// Calculer les moyennes par matière
$subjects = [];
$totalWeighted = 0;
$totalCoefficient = 0;

foreach ($grades as $grade) {
    $subject = $grade['subject_name'];
    
    if (!isset($subjects[$subject])) {
        $subjects[$subject] = [
            'grades' => [],
            'total' => 0,
            'coefficient' => 0,
            'count' => 0
        ];
    }
    
    $subjects[$subject]['grades'][] = $grade;
    $subjects[$subject]['total'] += $grade['score'] * $grade['coefficient'];
    $subjects[$subject]['coefficient'] += $grade['coefficient'];
    $subjects[$subject]['count']++;
    
    $totalWeighted += $grade['score'] * $grade['coefficient'];
    $totalCoefficient += $grade['coefficient'];
}

// Moyenne générale
$average = $totalCoefficient > 0 ? $totalWeighted / $totalCoefficient : 0;

// Récupérer les compétences évaluées
$stmt = $db->prepare("
    SELECT 
        sc.*,
        c.code as competence_code,
        c.description as competence_description,
        sub.name as subject_name
    FROM student_competences sc
    JOIN competences c ON sc.competence_id = c.id
    JOIN subjects sub ON c.subject_id = sub.id
    WHERE sc.student_id = ?
    ORDER BY sc.evaluation_date DESC
");
$stmt->execute([$student['id']]);
$competences = $stmt->fetchAll();

// Récupérer les devoirs à venir
$stmt = $db->prepare("
    SELECT 
        a.*,
        sub.name as subject_name,
        u.first_name,
        u.last_name
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN users u ON a.teacher_id = u.id
    WHERE a.class_id = ? AND a.due_date >= CURDATE()
    ORDER BY a.due_date ASC
    LIMIT 5
");
$stmt->execute([$student['class_id']]);
$upcomingEvaluations = $stmt->fetchAll();

// Récupérer les notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Élève</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h2>Suivi Scolaire</h2>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <span>Élève - <?= htmlspecialchars($student['class_name'] ?? 'Non affecté') ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <a href="student.php" class="menu-item active">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="#notes" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Mes notes</span>
                </a>
                
                <a href="#competences" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Mes compétences</span>
                </a>
                
                <a href="#devoirs" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Mes devoirs</span>
                </a>
                
                <a href="#bulletins" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Mes bulletins</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="#messages" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if (count($notifications) > 0): ?>
                    <span style="background: #f72585; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; margin-left: auto;">
                        <?= count($notifications) ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div>
                    <h1>Tableau de bord Élève</h1>
                    <p style="color: #6c757d; margin-top: 5px;">Bonjour <?= htmlspecialchars($user['first_name']) ?> !</p>
                </div>
                
                <div class="header-actions">
                    <button class="notification-btn" onclick="showNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifications) > 0): ?>
                        <span class="notification-badge"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="search-box">
                        <input type="text" placeholder="Rechercher..." style="padding: 8px 15px; border-radius: 20px; border: 1px solid #e9ecef;">
                    </div>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Moyenne générale</h3>
                        <div class="stat-number"><?= number_format($average, 2) ?>/20</div>
                        <div class="progress-bar" style="height: 8px; background: #e9ecef; border-radius: 4px; margin-top: 10px;">
                            <div class="progress-fill" style="height: 100%; width: <?= ($average / 20) * 100 ?>%; background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%); border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f72585 0%, #b5179e 100%);">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Notes reçues</h3>
                        <div class="stat-number"><?= count($grades) ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> <?= count($grades) > 0 ? round(count($grades) / 10 * 100) : 0 ?>% du trimestre
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Compétences</h3>
                        <div class="stat-number"><?= count($competences) ?></div>
                        <div class="stat-change">
                            <?php 
                            $acquired = array_filter($competences, fn($c) => $c['level'] === 'acquis' || $c['level'] === 'expert');
                            echo count($acquired) . ' acquises';
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f8961e 0%, #f3722c 100%);">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Devoirs à venir</h3>
                        <div class="stat-number"><?= count($upcomingEvaluations) ?></div>
                        <div class="stat-change">
                            <i class="fas fa-clock"></i> Prochain : 
                            <?= count($upcomingEvaluations) > 0 ? date('d/m', strtotime($upcomingEvaluations[0]['date'])) : 'Aucun' ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dernières notes -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Dernières notes</h2>
                    <a href="#notes" class="btn btn-sm btn-primary">
                        Voir toutes mes notes
                    </a>
                </div>
                
                <?php if (empty($grades)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune note pour le moment</h3>
                    <p>Vos notes apparaîtront ici dès qu'elles seront saisies par vos enseignants.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Devoir</th>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Coefficient</th>
                            <th>Professeur</th>
                            <th>Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($grades, 0, 5) as $grade): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($grade['subject_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($grade['evaluation_title']) ?></td>
                            <td><?= date('d/m/Y', strtotime($grade['evaluation_date'])) ?></td>
                            <td>
                                <span class="badge <?= $grade['score'] >= 10 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= number_format($grade['score'], 2) ?>/<?= $grade['max_score'] ?>
                                </span>
                                <small style="display: block; color: #6c757d;">
                                    (<?= number_format(($grade['score'] / $grade['max_score']) * 20, 2) ?>/20)
                                </small>
                            </td>
                            <td><?= $grade['coefficient'] ?></td>
                            <td><?= htmlspecialchars($grade['teacher_name']) ?></td>
                            <td>
                                <?php if ($grade['appreciation']): ?>
                                <span data-tooltip="<?= htmlspecialchars($grade['appreciation']) ?>">
                                    <?= htmlspecialchars(substr($grade['appreciation'], 0, 30)) ?>...
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Compétences évaluées -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Compétences évaluées</h2>
                </div>
                
                <?php if (empty($competences)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-tasks" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune compétence évaluée</h3>
                    <p>Vos compétences apparaîtront ici dès qu'elles seront évaluées par vos enseignants.</p>
                </div>
                <?php else: ?>
                <div style="padding: 20px;">
                    <div class="competence-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                        <?php foreach (array_slice($competences, 0, 6) as $comp): ?>
                        <div class="competence-card" style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid; 
                            <?php 
                            switch($comp['level']) {
                                case 'non-acquis': echo 'border-color: #f72585; background: rgba(247, 37, 133, 0.05);'; break;
                                case 'en-cours': echo 'border-color: #f8961e; background: rgba(248, 150, 30, 0.05);'; break;
                                case 'acquis': echo 'border-color: #4cc9f0; background: rgba(76, 201, 240, 0.05);'; break;
                                case 'expert': echo 'border-color: #2ecc71; background: rgba(46, 204, 113, 0.05);'; break;
                            }
                            ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <div>
                                    <strong style="display: block;"><?= htmlspecialchars($comp['subject_name']) ?></strong>
                                    <small style="color: #6c757d;"><?= htmlspecialchars($comp['competence_code']) ?></small>
                                </div>
                                <span class="badge" style="
                                    <?php 
                                    switch($comp['level']) {
                                        case 'non-acquis': echo 'background: rgba(247, 37, 133, 0.1); color: #c2185b;'; break;
                                        case 'en-cours': echo 'background: rgba(248, 150, 30, 0.1); color: #e67e22;'; break;
                                        case 'acquis': echo 'background: rgba(76, 201, 240, 0.1); color: #0097a7;'; break;
                                        case 'expert': echo 'background: rgba(46, 204, 113, 0.1); color: #27ae60;'; break;
                                    }
                                    ?>">
                                    <?= ucfirst(str_replace('-', ' ', $comp['level'])) ?>
                                </span>
                            </div>
                            
                            <p style="margin: 10px 0; font-size: 0.9rem;"><?= htmlspecialchars($comp['competence_description']) ?></p>
                            
                            <?php if ($comp['comment']): ?>
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);">
                                <small style="color: #6c757d;">
                                    <i class="fas fa-comment"></i> 
                                    <?= htmlspecialchars(substr($comp['comment'], 0, 100)) ?>
                                    <?= strlen($comp['comment']) > 100 ? '...' : '' ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 10px; font-size: 0.8rem; color: #6c757d;">
                                <i class="fas fa-calendar"></i> 
                                <?= date('d/m/Y', strtotime($comp['evaluation_date'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Devoirs à venir -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Devoirs à venir</h2>
                </div>
                
                <?php if (empty($upcomingEvaluations)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-calendar-check" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucun devoir à venir</h3>
                    <p>Profitez-en pour réviser vos leçons !</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Devoir</th>
                            <th>Type</th>
                            <th>Date limite</th>
                            <th>Professeur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingEvaluations as $eval): ?>
                        <tr>
                            <td><?= htmlspecialchars($eval['subject_name']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($eval['title']) ?></strong>
                                <?php if ($eval['description']): ?>
                                <br>
                                <small style="color: #6c757d;"><?= htmlspecialchars(substr($eval['description'], 0, 50)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= ucfirst($eval['type']) ?></span>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($eval['date'])) ?>
                                <?php 
                                $daysLeft = floor((strtotime($eval['date']) - time()) / (60 * 60 * 24));
                                if ($daysLeft <= 3) {
                                    echo '<br><small style="color: #f72585;"><i class="fas fa-exclamation-circle"></i> ', $daysLeft, ' jour(s)</small>';
                                } elseif ($daysLeft <= 7) {
                                    echo '<br><small style="color: #f8961e;"><i class="fas fa-clock"></i> ', $daysLeft, ' jour(s)</small>';
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($eval['teacher_name']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showEvaluationDetails(<?= $eval['id'] ?>)">
                                    <i class="fas fa-eye"></i> Détails
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Moyennes par matière -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Moyennes par matière</h2>
                </div>
                
                <?php if (empty($subjects)): ?>
                <div style="padding: 40px; text-align: center; color: #6c757d;">
                    <i class="fas fa-chart-pie" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Aucune moyenne disponible</h3>
                    <p>Les moyennes apparaîtront dès que vous aurez plusieurs notes.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Nombre de notes</th>
                            <th>Moyenne</th>
                            <th>Progression</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subjectName => $subjectData): 
                            $subjectAverage = $subjectData['coefficient'] > 0 ? $subjectData['total'] / $subjectData['coefficient'] : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($subjectName) ?></strong>
                            </td>
                            <td><?= $subjectData['count'] ?> note(s)</td>
                            <td>
                                <span class="badge <?= $subjectAverage >= 10 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= number_format($subjectAverage, 2) ?>/20
                                </span>
                            </td>
                            <td style="width: 200px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="progress-bar" style="flex: 1; height: 8px; background: #e9ecef; border-radius: 4px;">
                                        <div class="progress-fill" style="height: 100%; width: <?= ($subjectAverage / 20) * 100 ?>%; 
                                            background: <?= $subjectAverage >= 10 ? 'linear-gradient(135deg, #2ecc71 0%, #27ae60 100%)' : 'linear-gradient(135deg, #f72585 0%, #c2185b 100%)' ?>; 
                                            border-radius: 4px;"></div>
                                    </div>
                                    <small style="min-width: 40px; text-align: right;"><?= number_format(($subjectAverage / 20) * 100, 1) ?>%</small>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary" onclick="showSubjectDetails('<?= htmlspecialchars($subjectName) ?>')">
                                    <i class="fas fa-chart-bar"></i> Statistiques
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Panel des notifications -->
    <div id="notificationPanel" style="display: none; position: fixed; top: 70px; right: 20px; width: 350px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 1000; max-height: 400px; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
            <h3 style="margin: 0; display: flex; justify-content: space-between; align-items: center;">
                Notifications
                <button onclick="hideNotifications()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #6c757d;">
                    <i class="fas fa-times"></i>
                </button>
            </h3>
        </div>
        
        <div id="notificationList" style="padding: 0;">
            <?php foreach ($notifications as $notification): ?>
            <div style="padding: 15px; border-bottom: 1px solid #e9ecef; cursor: pointer;" onclick="markAsRead(<?= $notification['id'] ?>)">
                <div style="display: flex; gap: 10px;">
                    <div style="background: rgba(67, 97, 238, 0.1); width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4361ee;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div style="flex: 1;">
                        <strong style="display: block;"><?= htmlspecialchars($notification['title']) ?></strong>
                        <p style="margin: 5px 0 0; color: #6c757d; font-size: 0.9rem;"><?= htmlspecialchars($notification['message']) ?></p>
                        <small style="color: #adb5bd; font-size: 0.8rem;">
                            <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($notifications)): ?>
            <div style="padding: 40px; text-align: center; color: #6c757d;">
                <i class="fas fa-bell-slash" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p>Aucune notification</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="padding: 15px; text-align: center; border-top: 1px solid #e9ecef;">
            <a href="#" style="color: #4361ee; text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-history"></i> Voir toutes les notifications
            </a>
        </div>
    </div>
    
    <script src="../script.js"></script>
    <script>
    function showNotifications() {
        const panel = document.getElementById('notificationPanel');
        panel.style.display = 'block';
        
        // Fermer en cliquant à l'extérieur
        document.addEventListener('click', function closePanel(e) {
            if (!e.target.closest('.notification-btn') && !e.target.closest('#notificationPanel')) {
                panel.style.display = 'none';
                document.removeEventListener('click', closePanel);
            }
        });
    }
    
    function hideNotifications() {
        document.getElementById('notificationPanel').style.display = 'none';
    }
    
    function markAsRead(notificationId) {
        fetch('../api/mark-notification-read.php?id=' + notificationId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.querySelector(`[onclick="markAsRead(${notificationId})"]`);
                    if (notification) {
                        notification.style.opacity = '0.5';
                        setTimeout(() => notification.remove(), 300);
                    }
                    
                    // Mettre à jour le badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.remove();
                        }
                    }
                }
            });
    }
    
    function showEvaluationDetails(evaluationId) {
        // Simuler l'affichage des détails
        alert('Détails du devoir ' + evaluationId + ' - Fonctionnalité à implémenter');
    }
    
    function showSubjectDetails(subjectName) {
        // Simuler l'affichage des statistiques
        alert('Statistiques de ' + subjectName + ' - Fonctionnalité à implémenter');
    }
    
    // Gestion des tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 0.8rem;
                    z-index: 1000;
                    max-width: 300px;
                    white-space: normal;
                `;
                
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
                
                this.tooltip = tooltip;
            });
            
            element.addEventListener('mouseleave', function() {
                if (this.tooltip) {
                    this.tooltip.remove();
                    this.tooltip = null;
                }
            });
        });
    });
    </script>
</body>
</html>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
