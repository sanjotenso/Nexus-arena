<section class="section">
    <div class="container">
        <div class="profile-header">
            <div class="avatar"><?= e(strtoupper(substr($profile['username'] ?? 'U', 0, 1))) ?></div>
            <div>
                <h1 class="page-title"><?= e($profile['username'] ?? 'Unknown') ?></h1>
                <p class="meta"><?= e(strtoupper($profile['role'] ?? 'player')) ?> &bull; Joined <?= e($profile['created_at'] ?? '') ?></p>
            </div>
        </div>
        <div class="grid grid-3">
            <div class="stat card card-glass">
                <div class="stat-value"><?= e($stats['total_points'] ?? 0) ?></div>
                <div class="stat-label">Points</div>
            </div>
            <div class="stat card card-glass">
                <div class="stat-value"><?= e($stats['total_wins'] ?? 0) ?></div>
                <div class="stat-label">Wins</div>
            </div>
            <div class="stat card card-glass">
                <div class="stat-value"><?= e($stats['total_losses'] ?? 0) ?></div>
                <div class="stat-label">Losses</div>
            </div>
        </div>
        <h2 class="section-title">Teams</h2>
        <div class="grid grid-3">
            <?php if ($teams): foreach ($teams as $t): ?>
            <div class="card card-glass">
                <h3><a href="<?= e(app_url('/team/' . $t['id'])) ?>"><?= e($t['name']) ?></a></h3>
                <p class="meta">Tag: <?= e($t['tag']) ?></p>
            </div>
            <?php endforeach; else: ?>
            <p class="empty">No teams.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
