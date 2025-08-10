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
    email VARCHAR(100),
    role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des élèves
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    birth_date DATE,
    class_id INT,
    parent_id INT,
    admission_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des parents
CREATE TABLE parents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    student_id INT,
    relationship VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
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

-- Table des compétences/objectifs
CREATE TABLE competences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20),
    description TEXT NOT NULL,
    subject VARCHAR(50),
    cycle VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des évaluations
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    subject VARCHAR(50),
    class_id INT,
    teacher_id INT,
    max_score DECIMAL(5,2),
    coefficient DECIMAL(3,2) DEFAULT 1.00,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
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

-- Insertion des utilisateurs de test
-- Mot de passe pour tous: test123 (hashé avec password_hash('test123', PASSWORD_DEFAULT))
INSERT INTO users (username, password, email, role, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ecole.fr', 'admin', 'Administrateur École'),
('prof.math', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof.math@ecole.fr', 'teacher', 'Professeur Mathématiques'),
('prof.francais', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prof.francais@ecole.fr', 'teacher', 'Professeur Français'),
('eleve1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eleve1@ecole.fr', 'student', 'Jean Dupont'),
('eleve2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eleve2@ecole.fr', 'student', 'Marie Martin'),
('eleve3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eleve3@ecole.fr', 'student', 'Pierre Durand'),
('parent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent1@ecole.fr', 'parent', 'Parent Dupont'),
('parent2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent2@ecole.fr', 'parent', 'Parent Martin');

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
INSERT INTO competences (code, description, subject, cycle) VALUES
('MAT-001', 'Effectuer des calculs additifs et soustractifs', 'Mathématiques', 'cycle3'),
('MAT-002', 'Résoudre des problèmes de proportionnalité', 'Mathématiques', 'cycle3'),
('FR-001', 'Lire et comprendre un texte court', 'Français', 'cycle3'),
('FR-002', 'Écrire un texte cohérent et organisé', 'Français', 'cycle3'),
('FR-003', 'Maîtriser les règles de grammaire de base', 'Français', 'cycle3'),
('SCI-001', 'Comprendre les états de la matière', 'Sciences', 'cycle3'),
('HIST-001', 'Situer les grandes périodes historiques', 'Histoire', 'cycle3');

-- Insertion des devoirs
INSERT INTO assignments (title, description, subject, class_id, teacher_id, max_score, coefficient, due_date) VALUES
('Contrôle d\'addition', 'Exercices d\'addition et soustraction', 'Mathématiques', 1, 2, 20, 1.0, '2024-10-15'),
('Rédaction - Mon animal préféré', 'Rédiger un texte de 10 lignes', 'Français', 1, 3, 20, 1.5, '2024-10-20'),
('Problèmes de proportionnalité', '5 problèmes à résoudre', 'Mathématiques', 1, 2, 20, 1.2, '2024-10-25'),
('Grammaire - Les accords', 'Exercices sur les accords sujet-verbe', 'Français', 1, 3, 20, 1.0, '2024-10-18');

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
CREATE INDEX idx_grades_student ON grades(student_id);
CREATE INDEX idx_grades_assignment ON grades(assignment_id);
CREATE INDEX idx_assignments_class ON assignments(class_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_student_competences_student ON student_competences(student_id);

-- Vues utiles
CREATE VIEW view_student_grades AS
SELECT 
    s.id as student_id,
    u.full_name as student_name,
    g.score,
    g.appreciation,
    a.title as assignment_title,
    a.subject,
    a.max_score,
    a.coefficient,
    g.evaluation_date,
    t.full_name as teacher_name
FROM students s
JOIN users u ON s.user_id = u.id
JOIN grades g ON s.id = g.student_id
JOIN assignments a ON g.assignment_id = a.id
JOIN users t ON a.teacher_id = t.id;

CREATE VIEW view_class_statistics AS
SELECT 
    c.id as class_id,
    c.name as class_name,
    COUNT(DISTINCT s.id) as student_count,
    COUNT(g.id) as grade_count,
    AVG(g.score) as average_score,
    MIN(g.score) as min_score,
    MAX(g.score) as max_score
FROM classes c
LEFT JOIN students s ON c.id = s.class_id
LEFT JOIN grades g ON s.id = g.student_id
GROUP BY c.id, c.name;
# updated

# modifie

# modifie

# modifie
