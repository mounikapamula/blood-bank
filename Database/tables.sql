CREATE TABLE admins (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(120) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE appointments (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    donor_id INT(10) UNSIGNED NOT NULL,
    hospital VARCHAR(150) NOT NULL,
    city VARCHAR(100) NOT NULL,
    appointment_at DATETIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX (donor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE blood_banks (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    contact_number VARCHAR(15) DEFAULT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    available_blood_groups TEXT DEFAULT NULL,
    map_link TEXT DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE blood_requests (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    seeker_id INT(10) UNSIGNED NOT NULL,
    donor_id INT(10) UNSIGNED DEFAULT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    hospital VARCHAR(150) NOT NULL,
    city VARCHAR(100) NOT NULL,
    units_needed SMALLINT(5) UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'fulfilled') NOT NULL DEFAULT 'pending',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME DEFAULT NULL,
    fulfilled_at DATETIME DEFAULT NULL,
    notes VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (seeker_id),
    INDEX (donor_id),
    INDEX (blood_group),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE donations (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    donor_id INT(10) UNSIGNED NOT NULL,
    seeker_id INT(10) UNSIGNED DEFAULT NULL,
    hospital VARCHAR(150) NOT NULL,
    city VARCHAR(100) NOT NULL,
    units SMALLINT(5) UNSIGNED NOT NULL,
    donated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX (donor_id),
    INDEX (seeker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE donors (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    city VARCHAR(100) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('donor', 'seeker', 'both') NOT NULL DEFAULT 'donor',
    profile_pic VARCHAR(255) DEFAULT 'assets/default_pp.png',
    enable_2fa TINYINT(1) NOT NULL DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX (email),
    INDEX (blood_group),
    INDEX (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    type VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'info',
    related_id INT(11) DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (type),
    INDEX (related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE otps (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    otp_code VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE register (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    city VARCHAR(100) DEFAULT NULL,
    role ENUM('donor', 'seeker', 'both') NOT NULL DEFAULT 'donor',
    verification_token VARCHAR(64) NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE users (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'user', 'seeker', 'donor', 'both') NOT NULL DEFAULT 'user',
    city VARCHAR(50) DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    is_donor TINYINT(1) NOT NULL DEFAULT 0,
    last_donated_date DATE DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    blood_group VARCHAR(10) DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    enable_2fa TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    INDEX (name),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
