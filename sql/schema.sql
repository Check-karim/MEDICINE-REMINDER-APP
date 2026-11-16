-- Medicine Reminder schema for Digital Home Healthcare Ltd

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(80) NOT NULL,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'patient') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medicines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    dosage VARCHAR(60) NOT NULL,
    form VARCHAR(40) NOT NULL,
    instructions VARCHAR(240) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_medicines_user (user_id),
    CONSTRAINT fk_medicines_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dose_times (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    medicine_id INT UNSIGNED NOT NULL,
    dose_time TIME NOT NULL,
    quantity VARCHAR(40) NOT NULL,
    days CHAR(7) NOT NULL DEFAULT '1111111',
    PRIMARY KEY (id),
    KEY idx_dose_medicine (medicine_id),
    CONSTRAINT fk_dose_medicine FOREIGN KEY (medicine_id) REFERENCES medicines (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dose_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    dose_time_id INT UNSIGNED NOT NULL,
    medicine_name VARCHAR(120) NOT NULL,
    dosage VARCHAR(60) NOT NULL,
    scheduled_time TIME NOT NULL,
    log_date DATE NOT NULL,
    status ENUM('taken', 'skipped') NOT NULL,
    logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_dose_log (dose_time_id, log_date),
    KEY idx_logs_user_date (user_id, log_date),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_logs_medicine FOREIGN KEY (medicine_id) REFERENCES medicines (id) ON DELETE CASCADE,
    CONSTRAINT fk_logs_dose FOREIGN KEY (dose_time_id) REFERENCES dose_times (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
