<?php $title = 'My dashboard'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><h1 class="h2 mb-1">My dashboard</h1><p class="text-secondary mb-0">Your reports and the claims you have submitted.</p></div>
    <a class="btn btn-primary" href="/items/new">Report an item</a>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary small">Your reports</div><div class="display-6"><?= count($items) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary small">Claims waiting for review</div><div class="display-6"><?= (int) ($claimCounts['PENDING'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary small">Approved claims</div><div class="display-6"><?= (int) ($claimCounts['APPROVED'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-secondary small">Completed claims</div><div class="display-6"><?= (int) ($claimCounts['COMPLETED'] ?? 0) ?></div></div></div></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Items you reported</h2>
    <a href="/claims">View claims</a>
</div>
<?php if ($items === []): ?>
    <div class="empty-state p-5 text-center rounded-3"><h3 class="h5">You have not reported any items</h3><p class="text-secondary">Your reports will be listed here.</p><a class="btn btn-primary" href="/items/new">Create your first report</a></div>
<?php else: ?>
    <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Item</th><th>Type</th><th>Reported</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php if ($item['status'] === 'SUSPENDED'): ?>
                            <span class="fw-semibold"><?= e($item['title']) ?></span>
                        <?php else: ?>
                            <a class="fw-semibold" href="/items/<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a>
                        <?php endif; ?>
                        <div class="small text-secondary"><?= e($item['category']) ?> · <?= e($item['venue']) ?></div>
                    </td>
                    <td><?= e($item['item_type']) ?></td>
                    <td><?= e($item['event_date']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= e($item['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
