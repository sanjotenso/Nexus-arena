<section class="section">
    <div class="container">
        <div class="tournament-header">
            <h1 class="page-title"><?= e($tournament['title']) ?></h1>
            <span class="badge badge-<?= e($tournament['status']) ?>"><?= e(strtoupper($tournament['status'])) ?></span>
        </div>
        <div class="grid grid-2">
            <div class="card card-glass">
                <h3>Details</h3>
                <p><strong>Game:</strong> <?= e($tournament['game']) ?></p>
                <p><strong>Format:</strong> <?= e($tournament['format']) ?></p>
                <p><strong>Max Teams:</strong> <?= e($tournament['max_teams']) ?></p>
                <p><strong>Start:</strong> <?= e($tournament['start_date'] ?: 'TBA') ?></p>
                <p><?= e($tournament['description']) ?></p>
                <?php if ($user): ?>
                <button class="btn btn-primary mt-2" id="btnRegister" data-tid="<?= e($tournament['id']) ?>">Register</button>
                <?php endif; ?>
            </div>
            <div class="card card-glass">
                <h3>Participants</h3>
                <ul class="participant-list">
                    <?php if ($participants): foreach ($participants as $p): ?>
                    <li><?= e($p['username'] ?: ($p['team_name'] ?: 'Unknown')) ?></li>
                    <?php endforeach; else: ?>
                    <li class="empty">No participants yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="card card-glass mt-2">
            <h3>Bracket &amp; Matches</h3>
            <div id="bracket" class="bracket">
                <?php if ($matches): foreach ($matches as $m): ?>
                <div class="match">
                    <div class="match-team"><?= e($m['t1_name'] ?: 'TBD') ?></div>
                    <div class="match-score"><?= e($m['score1']) ?> - <?= e($m['score2']) ?></div>
                    <div class="match-team"><?= e($m['t2_name'] ?: 'TBD') ?></div>
                </div>
                <?php endforeach; else: ?>
                <p class="empty">Bracket not generated yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
