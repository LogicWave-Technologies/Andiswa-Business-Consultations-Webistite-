CREATE DATABASE IF NOT EXISTS andiswa_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE andiswa_business;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','client') NOT NULL DEFAULT 'client',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  service VARCHAR(150) NOT NULL DEFAULT 'General Enquiry',
  message TEXT NOT NULL,
  status ENUM('New','Under Review','Accepted','Rejected','Completed') NOT NULL DEFAULT 'New',
  admin_note TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_quote_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_quotes_status(status), INDEX idx_quotes_email(email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  quote_id INT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  status ENUM('Not Started','In Progress','On Hold','Completed') NOT NULL DEFAULT 'Not Started',
  progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
  start_date DATE NULL,
  due_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_updates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  update_text TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(80) NOT NULL UNIQUE,
  setting_value TEXT NULL
) ENGINE=InnoDB;

INSERT INTO settings(setting_key, setting_value) VALUES
('studio','Andiswa Business Consultation'),
('email','andiswasbusinessconsultations@outlook.com'),
('phone','+27 67 790 4428'),
('address','2 Belmont Terrace, Port Elizabeth, 6001'),
('hours','Monday – Friday, 08:00 – 18:00')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
