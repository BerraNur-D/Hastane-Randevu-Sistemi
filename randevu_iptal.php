<?php
session_start();
if (!isset($_SESSION['tc']) || $_SESSION['rol'] !== 'hasta') {
    header("Location: login.html");
    exit;
}

$hastaTC  = $_SESSION['tc'];
$randevuID = $_POST['randevu_id'] ?? null;
if (!$randevuID) {
    echo "<p>Randevu ID eksik.</p>";
    exit;
}

// Türkiye saat dilimi
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantısı
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

// 1) Randevu tarihini getir
$sqlT = "SELECT Tarih FROM Randevu WHERE ID = ?";
$stmtT = sqlsrv_query($conn, $sqlT, [$randevuID]);
if (!$stmtT) {
    die("Tarih sorgu hatası: " . print_r(sqlsrv_errors(), true));
}
$row = sqlsrv_fetch_array($stmtT, SQLSRV_FETCH_ASSOC);
if (!$row || !($row['Tarih'] instanceof DateTime)) {
    echo "<p>Randevu bulunamadı veya tarih bilgisi eksik.</p>";
    sqlsrv_close($conn);
    exit;
}

/**  
 * $row['Tarih'] SQLSRV’dan gelen DateTime objesi  
 * Örn: 2025-05-21 00:00:00  
 */
$randevuSqlTarihi = $row['Tarih'];

// 2) Son iptal zamanı = “randevu tarihinde bir gün önce saat 20:00”
$sonIptal = clone $randevuSqlTarihi;
$sonIptal->modify('-1 day')->setTime(20, 0, 0);

// 3) Şimdiki zaman
$now = new DateTime('now', new DateTimeZone('Europe/Istanbul'));

// 4) Eğer şu an >= sonIptal ise iptal süresi dolmuştur
if ($now >= $sonIptal) {
    echo "<p>Üzgünüz, randevu iptal süreniz dolmuştur.</p>";
    echo "<p>(Son iptal: " . $sonIptal->format('d.m.Y H:i') . ")</p>";
    echo "<a href='hasta_anasayfa.php'>⬅ Ana Sayfaya Dön</a>";
    sqlsrv_close($conn);
    exit;
}

// 5) Yetki ve durum kontrolü
$checkSQL = "
SELECT 1
FROM Randevu
WHERE ID = ?
  AND Durum = 'Aktif'
  AND (
       Hasta_TC = ?
    OR EXISTS (
         SELECT 1 FROM Yetkili
         WHERE Hasta_TC = ? AND Cocuk_TC = Randevu.Cocuk_TC
      )
  )";
$params = [$randevuID, $hastaTC, $hastaTC];
$checkStmt = sqlsrv_query($conn, $checkSQL, $params);

if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
    // 6) İptal et
    $upd = sqlsrv_query(
        $conn,
        "UPDATE Randevu SET Durum = 'İptal' WHERE ID = ?",
        [$randevuID]
    );
    if ($upd) {
        echo "<p>Randevu başarıyla iptal edildi.</p>";
    } else {
        echo "<p>İptal sırasında hata oluştu:</p>";
        print_r(sqlsrv_errors(), true);
    }
} else {
    echo "<p>Bu randevuyu iptal etme yetkiniz yok veya zaten iptal edilmiş.</p>";
}

echo "<a href='hasta_anasayfa.php'>⬅ Ana Sayfaya Dön</a>";
sqlsrv_close($conn);
?>