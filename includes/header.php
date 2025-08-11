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
