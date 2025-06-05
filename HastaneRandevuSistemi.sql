CREATE DATABASE Hastane_Randevu;
USE Hastane_Randevu;

CREATE TABLE Hastalar (
    TC CHAR(11) PRIMARY KEY CHECK (LEN(TC) = 11),
    Ad NVARCHAR(50),
    Soyad NVARCHAR(50),
    Cinsiyet NVARCHAR(10) CHECK (Cinsiyet IN ('Erkek', 'Kadın')),
    Sifre NVARCHAR(50),
    Dogum_Tarihi DATE
);
CREATE TABLE Cocuklar (
    TC CHAR(11) PRIMARY KEY CHECK (LEN(TC) = 11),
    Ad NVARCHAR(50),
    Soyad NVARCHAR(50),
    Dogum_Tarihi DATE
);

CREATE TABLE Ebeveyn_Cocuk (
    Anne_TC CHAR(11),
    Baba_TC CHAR(11),
    Cocuk_TC CHAR(11),
	PRIMARY KEY (Anne_TC, Baba_TC, Cocuk_TC),
    FOREIGN KEY (Anne_TC) REFERENCES Hastalar(TC),
    FOREIGN KEY (Baba_TC) REFERENCES Hastalar(TC),
	FOREIGN KEY (Cocuk_TC) REFERENCES Cocuklar(TC)
);
CREATE TABLE Yetkili (
    Hasta_TC CHAR(11),
    Cocuk_TC CHAR(11),
	PRIMARY KEY (Hasta_TC, Cocuk_TC),
    FOREIGN KEY (Hasta_TC) REFERENCES Hastalar(TC),
    FOREIGN KEY (Cocuk_TC) REFERENCES Cocuklar(TC)
);
CREATE TABLE Hastane (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    Ad NVARCHAR(50),
    Il NVARCHAR(50),
    Ilce NVARCHAR(50)
);
CREATE TABLE Bolum (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    Ad NVARCHAR(50)
);
CREATE TABLE Icerir (
    Hastane_ID INT,
    Bolum_ID INT,
	PRIMARY KEY (Hastane_ID, Bolum_ID),
    FOREIGN KEY (Hastane_ID) REFERENCES Hastane(ID),
    FOREIGN KEY (Bolum_ID) REFERENCES Bolum(ID)
);
CREATE TABLE Doktorlar (
    ID INT IDENTITY(1,1)  PRIMARY KEY,
    Ad NVARCHAR(50),
    Soyad NVARCHAR(50),
    Bolum_ID INT,
    Hastane_ID INT,
    FOREIGN KEY (Bolum_ID) REFERENCES Bolum(ID),
    FOREIGN KEY (Hastane_ID) REFERENCES Hastane(ID)
);
CREATE TABLE Izin (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    BasTarih DATE,
    BitTarih DATE
);
CREATE TABLE Doktor_Izin (
    Doktor_ID INT ,
    Izin_ID INT ,
	PRIMARY KEY (Doktor_ID, Izin_ID),
	FOREIGN KEY (Izin_ID) REFERENCES Izin(ID),
    FOREIGN KEY (Doktor_ID) REFERENCES Doktorlar(ID)
);
CREATE TABLE Randevu (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    Tarih DATE,
    Saat TIME,
    Durum NVARCHAR(20) DEFAULT 'Aktif',
    Hasta_TC CHAR(11),        -- Ebeveynin TC'si
    Cocuk_TC CHAR(11),        -- Çocuğun TC'si
    Hastane_ID INT,
    Bolum_ID INT,
    Doktor_ID INT,
    FOREIGN KEY (Hasta_TC) REFERENCES Hastalar(TC),   -- Ebeveynin TC'si
    FOREIGN KEY (Cocuk_TC) REFERENCES Cocuklar(TC),   -- Çocuğun TC'si
    FOREIGN KEY (Hastane_ID) REFERENCES Hastane(ID),
    FOREIGN KEY (Bolum_ID) REFERENCES Bolum(ID),
    FOREIGN KEY (Doktor_ID) REFERENCES Doktorlar(ID)
);


CREATE TABLE Yetki (
	Hasta_TC CHAR(11),
	Bolum_ID INT,
	PRIMARY KEY (Hasta_TC, Bolum_ID),
	FOREIGN KEY (Bolum_ID) REFERENCES Bolum(ID),
	FOREIGN KEY (Hasta_TC) REFERENCES Hastalar(TC)
);

CREATE TABLE Sevk_Edilir (
	Yonlendiren_Bolum INT,
	Yonlendirilen_Bolum INT,
	PRIMARY KEY (Yonlendiren_Bolum, Yonlendirilen_Bolum),
	FOREIGN KEY (Yonlendiren_Bolum) REFERENCES Bolum(ID),
	FOREIGN KEY (Yonlendirilen_Bolum) REFERENCES Bolum(ID)
);

------------------------STORE PROSEDÜR HASTA MI ÇOCUK MU ?--------------------------------
CREATE PROCEDURE EkleYeniKayit
    @TC CHAR(11),
    @Ad NVARCHAR(50),
    @Soyad NVARCHAR(50),
    @Cinsiyet NVARCHAR(10),
    @Sifre NVARCHAR(50),
    @Dogum_Tarihi DATE
AS
BEGIN
    -- TC zaten var mı kontrol et
    IF EXISTS (SELECT 1 FROM Hastalar WHERE TC = @TC) OR EXISTS (SELECT 1 FROM Cocuklar WHERE TC = @TC)
    BEGIN
        PRINT 'Bu TC ile zaten bir kayıt mevcut.'
        RETURN
    END

    DECLARE @Yas INT
    SET @Yas = DATEDIFF(YEAR, @Dogum_Tarihi, GETDATE())

    -- Doğum günü bu yıl henüz gelmediyse yaş 1 eksik olsun
    IF (DATEADD(YEAR, @Yas, @Dogum_Tarihi) > GETDATE())
        SET @Yas = @Yas - 1

    IF @Yas < 18
    BEGIN
        INSERT INTO Cocuklar (TC, Ad, Soyad, Dogum_Tarihi)
        VALUES (@TC, @Ad, @Soyad, @Dogum_Tarihi)
    END
    ELSE
    BEGIN
        INSERT INTO Hastalar (TC, Ad, Soyad, Cinsiyet, Sifre, Dogum_Tarihi)
        VALUES (@TC, @Ad, @Soyad, @Cinsiyet, @Sifre, @Dogum_Tarihi)
    END
END;



-----------------------------------------------------------
CREATE PROCEDURE RandevuAl
    @Hasta_TC CHAR(11),
    @Cocuk_TC CHAR(11),
    @Hastane_ID INT,
    @Bolum_ID INT,
    @Tarih DATE,
    @Saat TIME
AS
BEGIN
    DECLARE @Cinsiyet VARCHAR(10),
            @Yas INT,
            @DogumTarihi DATE,
            @Doktor_ID INT;  -- Doktor ID değişkeni tanımlanıyor

    -- Yaş ve (varsa) cinsiyet bilgisi alınıyor
    IF (@Cocuk_TC IS NOT NULL)
    BEGIN
        SELECT @DogumTarihi = Dogum_Tarihi FROM Cocuklar WHERE TC = @Cocuk_TC;
        SET @Yas = DATEDIFF(YEAR, @DogumTarihi, GETDATE());
        IF (DATEADD(YEAR, @Yas, @DogumTarihi) > GETDATE())
            SET @Yas = @Yas - 1;
    END
    ELSE
    BEGIN
        SELECT @DogumTarihi = Dogum_Tarihi, @Cinsiyet = Cinsiyet FROM Hastalar WHERE TC = @Hasta_TC;
        SET @Yas = DATEDIFF(YEAR, @DogumTarihi, GETDATE());
        IF (DATEADD(YEAR, @Yas, @DogumTarihi) > GETDATE())
            SET @Yas = @Yas - 1;
    END

    -- Yaş kontrolü
        -- 18 yaşından küçüklerin erişemeyeceği bölümler
    IF (@Yas < 18 AND @Bolum_ID IN (
        11, -- Endokrinoloji ve Metabolizma Hastalıkları
        12, -- Gastroenteroloji
        16, -- İç Hastalıkları (Dahiliye)
        17, -- İmmünoloji ve Alerji Hastalıkları
        20, -- Kardiyoloji
        22, -- Nefroloji
        24  -- Üroloji
    )) 
    BEGIN
        PRINT '18 yaşından küçük hastalar bu bölüme randevu alamaz.';
        RETURN;
    END

    -- 18 yaş ve üzeri kişilerin erişemeyeceği bölümler (çocuk uzmanlıkları)
    IF (@Yas >= 18 AND @Bolum_ID IN (
        2, -- Çocuk Cerrahisi
        3, -- Çocuk Endokrinolojisi
        4, -- Çocuk Gastroenterolojisi
        5, -- Çocuk İmmünolojisi ve Alerji Hastalıkları
        6, -- Çocuk Kardiyolojisi
        7, -- Çocuk Nörolojisi
		8  -- Çocuk Sağlığı ve Hastalıkları
    )) 
    BEGIN
        PRINT '18 yaş ve üzeri kişiler bu çocuk bölümüne randevu alamaz.';
        RETURN;
    END
	-- Cinsiyet kontrolü sadece yetişkinler için yapılır
    IF (@Cocuk_TC IS NULL AND @Cinsiyet = 'Erkek' AND @Bolum_ID = 2)
    BEGIN
        PRINT 'Erkekler Kadın Doğum doktoruna randevu alamaz.';
        RETURN;
    END
	-- Dahiliye → Gidilmeden gidilemeyecek bölümler
IF (@Bolum_ID IN (11, 12, 17, 20, 22))  -- Endokrinoloji, Gastroenteroloji, İmmünoloji, Kardiyoloji, Nefroloji
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM Randevu 
        WHERE Hasta_TC = @Hasta_TC 
          AND Bolum_ID = 16  -- İç Hastalıkları (Dahiliye)
    )
    BEGIN
        PRINT 'Bu bölüme gitmeden önce Dahiliye bölümünden randevu almalısınız.';
        RETURN;
    END
END

-- Çocuk Sağlığı → Gidilmeden gidilemeyecek bölümler
IF (@Bolum_ID IN (2, 3, 4, 5, 6, 7)) -- Çocuk Cerrahisi, Çocuk Endokrinolojisi, Çocuk Gastroenterolojisi, Çocuk İmmünolojisi, Çocuk Kardiyolojisi, Çocuk Nörolojisi
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM Randevu 
        WHERE Cocuk_TC = @Cocuk_TC 
          AND Bolum_ID = 8 -- Çocuk Sağlığı ve Hastalıkları
    )
    BEGIN
        PRINT 'Bu bölüme gitmeden önce Çocuk Sağlığı ve Hastalıkları bölümünden randevu almalısınız.';
        RETURN;
    END
END
-- Çocuğun TC'si varsa ebeveyn kontrolü yapılır
    IF (@Cocuk_TC IS NOT NULL)
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM Yetkili WHERE Hasta_TC = @Hasta_TC AND Cocuk_TC = @Cocuk_TC)
        BEGIN
            PRINT 'Çocuk kendisine randevu alamaz, ebeveyninin randevu alması gerekir.';
            RETURN;
        END
    END

	 -- Doktor ID alınır
    SELECT @Doktor_ID = ID 
    FROM Doktorlar 
    WHERE Hastane_ID = @Hastane_ID AND Bolum_ID = @Bolum_ID;

    -- Aynı tarih ve saatte AKTİF randevu var mı?
    IF EXISTS (
        SELECT 1 FROM Randevu 
        WHERE Doktor_ID = @Doktor_ID AND Tarih = @Tarih AND Saat = @Saat AND Durum = 'Aktif'
    )
    BEGIN
        PRINT 'Bu doktora bu tarih ve saatte zaten aktif bir randevu var.';
        RETURN;
    END

    -- Daha önce İPTAL edilmiş bir randevu varsa, onu GÜNCELLE
    IF EXISTS (
        SELECT 1 FROM Randevu 
        WHERE Doktor_ID = @Doktor_ID AND Tarih = @Tarih AND Saat = @Saat AND Durum = 'İptal'
    )
    BEGIN
        UPDATE Randevu
        SET Hasta_TC = @Hasta_TC,
            Cocuk_TC = @Cocuk_TC,
            Hastane_ID = @Hastane_ID,
            Bolum_ID = @Bolum_ID,
            Durum = 'Aktif'
        WHERE Doktor_ID = @Doktor_ID AND Tarih = @Tarih AND Saat = @Saat AND Durum = 'iptal';

        PRINT 'İptal edilen randevu yeniden güncellenip aktif hale getirildi.';
        RETURN;
    END

    -- Hiç randevu yoksa yeni kayıt
    INSERT INTO Randevu (Hasta_TC, Cocuk_TC, Hastane_ID, Bolum_ID, Doktor_ID, Tarih, Saat, Durum)
    VALUES (
        @Hasta_TC,
        @Cocuk_TC,
        @Hastane_ID,
        @Bolum_ID,
        @Doktor_ID,
        @Tarih,
        @Saat,
        'Aktif'
    );

    PRINT 'Randevunuz başarıyla oluşturuldu.';
END;

CREATE TRIGGER RandevuKontrolleri
ON Randevu
AFTER INSERT, UPDATE
AS
BEGIN
    DECLARE @Doktor_ID INT,
            @Durum VARCHAR(20),
            @Hasta_TC CHAR(11),
            @Bolum_ID INT,
            @SonRandevu DATE,
            @Cocuk_TC CHAR(11),
            @Tarih DATE,
            @Saat TIME;

    -- Yeni eklenen veya güncellenen randevudan gerekli verileri al
    SELECT @Doktor_ID = Doktor_ID, 
           @Hasta_TC = Hasta_TC, 
           @Bolum_ID = Bolum_ID,
           @Durum = Durum,
           @Cocuk_TC = Cocuk_TC,
           @Tarih = Tarih,
           @Saat = Saat
    FROM INSERTED;

    -- Doktorun izinde olup olmadığını kontrol et (Doktor_Izin tablosu)
    IF EXISTS (
        SELECT 1
        FROM Doktor_Izin DI
        JOIN Izin I ON DI.Izin_ID = I.ID
        WHERE DI.Doktor_ID = @Doktor_ID
          AND @Tarih BETWEEN I.BasTarih AND I.BitTarih
    )
    BEGIN
        PRINT 'Doktor izindeyken randevu alınamaz!';
        ROLLBACK TRANSACTION;
        RETURN;
    END

-- Hastanın bu bölümdeki en son 'Gelmedi' durumundaki randevusu alınır
SELECT @SonRandevu = MAX(Tarih) 
FROM Randevu 
WHERE Hasta_TC = @Hasta_TC AND Bolum_ID = @Bolum_ID AND Durum = 'Gelmedi' AND Tarih < @Tarih;

-- Eğer böyle bir kayıt varsa ve aradan 15 gün geçmediyse yeni randevu verilmez
IF @SonRandevu IS NOT NULL AND DATEDIFF(DAY, @SonRandevu, @Tarih) < 16
BEGIN
    PRINT 'Hasta bu bölümden 15 gün içinde tekrar randevu alamaz!';
    ROLLBACK TRANSACTION;
    RETURN;
END


    -- Çocuk randevu alırken, ebeveyninin randevu alması gerektiğini kontrol et
    IF (@Cocuk_TC IS NOT NULL)
    BEGIN
        -- Çocuğun ebeveynleri kontrol ediliyor
        IF NOT EXISTS (SELECT 1 FROM Yetkili WHERE Hasta_TC = @Hasta_TC AND Cocuk_TC = @Cocuk_TC)
        BEGIN
            PRINT 'Çocuk kendisine randevu alamaz, ebeveyninin randevu alması gerekir.';
            ROLLBACK TRANSACTION;  -- Randevu almayı iptal et
            RETURN;
        END
    END
END;

UPDATE Randevu
SET Durum = 'İptal'
WHERE ID = 26;  -- buraya iptal edilecek randevunun ID'si yazılmalı

CREATE VIEW TumRandevular
AS
SELECT 
    R.ID AS RandevuID,
    R.Hasta_TC,
    R.Cocuk_TC,
    R.Tarih,
    R.Saat,
    R.Durum,
    CASE 
        WHEN R.Cocuk_TC IS NOT NULL THEN C.Ad + ' ' + C.Soyad -- Ebeveyn çocuk için randevu alıyorsa çocuğun adı
        ELSE H.Ad + ' ' + H.Soyad -- Ebeveyn değilse hastanın adı
    END AS HastaAdSoyad,
    D.Ad + ' ' + D.Soyad AS DoktorAdSoyad,
    B.Ad AS Bolum
FROM Randevu R
LEFT JOIN Hastalar H ON R.Hasta_TC = H.TC
LEFT JOIN Cocuklar C ON R.Cocuk_TC = C.TC
LEFT JOIN Doktorlar D ON R.Doktor_ID = D.ID
LEFT JOIN Bolum B ON R.Bolum_ID = B.ID;

SELECT * FROM TumRandevular ;






-------------HASTA --ÇOCUK- YETKİLİ-- EBEVEYN_COCUK --BÖLÜM- HASTANE-- DOKTOR-- İÇERİR(Bölüm,HASTANE),SEVK EDİLİR EKLE ----------------
INSERT INTO Bolum (Ad)
VALUES
    ('Beyin ve Sinir Cerrahisi'),
    ('Çocuk Cerrahisi'),
    ('Çocuk Endokrinolojisi'),
    ('Çocuk Gastroenterolojisi'),
    ('Çocuk İmmünolojisi ve Alerji Hastalıkları'),
    ('Çocuk Kardiyolojisi'),
    ('Çocuk Nörolojisi'),
    ('Çocuk Sağlığı ve Hastalıkları'),
    ('Deri ve Zührevi Hastalıkları (Cildiye)'),
    ('Diş Hekimliği (Genel Diş)'),
    ('Endokrinoloji ve Metabolizma Hastalıkları'),
    ('Gastroenteroloji'),
    ('Genel Cerrahi'),
    ('Göğüs Hastalıkları'),
    ('Göz Hastalıkları'),
    ('İç Hastalıkları (Dahiliye)'),
    ('İmmünoloji ve Alerji Hastalıkları'),
    ('İş ve Meslek Hastalıkları'),
    ('Kadın Hastalıkları ve Doğum'),
    ('Kardiyoloji'),
    ('Kulak Burun Boğaz Hastalıkları'),
    ('Nefroloji'),
    ('Ruh Sağlığı ve Hastalıkları (Psikiyatri)'),
    ('Üroloji');

INSERT INTO Hastane (Ad, Il, Ilce)
VALUES 
('İstanbul Şehir Hastanesi', 'İstanbul', 'Başakşehir'),
('Acıbadem Maslak Hastanesi', 'İstanbul', 'Sarıyer'),
('Ankara Şehir Hastanesi', 'Ankara', 'Çankaya'),
('LIV Hospital Ankara', 'Ankara', 'Çankaya'),
('İzmir Şehir Hastanesi', 'İzmir', 'Bayraklı'),
('Medical Park İzmir Hastanesi', 'İzmir', 'Karşıyaka'),
('Bursa Şehir Hastanesi', 'Bursa', 'Nilüfer'),
('Medicana Bursa Hastanesi', 'Bursa', 'Osmangazi'),
('Konya Şehir Hastanesi', 'Konya', 'Meram'),
('Medicana Konya Hastanesi', 'Konya', 'Selçuklu'),
('Adana Şehir Hastanesi', 'Adana', 'Yüreğir'),
('Başkent Üniversitesi Adana Hastanesi', 'Adana', 'Seyhan'),
('Samsun Eğitim ve Araştırma Hastanesi', 'Samsun', 'İlkadım'),
('Medical Park Samsun Hastanesi', 'Samsun', 'Canik'),
('Kayseri Şehir Hastanesi', 'Kayseri', 'Kocasinan'),
('Acıbadem Kayseri Hastanesi', 'Kayseri', 'Melikgazi'),
('Sivas Numune Hastanesi', 'Sivas', 'Merkez'),
('Medicana Sivas Hastanesi', 'Sivas', 'Merkez'),
('Antalya Eğitim ve Araştırma Hastanesi', 'Antalya', 'Muratpaşa'),
('Medstar Antalya Hastanesi', 'Antalya', 'Muratpaşa'),
('Adapazarı Eğitim ve Araştırma Hastanesi', 'Sakarya', 'Adapazarı'),
('Medicana Sakarya Hastanesi', 'Sakarya', 'Serdivan'),
('Eskişehir Özel Anadolu Hastanesi', 'Eskişehir', 'Tepebaşı'),
('Medikal Park Eskişehir Hastanesi', 'Eskişehir', 'Odunpazarı'),
('Mersin Şehir Hastanesi', 'Mersin', 'Yenişehir'),
('Özel Mersin Akademi Hastanesi', 'Mersin', 'Akdeniz');

INSERT INTO Doktorlar (Ad, Soyad, Bolum_ID, Hastane_ID)
VALUES
-- Beyin ve Sinir Cerrahisi 
('Levent', 'Atahanlı', 1, 2),
('Eylül', 'Erdem', 1, 3),
('Ali Asaf', 'Denizoğlu', 1, 3),
('Ali', 'Vefa', 1, 5),

-- Çocuk Cerrahisi (2)
('Ela', 'Altındağ', 2, 2),
('Gülşah', 'Kurt', 2, 5),
('Tahir', 'Kaleli', 2, 7),


-- Çocuk Endokrinolojisi (3)
('Selin', 'Karaçay', 3, 2),
('Mehmet', 'Uğurlu', 3, 3),
('Hande', 'Demirtaş', 3, 5),

-- -- Çocuk Gastroenterolojisi (4)
('Esra', 'Demir', 4, 2),
('Ahmet', 'Korkmaz', 4, 5),
('Melis', 'Tanrıverdi', 4, 7),

--Çocuk İmmünoloji ve Alerji Hastalıkları(5)
('Seval', 'Yüce', 5, 2),
('Kerem', 'Ünsal', 5, 5),
('Tuğba', 'Erkan', 5, 8),

--Çocuk Kardiyolojisi   (6)
('Duygu', 'Şahin', 6, 2),
('Volkan', 'Başar', 6, 10),
('Aslı', 'Özdemir', 6, 15),

-- Çocuk Nörolojisi (7)
('Yasemin', 'Yıldız', 7, 2),
('Efe', 'Koç', 7, 15),
('Nil', 'Işık', 7, 18),

-- Çocuk Sağlığı ve Hastalıkları (8)
('Nazlı', 'Gülengül', 8, 2),
('Cem', 'Kurtuluş', 8, 8),
('Arda', 'Aksoy', 8, 19),

-- Deri ve Zührevi Hastalıkları (Cildiye) (9)
('Arslan', 'İbrahimoğlu', 9, 2),
('Aylin', 'Sezgin', 9, 6),
('Gökhan', 'Ateş', 9, 24),

-- Diş Hekimliği (Genel Diş) (10)
('Seda', 'Yıldırım', 10, 2),
('Erkan', 'Koçyiğit', 10,6 ),
('Burcu', 'Akçay', 10, 13),

-- Endokrinoloji ve Metabolizma Hastalıkları (11)
('Suat', 'Birtan', 11, 2),
('Zeynep', 'Albayrak', 11, 16),
('Emre', 'Aydoğdu', 11, 17),

-- Gastroenteroloji (12)
('Serkan', 'Özçelik', 12, 2),
('Canan', 'Balcı', 12, 16),
('Tunahan', 'Şen', 12, 12),

-- Genel Cerrahi (13)
('Fikret', 'Eralp', 13, 2),
('Ferman', 'Eryiğit', 13, 2),
('Timur', 'Yavuzoğlu', 13, 16),
('Evren', 'Yalkın', 13, 19),

-- Göğüs Hastalıkları (14)
('Leyla', 'Durmaz', 14, 2),
('Umut', 'Yıldız', 14, 16),
('Necla', 'Akın', 14, 14),

-- Göz Hastalıkları (15)
('Ayça', 'Turan', 15, 2),
('Ercan', 'Kurt', 15, 12),
('Mine', 'Aydın', 15, 16),

-- İç Hastalıkları (Dahiliye) (16)
('Zenan', 'Parlar Birtan', 16, 2),
('Faruk', 'Demiral', 16, 5),
('Nihan', 'Güngör', 16, 17),

-- İmmünoloji ve Alerji Hastalıkları (17)
('Serap', 'Karaman', 17, 2),
('Barış', 'Uluç', 17, 5),
('Filiz', 'Taş', 17, 7),



-- İş ve Meslek Hastalıkları (18)
('Hüseyin', 'Kavak', 18, 2),
('İlknur', 'Sarı', 18, 3),
('Murat', 'Ersoy', 18, 10),


-- Kadın Hastalıkları ve Doğum (19)
('Bahar', 'Özden', 19, 2),
('Rengin', 'Çevik', 19, 13),
('Dilara', 'Tuna', 19, 18),

-- Kardiyoloji (20)
('Onur', 'Yılmaz', 20, 2),
('Ayşegül', 'Mutlu', 20, 16),
('Halil', 'Özkan', 20, 19),



-- Kulak Burun Boğaz Hastalıkları (21)
('İrem', 'Soylu', 21, 2),
('Alper', 'Demirtaş', 21, 15),
('Derya', 'Yüce', 21, 21),

-- Nefroloji (22)
('Tuba', 'Çetin', 22, 2),
('Gökçe', 'Özer', 22, 12),
('Selim', 'Sarı', 22, 24),

-- Ruh Sağlığı ve Hastalıkları (Psikiyatri) (23)
('Asuman', 'Işık', 23, 2),
('Orhan', 'Deniz', 23, 6),
('Burak', 'Can', 23, 25),

-- Üroloji (24)
('Hakan', 'Duman', 24, 2),
('Sinem', 'Koç', 24, 3),
('Mete', 'Bayraktar', 24, 16);

INSERT INTO Icerir (Hastane_ID, Bolum_ID)
VALUES
(2, 1), (3, 1), (5, 1),
(2, 2), (5, 2), (7, 2),
(2, 3), (5, 3), (7, 3),
(2, 4), (5, 4), (7, 4),
(2, 5), (5, 5), (8, 5),
(2, 6), (10, 6), (15, 6),
(2, 7), (15, 7), (18, 7),
(2, 8), (8, 8), (19, 8),
(2, 9), (6, 9), (24, 9),
(2, 10), (6, 10), (13, 10),
(2, 11), (16, 11), (17, 11),
(2, 12), (12, 12), (16, 12),
(2, 13), (16, 13), (19, 13),
(2, 14), (16, 14), (14, 14),
(2, 15), (12, 15), (16, 15),
(2, 16), (5, 16), (17, 16),
(2, 17), (5, 17), (7, 17),
(2, 18), (3, 18), (10, 18),
(2, 19), (13, 19), (18, 19),
(2, 20), (16, 20), (19, 20),
(2, 21), (15, 21), (21, 21),
(2, 22), (12, 22), (14, 22),
(2, 23), (6, 23), (25, 23),
(2, 24), (3, 24), (16, 24);


EXEC EkleYeniKayit 
    @TC = '12345678901', 
    @Ad = 'Zeynep', 
    @Soyad = 'Kara', 
    @Cinsiyet = 'Kadın', 
    @Sifre = 'zey123', 
    @Dogum_Tarihi = '2010-06-07';

	EXEC EkleYeniKayit 
    @TC = '11111111111', 
    @Ad = 'Ali', 
    @Soyad = 'Kara', 
    @Cinsiyet = 'Erkek', 
    @Sifre = 'ali123', 
    @Dogum_Tarihi = '1999-08-07';

	EXEC EkleYeniKayit 
    @TC = '22222222222', 
    @Ad = 'Aslı', 
    @Soyad = 'Kara', 
    @Cinsiyet = 'Kadın', 
    @Sifre = 'aslı123', 
    @Dogum_Tarihi = '2000-02-07';

EXEC EkleYeniKayit 
    @TC = '33333333333', 
    @Ad = 'Mert', 
    @Soyad = 'Kara', 
    @Cinsiyet = 'Erkek', 
    @Sifre = 'mert123', 
    @Dogum_Tarihi = '2008-08-12';

EXEC EkleYeniKayit 
    @TC = '44444444444', 
    @Ad = 'Elif', 
    @Soyad = 'Demir', 
    @Cinsiyet = 'Kadın', 
    @Sifre = 'elif123', 
    @Dogum_Tarihi = '1990-01-25';

EXEC EkleYeniKayit 
    @TC = '55555555555', 
    @Ad = 'Burak', 
    @Soyad = 'Çelik', 
    @Cinsiyet = 'Erkek', 
    @Sifre = 'burak123', 
    @Dogum_Tarihi = '2005-03-03';

INSERT INTO Yetki(Hasta_TC, Bolum_ID)
VALUES 
('22222222222', '1'), 
('22222222222', '9'),
('22222222222', '10'),
('22222222222', '11'),
('22222222222', '12'),
('22222222222', '13'),
('22222222222', '14'),
('22222222222', '15'),
('22222222222', '16'),
('22222222222', '17'),
('22222222222', '18'),
('22222222222', '19'),
('22222222222', '20'),
('22222222222', '21'),
('22222222222', '22'),
('22222222222', '23'),
('22222222222', '24'),
('11111111111', '1'),
('11111111111', '9'),
('11111111111', '10'),
('11111111111', '11'),
('11111111111', '12'),
('11111111111', '13'),
('11111111111', '14'),
('11111111111', '15'),
('11111111111', '16'),
('11111111111', '17'),
('11111111111', '18'),
('11111111111', '20'),
('11111111111', '21'),
('11111111111', '22'),
('11111111111', '23'),
('11111111111', '24');
	
	----------------------EBEVEYN ÇOCUK BAĞLA-------------------
	INSERT INTO Yetkili (Hasta_TC, Cocuk_TC)
VALUES ('22222222222', '12345678901');
INSERT INTO Yetkili (Hasta_TC, Cocuk_TC)
VALUES ('11111111111', '12345678901');

INSERT INTO Yetkili (Hasta_TC, Cocuk_TC)
VALUES ('22222222222', '33333333333');
INSERT INTO Yetkili (Hasta_TC, Cocuk_TC)
VALUES ('11111111111', '33333333333');

INSERT INTO Ebeveyn_Cocuk (Anne_TC, Baba_TC, Cocuk_TC)
VALUES ('22222222222', '11111111111', '33333333333');
INSERT INTO Ebeveyn_Cocuk (Anne_TC, Baba_TC, Cocuk_TC)
VALUES ('22222222222', '11111111111', '12345678901');

INSERT INTO Sevk_Edilir (Yonlendiren_Bolum, Yonlendirilen_Bolum)
VALUES
(16, 11),  -- Dahiliye → Endokrinoloji ve Metabolizma Hastalıkları
(16, 12),  -- Dahiliye → Gastroenteroloji
(16, 22),  -- Dahiliye → Nefroloji
(16, 17),  -- Dahiliye → İmmünoloji ve Alerji Hastalıkları
(16, 20);  -- Dahiliye → Kardiyoloji

-- Çocuk Sağlığı (8) zorunlu bölümler
INSERT INTO Sevk_Edilir (Yonlendiren_Bolum, Yonlendirilen_Bolum)
VALUES
(8, 3),   -- Çocuk Sağlığı ve Hastalıkları → Çocuk Endokrinolojisi
(8, 6),   -- Çocuk Sağlığı ve Hastalıkları → Çocuk Kardiyolojisi
(8, 2),   -- Çocuk Sağlığı ve Hastalıkları → Çocuk Cerrahisi
(8, 7),   -- Çocuk Sağlığı ve Hastalıkları → Çocuk Nörolojisi
(8, 4),   -- Çocuk Sağlığı ve Hastalıkları → Çocuk Gastroenterolojisi
(8, 5);   -- Çocuk Sağlığı ve Hastalıkları → Çocuk İmmünolojisi ve Alerji Hastalıkları




INSERT INTO Izin (BasTarih, BitTarih)
VALUES ('2025-05-10', '2025-05-12'); -- İzin başlangıç ve bitiş tarihlerini belirliyoruz

-- Şimdi bu izin kaydını 4 numaralı doktora atıyoruz
DECLARE @Izin_ID INT;
SELECT @Izin_ID = SCOPE_IDENTITY();  -- Son eklenen izin ID'sini alıyoruz

INSERT INTO Doktor_Izin (Doktor_ID, Izin_ID)
VALUES (1, @Izin_ID); -- 1 numaralı doktora izin atıyoruz


INSERT INTO Randevu (Hasta_TC,Cocuk_TC, Hastane_ID,Bolum_ID ,Doktor_ID, Tarih, Saat,Durum)
VALUES ('22222222222',NULL,2,19 ,57 , '2025-06-04', '10:00','Geçmiş');