<h3><?= $customer["id"] ? "Edit Pelanggan" : "Tambah Pelanggan" ?></h3>

<form method="post" action="<?= h(
    url($customer["id"] ? "customers/update" : "customers/store"),
) ?>">
  <?php csrf_field(); ?>
  <?php if ($customer["id"]): ?>
    <input type="hidden" name="id" value="<?= h($customer["id"]) ?>">
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nama</label>
      <input class="form-control" name="name" required value="<?= h(
          $customer["name"],
      ) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Telepon</label>
      <input class="form-control" name="phone" value="<?= h(
          $customer["phone"],
      ) ?>">
    </div>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary"><?= $customer["id"]
        ? "Simpan Perubahan"
        : "Simpan" ?></button>
    <a href="<?= h(
        url("customers/index"),
    ) ?>" class="btn btn-secondary">Batal</a>
  </div>
</form>
