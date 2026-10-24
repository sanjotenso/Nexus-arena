<section class="section section-center">
    <div class="card card-glass form-card">
        <h2 class="form-title">Create Account</h2>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/register')) ?>" class="form" id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="30">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required>
            </div>
            <button type="submit" class="btn btn-primary w-full">Create Account</button>
        </form>
        <p class="form-footer">Already have an account? <a href="<?= e(app_url('/login')) ?>">Login</a></p>
    </div>
</section>
