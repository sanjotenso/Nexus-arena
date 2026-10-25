<section class="section section-center">
    <div class="card card-glass form-card">
        <h2 class="form-title">Welcome Back</h2>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/index.php?route=login')) ?>" class="form" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-full">Login</button>
        </form>
        <p class="form-footer">Don't have an account? <a href="<?= e(app_url('/register')) ?>">Register</a></p>
    </div>
</section>
