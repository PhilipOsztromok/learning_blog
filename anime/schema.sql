-- ============================================================
-- ANIME VAULT - Database Schema
-- Run this SQL on your MySQL/MariaDB server
-- Usage: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS anime_vault
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE anime_vault;

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    email        VARCHAR(120) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,        -- bcrypt hash
    role         ENUM('user','admin') NOT NULL DEFAULT 'user',
    avatar_url   VARCHAR(500) NULL,
    bio          TEXT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login   DATETIME NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Studios ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS studios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL UNIQUE,
    country      VARCHAR(80)  NULL,
    founded_year YEAR         NULL,
    website      VARCHAR(300) NULL,
    description  TEXT NULL,
    logo_url     VARCHAR(500) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Genres ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS genres (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(80) NOT NULL UNIQUE,
    slug  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Anime Shows ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS anime (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(300) NOT NULL,
    title_japanese  VARCHAR(300) NULL,
    title_english   VARCHAR(300) NULL,
    slug            VARCHAR(300) NOT NULL UNIQUE,
    synopsis        TEXT NULL,
    studio_id       INT NULL,
    type            ENUM('TV','Movie','OVA','ONA','Special','Music') NOT NULL DEFAULT 'TV',
    status          ENUM('Airing','Completed','Upcoming','Hiatus') NOT NULL DEFAULT 'Completed',
    episodes        SMALLINT UNSIGNED NULL,
    duration_min    SMALLINT UNSIGNED NULL,       -- average episode length
    premiered_season ENUM('Winter','Spring','Summer','Fall') NULL,
    premiered_year  YEAR NULL,
    finished_year   YEAR NULL,
    rating          DECIMAL(3,1) NULL,            -- site average 0-10
    age_rating      VARCHAR(50) NULL,             -- e.g. R - 17+ (violence & profanity)
    source          VARCHAR(80) NULL,             -- Manga, Light Novel, Original…
    poster_url      VARCHAR(500) NULL,
    banner_url      VARCHAR(500) NULL,
    trailer_url     VARCHAR(500) NULL,
    imdb_id         VARCHAR(20) NULL,
    mal_id          INT UNSIGNED NULL,            -- MyAnimeList ID (optional)
    external_data   JSON NULL,                    -- raw scraped data blob
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by      INT NULL,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_title (title(100)),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    FULLTEXT idx_search (title, synopsis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Anime ↔ Genres (M:N) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS anime_genres (
    anime_id  INT NOT NULL,
    genre_id  INT NOT NULL,
    PRIMARY KEY (anime_id, genre_id),
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── People (VAs, Directors, etc.) ──────────────────────────
CREATE TABLE IF NOT EXISTS people (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(200) NOT NULL,
    name_japanese VARCHAR(200) NULL,
    birth_date   DATE NULL,
    nationality  VARCHAR(80) NULL,
    photo_url    VARCHAR(500) NULL,
    bio          TEXT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cast / Characters ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS cast_members (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    anime_id      INT NOT NULL,
    person_id     INT NOT NULL,
    character_name VARCHAR(200) NULL,
    role          ENUM('Main','Supporting','Background') NOT NULL DEFAULT 'Main',
    language      VARCHAR(30) NOT NULL DEFAULT 'Japanese',  -- Japanese, English, etc.
    FOREIGN KEY (anime_id)  REFERENCES anime(id)  ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    INDEX idx_anime (anime_id),
    INDEX idx_person (person_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    anime_id   INT NOT NULL,
    person_id  INT NOT NULL,
    role       VARCHAR(150) NOT NULL,     -- Director, Writer, Composer…
    FOREIGN KEY (anime_id)  REFERENCES anime(id)  ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    INDEX idx_anime (anime_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Reviews ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    anime_id   INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 10),
    title      VARCHAR(200) NULL,
    body       TEXT NOT NULL,
    contains_spoilers TINYINT(1) NOT NULL DEFAULT 0,
    helpful_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_anime (user_id, anime_id),
    INDEX idx_anime (anime_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── User Watchlist / Lists ──────────────────────────────────
CREATE TABLE IF NOT EXISTS watchlist (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    anime_id  INT NOT NULL,
    status    ENUM('watching','completed','plan_to_watch','dropped','on_hold') NOT NULL DEFAULT 'plan_to_watch',
    progress  SMALLINT UNSIGNED NOT NULL DEFAULT 0,    -- episodes watched
    score     TINYINT UNSIGNED NULL CHECK (score BETWEEN 1 AND 10),
    added_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_anime (user_id, anime_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sessions ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
    token      CHAR(64) PRIMARY KEY,
    user_id    INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed default genres ───────────────────────────────────
INSERT IGNORE INTO genres (name, slug) VALUES
('Action','action'), ('Adventure','adventure'), ('Comedy','comedy'),
('Drama','drama'), ('Fantasy','fantasy'), ('Horror','horror'),
('Mecha','mecha'), ('Mystery','mystery'), ('Romance','romance'),
('Sci-Fi','sci-fi'), ('Slice of Life','slice-of-life'), ('Sports','sports'),
('Supernatural','supernatural'), ('Thriller','thriller'),
('Historical','historical'), ('Psychological','psychological'),
('Shounen','shounen'), ('Shoujo','shoujo'), ('Seinen','seinen'),
('Josei','josei'), ('Isekai','isekai'), ('Music','music'),
('School','school'), ('Harem','harem'), ('Military','military');

-- ── Seed default admin user ───────────────────────────────
-- Default password: Admin123! (CHANGE THIS IMMEDIATELY)
-- Hash generated with PHP: password_hash('Admin123!', PASSWORD_BCRYPT)
INSERT IGNORE INTO users (username, email, password, role) VALUES
('admin', 'admin@osztromok.com', '$2y$12$YmJhZGVmYWtlaGFzaHhlc3VvdE8uNGNlVW9KZ3ZwV3ZBaXREWVhXdWVua1NSYW5hYw==', 'admin');
-- NOTE: Replace the above hash with a real bcrypt hash before deploying!
-- Generate with: php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
