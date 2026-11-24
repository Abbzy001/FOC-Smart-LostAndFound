<?php $title = 'Browse items'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><h1 class="h2 mb-1">Lost and found listings</h1><p class="text-secondary mb-0">Search active items reported by the community.</p></div>
    <?php if (current_user() !== null): ?><a href="/items/new" class="btn btn-primary">Report an item</a><?php endif; ?>
</div>
<form class="card card-body border-0 shadow-sm mb-4" method="get" action="/items">
    <div class="row g-2">
        <div class="col-md-7"><label class="visually-hidden" for="q">Search</label><input class="form-control" id="q" name="q" value="<?= e($query) ?>" placeholder="Search title, description, category or venue"></div>
        <div class="col-md-3">
            <label class="visually-hidden" for="type">Listing type</label>
            <select class="form-select" id="type" name="type">
                <option value="">All reports</option>
                <option value="LOST" <?= $type === 'LOST' ? 'selected' : '' ?>>Lost</option>
                <option value="FOUND" <?= $type === 'FOUND' ? 'selected' : '' ?>>Found</option>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Search</button></div>
    </div>
</form>
<?php require __DIR__ . '/../partials/item_cards.php'; ?>
