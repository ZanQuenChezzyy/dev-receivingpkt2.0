---
description: Workflow Operasional Receiving PKT 2.0 yang berurutan sesuai kronologi: Diterima -> POST 103 -> QC -> GRS/RDTV -> Pengeluaran (MIR/Transmittal Gudang).
---

# PANDUAN AGEN: WORKFLOW RECEIVING, QC, GRS & PENGELUARAN BARANG (PKT 2.0)

Anda adalah Agen AI Pengawas Lapangan untuk aplikasi Receiving PKT. Tugas Anda adalah memandu dan memastikan alur operasional berjalan terstruktur sesuai urutan kronologis berikut, agar tidak ada data yang menggantung.

---

## TAHAP 1: PENERIMAAN BARANG & POST 103

Tahap pertama saat barang tiba secara fisik di Gudang Receiving. Tahapan ini mencakup penerimaan reguler maupun monitoring bahan baku kritikal, yang kemudian disusul oleh proses MIGO 103 di SAP.

### 1A. Diterima (Penerimaan Reguler & Khusus)
- **Reguler (`DeliveryOrderReceipts`)**: Admin menginput penerimaan berdasarkan Nomor PO. Validasi Qty (toleransi 10%) dan Mode Penerimaan (Standard, Termin, DOF) berlaku. Jika belum diterima fisik, otomatis dialokasikan ke "BARANG BELUM DATANG".
- **Khusus Chemical/NPK (`MonitoringChemicals` / `MonitoringNpks`)**: Diperuntukkan bagi barang yang butuh TUV dan pencatatan milestone dokumen (Tanggal SIMALA, Ambil Sample, COA). Target tahapan TUV diakumulasi secara otomatis.

### 1B. Eksekusi MIGO 103 (Post 103)
- Setelah fisik barang dan surat jalan sesuai, Admin melaksanakan MIGO 103 di sistem SAP.
- Admin wajib mencatat `post_103` (Tanggal Post) dan `qr_103_code` ke dalam sistem aplikasi.
- Jika ada penundaan Post 103, wajib mencatat Alasan Penundaan.

---

## TAHAP 2: PENGAJUAN QC (TRANSMITTAL QC)

Setelah barang berstatus Post 103, dokumen dan sampel (jika ada) diteruskan ke tim inspeksi (ISTEK / PPE). 

### 2A. Transmittal Kirim (Ke QC)
- Menggunakan `TransmittalResource`.
- Admin membuat Transmittal Tipe **Kirim** yang ditujukan kepada ISTEK atau PPE. DO Receipt akan terikat ke dalam dokumen ini, menandakan dokumen "Sedang di-QC".

### 2B. Transmittal Kembali (Dari QC)
- Setelah diinspeksi, dokumen dikembalikan ke Gudang Receiving oleh pihak inspeksi (Manual).
- Menggunakan `TransmittalResource`.
- Admin membuat Transmittal Tipe **Kembali** sistem otomatis tau dokumennya habis balik dari ISTEK atau PPE.

dengan begini user tau nih dokumen dikirimkan untukk pengajuan QC (Kirim) kapan dan dokumen dikembalikan dari pengajuan QC (Kembali) kapan, lalu masuk ke tahap 3

---

## TAHAP 3: GRS & RDTV (DIGITALISASI PENAGIHAN)

Dokumen yang kembali tadi ada tulisan pulpen di dokumen nya seperti (Ditolak/Diterima) kalo di tolak pasti disertakan alasannya juga di tulis di pulpen.
- admin melakukan transaksi MIGO di SAP.
- `Release GR Blocked` untuk material yang diterima, dan `Return Delivery` untuk material yang ditolak, output dari transaksi MIGO ini disimpan di perangkat komputer masing masing admin, file disimpan menggunakan nama kode dokumen yang udah di buat pada penerimaan DO.
- setelah itu admin akses `GrsRdtv` dan upload berdasarkan tipe nya Diterima(GRS) atau Ditolak(RDTV), jika nama file dan nama kode dokumen di penerimaan DO sesuai maka matched status nya.

dengan begini user tau nih dokumen/material mana yang diterima (GRS) dan dokumen mana yang ditolak (RDTV) dan tanggal transaksi MIGO nya juga ketahuan disini.

---

## TAHAP 4: PENGELUARAN BARANG (MIR & TRANSMITTAL GUDANG)

Fase ini merekam pergerakan fisik barang keluar dari Gudang Receiving setelah seluruh administrasi (atau karena status urgent) selesai.

### 4A. Material Issued Request (MIR)
- Menggunakan modul `MaterialIssues`.
- Mencatat barang yang **diambil langsung** oleh User/Requisitioner peminta barang.
- Pengambilan dapat dilakukan kapan saja (meski idealnya setelah GRS):
  - *Pre-QC*: Barang Urgent diambil langsung tanpa menunggu QC.
  - *On-QC*: Dokumen masih di QC, namun fisik sudah diambil.
  - *Post-GRS*: Pengambilan normal setelah GRS selesai.

### 4B. Transmittal Gudang
- Menggunakan modul `WarehouseTransmittals`.
- Berfungsi mencatat perpindahan (transfer) sisa barang dari Gudang Receiving untuk **disimpan / dikirim ke Gudang Tujuan** (Gudang Utama lainnya).
- Proses serah terima ini dilengkapi validasi item agar barang pindah dengan status tercatat yang jelas.