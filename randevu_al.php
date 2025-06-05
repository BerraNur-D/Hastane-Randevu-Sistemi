<?php
session_start();
if (!isset($_SESSION['tc'])) {
    header("Location: login.html");
    exit;
}

$serverName = "BNUR";
$connectionOptions = array(
    "Database" => "HastaneRandevuSistemi",
    "Uid" => "sa",
    "PWD" => "012.1515",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("Veritabanı bağlantı hatası.");
}

$hastaTC = $_SESSION['tc'];
$hastaneID = $_POST['hastane'] ?? null;
$bolumID   = $_POST['bolum'] ?? null;
$doktorID  = $_POST['doktor'] ?? null;
$tarihRaw  = $_POST['tarih'] ?? null;
$saatRaw   = $_POST['saat'] ?? null;
$cocukTC   = $_POST['cocuk_tc'] ?? null;

if (!$hastaneID || !$bolumID || !$doktorID || !$tarihRaw || !$saatRaw) {
    echo "Tüm alanlar doldurulmalıdır.";
    exit;
}

$tarih = date('Y-m-d', strtotime(str_replace('.', '-', $tarihRaw)));
$saat  = date('H:i:s', strtotime($saatRaw));

// ✅ 1. GEÇMİŞ TARİH kontrolü
$bugun = date('Y-m-d');
$zaman = date('H:i:s');
if ($tarih < $bugun || ($tarih == $bugun && $saat < $zaman)) {
    echo "Geçmiş bir tarihe/saate randevu alınamaz.";
    echo '<br><a href="randevu_al_sayfa.php">Geri Dön</a>';
    exit;
}

// ✅ 2. Cinsiyet kontrolü (erkekler kadın doğuma gidemesin)
if (is_null($cocukTC) && $bolumID == 19) {
    $sqlCinsiyet = "SELECT Cinsiyet FROM Hastalar WHERE TC = ?";
    $stmtC = sqlsrv_query($conn, $sqlCinsiyet, [$hastaTC]);
    $row = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC);
    if ($row && $row['Cinsiyet'] == 'Erkek') {
        echo "Erkek hastalar Kadın Hastalıkları ve Doğum bölümüne randevu alamaz.";
        echo '<br><a href="randevu_al_sayfa.php">Geri Dön</a>';
        exit;
    }
}

// 🔁 Stored procedure çağrısı (çocuk için mi ebeveyn için mi?)
$sql = "{CALL RandevuAl(?, ?, ?, ?, ?, ?, ?)}";
$params = [
    $hastaTC,
    $cocukTC, // NULL olabilir
    $hastaneID,
    $bolumID,
    $doktorID,
    $tarih,
    $saat
];

$stmt = sqlsrv_query($conn, $sql, $params);

// ❗ Hata kontrolü
if ($stmt === false) {
    foreach (sqlsrv_errors() as $error) {
        echo "Hata: " . $error['message'] . "<br>";
    }
    echo '<br><a href="randevu_al_sayfa.php">Geri Dön</a>';
    exit;
}

// ✅ Başarı
while (sqlsrv_next_result($stmt)) { } // tüm mesajları geç

echo "Randevu başarıyla alındı veya ilgili işlem yapıldı.";
echo '<br><a href="hasta_anasayfa.php">Ana Sayfaya Dön</a>';

sqlsrv_close($conn);
?>