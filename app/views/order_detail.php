<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Detail Order #<?= h($order["id"]) ?></h3>
    <div>
        <a href="<?= h(
                        url("orders/index"),
                    ) ?>" class="btn btn-sm btn-secondary">← Kembali</a>
    </div>
</div>

<ul class="nav nav-pills mb-3" id="tabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-info" type="button">Info</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-design" type="button">Design</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-vendor" type="button">Vendor</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-finance" type="button">Finance</button></li>
</ul>

<div class="tab-content">
    <!-- ===== TAB INFO ===== -->
    <div class="tab-pane fade show active" id="tab-info">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div><b>Pelanggan:</b> <?= h(
                                                    $order["customer_name"] ?? "-",
                                                ) ?></div>
                        <div><b>Produk:</b> <?= h($order["product"]) ?></div>
                        <div><b>Qty:</b> <?= h($order["quantity"]) ?></div>
                        <div><b>Channel:</b> <?= h($order["channel"]) ?></div>
                        <div><b>Deadline:</b> <?= h(date("d-m-Y H:i", strtotime($order["deadline"]))) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div><b>Assign Desainer:</b> <?= h(
                                                            $order["designer_name"] ?? "-",
                                                        ) ?></div>
                        <div>
                            <b>Status:</b>
                            <span class="badge bg-<?= status_badge_class(
                                                        $order["status"],
                                                    ) ?>"><?= h($order["status"]) ?></span>
                        </div>
                        <form class="mt-2" method="post" action="<?= h(
                                                                        url("orders/updateStatus"),
                                                                    ) ?>">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="id" value="<?= h($order["id"]) ?>">
                            <div class="input-group">
                                <select name="status" class="form-select">
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?= $s ?>" <?= $order["status"] === $s
                                                                        ? "selected"
                                                                        : "" ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary">Ubah Status</button>
                            </div>
                        </form>
                        <div class="form-text">Alur: admin → design → vendor → ready → picked</div>
                    </div>
                </div>

                <div class="mt-3">
                    <b>Spesifikasi:</b><br>
                    <?= nl2br(h($order["spec"])) ?>
                </div>
                <?php if (!empty($order["notes"])): ?>
                    <div class="mt-2"><b>Catatan:</b> <?= h($order["notes"]) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== TAB DESIGN ===== -->
    <div class="tab-pane fade" id="tab-design">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span>Riwayat Revisi</span>
                        <?php if (!empty($approval)): ?>
                            <span class="badge bg-success">Final approved (Rev-<?= h(
                                                                                    $approval["approved_rev"],
                                                                                ) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($revisions)): ?>
                            <div class="p-3 text-muted">Belum ada revisi yang diunggah.</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($revisions as $rv): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div><b>Revisi ke-<?= h($rv["rev_no"]) ?></b> · <?= h(date("d-m-Y H:i", strtotime($rv["created_at"]))) ?></div>
                                            <div class="small text-muted">Uploader: <?= h(
                                                                                        $rv["uploader"] ?? "-",
                                                                                    ) ?></div>
                                            <?php if (!empty($rv["note"])): ?>
                                                <div class="small">Catatan: <?= h($rv["note"]) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ms-3">
                                            <a class="btn btn-sm btn-outline-secondary" href="<?= h(
                                                                                                    $rv["file_path"],
                                                                                                ) ?>" target="_blank">Lihat</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Upload Revisi Baru</div>
                    <div class="card-body">
                        <form method="post" action="<?= h(
                                                        url("rev/upload"),
                                                    ) ?>" enctype="multipart/form-data">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?= h($order["id"]) ?>">
                            <div class="mb-3">
                                <label class="form-label">File desain</label>
                                <input class="form-control" type="file" name="file" required
                                    accept=".pdf,.ai,.eps,.svg,.cdr,.jpg,.jpeg,.png,.psd">
                                <div class="form-text">Format: PDF/AI/EPS/SVG/CDR/JPG/PNG/PSD. Maks 30 MB (sesuaikan php.ini).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan (opsional)</label>
                                <input class="form-control" name="note" maxlength="200" placeholder="Misal: ganti warna, koreksi teks">
                            </div>
                            <button class="btn btn-primary">Upload Revisi</button>
                        </form>

                        <hr>
                        <form method="post" action="<?= h(
                                                        url("rev/approveFinal"),
                                                    ) ?>" onsubmit="return confirm('Setujui final? Status akan pindah ke vendor.')">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?= h($order["id"]) ?>">
                            <button class="btn btn-success w-100"
                                <?= empty($revisions)
                                    ? 'disabled title="Belum ada revisi"'
                                    : "" ?>
                                <?= !empty($approval)
                                    ? 'disabled title="Sudah di-approve"'
                                    : "" ?>>Setujui Final & Lanjut ke Vendor</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- ===== TAB VENDOR ===== -->
    <div class="tab-pane fade" id="tab-vendor">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Status ke Mitra</div>
                    <div class="card-body">
                        <form method="post" action="<?= h(url("vendor/save")) ?>">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?= h($order["id"]) ?>">

                            <div class="mb-3">
                                <label class="form-label">Pilih Mitra</label>
                                <?php $vendorSel = $vendorJob["vendor_code"] ?? ""; ?>
                                <select class="form-select" name="vendor_code" required>
                                    <?php foreach (
                                        ["Mitra A", "Mitra B", "Mitra C", "Lainnya"]
                                        as $v
                                    ): ?>
                                        <option value="<?= $v ?>" <?= $vendorSel === $v
                                                                        ? "selected"
                                                                        : "" ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Nama asli mitra bisa disembunyikan (A/B/C) demi privasi.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status Produksi di Mitra</label>
                                <?php $st = $vendorJob["status"] ?? "sent"; ?>
                                <select class="form-select" name="status">
                                    <option value="sent" <?= $st === "sent"
                                                                ? "selected"
                                                                : "" ?>>Terkirim ke mitra (sent)</option>
                                    <option value="in_progress" <?= $st === "in_progress"
                                                                    ? "selected"
                                                                    : "" ?>>Sedang dikerjakan (in_progress)</option>
                                    <option value="done" <?= $st === "done"
                                                                ? "selected"
                                                                : "" ?>>Selesai di mitra (done)</option>
                                </select>
                                <div class="form-text">Saat memilih “done”, status order otomatis menjadi <b>ready</b>.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catatan/Keterangan</label>
                                <textarea class="form-control" name="return_info" rows="3"
                                    placeholder="Nomor resi/jadwal ambil, masalah produksi, dll"><?= h(
                                                                                                        $vendorJob["return_info"] ?? "",
                                                                                                    ) ?></textarea>
                            </div>

                            <button class="btn btn-primary">Simpan Vendor</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Ringkasan</div>
                    <div class="card-body">
                        <div><b>Mitra:</b> <?= h($vendorJob["vendor_code"] ?? "—") ?></div>
                        <div><b>Status Mitra:</b>
                            <?php
                            $vs = $vendorJob["status"] ?? null;
                            $label =
                                $vs === "done"
                                ? "success"
                                : ($vs === "in_progress"
                                    ? "warning"
                                    : "secondary");
                            ?>
                            <span class="badge bg-<?= $label ?>"><?= h(
                                                                        $vs ?? "belum diset",
                                                                    ) ?></span>
                        </div>
                        <?php if (!empty($vendorJob["sent_at"])): ?>
                            <div><b>Dikirim:</b> <?= h($vendorJob["sent_at"]) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($vendorJob["return_info"])): ?>
                            <div class="mt-2"><b>Catatan:</b><br><?= nl2br(
                                                                        h($vendorJob["return_info"]),
                                                                    ) ?></div>
                        <?php endif; ?>
                        <hr>
                        <div class="text-muted small">
                            Alur umum: <b>design → vendor → ready → picked</b>.<br>
                            Gunakan tab <b>Info</b> untuk memaksa ubah status jika perlu.
                        </div>
                    </div>
                </div>
                <hr>
                <h6 class="mb-2">Riwayat Status Mitra</h6>

                <?php if (!empty($vendorLogs)): ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($vendorLogs as $log): ?>
                            <li class="mb-1">
                                <span class="badge bg-secondary text-uppercase">
                                    <?= h($log["new_status"]) ?>
                                </span>
                                <span class="text-muted">
                                    <?= date("d-m-Y H:i", strtotime($log["changed_at"])) ?>
                                    <?php if (!empty($log["user_name"])): ?>
                                        · oleh <?= h($log["user_name"]) ?>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($log["old_status"])): ?>
                                    <br><span class="text-muted">
                                        dari: <?= h($log["old_status"]) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($log["note"])): ?>
                                    <br><span><?= nl2br(h($log["note"])) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0">
                        Belum ada perubahan status mitra.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== TAB FINANCE (placeholder) ===== -->
    <div class="tab-pane fade" id="tab-finance">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Tambah Pembayaran</div>
                    <div class="card-body">
                        <form method="post" action="<?= h(url("finance/save")) ?>">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?= h($order["id"]) ?>">

                            <div class="mb-3">
                                <label class="form-label">Metode / Jenis</label>
                                <select class="form-select" name="method" required>
                                    <option value="dp">DP</option>
                                    <option value="pelunasan">Pelunasan</option>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                    <!-- tambah opsi lain kalau perlu -->
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nominal (Rp)</label>
                                <input class="form-control" type="number" name="amount" min="0" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal Bayar</label>
                                <input class="form-control" type="datetime-local" name="paid_at"
                                    value="<?= date(
                                                "Y-m-d\TH:i",
                                            ) ?>"> <!-- format input HTML -->
                            </div>

                            <button class="btn btn-primary">Simpan</button>
                        </form>

                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Riwayat Pembayaran</div>
                    <div class="card-body">
                        <?php if ($payments): ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Metode/Jenis</th>
                                        <th>Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td><?= h($p["paid_at"]) ?></td>
                                            <td><?= h($p["method"]) ?></td>
                                            <td>Rp <?= number_format($p["amount"], 0, ",", ".") ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php else: ?>
                            <p class="text-muted">Belum ada pembayaran tercatat.</p>
                        <?php endif; ?>

                        <hr>
                        <p><b>Total Dibayar:</b> Rp <?= number_format(
                                                        $totalPaid,
                                                        0,
                                                        ",",
                                                        ".",
                                                    ) ?></p>
                        <?php if (!empty($order["total_price"])): ?>
                            <p><b>Total Tagihan:</b> Rp <?= number_format(
                                                            $order["total_price"],
                                                            0,
                                                            ",",
                                                            ".",
                                                        ) ?></p>
                            <p><b>Sisa Bayar:</b>
                                <span class="<?= $outstanding > 0
                                                    ? "text-danger"
                                                    : "text-success" ?>">Rp <?= number_format(
                                                                                max(0, $outstanding),
                                                                                0,
                                                                                ",",
                                                                                ".",
                                                                            ) ?></span>
                            </p>
                        <?php endif; ?>


                        <hr>
                        <p><b>Status Pembayaran:</b>
                            <?php $cls =
                                [
                                    "belum" => "secondary",
                                    "dp" => "warning",
                                    "lunas" => "success",
                                ][$order["payment_status"]] ?? "secondary"; ?>
                            <span class="badge bg-<?= $cls ?>"><?= h(
                                                                    strtoupper($order["payment_status"]),
                                                                ) ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                <a class="btn btn-outline-secondary" target="_blank" href="<?= h(
                                                                                url("invoice/show"),
                                                                            ) ?>&id=<?= h($order["id"]) ?>">
                    Preview Nota (HTML)
                </a>
                <a class="btn btn-primary" href="<?= h(url("invoice/pdf")) ?>&id=<?= h(
                                                                                        $order["id"],
                                                                                    ) ?>">
                    Cetak Nota (PDF)
                </a>
            </div>

        </div>
    </div>

</div>