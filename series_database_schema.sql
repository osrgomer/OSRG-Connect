-- SeriesList Database Schema
-- Run this in phpMyAdmin or MySQL console

-- Create database
CREATE DATABASE IF NOT EXISTS serieslist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE serieslist;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(500),
    manual_status ENUM('online', 'offline', 'auto') DEFAULT 'auto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_last_active (last_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friendships table (bidirectional relationships)
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    INDEX idx_user_id (user_id),
    INDEX idx_friend_id (friend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log table
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    show_title VARCHAR(255) NOT NULL,
    rating TINYINT DEFAULT NULL,
    progress INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TV Series table
CREATE TABLE IF NOT EXISTS series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tmdb_id INT DEFAULT NULL,
    title VARCHAR(500) NOT NULL,
    poster VARCHAR(500) DEFAULT NULL,
    rating DECIMAL(3,1) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'watching',
    progress INT DEFAULT 0,
    total INT DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_tmdb_id (tmdb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial users
INSERT INTO users (email, username, password, avatar) VALUES
('omersr12@gmail.com', 'Omer Shalom Rimon', '$2y$10$CRIgaIZEVovYatMQPFOA3OvTkYdArWFQMSPpi5J6VRT214peliifu', 'https://ui-avatars.com/api/?name=Omer+Shalom+Rimon&background=4f46e5&color=fff'),
('testy@osrg.lol', 'testy mesty', '$2y$10$EMcwyoNjK1mkqoAxAZi2teRx2ex8HquzMPoLewSm1hvQnw3WlMbYG', 'https://ui-avatars.com/api/?name=testy+mesty&background=4f46e5&color=fff')
ON DUPLICATE KEY UPDATE email=email;
