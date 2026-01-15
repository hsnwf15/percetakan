<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body text-center pt-4">
                <img src="<?= h(BASE_URL . '/assets/images/logo.png') ?>" alt="Brother Print Logo" class="mb-4" style="max-width: 150px;">
                <h3 class="mb-4">Login</h3>
                <form method="post" action="<?= h(url("auth/login")) ?>">
                    <?php csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</div>