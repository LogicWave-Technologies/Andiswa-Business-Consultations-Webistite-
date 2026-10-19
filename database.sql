-- =====================================================================
-- database.sql  -  the ONE shared database for the website AND the mobile app
-- ---------------------------------------------------------------------
-- Used by setup.php. Works on XAMPP (MariaDB) and on Azure Database for MySQL.
--
-- Who uses which table:
--   settings  -> website (contact page, footer, admin "Website & App" tab)
--                + app API  GET /api/studio   (Home and Contact pages)
--   quotes    -> website contact form (submit_quote.php)
--                + app API  POST /api/quotes  (Contact page)
--                The "source" column says where each request came from.
--   everything else -> website admin and client portal
--
-- Rules followed so this file also works on Azure:
--   * every table has its PRIMARY KEY inside CREATE TABLE (Azure can switch on
--     "sql_generate_invisible_primary_key", which breaks keys added later)
--   * CREATE TABLE IF NOT EXISTS and INSERT IGNORE, so running it again is safe
--     and never deletes or overwrites your data
-- =====================================================================
CREATE DATABASE IF NOT EXISTS andiswa_business CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE andiswa_business;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','client') NOT NULL DEFAULT 'client',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  service VARCHAR(150) NOT NULL DEFAULT 'General Enquiry',
  message TEXT NOT NULL,
  source ENUM('website','app') NOT NULL DEFAULT 'website',
  status ENUM('New','Under Review','Accepted','Rejected','Cancelled','Completed') NOT NULL DEFAULT 'New',
  admin_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY client_id (client_id),
  KEY status (status),
  KEY email (email),
  KEY source (source),
  CONSTRAINT quotes_ibfk_1 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id INT UNSIGNED NOT NULL,
  quote_id INT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  status ENUM('Not Started','In Progress','On Hold','Completed','Cancelled') NOT NULL DEFAULT 'Not Started',
  progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
  start_date DATE NULL,
  due_date DATE NULL,
  image_path VARCHAR(255) NULL,
  show_on_services TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY client_id (client_id),
  KEY quote_id (quote_id),
  KEY show_on_services (show_on_services),
  CONSTRAINT projects_ibfk_1 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT projects_ibfk_2 FOREIGN KEY (quote_id) REFERENCES quotes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_updates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  update_text TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY project_id (project_id),
  CONSTRAINT project_updates_ibfk_1 FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cancellation_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('Pending','Approved','Declined') NOT NULL DEFAULT 'Pending',
  admin_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY project_id (project_id),
  KEY user_id (user_id),
  CONSTRAINT cancellation_requests_ibfk_1 FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT cancellation_requests_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_number VARCHAR(40) NOT NULL,
  quote_id INT UNSIGNED NULL,
  client_id INT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL,
  due_date DATE NULL,
  status ENUM('Draft','Sent','Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY invoice_number (invoice_number),
  KEY quote_id (quote_id),
  KEY client_id (client_id, status),
  CONSTRAINT invoices_ibfk_1 FOREIGN KEY (quote_id) REFERENCES quotes (id) ON DELETE SET NULL,
  CONSTRAINT invoices_ibfk_2 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id, is_read),
  CONSTRAINT notifications_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id VARCHAR(100) NOT NULL,
  user_id INT UNSIGNED NULL,
  sender ENUM('user','bot') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY session_id (session_id),
  KEY created_at (created_at),
  CONSTRAINT chat_messages_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starting values. INSERT IGNORE = values the admin already changed are kept.
INSERT IGNORE INTO settings(setting_key, setting_value) VALUES
('studio','Andiswa Business Consultation'),
('email','andiswasbusinessconsultations@outlook.com'),
('phone','+27 67 790 4428'),
('address','2 Belmont Terrace, Port Elizabeth, 6001'),
('hours','Monday – Friday, 08:00 – 18:00'),
('whatsapp','27677904428'),
('facebook_url',''),
('youtube_url','');

-- The five starter services, only added when the table is still empty.
INSERT INTO services(name, description, active, sort_order)
SELECT s.name, s.description, 1, s.sort_order FROM (
  SELECT 'Graphic Design' AS name, 'Professional flyers, posters and marketing artwork.' AS description, 0 AS sort_order
  UNION ALL SELECT 'Branding & Identity', 'Logo design, brand identity and business stationery.', 1
  UNION ALL SELECT 'Web & Digital Design', 'Modern websites and digital visual experiences.', 2
  UNION ALL SELECT 'Social Media Design', 'Social media graphics and campaign content.', 3
  UNION ALL SELECT 'Business Consultation', 'Practical consultation for creative business growth.', 4
) s
WHERE NOT EXISTS (SELECT 1 FROM services);
