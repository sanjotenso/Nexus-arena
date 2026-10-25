<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'NEXUS ARENA') ?></title>

    <?php
        $cssPath = app_url('/assets/static/css/style.css');
    ?>
    <link rel="stylesheet" href="<?= e($cssPath) ?>">

</head>
<body data-base-url="<?= e($basePath) ?>">
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="<?= e(app_url('/')) ?>" class="logo">NEXUS<span>ARENA</span></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>

            <div class="nav-links" id="navLinks">
                <a href="<?= e(app_url('/tournaments')) ?>">Tournaments</a>
                <a href="<?= e(app_url('/leaderboard')) ?>">Leaderboard</a>

                <?php if (!empty($user)): ?>
                    <a href="<?= e(app_url('/dashboard')) ?>">Dashboard</a>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <a href="<?= e(app_url('/admin')) ?>">Admin</a>
                    <?php endif; ?>
                    <a href="<?= e(app_url('/profile/' . $user['id'])) ?>">Profile</a>
                    <a href="<?= e(app_url('/logout')) ?>" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="<?= e(app_url('/login')) ?>" class="btn btn-outline">Login</a>
                    <a href="<?= e(app_url('/register')) ?>" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main><?= $content ?></main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 NEXUS ARENA. All rights reserved.</p>
        </div>
    </footer>

    <div id="toast-container"></div>
    <script src="<?= e(app_url('/assets/static/js/app.js')) ?>"></script>
</body>
</html>
