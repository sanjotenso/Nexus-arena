<section class="hero">
    <div class="hero-glow"></div>
    <div class="container hero-inner">
        <h1 class="hero-title">FORGE YOUR LEGEND</h1>
        <p class="hero-subtitle">The ultimate tournament platform for elite competitors. Register, compete, and dominate in next-generation esports events.</p>
        <div class="hero-actions">
            <a href="<?= e(app_url('/tournaments')) ?>" class="btn btn-glow btn-large">Explore Tournaments</a>
            <a href="<?= e(app_url('/register')) ?>" class="btn btn-outline btn-large">Join Now</a>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2 class="section-title">Featured Tournaments</h2>
        <div class="grid grid-3">
            <?php foreach ($tournaments as $t): ?>
            <article class="card card-glass tournament-card">
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
                    <a href="<?= e(app_url('/tournaments/' . $t['id'])) ?>" class="btn btn-primary w-full">View Details</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section alt">
    <div class="container">
        <h2 class="section-title">Why NEXUS ARENA?</h2>
        <div class="grid grid-3">
            <div class="feature">
                <div class="feature-icon">01</div>
                <h3>Automated Brackets</h3>
                <p>Real-time single and double elimination brackets with live match updates.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">02</div>
                <h3>Team Management</h3>
                <p>Create squads, invite players, and manage rosters from your dashboard.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">03</div>
                <h3>Global Rankings</h3>
                <p>Earn points across tournaments and climb the global leaderboard.</p>
            </div>
        </div>
    </div>
</section>
