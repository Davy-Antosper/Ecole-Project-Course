-- ========================================
-- BASE DE DONNEES SUIVI SCOLAIRE
-- ========================================

DROP DATABASE IF EXISTS suivi_scolaire;
CREATE DATABASE suivi_scolaire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE suivi_scolaire;

-- ========================================
-- TABLE USERS (Utilisateurs)
-- ========================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- TABLE CLASSES
-- ========================================
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    teacher_id INT,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABLE STUDENTS
-- ========================================
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    class_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- ========================================
-- TABLE PARENTS
-- ========================================
CREATE TABLE parents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- TABLE TEACHERS
-- ========================================
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- TABLE SUBJECTS
-- ========================================
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE
);

-- ========================================
-- TABLE ASSIGNMENTS (Devoirs/Évaluations)
-- ========================================
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    class_id INT NOT NULL,
    subject_id INT,
    teacher_id INT,
    max_score DECIMAL(5,2) DEFAULT 20,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- TABLE GRADES (Notes)
-- ========================================
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    assignment_id INT NOT NULL,
    score DECIMAL(5,2),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- ========================================
-- TABLE COMPETENCES
-- ========================================
CREATE TABLE competences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20),
    description TEXT,
    subject_id INT,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- ========================================
-- INSERTION DONNEES TEST
-- ========================================

-- Utilisateurs (mot de passe: test123)
INSERT INTO users (email, password, role, first_name, last_name) VALUES
('admin@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'École'),
('prof.dupont@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Jean', 'Dupont'),
('prof.martin@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Marie', 'Martin'),
('alice@eleve.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Alice', 'Durand'),
('bob@eleve.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Bob', 'Bernard'),
('carole@parent.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Carole', 'Durand'),
('david@parent.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'David', 'Bernard');

-- Enseignants
INSERT INTO teachers (user_id) VALUES (2), (3);

-- Étudiants
INSERT INTO students (user_id, class_id) VALUES (4, NULL), (5, NULL);

-- Parents
INSERT INTO parents (user_id) VALUES (6), (7);

-- Classes
INSERT INTO classes (name, teacher_id) VALUES 
('CM1 A', 2),
('CM2 B', 3);

-- Matières
INSERT INTO subjects (name, code) VALUES
('Mathématiques', 'MATH'),
('Français', 'FR'),
('Sciences', 'SCI'),
('Histoire-Géographie', 'HG'),
('Éducation Physique', 'EPS');

-- Devoirs
INSERT INTO assignments (title, description, class_id, subject_id, teacher_id, max_score, due_date) VALUES
('Contrôle Maths', 'Calcul mental et géométrie', 1, 1, 2, 20, DATE_ADD(NOW(), INTERVAL 7 DAY)),
('Rédaction Français', 'Écrire un texte de 500 mots', 1, 2, 3, 20, DATE_ADD(NOW(), INTERVAL 5 DAY)),
('Projet Sciences', 'Enquête sur l\'énergie', 1, 3, 2, 20, DATE_ADD(NOW(), INTERVAL 10 DAY));

-- Notes de test
INSERT INTO grades (student_id, assignment_id, score, comment) VALUES
(1, 1, 18.5, 'Excellent travail'),
(1, 2, 16.0, 'Bon texte'),
(2, 1, 15.0, 'À améliorer'),
(2, 2, 17.5, 'Très bien');

-- Compétences
INSERT INTO competences (code, description, subject_id) VALUES
('MAT-001', 'Effectuer des calculs', 1),
('FR-001', 'Lire et comprendre', 2),
('SCI-001', 'Observer et analyser', 3);

# updated

# modifie

# modifie

# modifie
