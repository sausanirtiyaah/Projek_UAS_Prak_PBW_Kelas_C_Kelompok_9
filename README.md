# KosMed

## Nama

**KosMed — Health Tracker untuk Anak Kos**

## Deskripsi Singkat

KosMed adalah web sederhana yang dirancang untuk membantu anak kos dan mahasiswa dalam memantau kesehatan harian. Aplikasi ini menyediakan fitur untuk mencatat stok obat, pola makan, konsumsi air minum, reminder kesehatan, keluhan, pola tidur, dan ringkasan kesehatan mingguan.

Aplikasi ini dibuat agar pengguna dapat lebih mudah menjaga kesehatan secara mandiri, terutama bagi mahasiswa yang tinggal jauh dari keluarga dan memiliki aktivitas harian yang cukup padat.

## Fitur

Fitur utama yang tersedia dalam KosMed:

* Register dan login pengguna
* Dashboard kesehatan harian
* Stok obat
* Tracker makanan
* Tracker air minum
* Reminder kesehatan
* Catatan keluhan kesehatan
* Tracker tidur
* Ringkasan kesehatan mingguan

## Teknologi yang Digunakan

Aplikasi KosMed dikembangkan menggunakan beberapa teknologi berikut:

* **HTML** untuk struktur halaman
* **CSS** untuk desain tampilan aplikasi
* **JavaScript** untuk logic frontend dan interaksi
* **PHP** untuk backend API
* **MySQL** untuk database
* **XAMPP** sebagai server lokal
* **Bootstrap** untuk membantu tampilan responsive
* **Font Awesome** untuk ikon pada aplikasi

## Struktur Project

KosMed/
│
├── api/
│   ├── air/
│   ├── auth/
│   ├── config/
│   ├── helpers/
│   ├── keluhan/
│   ├── makanan/
│   ├── obat/
│   ├── reminder/
│   ├── ringkasan/
│   └── tidur/
│
├── app.js
├── database.sql
├── index.html
├── style.css
└── README.md

## Database

Database yang digunakan dalam project ini bernama:

kosmed_db

Tabel utama yang digunakan:

users
sessions
obat
makanan
air_minum
reminders
keluhan
tidur

## Menjalankan Aplikasi

### 1. Install XAMPP

Pastikan XAMPP sudah terinstall di laptop.

## 2. Jalankan Apache dan MySQL

Buka XAMPP Control Panel, lalu klik Start pada:

Apache
MySQL

Pastikan keduanya sudah berjalan.

### 3. Pindahkan Folder Project

Pindahkan folder project KosMed ini ke dalam folder:

C:\xampp\htdocs

### 4. Buat Database

Buka browser, lalu akses:

http://localhost/phpmyadmin

Buat database baru dengan nama:

kosmed_db

### 5. Import Database

Masuk ke database `kosmed_db`, lalu pilih menu Import.
Pilih file:

database.sql

Klik Import sampai proses selesai.

### 6. Cek Konfigurasi Database

Pastikan file berikut:

api/config/db.php

memiliki konfigurasi database seperti berikut:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kosmed_db');
```

### 7. Jalankan Aplikasi

Buka browser, lalu akses:

http://localhost/2408107010048_SausanIrtiyaah_UAS_PBW/

## Status Project

Project KosMed sudah dapat dijalankan secara lokal menggunakan XAMPP. Fitur utama seperti register, login, dashboard, stok obat, tracker makanan, tracker air minum, reminder, keluhan, tidur, dan ringkasan mingguan sudah tersedia dan dapat digunakan.

## Catatan

Pastikan Apache dan MySQL pada XAMPP selalu aktif sebelum membuka aplikasi melalui localhost.
