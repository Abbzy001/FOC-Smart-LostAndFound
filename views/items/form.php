<?php $title = 'Report an item'; ?>
<section class="card form-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h2 mb-2">Report an item</h1>
        <p class="text-secondary mb-4">Avoid sharing personal contact details or private identifying information in the public description.</p>
        <form method="post" action="/items/new">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="item_type">Report type</label>
                <select class="form-select" id="item_type" name="item_type" required>
                    <option value="LOST">I lost an item</option>
                    <option value="FOUND">I found an item</option>
                </select>
            </div>
            <div class="mb-3"><label class="form-label" for="title">Item title</label><input class="form-control" id="title" name="title" maxlength="120" required></div>
            <div class="mb-3"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" minlength="10" maxlength="5000" required></textarea></div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="category_id">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required><option value="">Choose one</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="venue_id">Venue</label>
                    <select class="form-select" id="venue_id" name="venue_id" required><option value="">Choose one</option><?php foreach ($venues as $venue): ?><option value="<?= (int) $venue['id'] ?>"><?= e($venue['name']) ?> — <?= e($venue['building']) ?></option><?php endforeach; ?></select>
                </div>
            </div>
            <div class="mb-3"><label class="form-label" for="event_date">Date lost or found</label><input class="form-control" type="date" id="event_date" name="event_date" max="<?= e(date('Y-m-d')) ?>" required></div>
            <fieldset id="challenge-fields" class="border rounded-3 p-3 mb-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold">Found-item verification (required for found items)</legend>
                <div class="mb-3"><label class="form-label" for="challenge_question">Hidden-feature question</label><input class="form-control" id="challenge_question" name="challenge_question" maxlength="255" placeholder="What is a detail only the owner would know?"></div>
                <div><label class="form-label" for="challenge_answer">Correct answer (kept private)</label><input class="form-control" id="challenge_answer" name="challenge_answer" maxlength="255" autocomplete="off"></div>
            </fieldset>
            <button class="btn btn-primary w-100" type="submit">Publish report</button>
        </form>
    </div>
</section>
<script>
(() => {
    const type = document.getElementById('item_type');
    const fields = document.getElementById('challenge-fields');
    const question = document.getElementById('challenge_question');
    const answer = document.getElementById('challenge_answer');
    const update = () => {
        const found = type.value === 'FOUND';
        fields.hidden = !found;
        question.required = found;
        answer.required = found;
    };
    type.addEventListener('change', update);
    update();
})();
</script>
