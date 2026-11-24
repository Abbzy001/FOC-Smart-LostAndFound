<?php $title = 'My claims'; ?>
<h1 class="h2 mb-2">Claims and handovers</h1>
<p class="text-secondary mb-4">Track claims you submitted and claims on items you found.</p>
<?php if ($claims === []): ?>
    <div class="empty-state p-5 text-center rounded-3"><h2 class="h5">No claims yet</h2><p class="mb-0 text-secondary">Claims submitted on found items and claims awaiting your review will appear here.</p></div>
<?php else: ?>
    <div class="vstack gap-3">
        <?php foreach ($claims as $claim): ?>
            <?php $isFinder = (int) $claim['reporter_id'] === (int) current_user()['id']; ?>
            <article class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div><span class="badge text-bg-secondary"><?= e($claim['status']) ?></span><h2 class="h5 mt-2"><a href="/items/<?= (int) $claim['item_id'] ?>"><?= e($claim['title']) ?></a></h2></div>
                        <span class="text-secondary small"><?= $isFinder ? 'Claimant: ' . e($claim['claimant_name']) : 'Finder: ' . e($claim['finder_name']) ?></span>
                    </div>
                    <p class="mb-3"><?= e($claim['challenge_response']) ?></p>
                    <?php if ($isFinder && $claim['status'] === 'PENDING'): ?>
                        <div class="d-flex gap-2">
                            <?php foreach (['approve' => 'Approve claim', 'reject' => 'Reject'] as $decision => $label): ?>
                                <form method="post" action="/claims/<?= (int) $claim['id'] ?>/review">
                                    <?= csrf_field() ?><input type="hidden" name="decision" value="<?= e($decision) ?>">
                                    <button class="btn <?= $decision === 'approve' ? 'btn-success' : 'btn-outline-danger' ?>" type="submit"><?= e($label) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($isFinder && $claim['status'] === 'APPROVED'): ?>
                        <p class="text-secondary">Claimant contact: <a href="mailto:<?= e($claim['claimant_email']) ?>"><?= e($claim['claimant_email']) ?></a></p>
                        <?php if (isset($_SESSION['handover_pin_notice']) && (int) $_SESSION['handover_pin_notice']['claim_id'] === (int) $claim['id']): ?>
                            <div class="alert alert-info mt-3"><strong>One-time handover PIN: <?= e($_SESSION['handover_pin_notice']['pin']) ?></strong><br>Share it privately with the claimant. It can only be used to confirm this handover.</div>
                            <?php unset($_SESSION['handover_pin_notice']); ?>
                        <?php endif; ?>
                        <form method="post" action="/claims/<?= (int) $claim['id'] ?>/complete" class="row g-2 align-items-end">
                            <?= csrf_field() ?>
                            <div class="col-sm-5"><label class="form-label" for="pin-<?= (int) $claim['id'] ?>">Verify handover PIN</label><input class="form-control" id="pin-<?= (int) $claim['id'] ?>" name="handover_pin" inputmode="numeric" pattern="\d{4}" maxlength="4" required></div>
                            <div class="col-sm-auto"><button class="btn btn-primary" type="submit">Confirm handover</button></div>
                        </form>
                    <?php endif; ?>
                    <?php if (!$isFinder && $claim['status'] === 'APPROVED'): ?>
                        <p class="text-success mb-0">Your claim is approved. Contact the finder at <a href="mailto:<?= e($claim['finder_email']) ?>"><?= e($claim['finder_email']) ?></a> to receive the handover PIN and arrange collection.</p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
