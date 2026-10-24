<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $projectRoot = dirname(__DIR__, 2);
        $databasePath = $projectRoot . '/nexus-arena/nexus_arena.db';
        self::$connection = new PDO('sqlite:' . $databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$connection->exec('PRAGMA foreign_keys = ON');
        self::initialize(self::$connection);

        return self::$connection;
    }

    private static function initialize(PDO $connection): void
    {
        $connection->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'player',
    avatar_url TEXT DEFAULT '',
    bio TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS teams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    tag TEXT NOT NULL,
    captain_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (captain_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS team_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(team_id, user_id)
);
CREATE TABLE IF NOT EXISTS tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    game TEXT NOT NULL,
    format TEXT DEFAULT 'single_elimination',
    status TEXT DEFAULT 'upcoming',
    start_date TEXT,
    end_date TEXT,
    max_teams INTEGER DEFAULT 16,
    description TEXT,
    banner TEXT DEFAULT '',
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tournament_id INTEGER NOT NULL,
    team_id INTEGER,
    user_id INTEGER,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'pending',
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tournament_id INTEGER NOT NULL,
    round INTEGER NOT NULL,
    match_number INTEGER NOT NULL,
    team1_id INTEGER,
    team2_id INTEGER,
    winner_id INTEGER,
    scheduled_at TEXT,
    status TEXT DEFAULT 'scheduled',
    score1 INTEGER DEFAULT 0,
    score2 INTEGER DEFAULT 0,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
);
CREATE TABLE IF NOT EXISTS leaderboard (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    team_id INTEGER,
    tournament_id INTEGER,
    rank INTEGER DEFAULT 0,
    points INTEGER DEFAULT 0,
    wins INTEGER DEFAULT 0,
    losses INTEGER DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
);
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    link TEXT,
    is_read INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
SQL);

        $userCount = (int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($userCount === 0) {
            $salt = bin2hex(random_bytes(16));
            $hash = hash_pbkdf2('sha256', 'admin123', $salt, 100000);
            $statement = $connection->prepare(
                'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $statement->execute(['admin', 'admin@nexusarena.com', $salt . '$' . $hash, 'admin']);
        }

        $tournamentCount = (int) $connection->query('SELECT COUNT(*) FROM tournaments')->fetchColumn();
        if ($tournamentCount === 0) {
            $statement = $connection->prepare(
                'INSERT INTO tournaments (title, game, status, start_date, max_teams, description)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $seedTournaments = [
                ['Neon Legends Open', 'Valorant', 'upcoming', '2026-11-15T18:00', 16, 'A high-stakes open tournament for elite squads.'],
                ['Cyber Clash 2026', 'League of Legends', 'ongoing', '2026-10-20T17:00', 8, 'Mid-season championship with double elimination.'],
                ['Apex Infinity Cup', 'Apex Legends', 'upcoming', '2026-12-01T16:00', 20, 'Battle royale showdown across multiple maps.'],
            ];
            foreach ($seedTournaments as $tournament) {
                $statement->execute($tournament);
            }
        }
    }
}
