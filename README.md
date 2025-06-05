# 🏥 Hastane Randevu Sistemi (MHRS Benzeri)

Bu proje, Türkiye'deki Merkezi Hekim Randevu Sistemi'ne (MHRS) benzer şekilde çalışan bir hastane randevu sistemidir. Veritabanı Tasarımı ve Uygulamaları dersi kapsamında geliştirilmiştir.

## 📌 Proje Özellikleri

- ⛔ Geçmiş tarih ve saate randevu alınamaz.
- 🔄 Kaynak bölüme gitmeden hedef bölüme randevu alınamaz.
  - Eğer hasta, kaynak bölüme daha önce **gelmediyse** veya randevusunu **iptal ettiyse**, hedef bölüme randevu alamaz.
  - Yalnızca **aktif** veya **tamamlanmış** randevular bu işlemi mümkün kılar.
- ⏳ Randevuya gelinmediği takdirde, ilgili bölüme **15 gün boyunca randevu alınamaz**.
- 👨‍⚕️ Aynı doktora, aynı gün ve aynı saate birden fazla randevu alınamaz.
- 👶 Çocuklar bazı yetişkin bölümlerine; yetişkinler çocuk bölümlerine randevu alamaz.
- 🚻 Erkekler Kadın Hastalıkları bölümüne randevu alamaz.
- 👨‍👧 Baba, **kızına Kadın Hastalıkları bölümünden** randevu alabilir.
- 👪 Anne ve baba, çocuklarına randevu alma ve iptal etme işlemlerini gerçekleştirebilir.
- 📅 Randevu, **1 gün öncesi saat 20:00’ye kadar** iptal edilebilir. Bu saatten sonra iptal mümkün değildir.
- 🔁 Süresi geçmemiş iptal edilen randevular başka bir kullanıcı tarafından tekrar alınabilir.
- 🏖️ Doktorun izinli olduğu günlerde randevu alınamaz.

## 🛠️ Kullanılan Teknolojiler

- **Veritabanı**: Microsoft SQL Server
- **Backend**: PHP
- **Frontend**: HTML
- **Sunucu**: XAMPP (Apache + SQLSRV eklentisiyle)
- **IDE**: Visual Studio Code

## 📁 Klasör Yapısı

hastane_randevu_sistemi/
└── hastane_randevu_sistemi/
└── cocuk_anasayfa.php
└── cocuk_randevu.php
└── cocuk_randevu_iptal.php
└── cocuk_randevu_sayfa.php
└── HastaneRandevuSistemi.sql
└── hasta_anasayfa.php
└── login.html
└── login.php
└── logout.php
└── randevu_al.php
└── randevu_al_sayfa.php
└── randevu_iptal.php


## 🚀 Kurulum Talimatları

1. `HastaneRandevuSistemi.sql` dosyasını kullanarak SQL Server üzerinde veritabanını oluşturun.
2. `hastane_randevu_sistemi` klasörünü XAMPP'ın `htdocs` dizinine kopyalayın.
3. `php.ini` dosyanıza SQLSRV uzantılarını eklemeyi unutmayın (Windows kullanıyorsanız `php_pdo_sqlsrv.dll` ve `php_sqlsrv.dll`).
4. Apache'yi başlatın.
5. Tarayıcıdan `http://localhost/hastane_randevu_sistemi/login.html` adresine giderek sistemi test edebilirsiniz.

## 📮 Katkı ve İletişim

Bu proje bir üniversite ödevi kapsamında geliştirilmiştir. Her türlü geri bildirime açıktır. Projede CSS yoktur, isteğinize göre güzelleştirme yapabilirsiniz.

## 👩‍💻 Geliştirenler

- Gizem Işık - Bilgisayar Mühendisliği 3. Sınıf
- Nisa Erinmez - Bilgisayar Mühendisliği 3. Sınıf
- Hatice Birdal - Bilgisayar Mühendisliği 3. Sınıf
- Berra Nur Dalaklıoğlu – Bilgisayar Mühendisliği 3. Sınıf



