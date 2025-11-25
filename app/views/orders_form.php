<h3>Order Baru</h3>
<form method="post" action="<?= h(url("orders/store")) ?>">
    <?php csrf_field(); ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama Pelanggan</label>
            <input class="form-control"
                name="customer_name"
                id="customer_name"
                list="pelangganList"
                placeholder="Ketik nama, pilih jika sudah ada">
            <small class="text-muted">
                Jika nama belum ada, ketik saja—akan dibuat otomatis.
            </small>
            <input type="hidden" name="customer_id" id="customer_id">
            <datalist id="pelangganList">
                <?php foreach ($customers as $name => $c): ?>
                    <option
                        value="<?= h($name) ?>"
                        data-id="<?= h($c['id']) ?>"
                        data-phones="<?= h(implode(',', $c['phones'])) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nomor Telepon</label>
            <input class="form-control"
                type="text"
                name="customer_phone"
                id="customer_phone"
                list="phoneList"
                placeholder="08xxxxxxxxxx">

            <!-- IMPORTANT: datalist nomor dikosongin. Akan diisi lewat JS -->
            <datalist id="phoneList"></datalist>
        </div>
    </div>
    <hr>
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Produk</label>
            <input class="form-control" name="product" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Qty</label>
            <input class="form-control" type="number" name="quantity" value="1" min="1">
        </div>
    </div>
    <br>
    <div class="row g-3">
        <div class="col-md-9">
            <label class="form-label">Spesifikasi</label>
            <textarea class="form-control" name="spec" rows="3" placeholder="Ukuran, bahan, finishing"></textarea>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-3">
            <label class="form-label">Channel</label>
            <select class="form-select" name="channel">
                <option value="whatsapp">WhatsApp</option>
                <option value="walkin">Walk-in</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Deadline</label>
            <input class="form-control" type="datetime-local" name="deadline">
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-3">
            <label class="form-label">DP (Rp)</label>
            <input class="form-control" type="number" step="0.01" name="dp_amount" value="0">
        </div>
        <div class="col-md-4">
            <label class="form-label">Total Harga (Rp)</label>
            <input class="form-control" type="number" name="total_price" min="0" step="100" placeholder="contoh: 150000">
        </div>
    </div>
    <hr>
    <div class="col-md-6">
        <label class="form-label">Assign Desainer</label>
        <select class="form-select" name="assigned_designer">
            <option value="">- Belum -</option>
            <?php foreach ($designers as $d): ?>
                <option value="<?= h($d["id"]) ?>"><?= h($d["name"]) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Catatan</label>
        <input class="form-control" name="notes">
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Simpan</button>
        <a href="<?= h(url("orders/index")) ?>" class="btn btn-secondary">Batal</a>
    </div>
    <br>
</form>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('customer_name');
        const phoneInput = document.getElementById('customer_phone');
        const idInput = document.getElementById('customer_id');
        const pelangganList = document.getElementById('pelangganList');
        const phoneList = document.getElementById('phoneList');

        function updatePhonesForName() {
            const val = nameInput.value.trim();
            let matched = null;

            // cari option di datalist nama yang persis sama dengan teks input
            for (const opt of pelangganList.options) {
                if (opt.value === val) {
                    matched = opt;
                    break;
                }
            }

            // bersihkan semua opsi phoneList
            phoneList.innerHTML = '';

            if (matched) {
                // pelanggan lama
                idInput.value = matched.dataset.id || '';

                const phonesStr = matched.dataset.phones || '';
                const phones = phonesStr
                    .split(',')
                    .map(p => p.trim())
                    .filter(p => p !== '');

                const uniquePhones = [...new Set(phones)];

                uniquePhones.forEach(ph => {
                    const opt = document.createElement('option');
                    opt.value = ph;
                    phoneList.appendChild(opt);
                });

                // kalau cuma 1 nomor → auto-fill
                if (uniquePhones.length === 1) {
                    phoneInput.value = uniquePhones[0];
                }
            } else {
                // nama tidak ada di list → pelanggan baru
                idInput.value = '';
                // biarkan phoneInput user isi sendiri
                phoneInput.value = '';
            }
        }

        // Trigger di berbagai event supaya lebih “nurut”
        nameInput.addEventListener('input', updatePhonesForName);
        nameInput.addEventListener('change', updatePhonesForName);
        nameInput.addEventListener('blur', updatePhonesForName);

        // kalau form dipakai untuk edit dan sudah ada value awal
        updatePhonesForName();
    });
</script>