<section class="section">
    <div class="container">
        <h1 class="page-title">Leaderboard</h1>
        <div class="card card-glass table-card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player / Team</th>
                        <th>Points</th>
                        <th>Wins</th>
                        <th>Losses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): foreach ($rows as $index => $r): ?>
                    <tr>
                        <td><?= e($r['rank'] ?: ($index + 1)) ?></td>
                        <td><?= e($r['username'] ?: ($r['team_name'] ?: 'Unknown')) ?></td>
                        <td><?= e($r['points']) ?></td>
                        <td><?= e($r['wins']) ?></td>
                        <td><?= e($r['losses']) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="empty">No data yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
