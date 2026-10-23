-- Hostel Complaint Tracking System - MySQL 8 Schema
-- In phpMyAdmin, select the target database before importing this file.
-- This avoids hardcoding a database name that may differ on shared hosting.

-- Table: users
CREATE TABLE IF NOT EXISTS users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  room_no VARCHAR(10),
  phone VARCHAR(15),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: categories
CREATE TABLE IF NOT EXISTS categories (
  category_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: complaints
CREATE TABLE IF NOT EXISTS complaints (
  complaint_id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  category_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  image_path VARCHAR(255),
  priority ENUM('low','medium','high') DEFAULT 'medium',
  status ENUM('pending','assigned','in_progress','resolved','closed') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_student (student_id),
  INDEX idx_category (category_id),
  INDEX idx_status (status),
  INDEX idx_priority (priority),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: complaint_updates
CREATE TABLE IF NOT EXISTS complaint_updates (
  update_id INT PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT NOT NULL,
  updated_by INT NOT NULL,
  old_status VARCHAR(20),
  new_status VARCHAR(20) NOT NULL,
  remarks TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_complaint (complaint_id),
  INDEX idx_updated_by (updated_by),
  INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: feedback
CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT NOT NULL UNIQUE,
  rating TINYINT CHECK (rating >= 1 AND rating <= 5),
  comments TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data Insertion

-- Insert Admin User
INSERT INTO users (name, email, password_hash, role, phone) VALUES
('Admin User', 'admin@hostel.com', '$2y$10$wqN0kKvbKL0Q7z.uRK5V.OMfT5ksXNnlCMzlCJRj3T2PsNBXf5yDK', 'admin', '9999999999');

-- Insert Student Users
INSERT INTO users (name, email, password_hash, role, room_no, phone) VALUES
('John Doe', 'john.doe@student.com', '$2y$10$wqN0kKvbKL0Q7z.uRK5V.OMfT5ksXNnlCMzlCJRj3T2PsNBXf5yDK', 'student', '101', '9111111111'),
('Jane Smith', 'jane.smith@student.com', '$2y$10$wqN0kKvbKL0Q7z.uRK5V.OMfT5ksXNnlCMzlCJRj3T2PsNBXf5yDK', 'student', '202', '9222222222');

-- Insert Categories
INSERT INTO categories (name) VALUES
('Electrical'),
('Plumbing'),
('Mess / Food'),
('Wi-Fi / Network'),
('Cleaning'),
('Other');

-- Insert Sample Complaints
-- Complaint 1: Pending status
INSERT INTO complaints (student_id, category_id, title, description, image_path, priority, status) VALUES
(2, 1, 'Broken Light Fixture in Room', 'The ceiling light fixture in my room is not working. It seems like a bulb or wiring issue.', '/uploads/complaints/light_001.jpg', 'medium', 'pending');

-- Complaint 2: In Progress status
INSERT INTO complaints (student_id, category_id, title, description, image_path, priority, status) VALUES
(3, 2, 'Leaking Tap in Bathroom', 'The tap in the bathroom is constantly dripping, wasting water. Please fix urgently.', '/uploads/complaints/tap_002.jpg', 'high', 'in_progress');

-- Complaint 3: Resolved status
INSERT INTO complaints (student_id, category_id, title, description, image_path, priority, status) VALUES
(2, 3, 'Poor Quality Food Served', 'The mess served substandard food today. The rice was undercooked and vegetables were not fresh.', '/uploads/complaints/food_003.jpg', 'medium', 'resolved');

-- Insert Complaint Updates
INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks) VALUES
(1, 1, 'pending', 'assigned', 'Assigned to electrical department for investigation');

INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks) VALUES
(2, 1, 'pending', 'assigned', 'Acknowledged and scheduled for repair tomorrow morning');

INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks) VALUES
(2, 1, 'assigned', 'in_progress', 'Repair work started on the leaking tap');

INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks) VALUES
(3, 1, 'pending', 'assigned', 'Forwarded to mess management for review');

INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks) VALUES
(3, 1, 'assigned', 'resolved', 'Issue discussed with kitchen team. Quality has been improved for next service');

-- Insert Feedback for Resolved Complaint
INSERT INTO feedback (complaint_id, rating, comments) VALUES
(3, 4, 'Good response time. The quality seems to have improved. Looking forward to consistent service.');

-- Optional: Display summary of inserted data
SELECT 'Data Insertion Complete!' AS status;
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_categories FROM categories;
SELECT COUNT(*) AS total_complaints FROM complaints;
SELECT COUNT(*) AS total_updates FROM complaint_updates;
SELECT COUNT(*) AS total_feedback FROM feedback;
