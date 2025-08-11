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
