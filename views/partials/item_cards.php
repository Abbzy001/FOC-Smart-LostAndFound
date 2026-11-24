<?php if ($items === []): ?>
    <div class="empty-state p-5 text-center rounded-3">
        <h3 class="h5">No listings yet</h3>
        <p class="text-secondary mb-0">New reports will appear here.</p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <span class="badge <?= $item['item_type'] === 'FOUND' ? 'text-bg-success' : 'text-bg-warning' ?>">
                            <?= e($item['item_type']) ?>
                        </span>
                        <h3 class="h5 mt-3"><a class="stretched-link text-decoration-none" href="/items/<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a></h3>
                        <p class="text-secondary mb-2"><?= e($item['category']) ?> · <?= e($item['venue']) ?></p>
                        <p class="mb-0"><?= e(mb_strlen($item['description']) > 150 ? mb_substr($item['description'], 0, 150) . '…' : $item['description']) ?></p>
                    </div>
                    <div class="card-footer bg-white border-0 text-secondary small">
                        <?= e($item['event_date']) ?> · <?= e($item['building']) ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
