# NEXUS ARENA

A modern, futuristic gaming tournament platform.

## Features
- Secure user registration and login
- Player dashboard with tournaments, teams, and notifications
- Tournament creation and registration
- Player profiles and team management
- Tournament brackets and match schedules
- Leaderboards and rankings
- Admin dashboard
- Search, filtering, and notifications

## Run Locally

1. Change to the application folder:
   cd nexus-arena

2. Install dependencies:
   pip install -r requirements.txt

3. Start the server:
   python app.py

4. Open http://localhost:8888

Default admin:
- Username: admin
- Password: admin123

## Deploy with Docker

docker build -t nexus-arena .
docker run -p 8888:8888 nexus-arena

## Production Notes
- Change COOKIE_SECRET in the environment.
- Replace SQLite with PostgreSQL for scale.
- Run behind Nginx with SSL.
- Enable XSRF cookies after updating the frontend to send the token.
[text](README.md) [text](register.html) [text](team.html) [text](tournament_detail.html) [text](tournaments.html) [text](.vscode) [text](nexus-arena) [text](admin.html) [text](base.html) [text](dashboard.html) [text](index.html) [text](leaderboard.html) [text](login.html) [text](profile.html)