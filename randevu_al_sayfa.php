<?php
session_start();
if (!isset($_SESSION['tc']) || $_SESSION['rol'] !== 'hasta') {
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
    die(print_r(sqlsrv_errors(), true));
}

// İller
$iller = [];
$ilSorgu = sqlsrv_query($conn, "SELECT DISTINCT Il FROM Hastane");
if ($ilSorgu === false) die(print_r(sqlsrv_errors(), true));
while ($row = sqlsrv_fetch_array($ilSorgu, SQLSRV_FETCH_ASSOC)) {
    $iller[] = $row['Il'];
}

// Seçilen değerler
$secilenIl = $_POST['il'] ?? '';
$secilenIlce = $_POST['ilce'] ?? '';
$secilenHastane = $_POST['hastane'] ?? '';
$secilenBolum = $_POST['bolum'] ?? '';

// İlçeler
$ilceler = [];
if ($secilenIl) {
    $sql = "SELECT DISTINCT Ilce FROM Hastane WHERE Il = ?";
    $stmt = sqlsrv_query($conn, $sql, [$secilenIl]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $ilceler[] = $row['Ilce'];
    }
}

// Hastaneler
$hastaneler = [];
if ($secilenIlce) {
    $sql = "SELECT ID, Ad FROM Hastane WHERE Ilce = ?";
    $stmt = sqlsrv_query($conn, $sql, [$secilenIlce]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $hastaneler[] = $row;
    }
}

// Bölümler
$bolumler = [];
if ($secilenHastane) {
    $sql = "SELECT Bolum.ID, Bolum.Ad FROM Bolum, Icerir WHERE Bolum.ID = Icerir.Bolum_ID and Hastane_ID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$secilenHastane]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $bolumler[] = $row;
    }
}

// Doktorlar
$doktorlar = [];
if ($secilenBolum && $secilenHastane) {
    $sql = "SELECT ID, Ad + ' ' + Soyad AS AdSoyad FROM Doktorlar WHERE Bolum_ID = ? AND Hastane_ID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$secilenBolum, $secilenHastane]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $doktorlar[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Randevu Al</title>
</head>
<body>
    <h2>Randevu Al</h2>
    <form method="post" action="">
        <label>İl:</label>
        <select name="il" onchange="this.form.submit()">
            <option value="">Seçiniz</option>
            <?php foreach ($iller as $il) {
                $selected = $il == $secilenIl ? 'selected' : '';
                echo "<option value='$il' $selected>$il</option>";
            } ?>
        </select><br>

        <label>İlçe:</label>
        <select name="ilce" onchange="this.form.submit()">
            <option value="">Seçiniz</option>
            <?php foreach ($ilceler as $ilce) {
                $selected = $ilce == $secilenIlce ? 'selected' : '';
                echo "<option value='$ilce' $selected>$ilce</option>";
            } ?>
        </select><br>

        <label>Hastane:</label>
        <select name="hastane" onchange="this.form.submit()">
            <option value="">Seçiniz</option>
            <?php foreach ($hastaneler as $hastane) {
                $selected = $hastane['ID'] == $secilenHastane ? 'selected' : '';
                echo "<option value='{$hastane['ID']}' $selected>{$hastane['Ad']}</option>";
            } ?>
        </select><br>

        <label>Bölüm:</label>
        <select name="bolum" onchange="this.form.submit()">
            <option value="">Seçiniz</option>
            <?php foreach ($bolumler as $bolum) {
                $selected = $bolum['ID'] == $secilenBolum ? 'selected' : '';
                echo "<option value='{$bolum['ID']}' $selected>{$bolum['Ad']}</option>";
            } ?>
        </select><br>

        <label>Doktor:</label>
        <select name="doktor" required>
            <option value="">Seçiniz</option>
            <?php foreach ($doktorlar as $doktor) {
                echo "<option value='{$doktor['ID']}'>{$doktor['AdSoyad']}</option>";
            } ?>
        </select><br>

        <label>Tarih:</label>
        <input type="date" name="tarih" required><br>

        <label>Saat:</label>
        <select name="saat" required>
            <?php
            for ($hour = 8; $hour <= 16; $hour++) {
                foreach (["00", "30"] as $minute) {
                    echo "<option value='$hour:$minute:00'>$hour:$minute</option>";
                }
            }
            ?>
        </select><br><br>

        <input type="submit" formaction="randevu_al.php" value="Randevu Al">
    </form>
    <br>
    <a href="hasta_anasayfa.php">⬅ Ana Sayfaya Dön</a>
</body>
</html>

<?php sqlsrv_close($conn); ?>