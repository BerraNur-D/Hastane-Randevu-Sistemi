<?php
session_start();
if (!isset($_SESSION['tc']) || !isset($_SESSION['cocuk_tc']) || $_SESSION['rol'] !== 'hasta') {
    header("Location: login.html");
    exit;
}

// 1) İstanbul saati
date_default_timezone_set('Europe/Istanbul');

$ebeveynTC = $_SESSION['tc'];
$cocukTC   = $_SESSION['cocuk_tc'];
$randevuID = $_POST['randevu_id'] ?? null;

if (!$randevuID) {
    echo "Randevu ID eksik.";
    exit;
}

// 2) Veritabanına bağlan
$serverName = "BNUR";
$connectionOptions = [
    "Database"     => "HastaneRandevuSistemi",
    "Uid"          => "sa",
    "PWD"          => "012.1515",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("Bağlantı hatası: " . print_r(sqlsrv_errors(), true));
}

// 3) Randevu tarihini al
$sqlT = "SELECT Tarih FROM Randevu WHERE ID = ?";
$stmtT = sqlsrv_query($conn, $sqlT, [$randevuID]);
if (!$stmtT) {
    die("Tarih sorgu hatası: " . print_r(sqlsrv_errors(), true));
}
$row = sqlsrv_fetch_array($stmtT, SQLSRV_FETCH_ASSOC);
if (!$row || empty($row['Tarih'])) {
    echo "<p>Randevu bulunamadı veya tarih bilgisi eksik.</p>";
    goto SON;
}

// SQL’den gelen Tarih (DateTime) nesnesi
$randevuSqlTarihi = $row['Tarih'];  // bu bir DateTime
// Bunu 'Y-m-d' formatında alıp yeni DateTime oluştur
$randevuTarihi = DateTime::createFromFormat(
    'Y-m-d',
    $randevuSqlTarihi->format('Y-m-d'),
    new DateTimeZone('Europe/Istanbul')
);

// 4) Son iptal zamanı = randevuTarihi -1 gün, saat 20:00
$sonIptal = clone $randevuTarihi;
$sonIptal->modify('-1 day')->setTime(20, 0, 0);

// 5) Şu anki zaman
$now = new DateTime('now', new DateTimeZone('Europe/Istanbul'));

// 6) Eğer şimdi >= sonIptal ise iptal zamanı dolmuş demektir
if ($now >= $sonIptal) {
    echo "<p>Üzgünüz, randevu iptal süreniz dolmuştur.</p>";
    echo "<p>Son iptal saati: " . $sonIptal->format('d.m.Y H:i') . "</p>";
    goto SON;
}

// 7) Yetki ve durum kontrolü:  
//    - Randevu aktif olmalı  
//    - Ebeveyn kendi TC'sine veya Yetkili tablosuna kayıtlıysa iptal edebilir  

$checkSQL = "
SELECT 1 
FROM Randevu
WHERE ID = ?
  AND Durum = 'Aktif'
  AND (
      Hasta_TC = ? 
      OR EXISTS (
          SELECT 1 
          FROM Yetkili 
          WHERE Hasta_TC = ? AND Cocuk_TC = Randevu.Cocuk_TC
      )
  )";
$params = [$randevuID, $ebeveynTC, $ebeveynTC];
$checkStmt = sqlsrv_query($conn, $checkSQL, $params);

if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
    // iptal et
    $upd = sqlsrv_query(
        $conn,
        "UPDATE Randevu SET Durum = 'İptal' WHERE ID = ?",
        [$randevuID]
    );
    if ($upd) {
        echo "<p>Randevunuz başarıyla iptal edildi.</p>";
    } else {
        echo "<p>İptal sırasında hata oluştu:</p>";
        print_r(sqlsrv_errors(), true);
    }
} else {
    echo "<p>Bu randevuyu iptal etme yetkiniz yok veya zaten iptal edilmiş.</p>";
}

SON:
?>
<form action="cocuk_anasayfa.php" method="post">
    <input type="hidden" name="cocuk_tc" value="<?php echo htmlspecialchars($cocukTC); ?>">
    <button type="submit">⬅ Çocuğun Anasayfasına Dön</button>
</form>

<?php
sqlsrv_close($conn);
?>