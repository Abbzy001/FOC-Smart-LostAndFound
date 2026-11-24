<?php
/** @var string $viewFile */
/** @var array $flashes */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Faculty of Computing lost and found community">
    <title><?= e($title ?? 'Smart Lost and Found') ?> | Faculty of Computing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/">FOC Lost &amp; Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="/items">Browse items</a>
                <?php if (current_user() !== null): ?>
                    <a class="nav-link" href="/dashboard">My dashboard</a>
                    <a class="nav-link" href="/items/new">Report an item</a>
                    <a class="nav-link" href="/claims">My claims</a>
                    <?php if (in_array(current_user()['role'], ['moderator', 'admin'], true)): ?>
                        <a class="nav-link" href="/admin">Moderation</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="navbar-nav align-items-lg-center">
                <?php if (current_user() !== null): ?>
                    <span class="navbar-text me-lg-3"><?= e(current_user()['full_name']) ?></span>
                    <form method="post" action="/logout" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-light btn-sm" type="submit">Sign out</button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="/login">Sign in</a>
                    <a class="btn btn-light btn-sm ms-lg-2" href="/register">Create account</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php foreach ($flashes as $notice): ?>
        <div class="alert alert-<?= e($notice['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($notice['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
    <?= $viewContent ?>
</main>
<footer class="border-top py-4 mt-5">
    <div class="container text-secondary small">Faculty of Computing Smart Lost and Found</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
