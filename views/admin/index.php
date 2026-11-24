<?php $title = 'Moderation'; ?>
<h1 class="h2 mb-2">Community moderation</h1>
<p class="text-secondary mb-4">Review reports and restore or suspend listings. Three unique reports automatically suspend a listing.</p>
<?php if ($items === []): ?>
    <div class="empty-state p-5 text-center rounded-3"><h2 class="h5">Nothing needs review</h2><p class="mb-0 text-secondary">Reported listings will appear here.</p></div>
<?php else: ?>
    <div class="vstack gap-3">
        <?php foreach ($items as $item): ?>
            <article class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div><span class="badge text-bg-danger"><?= (int) $item['flags_count'] ?> report(s)</span><span class="badge text-bg-secondary"><?= e($item['status']) ?></span><h2 class="h5 mt-2"><a href="/items/<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a></h2><p><?= e($item['description']) ?></p></div>
                        <form method="post" action="/admin/items/<?= (int) $item['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="<?= $item['status'] === 'SUSPENDED' ? 'restore' : 'suspend' ?>">
                            <button class="btn <?= $item['status'] === 'SUSPENDED' ? 'btn-success' : 'btn-outline-danger' ?>" type="submit"><?= $item['status'] === 'SUSPENDED' ? 'Restore listing' : 'Suspend listing' ?></button>
                        </form>
                    </div>
                    <?php foreach ($item['flags'] as $flag): ?>
                        <div class="border-top pt-2 mt-2 small"><strong><?= e($flag['full_name']) ?>:</strong> <?= e($flag['reason']) ?></div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
