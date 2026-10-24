<?php
declare(strict_types=1);

session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/php_helpers.php';

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/');

function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databasePath = __DIR__ . '/nexus-arena/nexus_arena.db';
    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    initialize_database($pdo);

    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
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

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $salt = bin2hex(random_bytes(16));
        $hash = hash_pbkdf2('sha256', 'admin123', $salt, 100000);
        $statement = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $statement->execute(['admin', 'admin@nexusarena.com', $salt . '$' . $hash, 'admin']);
    }

    $tournamentCount = (int) $pdo->query('SELECT COUNT(*) FROM tournaments')->fetchColumn();
    if ($tournamentCount === 0) {
        $statement = $pdo->prepare(
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

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $statement = database()->prepare('SELECT * FROM users WHERE id = ?');
    $statement->execute([(int) $_SESSION['user_id']]);
    $user = $statement->fetch();

    return $user ?: null;
}

function require_user(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('/login');
    }
    return $user;
}

function require_admin_json(): ?array
{
    $user = current_user();
    if ($user === null || $user['role'] !== 'admin') {
        json_response(['error' => 'Forbidden'], 403);
        return null;
    }
    return $user;
}

function json_body(): array
{
    $body = file_get_contents('php://input');
    if ($body === false || trim($body) === '') {
        return [];
    }

    try {
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        json_response(['error' => 'Invalid JSON body'], 400);
        return [];
    }

    if (!is_array($data)) {
        json_response(['error' => 'JSON body must be an object'], 400);
        return [];
    }
    return $data;
}

function verify_user_password(string $password, string $storedHash): bool
{
    if (preg_match('/^[a-f0-9]{32}\$[a-f0-9]{64}$/i', $storedHash) === 1) {
        [$salt, $hash] = explode('$', $storedHash, 2);
        $candidate = hash_pbkdf2('sha256', $password, $salt, 100000);
        return hash_equals($hash, $candidate);
    }
    return password_verify($password, $storedHash);
}

function page(string $template, array $data = [], string $title = 'NEXUS ARENA'): void
{
    render_page($template, $data, $title);
}

function run_route(): void
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    global $basePath;
    if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
        $path = substr($path, strlen($basePath));
    } elseif ($path === $basePath) {
        $path = '/';
    }
    $path = rtrim($path, '/') ?: '/';
    $user = current_user();
    $db = database();

    if ($path === '/' && $method === 'GET') {
        $tournaments = $db->query('SELECT * FROM tournaments ORDER BY created_at DESC LIMIT 6')->fetchAll();
        page('home', compact('tournaments', 'user'), 'NEXUS ARENA - Home');
        return;
    }

    if ($path === '/login') {
        if ($method === 'GET') {
            if ($user !== null) {
                redirect('/dashboard');
            }
            page('login', ['error' => null, 'user' => null], 'Login - NEXUS ARENA');
            return;
        }
        if ($method === 'POST') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $statement = $db->prepare('SELECT * FROM users WHERE username = ?');
            $statement->execute([$username]);
            $account = $statement->fetch();
            if ($account && verify_user_password($password, (string) $account['password_hash'])) {
                if (password_needs_rehash((string) $account['password_hash'], PASSWORD_DEFAULT)) {
                    $update = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $update->execute([password_hash($password, PASSWORD_DEFAULT), $account['id']]);
                }
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $account['id'];
                redirect('/dashboard');
            }
            page('login', ['error' => 'Invalid username or password.', 'user' => null], 'Login - NEXUS ARENA');
            return;
        }
        method_not_allowed();
    }

    if ($path === '/register') {
        if ($method === 'GET') {
            if ($user !== null) {
                redirect('/dashboard');
            }
            page('register', ['error' => null, 'user' => null], 'Register - NEXUS ARENA');
            return;
        }
        if ($method === 'POST') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['confirm'] ?? '');
            if ($username === '' || $email === '' || $password === '') {
                page('register', ['error' => 'All fields are required.', 'user' => null], 'Register - NEXUS ARENA');
                return;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                page('register', ['error' => 'Enter a valid email address.', 'user' => null], 'Register - NEXUS ARENA');
                return;
            }
            if ($password !== $confirm) {
                page('register', ['error' => 'Passwords do not match.', 'user' => null], 'Register - NEXUS ARENA');
                return;
            }
            if (strlen($password) < 6) {
                page('register', ['error' => 'Password must be at least 6 characters.', 'user' => null], 'Register - NEXUS ARENA');
                return;
            }
            try {
                $statement = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
                $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $db->lastInsertId();
                redirect('/dashboard');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                page('register', ['error' => 'Username or email already exists.', 'user' => null], 'Register - NEXUS ARENA');
            }
            return;
        }
        method_not_allowed();
    }

    if ($path === '/logout' && $method === 'GET') {
        $_SESSION = [];
        session_destroy();
        redirect('/');
    }

    if ($path === '/dashboard' && $method === 'GET') {
        $user = require_user();
        $statement = $db->query("SELECT * FROM tournaments WHERE status = 'upcoming' ORDER BY start_date LIMIT 10");
        $upcoming = $statement->fetchAll();
        $statement = $db->prepare(
            'SELECT t.* FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE tm.user_id = ?'
        );
        $statement->execute([$user['id']]);
        $teams = $statement->fetchAll();
        $statement = $db->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10'
        );
        $statement->execute([$user['id']]);
        $notifications = $statement->fetchAll();
        page('dashboard', compact('user', 'upcoming', 'teams', 'notifications'), 'Dashboard - NEXUS ARENA');
        return;
    }

    if ($path === '/tournaments' && $method === 'GET') {
        $status = trim((string) ($_GET['status'] ?? ''));
        $game = trim((string) ($_GET['game'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT * FROM tournaments WHERE 1 = 1';
        $params = [];
        if ($status !== '') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        if ($game !== '') {
            $sql .= ' AND game = ?';
            $params[] = $game;
        }
        if ($q !== '') {
            $sql .= ' AND (title LIKE ? OR game LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY start_date DESC';
        $statement = $db->prepare($sql);
        $statement->execute($params);
        $tournaments = $statement->fetchAll();
        $games = $db->query('SELECT DISTINCT game FROM tournaments')->fetchAll(PDO::FETCH_COLUMN);
        $filters = compact('status', 'game', 'q');
        page('tournaments', compact('tournaments', 'games', 'filters', 'user'), 'Tournaments - NEXUS ARENA');
        return;
    }

    if (preg_match('#^/tournaments/([0-9]+)$#', $path, $matches) && $method === 'GET') {
        $tournamentId = (int) $matches[1];
        $statement = $db->prepare('SELECT * FROM tournaments WHERE id = ?');
        $statement->execute([$tournamentId]);
        $tournament = $statement->fetch();
        if (!$tournament) {
            redirect('/tournaments');
        }
        $statement = $db->prepare(
            'SELECT p.*, u.username, t.name AS team_name FROM participants p
             LEFT JOIN users u ON p.user_id = u.id
             LEFT JOIN teams t ON p.team_id = t.id
             WHERE p.tournament_id = ?'
        );
        $statement->execute([$tournamentId]);
        $participants = $statement->fetchAll();
        $statement = $db->prepare(
            'SELECT m.*, t1.name AS t1_name, t2.name AS t2_name FROM matches m
             LEFT JOIN teams t1 ON m.team1_id = t1.id
             LEFT JOIN teams t2 ON m.team2_id = t2.id
             WHERE m.tournament_id = ? ORDER BY m.round, m.match_number'
        );
        $statement->execute([$tournamentId]);
        $matches = $statement->fetchAll();
        page('tournament_detail', compact('tournament', 'participants', 'matches', 'user'), $tournament['title'] . ' - NEXUS ARENA');
        return;
    }

    if ($path === '/api/tournaments' && $method === 'POST') {
        $user = require_admin_json();
        if ($user === null) {
            return;
        }
        $data = json_body();
        foreach (['title', 'game', 'start_date', 'max_teams'] as $required) {
            if (empty($data[$required])) {
                json_response(['error' => $required . ' is required'], 400);
                return;
            }
        }
        $statement = $db->prepare(
            'INSERT INTO tournaments (title, game, format, start_date, end_date, max_teams, description, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['title'], $data['game'], $data['format'] ?? 'single_elimination',
            $data['start_date'], $data['end_date'] ?? null, (int) $data['max_teams'],
            $data['description'] ?? '', $user['id'],
        ]);
        json_response(['id' => (int) $db->lastInsertId(), 'message' => 'Tournament created']);
        return;
    }

    if (preg_match('#^/api/tournaments/([0-9]+)/register$#', $path, $matches) && $method === 'POST') {
        if ($user === null) {
            json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        $tournamentId = (int) $matches[1];
        $statement = $db->prepare('SELECT id FROM tournaments WHERE id = ?');
        $statement->execute([$tournamentId]);
        if (!$statement->fetch()) {
            json_response(['error' => 'Tournament not found'], 404);
            return;
        }
        $statement = $db->prepare(
            'SELECT id FROM participants WHERE tournament_id = ? AND user_id = ?'
        );
        $statement->execute([$tournamentId, $user['id']]);
        if ($statement->fetch()) {
            json_response(['error' => 'Already registered'], 409);
            return;
        }
        $statement = $db->prepare(
            'INSERT INTO participants (tournament_id, user_id, status) VALUES (?, ?, ?)'
        );
        $statement->execute([$tournamentId, $user['id'], 'confirmed']);
        $statement = $db->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
        $statement->execute([
            $user['id'],
            'You registered for tournament #' . $tournamentId,
            '/tournaments/' . $tournamentId,
        ]);
        json_response(['message' => 'Registered successfully']);
        return;
    }

    if (preg_match('#^/api/tournaments/([0-9]+)/bracket$#', $path, $matches) && $method === 'GET') {
        $tournamentId = (int) $matches[1];
        $statement = $db->prepare('SELECT * FROM tournaments WHERE id = ?');
        $statement->execute([$tournamentId]);
        $tournament = $statement->fetch();
        if (!$tournament) {
            json_response(['error' => 'Tournament not found'], 404);
            return;
        }
        $statement = $db->prepare(
            'SELECT m.*, t1.name AS t1_name, t2.name AS t2_name FROM matches m
             LEFT JOIN teams t1 ON m.team1_id = t1.id
             LEFT JOIN teams t2 ON m.team2_id = t2.id
             WHERE m.tournament_id = ? ORDER BY m.round, m.match_number'
        );
        $statement->execute([$tournamentId]);
        $rounds = [];
        foreach ($statement->fetchAll() as $match) {
            $rounds[$match['round']][] = $match;
        }
        json_response(['tournament' => $tournament, 'rounds' => (object) $rounds]);
        return;
    }

    if (preg_match('#^/api/matches/([0-9]+)$#', $path, $matches) && $method === 'POST') {
        if (require_admin_json() === null) {
            return;
        }
        $data = json_body();
        $statement = $db->prepare(
            'UPDATE matches SET score1 = ?, score2 = ?, winner_id = ?, status = ? WHERE id = ?'
        );
        $statement->execute([
            (int) ($data['score1'] ?? 0), (int) ($data['score2'] ?? 0),
            $data['winner_id'] ?? null, $data['status'] ?? 'completed', (int) $matches[1],
        ]);
        json_response(['message' => 'Match updated']);
        return;
    }

    if (preg_match('#^/profile/([0-9]+)$#', $path, $matches) && $method === 'GET') {
        $profileId = (int) $matches[1];
        $statement = $db->prepare(
            'SELECT id, username, email, avatar_url, bio, role, created_at FROM users WHERE id = ?'
        );
        $statement->execute([$profileId]);
        $profile = $statement->fetch() ?: [];
        $statement = $db->prepare(
            'SELECT t.* FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE tm.user_id = ?'
        );
        $statement->execute([$profileId]);
        $teams = $statement->fetchAll();
        $statement = $db->prepare(
            'SELECT SUM(points) AS total_points, SUM(wins) AS total_wins, SUM(losses) AS total_losses
             FROM leaderboard WHERE user_id = ?'
        );
        $statement->execute([$profileId]);
        $stats = $statement->fetch() ?: [];
        page('profile', compact('profile', 'teams', 'stats', 'user'), ($profile['username'] ?? 'Profile') . ' - NEXUS ARENA');
        return;
    }

    if (preg_match('#^/team/([0-9]+)$#', $path, $matches) && $method === 'GET') {
        $teamId = (int) $matches[1];
        $statement = $db->prepare(
            'SELECT t.*, u.username AS captain_name FROM teams t
             JOIN users u ON t.captain_id = u.id WHERE t.id = ?'
        );
        $statement->execute([$teamId]);
        $team = $statement->fetch() ?: [];
        $statement = $db->prepare(
            'SELECT u.id, u.username, u.avatar_url FROM users u
             JOIN team_members tm ON u.id = tm.user_id WHERE tm.team_id = ?'
        );
        $statement->execute([$teamId]);
        $members = $statement->fetchAll();
        page('team', compact('team', 'members', 'user'), ($team['name'] ?? 'Team') . ' - NEXUS ARENA');
        return;
    }

    if ($path === '/api/teams' && $method === 'POST') {
        if ($user === null) {
            json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        $data = json_body();
        $name = trim((string) ($data['name'] ?? ''));
        $tag = trim((string) ($data['tag'] ?? ''));
        if ($name === '' || $tag === '') {
            json_response(['error' => 'Name and tag required'], 400);
            return;
        }
        try {
            $db->beginTransaction();
            $statement = $db->prepare('INSERT INTO teams (name, tag, captain_id) VALUES (?, ?, ?)');
            $statement->execute([$name, $tag, $user['id']]);
            $teamId = (int) $db->lastInsertId();
            $statement = $db->prepare('INSERT INTO team_members (team_id, user_id) VALUES (?, ?)');
            $statement->execute([$teamId, $user['id']]);
            $db->commit();
            json_response(['id' => $teamId, 'message' => 'Team created']);
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            json_response(['error' => 'Team name already exists'], 409);
        }
        return;
    }

    if ($path === '/api/teams/join' && $method === 'POST') {
        if ($user === null) {
            json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        $data = json_body();
        $teamId = filter_var($data['team_id'] ?? null, FILTER_VALIDATE_INT);
        if ($teamId === false || $teamId === null) {
            json_response(['error' => 'A valid team_id is required'], 400);
            return;
        }
        $statement = $db->prepare('SELECT id FROM teams WHERE id = ?');
        $statement->execute([$teamId]);
        if (!$statement->fetch()) {
            json_response(['error' => 'Team not found'], 404);
            return;
        }
        $statement = $db->prepare('SELECT id FROM team_members WHERE team_id = ? AND user_id = ?');
        $statement->execute([$teamId, $user['id']]);
        if ($statement->fetch()) {
            json_response(['error' => 'Already a member'], 409);
            return;
        }
        $statement = $db->prepare('INSERT INTO team_members (team_id, user_id) VALUES (?, ?)');
        $statement->execute([$teamId, $user['id']]);
        json_response(['message' => 'Joined team']);
        return;
    }

    if ($path === '/leaderboard' && $method === 'GET') {
        $rows = $db->query(
            'SELECT l.*, u.username, t.name AS team_name FROM leaderboard l
             LEFT JOIN users u ON l.user_id = u.id
             LEFT JOIN teams t ON l.team_id = t.id
             ORDER BY l.points DESC LIMIT 100'
        )->fetchAll();
        page('leaderboard', compact('rows', 'user'), 'Leaderboard - NEXUS ARENA');
        return;
    }

    if ($path === '/admin' && $method === 'GET') {
        if ($user === null || $user['role'] !== 'admin') {
            redirect('/');
        }
        $users = $db->query(
            'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC'
        )->fetchAll();
        $tournaments = $db->query('SELECT * FROM tournaments ORDER BY created_at DESC')->fetchAll();
        page('admin', compact('users', 'tournaments', 'user'), 'Admin - NEXUS ARENA');
        return;
    }

    if ($path === '/api/admin/users' && $method === 'GET') {
        if (require_admin_json() === null) {
            return;
        }
        $users = $db->query(
            'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC'
        )->fetchAll();
        json_response($users);
        return;
    }

    if (preg_match('#^/api/admin/tournaments/([0-9]+)$#', $path, $matches) && $method === 'DELETE') {
        if (require_admin_json() === null) {
            return;
        }
        $statement = $db->prepare('DELETE FROM tournaments WHERE id = ?');
        $statement->execute([(int) $matches[1]]);
        json_response(['message' => 'Deleted']);
        return;
    }

    if ($path === '/api/search' && $method === 'GET') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $statement = $db->prepare(
            'SELECT id, username, avatar_url FROM users WHERE username LIKE ? LIMIT 10'
        );
        $statement->execute(['%' . $q . '%']);
        $users = $statement->fetchAll();
        $statement = $db->prepare(
            'SELECT id, title, game FROM tournaments WHERE title LIKE ? OR game LIKE ? LIMIT 10'
        );
        $statement->execute(['%' . $q . '%', '%' . $q . '%']);
        json_response(['users' => $users, 'tournaments' => $statement->fetchAll()]);
        return;
    }

    if ($path === '/api/notifications') {
        if ($user === null) {
            json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        if ($method === 'GET') {
            $statement = $db->prepare(
                'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
            );
            $statement->execute([$user['id']]);
            json_response($statement->fetchAll());
            return;
        }
        if ($method === 'POST') {
            $data = json_body();
            $statement = $db->prepare(
                'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
            );
            $statement->execute([(int) ($data['id'] ?? 0), $user['id']]);
            json_response(['message' => 'Marked as read']);
            return;
        }
        method_not_allowed();
    }

    http_response_code(404);
    echo 'Not Found';
}

try {
    run_route();
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'An unexpected server error occurred.';
}
