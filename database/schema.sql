-- WatchVault Database Schema
-- MySQL Database for XAMPP

-- Create database
CREATE DATABASE IF NOT EXISTS watchvault CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE watchvault;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watchlist table
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tmdb_movie_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    poster_path VARCHAR(500),
    backdrop_path VARCHAR(500),
    overview TEXT,
    release_date DATE,
    media_type ENUM('movie', 'tv') NOT NULL DEFAULT 'movie',
    status ENUM('watching', 'wantToWatch', 'finished') NOT NULL DEFAULT 'wantToWatch',
    user_rating DECIMAL(3,1) DEFAULT NULL,
    user_review TEXT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_tmdb_movie_id (tmdb_movie_id),
    INDEX idx_status (status),
    INDEX idx_media_type (media_type),
    UNIQUE KEY unique_user_movie (user_id, tmdb_movie_id, media_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    watchlist_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (watchlist_id) REFERENCES watchlist(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;