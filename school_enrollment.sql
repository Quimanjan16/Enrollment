-- School Enrollment System Database Schema
-- Philippine Curriculum (Grades 7-10)

CREATE DATABASE IF NOT EXISTS school_enrollment;
USE school_enrollment;

-- 1. Users Table (All system users)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'cashier', 'assessment', 'registrar') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- 2. Students Table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    status ENUM('new', 'enrolled', 'continuing', 'graduated', 'dropped') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_name (last_name, first_name)
);

-- 3. Enrollment Table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL, -- Format: 2024-2025
    semester INT NOT NULL, -- 1 or 2
    grade_level INT NOT NULL, -- 7, 8, 9, 10
    enrollment_status ENUM('pending', 'verified', 'enrolled', 'cancelled') DEFAULT 'pending',
    total_tuition DECIMAL(10, 2) NOT NULL DEFAULT 0,
    additional_fees DECIMAL(10, 2) DEFAULT 0,
    scholarship_amount DECIMAL(10, 2) DEFAULT 0,
    net_amount DECIMAL(10, 2) NOT NULL,
    enrolled_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, academic_year, semester),
    INDEX idx_grade_level (grade_level),
    INDEX idx_status (enrollment_status)
);

-- 4. Payment Schedule Table
CREATE TABLE payment_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    payment_type ENUM('Prelim', 'Midterm', 'Pre-Final', 'Final') NOT NULL,
    amount_due DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) DEFAULT 0,
    due_date DATE,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (enrollment_id, payment_type),
    INDEX idx_status (payment_status)
);

-- 5. Payments Table (Transaction Log)
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_schedule_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Check', 'Bank Transfer', 'Card') NOT NULL,
    reference_number VARCHAR(100),
    paid_by INT NOT NULL,
    notes TEXT,
    verification_status ENUM('pending', 'verified') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_schedule_id) REFERENCES payment_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_date (created_at),
    INDEX idx_verification (verification_status)
);

-- 6. Scholarships Table
CREATE TABLE scholarships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scholarship_name VARCHAR(100) NOT NULL,
    scholarship_type ENUM('Full', 'Partial', 'Merit-based', 'Need-based') NOT NULL,
    discount_percentage DECIMAL(5, 2),
    discount_amount DECIMAL(10, 2),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
);

-- 7. Student Scholarships Table
CREATE TABLE student_scholarships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    scholarship_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    approved_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'active', 'cancelled') DEFAULT 'pending',
    approved_by INT, -- Admin/Registrar ID
    approved_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_status (status)
);

-- 8. Documents Table
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    document_type ENUM('Form 137', 'Form 138', 'Report Card') NOT NULL,
    file_path VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT, -- Registrar ID
    verified_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_status (status)
);

-- 9. Subjects Table
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    grade_level INT NOT NULL, -- 7, 8, 9, 10
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_grade (grade_level),
    INDEX idx_code (subject_code)
);

-- 10. Student Subjects Table
CREATE TABLE student_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade VARCHAR(3),
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment_subject (enrollment_id, subject_id),
    INDEX idx_status (status)
);

-- 11. Additional Fees Table
CREATE TABLE additional_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    fee_description VARCHAR(150) NOT NULL,
    fee_amount DECIMAL(10, 2) NOT NULL,
    applicable_grade INT, -- Can apply to specific grade
    created_by INT, -- Registrar ID
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_enrollment (enrollment_id)
);

-- 12. Exam Eligibility Table
CREATE TABLE exam_eligibility (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    exam_period ENUM('Prelim', 'Midterm', 'Pre-Final', 'Final') NOT NULL,
    is_eligible BOOLEAN DEFAULT FALSE,
    checked_by INT, -- Cashier ID
    checked_at TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(id),
    UNIQUE KEY unique_exam (enrollment_id, exam_period)
);

-- 13. Activity Log Table
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50), -- 'student', 'payment', 'enrollment', etc
    entity_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (created_at),
    INDEX idx_user (user_id)
);

-- Insert Philippine Subjects for Grades 7-10
INSERT INTO subjects (subject_code, subject_name, grade_level) VALUES
-- Grade 7
('ENG7', 'English', 7),
('FIL7', 'Filipino', 7),
('MTH7', 'Mathematics', 7),
('SCI7', 'Science', 7),
('AP7', 'Araling Panlipunan', 7),
('PE7', 'Physical Education', 7),
('MAPEH7', 'Music, Arts, PE, Health', 7),
('TLE7', 'Technology and Livelihood Education', 7),
('ESP7', 'Edukasyon sa Pagpapahalaga ng Kalusugan at Kalikasan', 7),
('ICT7', 'Information and Communication Technology', 7),

-- Grade 8
('ENG8', 'English', 8),
('FIL8', 'Filipino', 8),
('MTH8', 'Mathematics', 8),
('SCI8', 'Science', 8),
('AP8', 'Araling Panlipunan', 8),
('PE8', 'Physical Education', 8),
('MAPEH8', 'Music, Arts, PE, Health', 8),
('TLE8', 'Technology and Livelihood Education', 8),
('ESP8', 'Edukasyon sa Pagpapahalaga ng Kalusugan at Kalikasan', 8),
('ICT8', 'Information and Communication Technology', 8),

-- Grade 9
('ENG9', 'English', 9),
('FIL9', 'Filipino', 9),
('MTH9', 'Mathematics', 9),
('SCI9', 'Science', 9),
('AP9', 'Araling Panlipunan', 9),
('PE9', 'Physical Education', 9),
('MAPEH9', 'Music, Arts, PE, Health', 9),
('TLE9', 'Technology and Livelihood Education', 9),
('ESP9', 'Edukasyon sa Pagpapahalaga ng Kalusugan at Kalikasan', 9),
('ICT9', 'Information and Communication Technology', 9),

-- Grade 10
('ENG10', 'English', 10),
('FIL10', 'Filipino', 10),
('MTH10', 'Mathematics', 10),
('SCI10', 'Science', 10),
('AP10', 'Araling Panlipunan', 10),
('PE10', 'Physical Education', 10),
('MAPEH10', 'Music, Arts, PE, Health', 10),
('TLE10', 'Technology and Livelihood Education', 10),
('ESP10', 'Edukasyon sa Pagpapahalaga ng Kalusugan at Kalikasan', 10),
('ICT10', 'Information and Communication Technology', 10);

-- Insert Sample Scholarships
INSERT INTO scholarships (scholarship_name, scholarship_type, discount_percentage, description) VALUES
('Academic Excellence', 'Merit-based', 25, 'For students with excellent academic performance'),
('Financial Assistance', 'Need-based', 50, 'For underprivileged students'),
('Sports Scholarship', 'Merit-based', 20, 'For athletes'),
('Indigenous Peoples', 'Partial', 15, 'For Indigenous People students'),
('Solo Parent Support', 'Need-based', 30, 'For students with solo parents');

-- Insert Sample Admin User (password: admin123)
INSERT INTO users (username, email, full_name, role, password) VALUES
('admin', 'admin@school.edu.ph', 'Administrator', 'admin', '$2y$10$ddWlhdNSf9VvuQinZrZ9..m1.pcKHo1comnAC5uiKXVasuZEo4ay.');
