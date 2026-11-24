<?php
$title = 'Find what you lost';
?>
<section class="hero rounded-4 p-4 p-md-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <p class="text-uppercase small fw-semibold mb-2">Faculty of Computing community</p>
            <h1 class="display-5 fw-bold">Lost something? Found something?</h1>
            <p class="lead mb-4">Report it, browse recent listings, and securely reconnect items with their owners.</p>
            <a class="btn btn-light btn-lg me-2" href="/items">Browse items</a>
            <?php if (current_user() !== null): ?>
                <a class="btn btn-outline-light btn-lg" href="/items/new">Report an item</a>
            <?php else: ?>
                <a class="btn btn-outline-light btn-lg" href="/register">Join the community</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="hero-card p-4 rounded-3">
                <h2 class="h5">A safer handover</h2>
                <p class="mb-0">Sign in with your password. A separate one-time PIN is used only when an approved item is handed over.</p>
            </div>
        </div>
    </div>
</section>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h3 mb-0">Recently reported</h2>
    <a href="/items" class="link-primary">View all</a>
</div>
<?php require __DIR__ . '/partials/item_cards.php'; ?>
