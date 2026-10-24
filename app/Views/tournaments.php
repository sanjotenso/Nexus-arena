<section class="section">
    <div class="container">
        <h1 class="page-title">Tournaments</h1>
        <form method="get" action="<?= e(app_url('/tournaments')) ?>" class="filters">
            <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Search..." class="input">
            <select name="status" class="input">
                <option value="">All Statuses</option>
                <option value="upcoming" <?= $filters['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                <option value="ongoing" <?= $filters['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <select name="game" class="input">
                <option value="">All Games</option>
                <?php foreach ($games as $g): ?>
                <option value="<?= e($g) ?>" <?= $filters['game'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <div class="grid grid-3">
            <?php if ($tournaments): foreach ($tournaments as $t): ?>
            <article class="card card-glass tournament-card">
                <div class="card-header">
                    <span class="badge badge-<?= e($t['status']) ?>"><?= e(strtoupper($t['status'])) ?></span>
                    <h3><?= e($t['title']) ?></h3>
                    <p class="card-game"><?= e($t['game']) ?></p>
                </div>
                <div class="card-body">
                    <p><?= e($t['description'] ?: 'No description.') ?></p>
                </div>
                <div class="card-footer">
                    <a href="<?= e(app_url('/tournaments/' . $t['id'])) ?>" class="btn btn-primary w-full">Details</a>
                </div>
            </article>
            <?php endforeach; else: ?>
            <p class="empty">No tournaments found.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
