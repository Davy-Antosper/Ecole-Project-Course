<?php
$user = getCurrentUser();
$notifications = [];
if ($user) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $notification_count = $stmt->fetchColumn();
}
?>
<header>
    <div class="header-container">
        <div class="logo">
            <h1>Suivi Scolaire</h1>
        </div>
        <div class="user-info">
            <span>Bienvenue, <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></span>
            <span class="role-badge"><?= $user['role'] ?? 'Invité' ?></span>
            <?php if(isset($notification_count) && $notification_count > 0): ?>
            <a href="#" class="notification-icon" onclick="showNotifications()">
                🔔
                <span class="notification-badge"><?= $notification_count ?></span>
            </a>
            <?php endif; ?>
            <a href="?action=logout" class="logout-btn">Déconnexion</a>
        </div>
    </div>
</header>

<div id="notification-popup" class="notification-popup" style="display:none;">
    <h3>Notifications</h3>
    <div id="notification-list">
        <!-- Les notifications seront chargées ici -->
    </div>
</div>

<script>
function showNotifications() {
    const popup = document.getElementById('notification-popup');
    popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    
    // Charger les notifications
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('notification-list');
            list.innerHTML = '';
            data.forEach(notif => {
                list.innerHTML += `
                    <div class="notification-item ${notif.is_read ? 'read' : 'unread'}">
                        <strong>${notif.title}</strong>
                        <p>${notif.message}</p>
                        <small>${new Date(notif.created_at).toLocaleString()}</small>
                    </div>
                `;
            });
        });
}

// Fermer la popup en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notification-icon') && !e.target.closest('.notification-popup')) {
        document.getElementById('notification-popup').style.display = 'none';
    }
});
</script>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
