<section class="section">
    <div class="container">
        <h1 class="page-title">Dashboard</h1>
        <div class="dashboard-grid">
            <div class="dashboard-panel">
                <h2>Upcoming Tournaments</h2>
                <div class="list">
                    <?php if ($upcoming): foreach ($upcoming as $t): ?>
                    <div class="list-item">
                        <div>
                            <a href="<?= e(app_url('/tournaments/' . $t['id'])) ?>"><?= e($t['title']) ?></a>
                            <span class="meta"><?= e($t['game']) ?> &bull; <?= e($t['start_date'] ?: 'TBA') ?></span>
                        </div>
                        <span class="badge badge-<?= e($t['status']) ?>"><?= e($t['status']) ?></span>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="empty">No upcoming tournaments.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-panel">
                <h2>My Teams</h2>
                <div class="list">
                    <?php if ($teams): foreach ($teams as $team): ?>
                    <div class="list-item">
                        <a href="<?= e(app_url('/team/' . $team['id'])) ?>"><?= e($team['name']) ?> [<?= e($team['tag']) ?>]</a>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="empty">You are not in any team.</p>
                    <?php endif; ?>
                </div>
                <button class="btn btn-secondary mt-2" onclick="openModal('createTeamModal')">Create Team</button>
            </div>
            <div class="dashboard-panel">
                <h2>Notifications</h2>
                <div class="list">
                    <?php if ($notifications): foreach ($notifications as $n): ?>
                    <div class="list-item">
                        <a href="<?= e($n['link'] ? app_url($n['link']) : '#') ?>"><?= e($n['message']) ?></a>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="empty">No notifications.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="createTeamModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content card card-glass">
        <h3>Create Team</h3>
        <form id="createTeamForm" class="form">
            <div class="form-group">
                <label for="teamName">Team Name</label>
                <input type="text" id="teamName" required>
            </div>
            <div class="form-group">
                <label for="teamTag">Tag</label>
                <input type="text" id="teamTag" required maxlength="5">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('createTeamModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>
