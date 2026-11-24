CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    matric_number VARCHAR(20) NOT NULL,
    department ENUM('COM', 'SWE', 'IFT', 'CBS') NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'moderator', 'admin') NOT NULL DEFAULT 'student',
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_matric_number (matric_number),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS venues (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    building VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_venues_name_building (name, building),
    KEY idx_venues_building (building)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reporter_id BIGINT UNSIGNED NOT NULL,
    category_id SMALLINT UNSIGNED NOT NULL,
    venue_id SMALLINT UNSIGNED NOT NULL,
    item_type ENUM('LOST', 'FOUND') NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    challenge_question VARCHAR(255) NULL,
    challenge_answer_hash VARCHAR(255) NULL,
    status ENUM('ACTIVE', 'CLAIM_PENDING', 'RESOLVED', 'SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
    flags_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_items_reporter FOREIGN KEY (reporter_id) REFERENCES users (id),
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_items_venue FOREIGN KEY (venue_id) REFERENCES venues (id),
    KEY idx_items_type_status_date (item_type, status, event_date),
    KEY idx_items_category (category_id),
    KEY idx_items_venue (venue_id),
    KEY idx_items_reporter (reporter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS claims (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id BIGINT UNSIGNED NOT NULL,
    claimant_id BIGINT UNSIGNED NOT NULL,
    challenge_response VARCHAR(500) NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'REJECTED', 'COMPLETED') NOT NULL DEFAULT 'PENDING',
    handover_pin_hash VARCHAR(255) NULL,
    pin_expires_at DATETIME NULL,
    pin_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_claims_item FOREIGN KEY (item_id) REFERENCES items (id),
    CONSTRAINT fk_claims_claimant FOREIGN KEY (claimant_id) REFERENCES users (id),
    KEY idx_claims_item_claimant (item_id, claimant_id),
    KEY idx_claims_claimant_status (claimant_id, status),
    KEY idx_claims_item_status (item_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flags_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_flags_item FOREIGN KEY (item_id) REFERENCES items (id),
    CONSTRAINT fk_flags_user FOREIGN KEY (user_id) REFERENCES users (id),
    UNIQUE KEY uq_flags_item_user (item_id, user_id),
    KEY idx_flags_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name) VALUES
    ('Electronics'),
    ('Books and notes'),
    ('Clothing and accessories'),
    ('Keys and cards'),
    ('Bags and cases'),
    ('Other');

INSERT IGNORE INTO venues (name, building) VALUES
    ('Faculty of Computing', 'Faculty of Computing'),
    ('Main Library', 'Main Library'),
    ('Student Centre', 'Student Centre');
