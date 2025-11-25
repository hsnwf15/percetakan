<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Login</div>
            <div class="card-body">
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