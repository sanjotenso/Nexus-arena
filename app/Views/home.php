<section class="hero landing-hero" id="top">
    <div class="container landing-layout">
        <div class="hero-copy">
            <p class="eyebrow">THE ARENA IS YOURS</p>
            <h1 class="hero-title">FORGE YOUR<br>LEGEND.</h1>
            <p class="hero-subtitle">
                The ultimate tournament platform for elite competitors. Find your next match,
                build your squad, and climb the rankings.
            </p>
            <a href="#tournaments" class="btn btn-primary btn-large">Explore tournaments <span aria-hidden="true">&rarr;</span></a>
            <div class="hero-proof">
                <span class="proof-mark" aria-hidden="true">N</span>
                <span>Compete. Connect. Conquer.</span>
            </div>
        </div>

        <aside class="auth-card" id="auth" aria-label="Account access">
            <?php if (!empty($user)): ?>
                <div class="auth-welcome">
                    <span class="auth-kicker">PLAYER ACCOUNT</span>
                    <h2>Welcome back, <?= e($user['username']) ?>.</h2>
                    <p>Your next match is waiting. Head to your dashboard to keep your run going.</p>
                    <a href="<?= e(app_url('/dashboard')) ?>" class="btn btn-primary w-full">Open dashboard</a>
                </div>
            <?php else: ?>
                <div class="auth-heading">
                    <span class="auth-kicker">YOUR NEXT WIN STARTS HERE</span>
                    <h2>Enter the arena</h2>
                    <p>Sign in or create your player account.</p>
                </div>

                <div class="auth-tabs" role="tablist" aria-label="Account access options">
                    <button type="button" class="auth-tab<?= $authTab === 'login' ? ' active' : '' ?>"
                            id="auth-login-tab" role="tab" aria-controls="auth-login-panel"
                            aria-selected="<?= $authTab === 'login' ? 'true' : 'false' ?>" data-auth-tab="login">Log in</button>
                    <button type="button" class="auth-tab<?= $authTab === 'register' ? ' active' : '' ?>"
                            id="auth-register-tab" role="tab" aria-controls="auth-register"
                            aria-selected="<?= $authTab === 'register' ? 'true' : 'false' ?>" data-auth-tab="register">Create account</button>
                </div>

                <?php if (!empty($authError)): ?>
                    <div class="alert alert-error auth-error" role="alert"><?= e($authError) ?></div>
                <?php endif; ?>

                <div class="auth-panel<?= $authTab === 'login' ? ' active' : '' ?>" id="auth-login-panel" role="tabpanel" aria-labelledby="auth-login-tab">
                    <form method="post" action="<?= e(app_url('/index.php?route=login')) ?>" class="form" id="loginForm">
                        <div class="form-group">
                            <label for="login-username">Username</label>
                            <input type="text" id="login-username" name="username" required autocomplete="username" placeholder="Your player name">
                        </div>
                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <input type="password" id="login-password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Log in to NEXUS <span aria-hidden="true">&rarr;</span></button>
                    </form>
                    <p class="auth-note">New to NEXUS? <button type="button" class="auth-inline-link" data-auth-tab="register">Create an account</button></p>
                </div>

                <div class="auth-panel<?= $authTab === 'register' ? ' active' : '' ?>" id="auth-register" role="tabpanel" aria-labelledby="auth-register-tab">
                    <form method="post" action="<?= e(app_url('/index.php?route=register')) ?>" class="form" id="registerForm">
                        <div class="form-group">
                            <label for="register-username">Username</label>
                            <input type="text" id="register-username" name="username" required minlength="3" maxlength="30" autocomplete="username" placeholder="Choose a player name">
                        </div>
                        <div class="form-group">
                            <label for="register-email">Email</label>
                            <input type="email" id="register-email" name="email" required autocomplete="email" placeholder="you@example.com">
                        </div>
                        <div class="form-group">
                            <label for="register-password">Password</label>
                            <input type="password" id="register-password" name="password" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                        </div>
                        <div class="form-group">
                            <label for="register-confirm">Confirm password</label>
                            <input type="password" id="register-confirm" name="confirm" required minlength="6" autocomplete="new-password" placeholder="Enter your password again">
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Create your account <span aria-hidden="true">&rarr;</span></button>
                    </form>
                    <p class="auth-note">Already a player? <button type="button" class="auth-inline-link" data-auth-tab="login">Log in</button></p>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class="section" id="tournaments">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">MAKE YOUR MOVE</p>
                <h2 class="section-title">Featured tournaments</h2>
            </div>
            <a href="<?= e(app_url('/tournaments')) ?>" class="text-link">Browse all tournaments <span aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="grid grid-3">
            <?php if ($tournaments): foreach ($tournaments as $t): ?>
                <article class="card tournament-card">
                    <div class="card-header">
                        <span class="badge badge-<?= e($t['status']) ?>"><?= e(strtoupper($t['status'])) ?></span>
                        <h3 class="card-title"><?= e($t['title']) ?></h3>
                        <p class="card-game"><?= e($t['game']) ?></p>
                    </div>
                    <div class="card-body">
                        <p><?= e($t['description'] ?: 'No description provided.') ?></p>
                        <div class="meta">
                            <span>Max teams: <?= e($t['max_teams']) ?></span>
                            <span>Start: <?= e($t['start_date'] ?: 'TBA') ?></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="<?= e(app_url('/tournaments/' . $t['id'])) ?>" class="btn btn-outline w-full">View tournament</a>
                    </div>
                </article>
            <?php endforeach; else: ?>
                <p class="empty">No tournaments are listed yet. Check back soon.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section leaderboard-section" id="leaderboard">
    <div class="container leaderboard-layout">
        <div>
            <p class="eyebrow">THE ONES TO BEAT</p>
            <h2 class="section-title">Leaderboard</h2>
            <p class="section-intro">Every match counts. Earn points, rise through the ranks, and make your name known.</p>
            <a href="<?= e(app_url('/leaderboard')) ?>" class="text-link">View full rankings <span aria-hidden="true">&rarr;</span></a>
        </div>
        <div class="card table-card leaderboard-card">
            <table class="table">
                <thead>
                    <tr><th>Rank</th><th>Player / Team</th><th>Points</th></tr>
                </thead>
                <tbody>
                    <?php if ($leaderboard): foreach ($leaderboard as $index => $row): ?>
                        <tr>
                            <td><span class="rank-number"><?= e($row['rank'] ?: ($index + 1)) ?></span></td>
                            <td><?= e($row['username'] ?: ($row['team_name'] ?: 'Unknown')) ?></td>
                            <td class="points-cell"><?= e($row['points']) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="3" class="empty">No rankings yet. Be the first to compete.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="section alt" id="about">
    <div class="container">
        <p class="eyebrow">BUILT FOR COMPETITORS</p>
        <h2 class="section-title">Why NEXUS ARENA?</h2>
        <div class="grid grid-3">
            <div class="feature">
                <div class="feature-icon">01</div>
                <h3>Automated brackets</h3>
                <p>Real-time single and double elimination brackets with live match updates.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">02</div>
                <h3>Team management</h3>
                <p>Create squads, invite players, and manage rosters from your dashboard.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">03</div>
                <h3>Global rankings</h3>
                <p>Earn points across tournaments and climb the global leaderboard.</p>
            </div>
        </div>
    </div>
</section>
