<section class="section">
    <div class="container">
        <h1 class="page-title"><?= e($team['name'] ?? 'Team') ?> <span class="meta">[<?= e($team['tag'] ?? '') ?>]</span></h1>
        <p class="meta">Captain: <?= e($team['captain_name'] ?? 'Unknown') ?></p>
        <h2 class="section-title">Roster</h2>
        <div class="grid grid-4">
            <?php if ($members): foreach ($members as $m): ?>
            <div class="card card-glass member-card">
                <div class="avatar"><?= e(strtoupper(substr($m['username'], 0, 1))) ?></div>
                <h4><a href="<?= e(app_url('/profile/' . $m['id'])) ?>"><?= e($m['username']) ?></a></h4>
            </div>
            <?php endforeach; else: ?>
            <p class="empty">No members.</p>
            <?php endif; ?>
        </div>
        <?php if ($user): ?>
        <button class="btn btn-secondary mt-2" id="btnJoinTeam" data-tid="<?= e($team['id'] ?? '') ?>">Join Team</button>
        <?php endif; ?>
    </div>
</section>
