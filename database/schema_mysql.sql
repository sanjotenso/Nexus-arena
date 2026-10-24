-- NEXUS ARENA database schema for MySQL/MariaDB (phpMyAdmin).
-- In phpMyAdmin, open the Import tab and import this file.

CREATE DATABASE IF NOT EXISTS `database_Nexus`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `database_Nexus`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'player',
    avatar_url VARCHAR(2048) NOT NULL DEFAULT '',
    bio VARCHAR(1000) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    tag VARCHAR(32) NOT NULL,
    captain_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teams_name (name),
    KEY idx_teams_captain_id (captain_id),
    CONSTRAINT fk_teams_captain
        FOREIGN KEY (captain_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    team_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_team_members_team_user (team_id, user_id),
    KEY idx_team_members_user_id (user_id),
    CONSTRAINT fk_team_members_team
        FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_team_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tournaments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    game VARCHAR(150) NOT NULL,
    format VARCHAR(64) NOT NULL DEFAULT 'single_elimination',
    status VARCHAR(32) NOT NULL DEFAULT 'upcoming',
    start_date VARCHAR(32) DEFAULT NULL,
    end_date VARCHAR(32) DEFAULT NULL,
    max_teams INT NOT NULL DEFAULT 16,
    description TEXT DEFAULT NULL,
    banner VARCHAR(2048) NOT NULL DEFAULT '',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tournaments_status (status),
    KEY idx_tournaments_created_by (created_by),
    CONSTRAINT fk_tournaments_creator
        FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS participants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tournament_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    PRIMARY KEY (id),
    KEY idx_participants_tournament_id (tournament_id),
    KEY idx_participants_team_id (team_id),
    KEY idx_participants_user_id (user_id),
    CONSTRAINT fk_participants_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments (id),
    CONSTRAINT fk_participants_team
        FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_participants_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tournament_id INT UNSIGNED NOT NULL,
    round INT NOT NULL,
    match_number INT NOT NULL,
    team1_id INT UNSIGNED DEFAULT NULL,
    team2_id INT UNSIGNED DEFAULT NULL,
    winner_id INT UNSIGNED DEFAULT NULL,
    scheduled_at VARCHAR(32) DEFAULT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
    score1 INT NOT NULL DEFAULT 0,
    score2 INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_matches_tournament_id (tournament_id),
    CONSTRAINT fk_matches_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leaderboard (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    team_id INT UNSIGNED DEFAULT NULL,
    tournament_id INT UNSIGNED DEFAULT NULL,
    `rank` INT NOT NULL DEFAULT 0,
    points INT NOT NULL DEFAULT 0,
    wins INT NOT NULL DEFAULT 0,
    losses INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_leaderboard_user_id (user_id),
    KEY idx_leaderboard_team_id (team_id),
    KEY idx_leaderboard_tournament_id (tournament_id),
    CONSTRAINT fk_leaderboard_user
        FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_leaderboard_team
        FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_leaderboard_tournament
        FOREIGN KEY (tournament_id) REFERENCES tournaments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    link TEXT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user_id (user_id),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
