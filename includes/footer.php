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
    
