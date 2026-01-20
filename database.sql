CREATE DATABASE IF NOT EXISTS employee_feedback;
USE employee_feedback;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department VARCHAR(100) NOT NULL,
  feedback TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create a default admin:
-- email: admin@company.com
-- password: Admin@123
-- NOTE: This hash is for "Admin@123" using PHP password_hash().
INSERT INTO users (full_name, email, password_hash, role)
VALUES (
  'System Admin',
  'admin@company.com',
  '$2y$10$Qn2ZpOeWm7bM2EwXc8VtTeu8Xx0mZsI1bJfW2cYp5g6Qn9qO6HhQy',
  'admin'
)
ON DUPLICATE KEY UPDATE email=email;
