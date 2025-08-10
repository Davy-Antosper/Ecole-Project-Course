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
    admission_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des parents
CREATE TABLE parents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    student_id INT,
    relationship VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Table des classes
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    teacher_id INT,
    school_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    FOREIGN KEY (class_id) REFERENCES classes(id)
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
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (competence_id) REFERENCES competences(id)
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
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (competence_id) REFERENCES competences(id)
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
    FOREIGN KEY (user_id) REFERENCES users(id)
);


-- Mot de passe : admin123
INSERT INTO users (username, password, role, full_name) VALUES 
('admin', '$2y$10$YourHashHere', 'admin', 'Administrateur'),
('prof', '$2y$10$YourHashHere', 'teacher', 'Professeur Test'),
('eleve', '$2y$10$YourHashHere', 'student', 'Élève Test'),
('parent', '$2y$10$YourHashHere', 'parent', 'Parent Test');
# updated

# modifie

# modifie

# modifie
