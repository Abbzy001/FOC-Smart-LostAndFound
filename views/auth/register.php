<?php $title = 'Create account'; ?>
<section class="card form-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h2 mb-2">Create your account</h1>
        <p class="text-secondary mb-4">For Faculty of Computing students with a valid registration number.</p>
        <form method="post" action="/register">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="full_name">Full name</label>
                <input class="form-control" id="full_name" name="full_name" maxlength="120" autocomplete="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="matric_number">Registration number</label>
                <input class="form-control" id="matric_number" name="matric_number" placeholder="CST/24/COM/12345" pattern="CST/\d{2}/(COM|SWE|IFT|CBS)/\d{5}" maxlength="20" required>
                <div class="form-text">Accepted departments: COM, SWE, IFT, CBS.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="email" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="10" maxlength="200" autocomplete="new-password" required>
                </div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Create account</button>
        </form>
        <p class="mt-3 mb-0 text-center">Already registered? <a href="/login">Sign in</a>.</p>
    </div>
</section>
