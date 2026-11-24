<?php $title = 'Sign in'; ?>
<section class="card form-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h2 mb-2">Sign in</h1>
        <p class="text-secondary mb-4">Use your registration number or email and account password.</p>
        <form method="post" action="/login">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="identifier">Registration number or email</label>
                <input class="form-control" id="identifier" name="identifier" autocomplete="username" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>
        <p class="mt-3 mb-0 text-center">New here? <a href="/register">Create an account</a>.</p>
    </div>
</section>
