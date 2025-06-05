<?php
session_start();
if (!isset($_SESSION['tc']) || $_SESSION['rol'] !== 'hasta') {
    header("Location: login.html");
    exit;
}

// 1. POST varsa çocuk_tc'yi al ve oturuma kaydet
if (isset($_POST['cocuk_tc'])) {
    $_SESSION['cocuk_tc'] = $_POST['cocuk_tc'];
}

// 2. Oturumda çocuk_tc yoksa erişimi engelle
if (!isset($_SESSION['cocuk_tc'])) {
    echo "Geçersiz erişim.";
    exit;
}

$cocukTC = $_SESSION['cocuk_tc'];

$serverName = "BNUR";
$connectionOptions = array(
    "Database" => "HastaneRandevuSistemi",
    "Uid" => "sa",
    "PWD" => "012.1515",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("Veritabanı bağlantı hatası: " . print_r(sqlsrv_errors(), true));
}

// Çocuğun bilgilerini al
$sqlChild = "SELECT Ad, Soyad FROM Cocuklar WHERE TC = ?";
$stmtChild = sqlsrv_query($conn, $sqlChild, array($cocukTC));
$child = sqlsrv_fetch_array($stmtChild, SQLSRV_FETCH_ASSOC);

// Çocuğun randevularını al
$sqlRandevu = "SELECT * FROM TumRandevular WHERE Cocuk_TC = ? ORDER BY Tarih DESC";
$randevuStmt = sqlsrv_query($conn, $sqlRandevu, array($cocukTC));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Çocuk Ana Sayfası</title>
</head>
<body>
  <h2><?php echo $child['Ad'] . ' ' . $child['Soyad']; ?> için Randevu Paneli</h2>

  <form action="cocuk_randevu_sayfa.php" method="get">
    <button type="submit">Randevu Al</button>
  </form>

  <form action="cocuk_randevu_iptal.php" method="post">
    <label>Randevu ID:</label>
    <input type="number" name="randevu_id" required>
    <button type="submit">Randevu İptal Et</button>
  </form>

  <form action="hasta_anasayfa.php" method="get">
    <button type="submit">⬅ Hastaya Geri Dön</button>
  </form>

  <h3>Randevular</h3>
  <table border="1">
    <tr>
      <th>ID</th>
      <th>Tarih</th>
      <th>Saat</th>
      <th>Durum</th>
      <th>Doktor</th>
      <th>Bölüm</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($randevuStmt, SQLSRV_FETCH_ASSOC)) { ?>
      <tr>
        <td><?php echo $row['RandevuID']; ?></td>
        <td><?php echo $row['Tarih']->format('Y-m-d'); ?></td>
        <td><?php echo $row['Saat']->format('H:i'); ?></td>
        <td><?php echo $row['Durum']; ?></td>
        <td><?php echo $row['DoktorAdSoyad']; ?></td>
        <td><?php echo $row['Bolum']; ?></td>
      </tr>
    <?php } ?>
  </table>
</body>
</html>

<?php sqlsrv_close($conn); ?>