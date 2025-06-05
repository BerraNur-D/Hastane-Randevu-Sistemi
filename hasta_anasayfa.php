<?php
session_start();
if (!isset($_SESSION['tc']) || $_SESSION['rol'] !== 'hasta') {
    header("Location: login.html");
    exit;
}

$hastaTC = $_SESSION['tc'];
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

// Randevuları al
$randevuSorgu = "SELECT * FROM TumRandevular WHERE Hasta_TC = ? OR Cocuk_TC IN (
    SELECT Cocuk_TC FROM Yetkili WHERE Hasta_TC = ?
) ORDER BY Tarih DESC";
$randevuStmt = sqlsrv_query($conn, $randevuSorgu, array($hastaTC, $hastaTC));

// Yetkili olduğu çocuk var mı?
$cocukSorgu = "SELECT Cocuk_TC FROM Yetkili WHERE Hasta_TC = ?";
$cocukStmt = sqlsrv_query($conn, $cocukSorgu, array($hastaTC));
$hasCocuk = sqlsrv_has_rows($cocukStmt);

$adSorgu = "SELECT Ad, Soyad FROM Hastalar WHERE TC = ?";
$adStmt = sqlsrv_query($conn, $adSorgu,array($hastaTC));
$hastaAdSoyad = "Bilinmiyor";

if ($adStmt && $row = sqlsrv_fetch_array($adStmt, SQLSRV_FETCH_ASSOC)) {
    $hastaAdSoyad = $row['Ad'] . ' ' . $row['Soyad'];
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Hasta Ana Sayfa</title>
</head>
<body>
  <h2>Hoş Geldiniz : <?php echo htmlspecialchars($hastaAdSoyad); ?></h2>

  <form action="randevu_al_sayfa.php" method="get">
    <button type="submit">Randevu Al</button>
  </form>

  <form action="randevu_iptal.php" method="post">
    <label>Randevu ID:</label>
    <input type="number" name="randevu_id" required>
    <button type="submit">Randevu İptal Et</button>
  </form>

  <?php if ($hasCocuk): ?>
    <form action="cocuk_anasayfa.php" method="post">
      <label>Yetkili Olduğunuz Çocuk TC:</label>
      <select name="cocuk_tc">
        <?php while ($row = sqlsrv_fetch_array($cocukStmt, SQLSRV_FETCH_ASSOC)) {
            echo "<option value='" . $row['Cocuk_TC'] . "'>" . $row['Cocuk_TC'] . "</option>";
        } ?>
      </select>
      <button type="submit">Yetkili Olduklarım</button>
    </form>
  <?php endif; ?>

  <form action="logout.php" method="post">
    <button type="submit">Çıkış Yap</button>
  </form>

  <h3>Randevularım</h3>
  <table border="1">
    <tr>
      <th>ID</th>
      <th>Tarih</th>
      <th>Saat</th>
      <th>Durum</th>
      <th>Hasta</th>
      <th>Doktor</th>
      <th>Bölüm</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($randevuStmt, SQLSRV_FETCH_ASSOC)) { ?>
      <tr>
        <td><?php echo $row['RandevuID']; ?></td>
        <td><?php echo $row['Tarih']->format('Y-m-d'); ?></td>
        <td><?php echo $row['Saat']->format('H:i'); ?></td>
        <td><?php echo $row['Durum']; ?></td>
        <td><?php echo $row['HastaAdSoyad']; ?></td>
        <td><?php echo $row['DoktorAdSoyad']; ?></td>
        <td><?php echo $row['Bolum']; ?></td>
      </tr>
    <?php } ?>
  </table>
</body>
</html>

<?php sqlsrv_close($conn); ?>