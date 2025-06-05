<?php
session_start();
if (!isset($_SESSION['tc']) || !isset($_SESSION['cocuk_tc']) || $_SESSION['rol'] !== 'hasta') {
    header("Location: login.html");
    exit;
}

$ebeveynTC = $_SESSION['tc'];
$cocukTC = $_SESSION['cocuk_tc'];

$hastaneID = $_POST['hastane'];
$bolumID = $_POST['bolum'];
$doktorID = $_POST['doktor'];
$tarihRaw = $_POST['tarih'];
$saatRaw = $_POST['saat'];

if (!$hastaneID || !$bolumID || !$doktorID || !$tarihRaw || !$saatRaw) {
    echo "Tüm alanlar doldurulmalıdır.";
    exit;
}

// Tarih ve saat düzenleme
$tarih = date('Y-m-d', strtotime(str_replace('.', '-', $tarihRaw)));
$saat  = date('H:i:s', strtotime($saatRaw));

$bugun = date('Y-m-d');
$zaman = date('H:i:s');

// 🔴 Geçmiş tarihe randevu kontrolü
if ($tarih < $bugun || ($tarih == $bugun && $saat < $zaman)) {
    echo "Geçmiş bir tarihe/saate randevu alınamaz.<br>";
    echo '<a href="cocuk_randevu_sayfa.php">Geri Dön</a>';
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

// 🔴 Çocuğun cinsiyeti erkekse ve Kadın Doğum (ID=19) seçildiyse randevu reddedilir
if ($bolumID == 19) {
    $sqlCinsiyet = "SELECT Cinsiyet FROM Cocuklar WHERE TC = ?";
    $stmtC = sqlsrv_query($conn, $sqlCinsiyet, [$cocukTC]);
    $row = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC);
    if ($row && $row['Cinsiyet'] === 'Erkek') {
        echo "Erkek çocuklar Kadın Hastalıkları ve Doğum bölümüne randevu alamaz.<br>";
        echo '<a href="cocuk_randevu_sayfa.php">Geri Dön</a>';
        exit;
    }
}

// ✅ Randevu alma işlemi (stored procedure çağrısı)
$sql = "{CALL RandevuAl(?, ?, ?, ?, ?, ?, ?)}";
$params = [$ebeveynTC, $cocukTC, $hastaneID, $bolumID, $doktorID, $tarih, $saat];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    //echo "<p>Bir hata oluştu:</p>";
    foreach (sqlsrv_errors() as $error) {
        echo "Hata: " . $error['message'] . "<br>";
    }
} else {
    echo "<p>Çocuğunuz için randevu başarıyla oluşturuldu.</p>";
}
?>

<form action="cocuk_anasayfa.php" method="post">
    <input type="hidden" name="cocuk_tc" value="<?php echo $cocukTC; ?>">
    <button type="submit">⬅ Çocuğun Anasayfasına Dön</button>
</form>

<?php sqlsrv_close($conn); ?>