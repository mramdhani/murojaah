# QUL Surah Name Font v4

Paket ini berisi font kaligrafi untuk seluruh 114 nama surah Al-Quran.

## Isi paket

- `surah-name-v4.ttf` — aplikasi desktop, Android, iOS, dan desain
- `surah-name-v4.woff` — web
- `surah-name-v4.woff2` — web modern, ukuran lebih efisien

## Cara menggunakan

Gunakan kode ligature berikut sebagai teks:

- `surah001` — Al-Fatihah
- `surah002` — Al-Baqarah
- dan seterusnya sampai `surah114` — An-Nas
- `surah-icon` — ikon kata Surah

Contoh HTML/CSS:

```css
@font-face {
  font-family: "SurahNameV4";
  src: url("surah-name-v4.woff2") format("woff2"),
       url("surah-name-v4.woff") format("woff"),
       url("surah-name-v4.ttf") format("truetype");
  font-display: swap;
}

.surah-name {
  font-family: "SurahNameV4";
}
```

```html
<div class="surah-name">surah002</div>
```

## Sumber dan kredit

- Halaman resmi: https://qul.tarteel.ai/resources/font/457
- Penyedia: Quranic Universal Library (QUL) / Tarteel AI

Font di dalam paket tidak diubah. Hak dan ketentuan penggunaan mengikuti penyedia serta kontributor aslinya. Periksa halaman sumber sebelum mendistribusikan ulang atau memakainya untuk produk komersial.
