<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Dashboard</h1>
        <div class="tabs">
            <button type="button" class="tab-btn active" onclick="switchTab(event, 'tab-users')">Users</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-tournaments')">Tournaments</button>
        </div>
        <div id="tab-users" class="tab-panel active">
            <div class="card card-glass table-card">
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['id']) ?></td>
                            <td><?= e($u['username']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e($u['role']) ?></td>
                            <td><?= e($u['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="tab-tournaments" class="tab-panel">
            <button class="btn btn-primary mb-2" onclick="openModal('createTournamentModal')">Create Tournament</button>
            <div class="card card-glass table-card">
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Title</th><th>Game</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tournaments as $t): ?>
                        <tr>
                            <td><?= e($t['id']) ?></td>
                            <td><?= e($t['title']) ?></td>
                            <td><?= e($t['game']) ?></td>
                            <td><?= e($t['status']) ?></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="deleteTournament(<?= e($t['id']) ?>)">Delete</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div id="createTournamentModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content card card-glass">
        <h3>Create Tournament</h3>
        <form id="createTournamentForm" class="form">
            <div class="form-group">
                <label for="tTitle">Title</label>
                <input type="text" id="tTitle" required>
            </div>
            <div class="form-group">
                <label for="tGame">Game</label>
                <input type="text" id="tGame" required>
            </div>
            <div class="form-group">
                <label for="tStart">Start Date</label>
                <input type="datetime-local" id="tStart" required>
            </div>
            <div class="form-group">
                <label for="tMax">Max Teams</label>
                <input type="number" id="tMax" min="2" required>
            </div>
            <div class="form-group">
                <label for="tDesc">Description</label>
                <textarea id="tDesc"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('createTournamentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>
