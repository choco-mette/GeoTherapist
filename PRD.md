# Product Requirement Document (PRD)

## 1. Project Overview

- **Nama Proyek:** GeoTherapist
- **Deskripsi:** Aplikasi internal berbasis web untuk mengelola master data terapis dan melakukan pencarian lokasi pelanggan secara real-time guna menampilkan urutan 10+ terapis dari jarak tempuh terdekat hingga terjauh beserta estimasi waktu perjalanan dan tombol dispatch WhatsApp.
- **Target Stack:**
- **Backend:** Laravel 11 / 12 (PHP 8.2+)
- **Frontend Stack:** Blade + Tailwind CSS + Alpine.js (atau Laravel Livewire v3)
- **Database:** sqlite
- **Peta & Geocoding:** Leaflet.js + OpenStreetMap Tile Layer + Nominatim OSM Geocoding (Gratis, tanpa API key berbayar)

- **Desain & Tema UI:**
- Modern Dark / Slate UI dengan dominasi warna **Hitam** (`#090d16` / `#0f172a`), Slate Dark (`#1e293b`), dan aksen **Biru** (`#2563eb` / `#3b82f6` / `#60a5fa`).

---

## 2. Core User Stories & Roles

- **User (Admin / Dispatcher Operasional):**

1. _Sebagai admin_, saya ingin mengelola data terapis (tambah, edit, hapus, ubah status ketersediaan, ubah home base & koordinat) agar data lokasi terapis selalu akurat.
2. _Sebagai admin_, saya ingin mengetik nama alamat/kecamatan pelanggan di dashboard dan melihat titiknya di peta Leaflet secara otomatis tanpa API berbayar.
3. _Sebagai admin_, saya ingin titik pelanggan dapat digeser (_drag-and-drop_) langsung ke jalan/gang rumah pelanggan untuk memastikan titik akurat.
4. _Sebagai admin_, saya ingin melihat daftar terapis berjejer terurut otomatis dari jarak terdekat (Rank #1) hingga terjauh lengkap dengan estimasi waktu tempuh Motor dan Mobil.
5. _Sebagai admin_, saya ingin menekan satu tombol "Kirim Order via WA" yang langsung membuka chat WhatsApp ke terapis terpilih dengan draf format pesan lengkap.

---

## 3. Database Schema & Migration

### Table: `therapists`

```sql
CREATE TABLE therapists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(25) NOT NULL,
    specialization VARCHAR(100) DEFAULT 'General Massage',
    rating DECIMAL(2,1) DEFAULT 5.0,
    review_count INT DEFAULT 0,
    address_label VARCHAR(255) NOT NULL, -- e.g. "Jl. Rungkut Asri No. 12"
    subdistrict VARCHAR(100) NULL,       -- e.g. "Rungkut"
    city VARCHAR(100) DEFAULT 'Surabaya',
    latitude DECIMAL(10, 7) NOT NULL,    -- e.g. -7.3195200
    longitude DECIMAL(10, 7) NOT NULL,   -- e.g. 112.7753100
    status ENUM('available', 'on_duty', 'off') DEFAULT 'available',
    avatar_url VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

```

### Table: `dispatch_logs` (Optional / History)

```sql
CREATE TABLE dispatch_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapist_id BIGINT UNSIGNED NOT NULL,
    customer_name VARCHAR(150) NULL,
    customer_address TEXT NOT NULL,
    customer_lat DECIMAL(10, 7) NOT NULL,
    customer_lng DECIMAL(10, 7) NOT NULL,
    distance_km DECIMAL(6, 2) NOT NULL,
    est_minutes_motor INT NOT NULL,
    status ENUM('drafted', 'sent', 'completed') DEFAULT 'drafted',
    created_at TIMESTAMP NULL,
    FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE
);

```

---

## 4. System Architecture & Algorithms

### 4.1 Perhitungan Jarak (Haversine + Urban Tortuosity)

Gunakan rumus Haversine dengan faktor kelokan rute perkotaan Indonesia (_urban winding factor_ 1.32x - 1.35x) agar estimasi jarak realistis mendekati jalan riil:

```
Distance_Straight = Haversine(lat1, lon1, lat2, lon2)
Distance_Road_KM = Distance_Straight * 1.34

```

### 4.2 Estimasi Waktu Tempuh

Dihitung berdasarkan kecepatan rata-rata perkotaan:

- **Motor:** Rata-rata 26 km/jam + buffer penyesuaian lampu merah/gang 3 menit.

$$\text{Waktu Motor (menit)} = \left(\frac{\text{Distance\_Road\_KM}}{26} \times 60\right) + 3$$

- **Mobil:** Rata-rata 18 km/jam + buffer lampu merah/macet 6 menit.

$$\text{Waktu Mobil (menit)} = \left(\frac{\text{Distance\_Road\_KM}}{18} \times 60\right) + 6$$

### 4.3 Geocoding Gratis (Frontend Leaflet)

- Request langsung via browser ke Nominatim OSM:
  `[https://nominatim.openstreetmap.org/search?format=json&q=](https://nominatim.openstreetmap.org/search?format=json&q=){query}&limit=5`
- Di-filter dengan _bounding box_ atau penambahan nama kota (misal: "Surabaya") untuk mempercepat dan meningkatkan akurasi.

---

## 5. UI/UX & Color System Specification

- **Color Palette:**
- Background Utama: `bg-[#0b0f17]` (Deep Obsidian Black)
- Card / Sidebar: `bg-[#131b2a]` dengan border `border-blue-900/30`
- Primary Accent: `bg-blue-600` hover `bg-blue-500`
- Text Primary: `text-slate-100`
- Text Secondary: `text-slate-400`
- Status Badge:
- Available: `bg-emerald-500/10 text-emerald-400 border-emerald-500/20`
- On Duty: `bg-amber-500/10 text-amber-400 border-amber-500/20`
- Off: `bg-rose-500/10 text-rose-400 border-rose-500/20`

- **Komponen Layout:**
- **Header:** Navbar gelap dengan judul sistem, toggle menu Master Terapis, dan status operasional.
- **Halaman 1 (Dashboard Utama / Fitur Inti):**
- _Kolom Kiri (Input & Hasil Urutan):_ Input pencarian teks alamat, chip shortcut kecamatan, filter ketersediaan, serta card daftar 10 terapis terurut dari yang terdekat (Rank #1 highlight badge).
- _Kolom Kanan (Peta Interaktif):_ Peta Leaflet Full-height dengan pin customer (merah animasi) dan 10 pin terapis (biru/hijau), serta garis rute putus-putus ke terapis terdekat.

- **Halaman 2 (Master Data Terapis):**
- Tabel data terapis dengan pagination, pencarian nama, modal Tambah/Edit terapis, dan penentu titik koordinat home base menggunakan mini-map picker.

---

## 6. Functional Specifications & Endpoints

### Route List:

| Method   | URI                       | Controller Action                  | Deskripsi                                                            |
| -------- | ------------------------- | ---------------------------------- | -------------------------------------------------------------------- |
| `GET`    | `/`                       | `DashboardController@index`        | Dashboard plotting terapis & customer                                |
| `GET`    | `/api/therapists/nearby`  | `TherapistApiController@getNearby` | Mengembalikan JSON list terapis berurut berdasarkan lat/lng customer |
| `GET`    | `/therapists`             | `TherapistController@index`        | List master data terapis                                             |
| `POST`   | `/therapists`             | `TherapistController@store`        | Simpan terapis baru                                                  |
| `PUT`    | `/therapists/{id}`        | `TherapistController@update`       | Update data terapis & home base                                      |
| `PATCH`  | `/therapists/{id}/status` | `TherapistController@updateStatus` | Toggle cepat status (Ready/Busy/Off)                                 |
| `DELETE` | `/therapists/{id}`        | `TherapistController@destroy`      | Hapus terapis                                                        |

### Format Template Pesan WhatsApp:

```text
Halo [Nama Terapis], ada order masuk:
- Alamat Pelanggan: [Alamat/Kecamatan Customer]
- Jarak Tempuh: [XX.X] km dari home base kamu
- Estimasi Waktu (Motor): [XX] menit
- Maps Lokasi: https://maps.google.com/?q=[lat],[lng]

Mohon konfirmasi kesiapannya segera. Terima kasih!

```

---

## 7. Step-by-Step Implementation Prompt (Copy & Paste ke Antigravity)

Salin prompt di bawah ini ke Antigravity/Cursor untuk mulai membangun proyek:

```text
Buatkan aplikasi web Laravel 11 dengan Tailwind CSS dan Alpine.js berdasarkan PRD di atas.

Kebutuhan Utama:
1. Skema Database:
   - Buat Migration dan Model `Therapist` lengkap dengan seeder 10 data terapis riil di berbagai kecamatan (Surabaya).
2. Tampilan UI:
   - Terapkan dark theme modern (background hitam #0b0f17 dan aksen biru terang #3b82f6).
   - Buat Layout Master dengan Navbar navigasi ke "Dashboard Dispatch" dan "Master Data Terapis".
3. Fitur Utama (Dashboard Dispatch):
   - Integrasikan Leaflet.js dengan Tile OpenStreetMap (gratis).
   - Buat input text pencarian alamat pelanggan yang melakukan geocoding gratis ke Nominatim OpenStreetMap (disertai debounce).
   - Marker customer dapat di-drag langsung di peta untuk menyesuaikan titik koordinat.
   - Sediakan perhitungan jarak jalan (Haversine x 1.34) dan estimasi waktu tempuh Motor & Mobil secara reaktif.
   - List terapis berjejer otomatis dari yang terdekat (Rank #1) ke terjauh.
   - Tombol "Kirim WA" pada masing-masing terapis yang membuka link wa.me dengan teks template order otomatis.
4. Fitur Master Data Terapis:
   - CRUD lengkap (Nama, No WA, Keahlian, Alamat Home Base, Lat, Lng, Status).
   - Pada form tambah/edit sertakan mini-map picker Leaflet untuk menentukan koordinat lat/lng home base.

Gunakan struktur kode Laravel yang rapi, modular, dan siap pakai.

```
