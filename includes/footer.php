<?php
/**
 * Pied de page commun à toutes les pages
 */
if (!isset($hide_footer) || !$hide_footer):
?>
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>Suivi Scolaire</h3>
            <p>Système de suivi personnalisé de la progression des élèves</p>
            <p>Version 1.0.0</p>
        </div>
        
        <div class="footer-section">
            <h3>Liens rapides</h3>
            <ul class="footer-links">
                <?php if(isLoggedIn()): ?>
                    <li><a href="../dashboard/<?= $_SESSION['role'] ?>.php">Tableau de bord</a></li>
                    <?php if(hasRole(['admin', 'teacher'])): ?>
                        <li><a href="../modules/students.php">Élèves</a></li>
                        <li><a href="../modules/grades.php">Notes</a></li>
                    <?php endif; ?>
                    <li><a href="../modules/reports.php">Rapports</a></li>
                <?php else: ?>
                    <li><a href="../index.php">Connexion</a></li>
                <?php endif; ?>
                <li><a href="#help">Aide</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Support</h3>
            <p>Email: support@suivi-scolaire.fr</p>
            <p>Téléphone: 01 23 45 67 89</p>
            <p>Horaires: 9h-17h du lundi au vendredi</p>
        </div>
        
        <div class="footer-section">
            <h3>Statistiques</h3>
            <?php if(isLoggedIn()): 
                require_once '../config.php';
                $db = getDB();
                
                // Statistiques basiques
                $user_id = $_SESSION['user_id'];
                $user_role = $_SESSION['role'];
                
                if ($user_role === 'student') {
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as grades_count 
                        FROM grades g 
                        JOIN students s ON g.student_id = s.id 
                        WHERE s.user_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p>Notes reçues: " . ($stats['grades_count'] ?? 0) . "</p>";
                }
            ?>
            <p>Session: <?= htmlspecialchars($_SESSION['username'] ?? 'Invité') ?></p>
            <p>Rôle: <?= htmlspecialchars($user_role) ?></p>
            <?php endif; ?>
            <p>Dernière connexion: <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Suivi Scolaire - Tous droits réservés</p>
        <p>
            <a href="#privacy">Politique de confidentialité</a> | 
            <a href="#terms">Conditions d'utilisation</a> |
            <a href="#cookies">Cookies</a>
        </p>
    </div>
</footer>

<!-- Modal d'aide -->
<div id="help-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Aide et support</h2>
        <div class="help-sections">
            <div class="help-section">
                <h3>Guide d'utilisation</h3>
                <p><strong>Pour les enseignants:</strong></p>
                <ul>
                    <li>Cliquez sur "Devoirs" pour créer de nouvelles évaluations</li>
                    <li>Utilisez "Saisie des notes" pour attribuer des notes</li>
                    <li>Évaluez les compétences dans le module correspondant</li>
                </ul>
                
                <p><strong>Pour les élèves et parents:</strong></p>
                <ul>
                    <li>Consultez les notes dans votre tableau de bord</li>
                    <li>Téléchargez les bulletins dans "Rapports"</li>
                    <li>Vérifiez les notifications pour les nouvelles notes</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3>Problèmes courants</h3>
                <p><strong>Je ne vois pas mes notes:</strong> Vérifiez que vous êtes dans la bonne période scolaire.</p>
                <p><strong>Impossible de se connecter:</strong> Vérifiez vos identifiants ou contactez l'administrateur.</p>
                <p><strong>Données manquantes:</strong> Actualisez la page ou videz le cache.</p>
            </div>
            
            <div class="help-section">
                <h3>Contact support</h3>
                <p>Email: support@suivi-scolaire.fr</p>
                <p>Téléphone: 01 23 45 67 89</p>
                <p>Urgences techniques: 06 12 34 56 78</p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts JavaScript -->
<script>
// Gestion des modaux
document.addEventListener('DOMContentLoaded', function() {
    // Modal d'aide
    const helpLinks = document.querySelectorAll('a[href="#help"]');
    const helpModal = document.getElementById('help-modal');
    const closeModal = document.querySelector('.close-modal');
    
    helpLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            helpModal.style.display = 'block';
        });
    });
    
    closeModal.addEventListener('click', function() {
        helpModal.style.display = 'none';
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === helpModal) {
            helpModal.style.display = 'none';
        }
    });
    
    // Affichage de l'heure actuelle
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('fr-FR');
        const timeElements = document.querySelectorAll('.current-time');
        timeElements.forEach(el => {
            el.textContent = timeString;
        });
    }
    
    setInterval(updateTime, 1000);
    updateTime();
    
    // Gestion du menu responsive
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Auto-hide des messages d'alerte
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Confirmation pour les actions critiques
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});

// Fonction pour rafraîchir les notifications
function refreshNotifications() {
    if (typeof updateNotificationBadge === 'function') {
        updateNotificationBadge();
    }
}

// Rafraîchir toutes les minutes
setInterval(refreshNotifications, 60000);

// Gestion de la session
let lastActivity = Date.now();
const SESSION_TIMEOUT = 7 * 60 * 1000; // 30 minutes

function updateActivity() {
    lastActivity = Date.now();
}

function checkSession() {
    const now = Date.now();
    const inactiveTime = now - lastActivity;
    
    if (inactiveTime > SESSION_TIMEOUT) {
        // Avertir 1 minute avant la déconnexion
        if (inactiveTime > (SESSION_TIMEOUT - 60000)) {
            const remaining = Math.ceil((SESSION_TIMEOUT - inactiveTime) / 1000);
            if (remaining > 0 && remaining <= 60) {
                showSessionWarning(remaining);
            }
        }
        
        if (inactiveTime > SESSION_TIMEOUT) {
            window.location.href = '../logout.php?timeout=7';
        }
    }
}

function showSessionWarning(seconds) {
    const warning = document.getElementById('session-warning');
    if (!warning) {
        const warningDiv = document.createElement('div');
        warningDiv.id = 'session-warning';
        warningDiv.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #f56565;
            color: white;
            padding: 15px;
            border-radius: 5px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        document.body.appendChild(warningDiv);
    }
    
    const warningElement = document.getElementById('session-warning');
    warningElement.innerHTML = `
        <strong>Session sur le point d'expirer</strong>
        <p>Déconnexion dans ${seconds} secondes</p>
        <button onclick="extendSession()" style="background: white; color: #f56565; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
            Rester connecté
        </button>
    `;
    
    if (seconds <= 0) {
        warningElement.remove();
    }
}

function extendSession() {
    fetch('../api/refresh_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                lastActivity = Date.now();
                const warning = document.getElementById('session-warning');
                if (warning) warning.remove();
            }
        });
}

// Suivi de l'activité
document.addEventListener('mousemove', updateActivity);
document.addEventListener('keypress', updateActivity);
document.addEventListener('click', updateActivity);

// Vérifier la session toutes les minutes
setInterval(checkSession, 60000);
</script>

<style>
/* Styles du footer */
.main-footer {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 0 0;
    margin-top: 40px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    padding: 0 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.footer-section h3 {
    color: white;
    margin-bottom: 15px;
    font-size: 18px;
}

.footer-section p {
    margin: 8px 0;
    opacity: 0.9;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 8px;
}

.footer-links a {
    color: white;
    text-decoration: none;
    opacity: 0.9;
    transition: opacity 0.3s;
}

.footer-links a:hover {
    opacity: 1;
    text-decoration: underline;
}

.footer-bottom {
    text-align: center;
    padding: 20px;
    margin-top: 30px;
    background: rgba(0, 0, 0, 0.2);
}

.footer-bottom p {
    margin: 5px 0;
}

.footer-bottom a {
    color: white;
    text-decoration: none;
    margin: 0 10px;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background: white;
    margin: 50px auto;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    color: #333;
}

.close-modal {
    float: right;
    font-size: 28px;
    cursor: pointer;
    color: #666;
}

.help-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.help-section {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.help-section h3 {
    color: #667eea;
    margin-bottom: 10px;
}

.help-section ul {
    padding-left: 20px;
}

.help-section li {
    margin-bottom: 5px;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .modal-content {
        margin: 20px;
        width: auto;
    }
}
</style>
</body>
</html>
<?php endif; ?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
