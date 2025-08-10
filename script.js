// Gestion des notifications
document.addEventListener('DOMContentLoaded', function() {
    // Toggle du menu mobile
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Gestion des onglets
    const tabs = document.querySelectorAll('[data-tab]');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabs.forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Activer l'onglet courant
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Confirmation des actions
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir continuer ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide des alertes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Validation des formulaires
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Ajouter un message d'erreur
                    let errorMessage = field.parentNode.querySelector('.error-message');
                    if (!errorMessage) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.style.color = '#f72585';
                        errorMessage.style.fontSize = '0.8rem';
                        errorMessage.style.marginTop = '5px';
                        field.parentNode.appendChild(errorMessage);
                    }
                    errorMessage.textContent = 'Ce champ est obligatoire';
                } else {
                    field.classList.remove('error');
                    const errorMessage = field.parentNode.querySelector('.error-message');
                    if (errorMessage) errorMessage.remove();
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Veuillez remplir tous les champs obligatoires', 'error');
            }
        });
    });
    
    // Gestion des notifications
    const notificationBtn = document.querySelector('.notification-btn');
    const notificationPanel = document.querySelector('.notification-panel');
    
    if (notificationBtn && notificationPanel) {
        notificationBtn.addEventListener('click', function() {
            notificationPanel.classList.toggle('show');
        });
        
        // Fermer en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (!notificationBtn.contains(e.target) && !notificationPanel.contains(e.target)) {
                notificationPanel.classList.remove('show');
            }
        });
    }
    
    // Mise à jour du badge de notifications
    function updateNotificationBadge() {
        fetch('/suivi-scolaire/api/get-notifications.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = data.unread;
                    badge.style.display = data.unread > 0 ? 'flex' : 'none';
                }
            });
    }
    
    // Rafraîchir toutes les minutes
    setInterval(updateNotificationBadge, 60000);
    updateNotificationBadge();
    
    // Fonction pour afficher des toasts
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Fermeture automatique
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
        
        // Fermeture manuelle
        toast.querySelector('.toast-close').addEventListener('click', function() {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
    };
    
    // Gestion de la session
    let lastActivity = Date.now();
    const SESSION_TIMEOUT = 4 * 60 * 1000; // 30 minutes
    
    function updateActivity() {
        lastActivity = Date.now();
    }
    
    function checkSession() {
        const now = Date.now();
        const inactiveTime = now - lastActivity;
        
        if (inactiveTime > SESSION_TIMEOUT - 60000) { // 1 minute avant expiration
            const remaining = Math.ceil((SESSION_TIMEOUT - inactiveTime) / 1000);
            if (remaining > 0 && remaining <= 60) {
                showSessionWarning(remaining);
            }
        }
        
        if (inactiveTime > SESSION_TIMEOUT) {
            window.location.href = '/suivi-scolaire/logout.php?timeout=4';
        }
    }
    
    function showSessionWarning(seconds) {
        let warning = document.getElementById('session-warning');
        if (!warning) {
            warning = document.createElement('div');
            warning.id = 'session-warning';
            warning.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: white;
                padding: 20px;
                border-radius: var(--border-radius);
                box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                z-index: 9999;
                max-width: 300px;
            `;
            warning.innerHTML = `
                <h4 style="margin-bottom: 10px;">Session sur le point d'expirer</h4>
                <p style="margin-bottom: 15px;">Déconnexion dans <span id="session-countdown">${seconds}</span> secondes</p>
                <button onclick="extendSession()" class="btn btn-primary btn-sm">
                    Rester connecté
                </button>
            `;
            document.body.appendChild(warning);
        }
        
        document.getElementById('session-countdown').textContent = seconds;
        
        if (seconds <= 0) {
            warning.remove();
        }
    }
    
    window.extendSession = function() {
        fetch('/suivi-scolaire/api/refresh-session.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    lastActivity = Date.now();
                    const warning = document.getElementById('session-warning');
                    if (warning) warning.remove();
                    showToast('Session prolongée', 'success');
                }
            });
    };
    
    // Suivi de l'activité
    ['mousemove', 'keypress', 'click'].forEach(event => {
        document.addEventListener(event, updateActivity);
    });
    
    // Vérifier la session toutes les minutes
    setInterval(checkSession, 60000);
    
    // Initialiser les tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
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

// Gestion des filtres de tableau
function filterTable(tableId, searchId) {
    const search = document.getElementById(searchId).value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    }
}

// Fonction pour exporter des données
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const rowData = [];
        const cols = row.querySelectorAll('td, th');
        
        cols.forEach(col => {
            rowData.push(`"${col.textContent.trim().replace(/"/g, '""')}"`);
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, filename);
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}
// Refactored: better async handling

// correction asynchrone

// correction asynchrone

// correction asynchrone
