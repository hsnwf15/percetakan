# Daftar Rencana Laporan (Reporting)

## ✅ Report 1 — Laporan Pemasukan Bulanan
**Tujuan:** Mengetahui pemasukan usaha

**Isi:**
* Total pemasukan
* Daftar transaksi
* Grafik pemasukan

**Sumber data:** `payments`
> ➡️ 1 report berdiri sendiri

---

## ✅ Report 2 — Laporan Order Bulanan
**Tujuan:** Mengetahui aktivitas produksi

**Isi:**
* Jumlah order
* Status order
* Grafik distribusi status

**Sumber data:** `orders`
> ➡️ 1 report berdiri sendiri

---

## ✅ Report 3 — Laporan Fee Desainer
**Tujuan:** Mengetahui biaya SDM (desain)

**Isi:**
* Rekap fee per desainer
* Rekap mingguan / bulanan
* Grafik batang fee

**Sumber data:** `orders` (kolom `designer_fee`)
> ➡️ 1 report berdiri sendiri

---

## ✅ Report 4 — Laporan Kinerja Desainer
**Tujuan:** Evaluasi performa SDM

**Isi:**
* Jumlah order per desainer
* Rata-rata fee
* Perbandingan antar desainer

**Sumber data:** `orders` + `users` (Join Table)
> ➡️ 1 report berdiri sendiri

---

## ✅ Report 5 — Laporan Revisi Desain
**Tujuan:** Seberapa sering terjadi revisi, Order mana yang paling banyak revisi dan Dampak revisi terhadap waktu dan beban kerja desainer

**Isi:**
* Total revisi dalam periode tertentu
* Rata-rata revisi per order
* Daftar order dengan revisi terbanyak
* Grafik jumlah revisi per desainer

**Sumber data:** `design_revisions`, `orders`, `users`
> ➡️ 1 report berdiri sendiri