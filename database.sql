-- ========================================
-- KosMed v2 Database Schema
-- Import via: phpMyAdmin > Import
-- ========================================

CREATE DATABASE IF NOT EXISTS kosmed_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kosmed_db;

-- Hapus tabel lama (urutan FK)
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS keluhan;
DROP TABLE IF EXISTS tidur;
DROP TABLE IF EXISTS makanan;
DROP TABLE IF EXISTS air_minum;
DROP TABLE IF EXISTS obat;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS users;

-- 1. Users
CREATE TABLE users (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Sessions (token auth)
CREATE TABLE sessions (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Stok Obat
CREATE TABLE obat (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    nama       VARCHAR(100) NOT NULL,
    kegunaan   VARCHAR(200),
    stok       INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tracker Makanan
CREATE TABLE makanan (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    user_id       INT NOT NULL,
    nama          VARCHAR(150) NOT NULL,
    kategori      ENUM('sehat','cukup sehat','kurang sehat') NOT NULL DEFAULT 'cukup sehat',
    waktu         ENUM('pagi','siang','malam','snack') NOT NULL DEFAULT 'siang',
    tanggal       DATE NOT NULL,
    auto_detected TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tracker Air Minum
CREATE TABLE air_minum (
    id      INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jumlah  INT NOT NULL DEFAULT 0,
    target  INT NOT NULL DEFAULT 8,
    UNIQUE KEY unique_user_date (user_id, tanggal),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Reminder Harian
CREATE TABLE reminders (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    teks       VARCHAR(200) NOT NULL,
    ikon       VARCHAR(10) NOT NULL DEFAULT '🔔',
    is_done    TINYINT(1) NOT NULL DEFAULT 0,
    tanggal    DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Catatan Keluhan Kesehatan
CREATE TABLE keluhan (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    jenis      VARCHAR(100) NOT NULL,
    catatan    TEXT,
    intensitas ENUM('ringan','sedang','berat') NOT NULL DEFAULT 'ringan',
    tanggal    DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tracker Tidur
CREATE TABLE tidur (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    user_id      INT NOT NULL,
    jam_tidur    TIME NOT NULL,
    jam_bangun   TIME NOT NULL,
    durasi_menit INT NOT NULL DEFAULT 0,
    tanggal      DATE NOT NULL,
    catatan      VARCHAR(200),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
