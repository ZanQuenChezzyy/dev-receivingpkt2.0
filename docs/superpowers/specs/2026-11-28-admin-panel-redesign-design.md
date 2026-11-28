# Desain Perombakan UI Admin Panel Filament v5 — Receiving PKT v2.0

**Tanggal:** 2026-11-28
**Target:** Seluruh Admin Panel Filament (`/admin`) pada `dev-receivingpkt2.0`
**Referensi Visual:** Gaya minimalist tactile (Ron Design / SugarCRM) yang dipadukan dengan identitas warna **PT Pupuk Kalimantan Timur (Oranye `#F47920` & Biru `#0054A6`)**.

---

## 1. Tujuan & Prinsip Desain
Mengubah tampilan seluruh Admin Panel Filament agar terasa lebih modern, bersih, lega, dan terstruktur tanpa mengubah logika bisnis, hak akses Spatie/Shield, maupun kestabilan fitur yang sudah berjalan:
- **Ruang kerja lebih lega**: Meniadakan sidebar teks lebar di desktop dengan menggantinya ke **Top Navigation + Mega Menu Terstruktur** dan **Rail Ikon Melingkar Tipis** di sisi kiri.
- **Identitas PKT tetap kuat**: Mempertahankan **Oranye PKT (`#F47920`)** dan **Biru PKT (`#0054A6`)** di atas kanvas abu-perak lembut (`#E6EAEF`) dengan kartu putih bersih bersudut besar (`24px–28px`) dan tinta kontras tinggi (`#10141A`).
- **Konsisten di Light Mode & Dark Mode**: Mode terang menonjolkan permukaan putih bersih di atas kanvas `#E6EAEF`; mode gelap memakai permukaan charcoal modern berbasis `#10141A` / `#161C24`.
- **Anti-regresi**: Seluruh guard responsif, dropdown tabel (`viewport-fixed`), tab vertikal/horizontal, dan navigasi mobile (`< lg`) tetap dijaga penuh.

### Non-Tujuan (Di Luar Lingkup)
- Tidak mengubah alur kerja bisnis, validasi form, query database, ataupun halaman publik/portal tracking vendor di luar `/admin`.
- Tidak mengubah halaman Login (`AuthDesignerPlugin`) secara struktural selain penyelarasan font dan palet dasar.

---

## 2. Sistem Visual (Warna, Tipografi, & Geometri)

### 2.1 Palet Warna
| Peran | Light Mode | Dark Mode | Penggunaan |
| :--- | :--- | :--- | :--- |
| **Canvas Dasar** | `#E6EAEF` (dengan semburat lembut `#0054A6` 6% & `#F47920` 5%) | `#0D1117` (dengan semburat lembut `#0054A6` 14% & `#F47920` 8%) | Latar belakang utama halaman |
| **Permukaan Kartu Utama** | `#FFFFFF` | `#151B24` | Tabel, widget, section, mega menu, modal |
| **Permukaan Sekunder / Nested** | `#F4F6F9` | `#1C232F` | Header tabel, sub-kartu, item hover, input |
| **Tinta Utama (Ink)** | `#10141A` | `#F3F5F8` | Judul halaman, teks utama, pill navigasi aktif (opsional kontras) |
| **Primary Brand (PKT Orange)** | `#F47920` | `#F47920` | Tombol aksi utama, pill aktif, indikator fokus, progress bar |
| **Secondary Brand (PKT Blue)** | `#0054A6` / `#83A2DB` | `#3B82F6` / `#83A2DB` | Badge informasi, aksen kategori mega menu, tautan & ikon sekunder |
| **Danger / Alert (Coral)** | `#CE6969` / `#DC4C4C` | `#E07A7A` | Titik notifikasi, status penolakan/RDTV, aksi hapus |
| **Border Halus** | `rgba(16, 20, 26, 0.07)` | `rgba(255, 255, 255, 0.08)` | Garis pemisah tipis dan kontur kartu |

### 2.2 Tipografi
- **Font Utama**: **Plus Jakarta Sans** (geometris modern yang paling mendekati font *Lufga* pada referensi, dimuat gratis via Google Fonts melalui konfigurasi `->font('Plus Jakarta Sans')` di `AdminPanelProvider`).
- **Judul Halaman (`.fi-header-heading`)**: `1.75rem` (28px), `font-weight: 700`, `letter-spacing: -0.025em`.
- **Kode / Angka (PO, GRS, DO, Rupiah)**: tetap menggunakan font monospace tabular agar rapi saat dibandingkan di tabel.

### 2.3 Geometri & Elevasi
- **Kartu Utama / Tabel / Modal**: `border-radius: 1.5rem` (`24px`), bayangan lembut menyebar (`0 10px 30px -12px rgba(16, 20, 26, 0.08)`).
- **Sub-kartu / Section di dalam Form**: `border-radius: 1.125rem` (`18px`).
- **Tombol Utama, Tab Pill, Status Badge, Item Topbar**: `border-radius: 9999px` (pill penuh) atau `14px` untuk input teks/select.
- **Tombol Ikon Header & Rail Kiri**: Lingkaran sempurna (`40px × 40px` di topbar, `42px × 42px` di rail kiri).

---

## 3. Arsitektur Navigasi: Topbar Pill + Mega Menu Terstruktur + Rail Ikon Kiri

```text
┌─────────────────────────────────────────────────────────────────────────────────┐
│ [Logo PKT]   Dashboard  Penerimaan ▾  [Invoice ▾]  Gudang ▾  Laporan ▾  Master ▾│
│                                                           [🔍] [ID] [🔔•] [👤] │
├──────┬──────────────────────────────────────────────────────────────────────────┤
│ [🏠] │  Judul Halaman                                        [+ Aksi Utama]     │
│ [📊] │  ┌────────────────────────────────────────────────────────────────────┐  │
│ [📦] │  │  Konten Utama (Tabel / Form / Widget) dalam kartu putih 24px       │  │
│ [📄] │  │  Lebar penuh, header bersih, badge pill, aksi rapi                 │  │
│ [⬆️] │  └────────────────────────────────────────────────────────────────────┘  │
│ [🧾] │                                                                          │
│ [📷] │                                                                          │
│ [⏱️] │                                                                          │
│      │                                                                          │
│ ┌──┐ │                                                                          │
│ │🌙│ │                                                                          │
│ │☀️│ │                                                                          │
│ └──┘ │                                                                          │
└──────┴──────────────────────────────────────────────────────────────────────────┘
```

### 3.1 Struktur 6 Kategori Topbar & Mega Menu
Seluruh item diambil secara dinamis dari `filament()->getNavigation()` sehingga **hak akses per role, badge hitungan otomatis, status aktif, dan terjemahan bahasa tetap 100% mengikuti sistem yang ada**:

1. **Dashboard** — Tautan langsung ke Halaman Utama.
2. **Penerimaan ▾** *(Mega Menu 2 Kolom)*:
   - *Kolom 1 — Penerimaan Receiving*: Penerimaan DO, Detail Penerimaan DO, Monitoring Material, Monitoring Chemical, Monitoring NPK.
   - *Kolom 2 — Transaksi QC & GRS*: GRS & RDTV, Pengajuan QC.
3. **Invoice ▾** *(Mega Menu 1 Kolom Lebar / Grid 2 Kolom Kartu)*:
   - *Penerimaan Jasa / Invoice*: Invoice Barang, Upload Gabungan GRS/RDTV, Transmittal Kasir, Monitoring Karung, Template Email GRS/RDTV.
4. **Gudang ▾** *(Mega Menu 2 Kolom)*:
   - *Kolom 1 — Pengeluaran & Pengiriman*: Material Issue (MIR), Pengiriman Gudang (Scan Transmittal Gudang, Transmittal Gudang, Master MB51, Antrean Pengiriman).
   - *Kolom 2 — Monitoring Receiving*: Aging Material Non-Stock & PD, Unit Peminta & PIC.
5. **Laporan ▾** *(Mega Menu 1 Kolom)*:
   - *Laporan*: Laporan Bulanan, Laporan Material Diambil.
6. **Master & Sistem ▾** *(Mega Menu 2 Kolom)*:
   - *Kolom 1 — Data Master*: Purchase Order Terbit, Data Vendor, Gudang Tujuan, Lokasi Receiving, Aturan PIC Material, QC Karung.
   - *Kolom 2 — Kelola Pengguna*: Pengguna, Akses dan Perizinan.

> **Catatan izin & responsif**:
> - Kategori atau kolom yang tidak memiliki item yang boleh diakses oleh role user otomatis disembunyikan.
> - Pill kategori yang sedang aktif ditampilkan sebagai **pill solid Oranye PKT (`#F47920`)** dengan teks putih dan bayangan lembut (atau aksen garis Biru PKT `#0054A6` pada item submenu aktif di dalam Mega Menu).
> - Mega Menu terbuka mulus saat diklik (dan menutup otomatis saat klik di luar, menekan `Esc`, atau berpindah halaman via Livewire SPA).
> - Pada layar tablet/mobile (`< 1024px`), tombol hamburger membuka drawer navigasi native Filament agar seluruh menu tetap nyaman diakses di HP.

### 3.2 Floating Left Icon Rail (Desktop `lg+`)
Bar vertikal tipis (`64px`) mengambang di sisi kiri layar (selaras dengan gambar referensi):
- **Shortcut 1-klik** (dinamis sesuai hak akses user): Beranda, Dashboard, Penerimaan DO, GRS & RDTV, Upload Gabungan GRS/RDTV, Invoice Barang, Scan Transmittal Gudang, Aging Material Non-Stock & PD.
- Tiap tombol berbentuk lingkaran `42px × 42px` putih bersih (mode gelap: `#151B24`), menampilkan tooltip nama menu saat hover, dan berubah ke warna **Oranye PKT (`#F47920`)** saat halaman tersebut aktif.
- **Switcher Light/Dark Vertikal** di bagian bawah rail kiri: kapsul vertikal berisi 2 lingkaran (Bulan & Matahari) yang terhubung ke event `theme-changed` Filament & cookie `theme`, persis seperti pojok kiri bawah pada gambar referensi.

### 3.3 Top-Right Utility Cluster
Seluruh utilitas kanan atas ditata sebagai **tombol lingkaran putih (`40px × 40px`)** sejajar rapi:
- Tombol **Pencarian Global** (`🔍`)
- **AI System Toggle** (khusus role `Developer`)
- **Language Switcher** (`ID` / `EN`)
- **Notifikasi Database** (`🔔` dengan titik badge coral `#CE6969`)
- **Avatar User** melingkar di ujung kanan (posisi `UserMenuPosition::Topbar`).

---

## 4. Pendekatan Implementasi Teknis (Upgrade-Safe Filament v5)

Pilihan arsitektur yang dipilih: **Custom Topbar Livewire Component + Render Hook Left Rail + Theme CSS v2**, tanpa memodifikasi file di dalam folder `vendor/`.

| Komponen | File yang Dibuat / Diubah | Peran |
| :--- | :--- | :--- |
| Konfigurasi Panel | `app/Providers/Filament/AdminPanelProvider.php` | Aktifkan `->topNavigation()`, `->font('Plus Jakarta Sans')`, `->userMenu(UserMenuPosition::Topbar)`, daftarkan `->topbarLivewireComponent(MegaMenuTopbar::class)`, daftarkan render hook `LAYOUT_START` untuk Left Icon Rail, perbarui palet `primary` (`#F47920`), `info` (`#0054A6`), `danger` (`#CE6969`). |
| Topbar & Mega Menu | `app/Filament/Livewire/MegaMenuTopbar.php` + `resources/views/filament/livewire/mega-menu-topbar.blade.php` | Menggantikan bar dropdown datar bawaan Filament menjadi 6 pill kategori + panel Mega Menu multi-kolom terstruktur berbasis `filament()->getNavigation()`. |
| Left Floating Icon Rail | `resources/views/filament/components/left-icon-rail.blade.php` | Rail ikon bulat cepat di sisi kiri + kapsul toggle Light/Dark vertikal di bagian bawah. |
| Topbar Utilities | `resources/views/filament/components/topbar-actions.blade.php` | Menyelaraskan AI toggle & language switcher menjadi tombol bulat senada (toggle tema dipindah ke rail kiri di desktop, tetap tersedia di mobile). |
| Tema CSS Global | `resources/css/filament/admin/theme.css` | Perombakan menyeluruh token warna, kanvas `#E6EAEF`/`#0D1117`, kartu `24px`, tombol pill, tabel bersih, form input, modal, badge, dan mempertahankan seluruh guard `ResponsiveUiGuardTest`. |
| Pengujian | `tests/Feature/AdminPanelRedesignTest.php` + `tests/Feature/ResponsiveUiGuardTest.php` | Memastikan panel render normal, pengelompokan Mega Menu sesuai hak akses, guard dropdown/tab tidak regresi, dan build Vite valid. |

---

## 5. Verifikasi Keberhasilan
1. `npm run build` berhasil tanpa error dan menghasilkan manifest tema terbaru.
2. Seluruh test suite UI (`ResponsiveUiGuardTest`, `AdminPanelRedesignTest`, `TabsComponentTest`, `FrontendButtonLoadingTest`, `ScanWarehouseTransmittalTest`) lulus 100%.
3. Verifikasi visual & fungsional pada halaman utama: Dashboard, Invoice Barang, Upload Gabungan GRS/RDTV, Scan Transmittal Gudang, dan Aging Material pada mode Terang & Gelap serta ukuran layar desktop (`1366px`, `1920px`) dan mobile.
