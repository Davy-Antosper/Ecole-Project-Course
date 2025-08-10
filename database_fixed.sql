-- Supprimer la base si elle existe
DROP DATABASE IF EXISTS suivi_scolaire;

-- Créer la base de données
CREATE DATABASE suivi_scolaire;
USE suivi_scolaire;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL,
    full_name VARCHAR(100),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des élèves
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    birth_date DATE,
    class_id INT,
    parent_id INT,
    admission_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des parents
CREATE TABLE parents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    student_id INT,
    relationship VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Table des enseignants
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    specialization VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des classes
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    teacher_id INT,
    school_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des matières
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des compétences/objectifs
CREATE TABLE competences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20),
    description TEXT NOT NULL,
    subject_id INT,
    cycle VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- Table des évaluations (devoirs/travaux)
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    subject_id INT,
    class_id INT,
    teacher_id INT,
    max_score DECIMAL(5,2) DEFAULT 20,
    coefficient DECIMAL(3,2) DEFAULT 1.00,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- Table des notes
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    assignment_id INT,
    score DECIMAL(5,2),
    appreciation TEXT,
    competence_id INT,
    evaluated_by INT,
    evaluation_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (competence_id) REFERENCES competences(id) ON DELETE SET NULL,
    FOREIGN KEY (evaluated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des compétences évaluées
CREATE TABLE student_competences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    competence_id INT,
    level ENUM('non-acquis', 'en-cours', 'acquis', 'expert') DEFAULT 'en-cours',
    evaluation TEXT,
    evaluated_by INT,
    evaluation_date DATE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (competence_id) REFERENCES competences(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(100),
    message TEXT,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table d'audit (optionnel mais utile)
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insertion des utilisateurs de test
-- Mot de passe pour tous: test123 (hashé avec password_hash('test123', PASSWORD_DEFAULT))
INSERT INTO users (username, password, email, role, full_name, first_name, last_name, phone) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ecole.fr', 'admin', 'Admin École', 'Admin', 'École', '0601020304'),
('prof.math', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof.math@ecole.fr', 'teacher', 'Professeur Mathématiques', 'Jean', 'Durand', '0601020305'),
('prof.francais', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof.francais@ecole.fr', 'teacher', 'Professeur Français', 'Marie', 'Bernard', '0601020306'),
('eleve1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eleve1@ecole.fr', 'student', 'Jean Dupont', 'Jean', 'Dupont', '0601020307'),
('eleve2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eleve2@ecole.fr', 'student', 'Marie Martin', 'Marie', 'Martin', '0601020308'),
('eleve3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eleve3@ecole.fr', 'student', 'Pierre Durand', 'Pierre', 'Durand', '0601020309'),
('parent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent1@ecole.fr', 'parent', 'Parent Dupont', 'Marc', 'Dupont', '0601020310'),
('parent2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent2@ecole.fr', 'parent', 'Parent Martin', 'Sophie', 'Martin', '0601020311');

-- Insertion des matières
INSERT INTO subjects (name, code) VALUES
('Mathématiques', 'MATH'),
('Français', 'FR'),
('Sciences', 'SCI'),
('Histoire-Géographie', 'HG'),
('Éducation Physique', 'EPS'),
('Arts Plastiques', 'AP');

-- Insertion des enseignants
INSERT INTO teachers (user_id, specialization) VALUES
(2, 'Mathématiques'),
(3, 'Français');

-- Insertion des classes
INSERT INTO classes (name, teacher_id, school_year) VALUES
('CM1 A', 2, '2024-2025'),
('CM2 B', 3, '2024-2025');

-- Insertion des élèves
INSERT INTO students (user_id, birth_date, class_id, parent_id) VALUES
(4, '2015-03-15', 1, 1),
(5, '2015-07-22', 1, 2),
(6, '2014-11-30', 2, NULL);

-- Insertion des parents
INSERT INTO parents (user_id, student_id, relationship) VALUES
(7, 1, 'père'),
(8, 2, 'mère');

-- Insertion des compétences
INSERT INTO competences (code, description, subject_id, cycle) VALUES
('MAT-001', 'Effectuer des calculs additifs et soustractifs', 1, 'cycle3'),
('MAT-002', 'Résoudre des problèmes de proportionnalité', 1, 'cycle3'),
('FR-001', 'Lire et comprendre un texte court', 2, 'cycle3'),
('FR-002', 'Écrire un texte cohérent et organisé', 2, 'cycle3'),
('FR-003', 'Maîtriser les règles de grammaire de base', 2, 'cycle3'),
('SCI-001', 'Comprendre les états de la matière', 3, 'cycle3'),
('HG-001', 'Situer les grandes périodes historiques', 4, 'cycle3');

-- Insertion des devoirs
INSERT INTO assignments (title, description, subject_id, class_id, teacher_id, max_score, coefficient, due_date) VALUES
('Contrôle d\'addition', 'Exercices d\'addition et soustraction', 1, 1, 2, 20, 1.0, '2024-10-15'),
('Rédaction - Mon animal préféré', 'Rédiger un texte de 10 lignes', 2, 1, 3, 20, 1.5, '2024-10-20'),
('Problèmes de proportionnalité', '5 problèmes à résoudre', 1, 1, 2, 20, 1.2, '2024-10-25'),
('Grammaire - Les accords', 'Exercices sur les accords sujet-verbe', 2, 1, 3, 20, 1.0, '2024-10-18');

-- Insertion de notes de test
INSERT INTO grades (student_id, assignment_id, score, appreciation, evaluated_by, evaluation_date) VALUES
(1, 1, 18.5, 'Très bon travail', 2, '2024-10-16'),
(2, 1, 15.0, 'Bien mais quelques erreurs', 2, '2024-10-16'),
(1, 2, 16.0, 'Bon texte mais quelques fautes', 3, '2024-10-21'),
(2, 2, 19.0, 'Excellent !', 3, '2024-10-21'),
(1, 3, 17.5, 'Bon raisonnement', 2, '2024-10-26'),
(2, 3, 14.0, 'Difficultés sur certains problèmes', 2, '2024-10-26');

-- Insertion d'évaluations de compétences
INSERT INTO student_competences (student_id, competence_id, level, evaluation, evaluated_by, evaluation_date) VALUES
(1, 1, 'acquis', 'Maîtrise parfaite des additions et soustractions', 2, '2024-10-16'),
(1, 3, 'acquis', 'Bonne compréhension des textes', 3, '2024-10-21'),
(1, 4, 'en-cours', 'Progrès nécessaires en rédaction', 3, '2024-10-21'),
(2, 1, 'acquis', 'Compétence acquise', 2, '2024-10-16'),
(2, 3, 'expert', 'Excellente compréhension', 3, '2024-10-21'),
(2, 4, 'acquis', 'Bonne qualité de rédaction', 3, '2024-10-21');

-- Insertion de notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(4, 'Nouvelle note', 'Vous avez reçu une note de 18.5/20 en Mathématiques', 'grade'),
(5, 'Nouvelle note', 'Vous avez reçu une note de 15.0/20 en Mathématiques', 'grade'),
(4, 'Devoir à faire', 'Nouveau devoir: Problèmes de proportionnalité à rendre pour le 25/10', 'assignment'),
(5, 'Devoir à faire', 'Nouveau devoir: Problèmes de proportionnalité à rendre pour le 25/10', 'assignment');

-- Création d'index pour améliorer les performances
CREATE INDEX idx_students_class ON students(class_id);
CREATE INDEX idx_students_user ON students(user_id);
CREATE INDEX idx_teachers_user ON teachers(user_id);
CREATE INDEX idx_parents_user ON parents(user_id);
CREATE INDEX idx_grades_student ON grades(student_id);
CREATE INDEX idx_grades_assignment ON grades(assignment_id);
CREATE INDEX idx_assignments_class ON assignments(class_id);
CREATE INDEX idx_assignments_teacher ON assignments(teacher_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_student_competences_student ON student_competences(student_id);
CREATE INDEX idx_competences_subject ON competences(subject_id);

# updated

# modifie

# modifie

# modifie
