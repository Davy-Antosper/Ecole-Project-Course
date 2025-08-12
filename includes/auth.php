<?php
/**
 * Fichier d'authentification et gestion des permissions
 */

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

// Fonction pour vérifier les rôles
function hasRole($allowed_roles) {
    if (!isLoggedIn()) return false;
    
    $user_role = $_SESSION['role'] ?? null;
    
    if (is_array($allowed_roles)) {
        return in_array($user_role, $allowed_roles);
    }
    
    return $user_role === $allowed_roles;
}

// Fonction pour rediriger si mauvais rôle
function requireRole($allowed_roles) {
    requireLogin();
    
    if (!hasRole($allowed_roles)) {
        if (is_array($allowed_roles)) {
            $roles = implode(', ', $allowed_roles);
            $message = "Accès refusé. Rôles autorisés : $roles";
        } else {
            $message = "Accès refusé. Rôle requis : $allowed_roles";
        }
        
        die("<div style='padding: 20px; text-align: center;'>
                <h2>Accès non autorisé</h2>
                <p>$message</p>
                <a href='../dashboard/{$_SESSION['role']}.php'>Retour au tableau de bord</a>
            </div>");
    }
}

// Fonction pour vérifier les permissions spécifiques
function can($permission) {
    $user_role = $_SESSION['role'] ?? null;
    
    $permissions = [
        'admin' => [
            'manage_users',
            'manage_classes', 
            'manage_students',
            'manage_teachers',
            'manage_parents',
            'view_all_grades',
            'generate_reports',
            'manage_system'
        ],
        'teacher' => [
            'manage_own_classes',
            'grade_students',
            'evaluate_competences',
            'create_assignments',
            'view_own_grades',
            'generate_class_reports'
        ],
        'student' => [
            'view_own_grades',
            'view_own_competences',
            'view_assignments'
        ],
        'parent' => [
            'view_child_grades',
            'view_child_competences',
            'view_child_attendance'
        ]
    ];
    
    return isset($permissions[$user_role]) && in_array($permission, $permissions[$user_role]);
}

// Fonction pour vérifier l'accès à une ressource spécifique
function canAccessStudent($student_id) {
    $user_role = $_SESSION['role'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_role === 'admin') return true;
    
    if ($user_role === 'teacher') {
        // Vérifier si l'élève est dans une classe de l'enseignant
        require_once '../config.php';
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM students s
            JOIN classes c ON s.class_id = c.id
            WHERE s.id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$student_id, $user_id]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    if ($user_role === 'student') {
        // Vérifier si c'est son propre profil
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $student && $student['id'] == $student_id;
    }
    
    if ($user_role === 'parent') {
        // Vérifier si c'est son enfant
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM students s
            JOIN parents p ON s.id = p.student_id
            WHERE s.id = ? AND p.user_id = ?
        ");
        $stmt->execute([$student_id, $user_id]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    return false;
}

// Fonction pour récupérer les étudiants accessibles
function getAccessibleStudents() {
    $user_role = $_SESSION['role'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;
    
    require_once '../config.php';
    $db = getDB();
    
    $sql = "SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON s.user_id = u.id";
    $params = [];
    
    switch ($user_role) {
        case 'admin':
            // Pas de filtre pour admin
            break;
            
        case 'teacher':
            $sql .= " JOIN classes c ON s.class_id = c.id WHERE c.teacher_id = ?";
            $params[] = $user_id;
            break;
            
        case 'student':
            $sql .= " WHERE s.user_id = ?";
            $params[] = $user_id;
            break;
            
        case 'parent':
            $sql .= " JOIN parents p ON s.id = p.student_id WHERE p.user_id = ?";
            $params[] = $user_id;
            break;
            
        default:
            return [];
    }
    
    $sql .= " ORDER BY u.full_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour journaliser les actions
function logAction($action, $details = null) {
    if (!isLoggedIn()) return;
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = date('Y-m-d H:i:s') . " - User $user_id - $action";
    if ($details) {
        $log_entry .= " - " . json_encode($details);
    }
    $log_entry .= " - IP: $ip_address - Agent: $user_agent\n";
    
    // Écrire dans un fichier log
    $log_file = __DIR__ . '/../logs/actions.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Fonction pour créer une notification
function createNotification($user_id, $title, $message, $type = 'info') {
    require_once '../config.php';
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, title, message, type) 
        VALUES (?, ?, ?, ?)
    ");
    
    return $stmt->execute([$user_id, $title, $message, $type]);
}

// Fonction pour valider les données d'entrée
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';
        
        if (strpos($rule, 'required') !== false && empty(trim($value))) {
            $errors[$field] = "Le champ est obligatoire";
            continue;
        }
        
        if (strpos($rule, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = "Email invalide";
        }
        
        if (strpos($rule, 'numeric') !== false && !empty($value) && !is_numeric($value)) {
            $errors[$field] = "Doit être un nombre";
        }
        
        if (strpos($rule, 'date') !== false && !empty($value) && !strtotime($value)) {
            $errors[$field] = "Date invalide";
        }
        
        if (strpos($rule, 'min:') !== false) {
            preg_match('/min:(\d+)/', $rule, $matches);
            $min = $matches[1] ?? 0;
            if (strlen($value) < $min) {
                $errors[$field] = "Minimum $min caractères";
            }
        }
        
        if (strpos($rule, 'max:') !== false) {
            preg_match('/max:(\d+)/', $rule, $matches);
            $max = $matches[1] ?? 0;
            if (strlen($value) > $max) {
                $errors[$field] = "Maximum $max caractères";
            }
        }
    }
    
    return $errors;
}
?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
