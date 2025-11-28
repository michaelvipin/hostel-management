-- schema.sql
CREATE DATABASE IF NOT EXISTS hostel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_db;

-- users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','warden','student') NOT NULL,
  roll_no VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- attendance table (one row per student per date)
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  date DATE NOT NULL,
  status ENUM('present','absent') NOT NULL,
  marked_by INT DEFAULT NULL,
  marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_date (student_id, date),
  CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_marked_by FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- leaves table
CREATE TABLE IF NOT EXISTS leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  reason TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  decided_by INT DEFAULT NULL,
  decided_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_leave_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_leave_decided_by FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;