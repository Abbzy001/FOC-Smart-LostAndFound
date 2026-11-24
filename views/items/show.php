<?php $title = $item['title']; ?>
<div class="row g-4">
    <div class="col-lg-8">
        <article class="card detail-card border-0 shadow-sm">
            <div class="card-body p-4">
                <span class="badge <?= $item['item_type'] === 'FOUND' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($item['item_type']) ?></span>
                <span class="badge text-bg-secondary"><?= e($item['status']) ?></span>
                <h1 class="h2 mt-3"><?= e($item['title']) ?></h1>
                <p class="text-secondary"><?= e($item['category']) ?> · <?= e($item['venue']) ?> · <?= e($item['building']) ?></p>
                <p class="item-description"><?= nl2br(e($item['description'])) ?></p>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Date reported lost/found</dt><dd class="col-sm-8"><?= e($item['event_date']) ?></dd>
                    <dt class="col-sm-4">Reported by</dt><dd class="col-sm-8"><?= e($item['reporter_name']) ?></dd>
                </dl>
                <?php if ($item['item_type'] === 'FOUND' && !$ownsItem && $item['status'] === 'ACTIVE'): ?>
                    <hr><h2 class="h5">Confirm you own this item</h2>
                    <p class="text-secondary">Answer the private detail question. Your answer is checked privately and shared with the finder only as part of your claim.</p>
                    <?php if ($existingClaim !== null): ?>
                        <p>You have already submitted a claim for this item (<?= e($existingClaim['status']) ?>).</p>
                    <?php else: ?>
                        <form method="post" action="/items/<?= (int) $item['id'] ?>/claims">
                            <?= csrf_field() ?>
                            <p class="fw-semibold"><?= e($item['challenge_question']) ?></p>
                            <div class="mb-3"><label class="form-label" for="challenge_answer">Your answer</label><input class="form-control" id="challenge_answer" name="challenge_answer" maxlength="255" required></div>
                            <div class="mb-3"><label class="form-label" for="challenge_response">Tell the finder why this is yours</label><textarea class="form-control" id="challenge_response" name="challenge_response" rows="3" minlength="5" maxlength="500" required></textarea></div>
                            <button class="btn btn-primary" type="submit">Submit claim</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if (current_user() !== null && !$ownsItem && $item['status'] !== 'RESOLVED'): ?>
                <div class="card-footer bg-white p-3">
                    <form method="post" action="/items/<?= (int) $item['id'] ?>/flags" class="d-flex flex-column flex-md-row gap-2">
                        <?= csrf_field() ?>
                        <label class="visually-hidden" for="reason">Reason for reporting listing</label>
                        <input class="form-control" id="reason" name="reason" placeholder="Reason to report this listing" maxlength="255" required>
                        <button class="btn btn-outline-danger text-nowrap" type="submit">Report listing</button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
    </div>
    <aside class="col-lg-4">
        <?php if ($matches !== []): ?>
            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Possible matches</h2>
                    <p class="text-secondary small">Ranked by category, location, date and description similarity.</p>
                    <?php foreach ($matches as $match): ?>
                        <div class="border-top py-3">
                            <a class="fw-semibold" href="/items/<?= (int) $match['id'] ?>"><?= e($match['title']) ?></a>
                            <div class="small text-secondary"><?= e($match['item_type']) ?> · <?= e($match['venue']) ?> · <?= e($match['event_date']) ?></div>
                            <span class="badge text-bg-primary mt-1"><?= e($match['match_score']) ?>% match</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5">Matching</h2><p class="text-secondary mb-0">No likely matching listings are available yet.</p></div></div>
        <?php endif; ?>
    </aside>
</div>
