-- CREATE DATABASE IF NOT EXISTS examflow;
-- USE examflow;

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    failed_attempts INT DEFAULT 0,
    last_attempt TIMESTAMP NULL DEFAULT NULL
);

-- Exams table
CREATE TABLE IF NOT EXISTS exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(10),
    title VARCHAR(255),
    description TEXT,
    time_limit INT, -- in minutes
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    question_text TEXT,
    option_a VARCHAR(255),
    option_b VARCHAR(255),
    option_c VARCHAR(255),
    option_d VARCHAR(255),
    correct_answer CHAR(1), -- 'A', 'B', 'C', or 'D'
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
);

-- Exam Participants & Scores
CREATE TABLE IF NOT EXISTS exam_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    participant_id VARCHAR(10),
    score INT,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id),
    FOREIGN KEY (participant_id) REFERENCES users(id)
);

-- Add exam access credentials and status
ALTER TABLE exams 
ADD COLUMN exam_password VARCHAR(50) AFTER time_limit,
ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER exam_password,
ADD COLUMN start_time DATETIME AFTER is_active,
ADD COLUMN end_time DATETIME AFTER start_time;

-- Add question points value
ALTER TABLE questions
ADD COLUMN points INT DEFAULT 1 AFTER correct_answer;

-- Create exam access table for additional security
CREATE TABLE IF NOT EXISTS exam_access (
    access_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    participant_id VARCHAR(10),
    access_granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id),
    FOREIGN KEY (participant_id) REFERENCES users(id),
    UNIQUE KEY (exam_id, participant_id)
);