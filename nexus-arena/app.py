import os
import sqlite3
import json
import hashlib
import secrets
import tornado.ioloop
import tornado.web
from tornado.options import define, options, parse_command_line

define("port", default=8888, help="run on the given port", type=int)
DB_FILE = "nexus_arena.db"
PROJECT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
STATIC_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "static")


def get_db():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def init_db():
    conn = get_db()
    conn.executescript("""
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
    """)
    cur = conn.cursor()
    cur.execute("SELECT id FROM users WHERE username = ?", ("admin",))
    if not cur.fetchone():
        salt = secrets.token_hex(16)
        phash = hashlib.pbkdf2_hmac('sha256', "admin123".encode(), salt.encode(), 100000).hex()
        cur.execute("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
                    ("admin", "admin@nexusarena.com", f"{salt}${phash}", "admin"))
    cur.execute("SELECT id FROM tournaments LIMIT 1")
    if not cur.fetchone():
        cur.execute("INSERT INTO tournaments (title, game, status, start_date, max_teams, description) VALUES (?, ?, ?, ?, ?, ?)",
                    ("Neon Legends Open", "Valorant", "upcoming", "2026-11-15T18:00", 16, "A high-stakes open tournament for elite squads."))
        cur.execute("INSERT INTO tournaments (title, game, status, start_date, max_teams, description) VALUES (?, ?, ?, ?, ?, ?)",
                    ("Cyber Clash 2026", "League of Legends", "ongoing", "2026-10-20T17:00", 8, "Mid-season championship with double elimination."))
        cur.execute("INSERT INTO tournaments (title, game, status, start_date, max_teams, description) VALUES (?, ?, ?, ?, ?, ?)",
                    ("Apex Infinity Cup", "Apex Legends", "upcoming", "2026-12-01T16:00", 20, "Battle royale showdown across multiple maps."))
    conn.commit()
    conn.close()


def hash_password(password):
    salt = secrets.token_hex(16)
    phash = hashlib.pbkdf2_hmac('sha256', password.encode(), salt.encode(), 100000).hex()
    return f"{salt}${phash}"


def verify_password(password, stored):
    salt, phash = stored.split('$', 1)
    check = hashlib.pbkdf2_hmac('sha256', password.encode(), salt.encode(), 100000).hex()
    return secrets.compare_digest(phash, check)


class BaseHandler(tornado.web.RequestHandler):
    def get_current_user(self):
        uid = self.get_secure_cookie("user_id")
        if uid:
            db = get_db()
            user = db.execute("SELECT * FROM users WHERE id = ?", (int(uid),)).fetchone()
            db.close()
            if user:
                return dict(user)
        return None

    def write_json(self, obj, status=200):
        self.set_status(status)
        self.set_header("Content-Type", "application/json")
        self.write(json.dumps(obj))


class MainHandler(BaseHandler):
    def get(self):
        db = get_db()
        tournaments = db.execute("SELECT * FROM tournaments ORDER BY created_at DESC LIMIT 6").fetchall()
        db.close()
        self.render("index.html", tournaments=[dict(t) for t in tournaments], user=self.current_user)


class LoginHandler(BaseHandler):
    def get(self):
        if self.current_user:
            self.redirect("/dashboard")
            return
        self.render("login.html", error=None, user=None)

    def post(self):
        username = self.get_argument("username", "")
        password = self.get_argument("password", "")
        db = get_db()
        user = db.execute("SELECT * FROM users WHERE username = ?", (username,)).fetchone()
        db.close()
        if user and verify_password(password, user["password_hash"]):
            self.set_secure_cookie("user_id", str(user["id"]))
            self.redirect("/dashboard")
        else:
            self.render("login.html", error="Invalid username or password.", user=None)


class RegisterHandler(BaseHandler):
    def get(self):
        if self.current_user:
            self.redirect("/dashboard")
            return
        self.render("register.html", error=None, user=None)

    def post(self):
        username = self.get_argument("username", "").strip()
        email = self.get_argument("email", "").strip()
        password = self.get_argument("password", "")
        confirm = self.get_argument("confirm", "")
        if not username or not email or not password:
            self.render("register.html", error="All fields are required.", user=None)
            return
        if password != confirm:
            self.render("register.html", error="Passwords do not match.", user=None)
            return
        if len(password) < 6:
            self.render("register.html", error="Password must be at least 6 characters.", user=None)
            return
        db = get_db()
        try:
            ph = hash_password(password)
            db.execute("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)", (username, email, ph))
            db.commit()
            user = db.execute("SELECT * FROM users WHERE username = ?", (username,)).fetchone()
            self.set_secure_cookie("user_id", str(user["id"]))
            self.redirect("/dashboard")
        except sqlite3.IntegrityError:
            self.render("register.html", error="Username or email already exists.", user=None)
        finally:
            db.close()


class LogoutHandler(BaseHandler):
    def get(self):
        self.clear_cookie("user_id")
        self.redirect("/")


class DashboardHandler(BaseHandler):
    def get(self):
        if not self.current_user:
            self.redirect("/login")
            return
        db = get_db()
        upcoming = db.execute("SELECT * FROM tournaments WHERE status = 'upcoming' ORDER BY start_date LIMIT 10").fetchall()
        my_teams = db.execute("SELECT t.* FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE tm.user_id = ?", (self.current_user["id"],)).fetchall()
        notifications = db.execute("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", (self.current_user["id"],)).fetchall()
        db.close()
        self.render("dashboard.html", user=self.current_user, upcoming=[dict(r) for r in upcoming],
                    teams=[dict(r) for r in my_teams], notifications=[dict(r) for r in notifications])


class TournamentListHandler(BaseHandler):
    def get(self):
        db = get_db()
        status = self.get_argument("status", "")
        game = self.get_argument("game", "")
        q = self.get_argument("q", "")
        sql = "SELECT * FROM tournaments WHERE 1=1"
        params = []
        if status:
            sql += " AND status = ?"
            params.append(status)
        if game:
            sql += " AND game = ?"
            params.append(game)
        if q:
            sql += " AND (title LIKE ? OR game LIKE ?)"
            params.append(f"%{q}%")
            params.append(f"%{q}%")
        sql += " ORDER BY start_date DESC"
        tournaments = db.execute(sql, params).fetchall()
        games = [r[0] for r in db.execute("SELECT DISTINCT game FROM tournaments").fetchall()]
        db.close()
        self.render("tournaments.html", tournaments=[dict(t) for t in tournaments], games=games,
                    filters={"status": status, "game": game, "q": q}, user=self.current_user)


class TournamentDetailHandler(BaseHandler):
    def get(self, tid):
        db = get_db()
        tournament = db.execute("SELECT * FROM tournaments WHERE id = ?", (tid,)).fetchone()
        if not tournament:
            self.redirect("/tournaments")
            db.close()
            return
        participants = db.execute("SELECT p.*, u.username, t.name as team_name FROM participants p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN teams t ON p.team_id = t.id WHERE p.tournament_id = ?", (tid,)).fetchall()
        matches = db.execute("SELECT m.*, t1.name as t1_name, t2.name as t2_name FROM matches m LEFT JOIN teams t1 ON m.team1_id = t1.id LEFT JOIN teams t2 ON m.team2_id = t2.id WHERE m.tournament_id = ? ORDER BY m.round, m.match_number", (tid,)).fetchall()
        db.close()
        self.render("tournament_detail.html", tournament=dict(tournament),
                    participants=[dict(p) for p in participants],
                    matches=[dict(m) for m in matches], user=self.current_user)


class TournamentApiHandler(BaseHandler):
    def post(self):
        if not self.current_user or self.current_user.get("role") != "admin":
            self.write_json({"error": "Forbidden"}, 403)
            return
        data = json.loads(self.request.body or "{}")
        required = ["title", "game", "start_date", "max_teams"]
        for r in required:
            if not data.get(r):
                self.write_json({"error": f"{r} is required"}, 400)
                return
        db = get_db()
        cur = db.cursor()
        cur.execute("INSERT INTO tournaments (title, game, format, start_date, end_date, max_teams, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    (data["title"], data["game"], data.get("format", "single_elimination"), data["start_date"],
                     data.get("end_date"), data["max_teams"], data.get("description", ""), self.current_user["id"]))
        tid = cur.lastrowid
        db.commit()
        db.close()
        self.write_json({"id": tid, "message": "Tournament created"})


class TournamentRegisterHandler(BaseHandler):
    def post(self, tid):
        if not self.current_user:
            self.write_json({"error": "Unauthorized"}, 401)
            return
        db = get_db()
        existing = db.execute("SELECT id FROM participants WHERE tournament_id = ? AND user_id = ?", (tid, self.current_user["id"])).fetchone()
        if existing:
            db.close()
            self.write_json({"error": "Already registered"}, 409)
            return
        db.execute("INSERT INTO participants (tournament_id, user_id, status) VALUES (?, ?, ?)", (tid, self.current_user["id"], "confirmed"))
        db.execute("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)",
                   (self.current_user["id"], f"You registered for tournament #{tid}", f"/tournaments/{tid}"))
        db.commit()
        db.close()
        self.write_json({"message": "Registered successfully"})


class BracketApiHandler(BaseHandler):
    def get(self, tid):
        db = get_db()
        tournament = db.execute("SELECT * FROM tournaments WHERE id = ?", (tid,)).fetchone()
        matches = db.execute("SELECT m.*, t1.name as t1_name, t2.name as t2_name FROM matches m LEFT JOIN teams t1 ON m.team1_id = t1.id LEFT JOIN teams t2 ON m.team2_id = t2.id WHERE m.tournament_id = ? ORDER BY m.round, m.match_number", (tid,)).fetchall()
        db.close()
        rounds = {}
        for m in matches:
            r = m["round"]
            if r not in rounds:
                rounds[r] = []
            rounds[r].append(dict(m))
        self.write_json({"tournament": dict(tournament), "rounds": rounds})


class MatchUpdateHandler(BaseHandler):
    def post(self, mid):
        if not self.current_user or self.current_user.get("role") != "admin":
            self.write_json({"error": "Forbidden"}, 403)
            return
        data = json.loads(self.request.body or "{}")
        db = get_db()
        db.execute("UPDATE matches SET score1 = ?, score2 = ?, winner_id = ?, status = ? WHERE id = ?",
                   (data.get("score1", 0), data.get("score2", 0), data.get("winner_id"), data.get("status", "completed"), mid))
        db.commit()
        db.close()
        self.write_json({"message": "Match updated"})


class ProfileHandler(BaseHandler):
    def get(self, uid):
        db = get_db()
        user = db.execute("SELECT id, username, email, avatar_url, bio, role, created_at FROM users WHERE id = ?", (uid,)).fetchone()
        teams = db.execute("SELECT t.* FROM teams t JOIN team_members tm ON t.id = tm.team_id WHERE tm.user_id = ?", (uid,)).fetchall()
        stats = db.execute("SELECT SUM(points) as total_points, SUM(wins) as total_wins, SUM(losses) as total_losses FROM leaderboard WHERE user_id = ?", (uid,)).fetchone()
        db.close()
        self.render("profile.html", profile=dict(user) if user else {}, teams=[dict(t) for t in teams],
                    stats=dict(stats) if stats else {}, user=self.current_user)


class TeamHandler(BaseHandler):
    def get(self, tid):
        db = get_db()
        team = db.execute("SELECT t.*, u.username as captain_name FROM teams t JOIN users u ON t.captain_id = u.id WHERE t.id = ?", (tid,)).fetchone()
        members = db.execute("SELECT u.id, u.username, u.avatar_url FROM users u JOIN team_members tm ON u.id = tm.user_id WHERE tm.team_id = ?", (tid,)).fetchall()
        db.close()
        self.render("team.html", team=dict(team) if team else {}, members=[dict(m) for m in members], user=self.current_user)


class TeamApiHandler(BaseHandler):
    def post(self):
        if not self.current_user:
            self.write_json({"error": "Unauthorized"}, 401)
            return
        data = json.loads(self.request.body or "{}")
        name = data.get("name", "").strip()
        tag = data.get("tag", "").strip()
        if not name or not tag:
            self.write_json({"error": "Name and tag required"}, 400)
            return
        db = get_db()
        try:
            cur = db.cursor()
            cur.execute("INSERT INTO teams (name, tag, captain_id) VALUES (?, ?, ?)", (name, tag, self.current_user["id"]))
            tid = cur.lastrowid
            cur.execute("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)", (tid, self.current_user["id"]))
            db.commit()
            self.write_json({"id": tid, "message": "Team created"})
        except sqlite3.IntegrityError:
            self.write_json({"error": "Team name already exists"}, 409)
        finally:
            db.close()


class TeamJoinHandler(BaseHandler):
    def post(self):
        if not self.current_user:
            self.write_json({"error": "Unauthorized"}, 401)
            return
        data = json.loads(self.request.body or "{}")
        tid = data.get("team_id")
        db = get_db()
        existing = db.execute("SELECT id FROM team_members WHERE team_id = ? AND user_id = ?", (tid, self.current_user["id"])).fetchone()
        if existing:
            db.close()
            self.write_json({"error": "Already a member"}, 409)
            return
        db.execute("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)", (tid, self.current_user["id"]))
        db.commit()
        db.close()
        self.write_json({"message": "Joined team"})


class LeaderboardHandler(BaseHandler):
    def get(self):
        db = get_db()
        rows = db.execute("SELECT l.*, u.username, t.name as team_name FROM leaderboard l LEFT JOIN users u ON l.user_id = u.id LEFT JOIN teams t ON l.team_id = t.id ORDER BY l.points DESC LIMIT 100").fetchall()
        db.close()
        self.render("leaderboard.html", rows=[dict(r) for r in rows], user=self.current_user)


class AdminHandler(BaseHandler):
    def get(self):
        if not self.current_user or self.current_user.get("role") != "admin":
            self.redirect("/")
            return
        db = get_db()
        users = db.execute("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC").fetchall()
        tournaments = db.execute("SELECT * FROM tournaments ORDER BY created_at DESC").fetchall()
        db.close()
        self.render("admin.html", users=[dict(u) for u in users], tournaments=[dict(t) for t in tournaments], user=self.current_user)


class AdminUsersHandler(BaseHandler):
    def get(self):
        if not self.current_user or self.current_user.get("role") != "admin":
            self.write_json({"error": "Forbidden"}, 403)
            return
        db = get_db()
        users = db.execute("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC").fetchall()
        db.close()
        self.write_json([dict(u) for u in users])


class AdminTournamentHandler(BaseHandler):
    def delete(self, tid):
        if not self.current_user or self.current_user.get("role") != "admin":
            self.write_json({"error": "Forbidden"}, 403)
            return
        db = get_db()
        db.execute("DELETE FROM tournaments WHERE id = ?", (tid,))
        db.commit()
        db.close()
        self.write_json({"message": "Deleted"})


class SearchHandler(BaseHandler):
    def get(self):
        q = self.get_argument("q", "")
        db = get_db()
        users = db.execute("SELECT id, username, avatar_url FROM users WHERE username LIKE ? LIMIT 10", (f"%{q}%",)).fetchall()
        tournaments = db.execute("SELECT id, title, game FROM tournaments WHERE title LIKE ? OR game LIKE ? LIMIT 10", (f"%{q}%", f"%{q}%")).fetchall()
        db.close()
        self.write_json({"users": [dict(u) for u in users], "tournaments": [dict(t) for t in tournaments]})


class NotificationHandler(BaseHandler):
    def get(self):
        if not self.current_user:
            self.write_json({"error": "Unauthorized"}, 401)
            return
        db = get_db()
        rows = db.execute("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20", (self.current_user["id"],)).fetchall()
        db.close()
        self.write_json([dict(r) for r in rows])

    def post(self):
        if not self.current_user:
            self.write_json({"error": "Unauthorized"}, 401)
            return
        data = json.loads(self.request.body or "{}")
        nid = data.get("id")
        db = get_db()
        db.execute("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", (nid, self.current_user["id"]))
        db.commit()
        db.close()
        self.write_json({"message": "Marked as read"})


def make_app():
    settings = dict(
        template_path=PROJECT_DIR,
        static_path=STATIC_DIR,
        cookie_secret=os.environ.get("COOKIE_SECRET", "__CHANGE_ME__" * 2),
        login_url="/login",
        debug=True,
        xsrf_cookies=False,
    )
    return tornado.web.Application([
        (r"/", MainHandler),
        (r"/login", LoginHandler),
        (r"/register", RegisterHandler),
        (r"/logout", LogoutHandler),
        (r"/dashboard", DashboardHandler),
        (r"/tournaments", TournamentListHandler),
        (r"/tournaments/([0-9]+)", TournamentDetailHandler),
        (r"/api/tournaments", TournamentApiHandler),
        (r"/api/tournaments/([0-9]+)/register", TournamentRegisterHandler),
        (r"/api/tournaments/([0-9]+)/bracket", BracketApiHandler),
        (r"/api/matches/([0-9]+)", MatchUpdateHandler),
        (r"/profile/([0-9]+)", ProfileHandler),
        (r"/team/([0-9]+)", TeamHandler),
        (r"/api/teams", TeamApiHandler),
        (r"/api/teams/join", TeamJoinHandler),
        (r"/leaderboard", LeaderboardHandler),
        (r"/admin", AdminHandler),
        (r"/api/admin/users", AdminUsersHandler),
        (r"/api/admin/tournaments/([0-9]+)", AdminTournamentHandler),
        (r"/api/search", SearchHandler),
        (r"/api/notifications", NotificationHandler),
    ], **settings)


if __name__ == "__main__":
    parse_command_line()
    init_db()
    app = make_app()
    app.listen(options.port)
    print(f"NEXUS ARENA running at http://localhost:{options.port}")
    tornado.ioloop.IOLoop.current().start()