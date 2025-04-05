CREATE DATABASE school_system;
USE school_system;

CREATE TABLE professors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    specialty VARCHAR(100)
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    school_year VARCHAR(10)
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    birth_date DATE
);

CREATE TABLE professor_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT,
    class_id INT,
    FOREIGN KEY (professor_id) REFERENCES professors(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    student_id INT,
    date DATE,
    present BOOLEAN,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Dados de exemplo
INSERT INTO professors (name, username, password, specialty) 
VALUES ('João Silva', 'joao', '12345', 'Matemática');

INSERT INTO classes (name, school_year) 
VALUES ('Turma A', '2025');

INSERT INTO professor_classes (professor_id, class_id) 
VALUES (1, 1);

INSERT INTO students (name, birth_date) 
VALUES ('Maria Souza', '2010-05-15');