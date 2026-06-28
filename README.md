# Murojaah 📖

Murojaah adalah aplikasi web/mobile-friendly yang dirancang khusus untuk membantu para penghafal Al-Qur'an melakukan hafalan (*tahfidz*) dan pengulangan (*murojaah*) per ayat secara terfokus. 

Aplikasi ini menggunakan konsep **Remote Hafalan Fullscreen**: bebas scroll, satu layar fokus pada satu ayat, dengan tombol berukuran besar untuk memudahkan navigasi satu tangan serta perekaman status kelancaran hafalan secara instan.

---

## 🛠️ Tech Stack

*   **Backend REST API**: Laravel 11 + MySQL/MariaDB database.
*   **Frontend Web**: Nuxt 3 (Nuxt 4 layout style) + Pinia state management + Vanilla CSS design system.
*   **Font Khusus**: *Amiri Quran* (untuk visualisasi huruf Arab Uthmani yang indah dan presisi) & *Inter* (untuk UI modern).

---

## 📂 Struktur Project

```text
murojaah/
├── api/             # Laravel Backend REST API
└── web/             # Nuxt 3 Frontend Client (mobile-first)
```

---

## 🚀 Panduan Setup & Instalasi

### 1. Prasyarat (*Prerequisites*)
*   PHP >= 8.2 & Composer
*   Node.js >= 20 & NPM
*   MySQL atau MariaDB server (bisa menggunakan **DBNgin** atau Docker)

---

### 2. Setup Backend (Laravel API)

1.  Buka terminal, masuk ke direktori `api`:
    ```bash
    cd api
    ```
2.  Install dependencies:
    ```bash
    composer install
    ```
3.  Salin file environtment dan sesuaikan konfigurasi database:
    ```bash
    cp .env.example .env
    ```
    *Buka file `.env` yang baru dibuat dan sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai MySQL/MariaDB lokal Kakak.*
4.  Generate application key:
    ```bash
    php artisan key:generate
    ```
5.  Pastikan service database Kakak sudah aktif, kemudian jalankan migration dan **seeder Quran**:
    ```bash
    php artisan migrate
    # Jalankan seeder untuk mengisi data 114 surat & ~6.236 ayat lengkap dengan terjemahan
    php artisan db:seed
    ```
6.  Jalankan server API Laravel:
    ```bash
    php artisan serve
    ```
    *Secara default API akan berjalan di: `http://localhost:8000`*

---

### 3. Setup Frontend (Nuxt Web)

1.  Buka terminal baru, masuk ke direktori `web`:
    ```bash
    cd web
    ```
2.  Install dependencies:
    ```bash
    npm install
    ```
3.  Jalankan development server Nuxt:
    ```bash
    npm run dev
    ```
    *Web frontend siap dibuka di: `http://localhost:3000`*

---

## 📱 Fitur Utama Remote Fullscreen Page

*   **Fullscreen Mode (100dvh)**: Tanpa scroll vertikal, ideal untuk smartphone portrait.
*   **Hide/Show Teks Ayat**: Teks ayat disembunyikan secara default untuk menguji hafalan. Cukup ketuk area layar untuk menampilkan teks Arab dan terjemahan Indonesia.
*   **Status Hafalan Instan**:
    *   **Lupa**: Menyimpan status `forgot` dan otomatis berlanjut ke ayat berikutnya.
    *   **Ragu**: Menyimpan status `doubtful` dan otomatis berlanjut ke ayat berikutnya.
    *   **Lancar**: Menyimpan status `fluent` dan otomatis berlanjut ke ayat berikutnya.
*   **Haptic feedback**: Menghasilkan sensasi getaran lembut saat Kakak mengetuk tombol navigasi atau status penilaian di mobile browser.
*   **Navigasi Swipe Gesture**:
    *   Geser **Kiri/Kanan** untuk navigasi Ayat Sebelumnya/Berikutnya.
    *   Geser **Atas/Bawah** (Swipe Up/Down) untuk berpindah Surat secara instan.
*   **iOS Bottom Pickers**:
    *   Ketuk **Nama Surat** untuk memunculkan modal geser list 114 surat lengkap dengan fitur cari.
    *   Ketuk **Nomor Ayat** di kanan atas untuk memunculkan grid pemilihan nomor ayat instan.
*   **Statistik & Progress**:
    *   Halaman ringkasan progress per surat dengan bar chart berwarna.
    *   Halaman riwayat lengkap murojaah harian yang terstruktur rapi.
