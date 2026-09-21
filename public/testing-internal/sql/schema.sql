-- Run this once against your MySQL database to set it up.
-- Example: mysql -u root -p your_db_name < sql/schema.sql

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: create a first admin account.
-- Generate a hash with: php -r "echo password_hash('ChangeMe123!', PASSWORD_DEFAULT);"
-- Then insert it manually, e.g.:
-- INSERT INTO users (name, email, password_hash, role)
-- VALUES ('Admin', 'admin@example.com', '<paste-hash-here>', 'admin');
