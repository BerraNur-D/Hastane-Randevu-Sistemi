<?php
// cocuk_randevu_sayfa.php
session_start();
if (!isset($_SESSION['tc']) || !isset($_SESSION['cocuk_tc']) || $_SESSION['rol'] !== 'hasta') {
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

// Seçimler
$secilenIl = $_POST['il'] ?? '';
$secilenIlce = $_POST['ilce'] ?? '';
$secilenHastane = $_POST['hastane'] ?? '';
$secilenBolum = $_POST['bolum'] ?? '';

function getOptions($conn, $query, $params = []) {
    $stmt = sqlsrv_query($conn, $query, $params);
    $list = [];
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $list[] = $row;
        }
    }
    return $list;
}

$iller = getOptions($conn, "SELECT DISTINCT Il FROM Hastane");
$ilceler = $secilenIl ? getOptions($conn, "SELECT DISTINCT Ilce FROM Hastane WHERE Il = ?", [$secilenIl]) : [];
$hastaneler = $secilenIlce ? getOptions($conn, "SELECT ID, Ad FROM Hastane WHERE Ilce = ?", [$secilenIlce]) : [];
$bolumler = $secilenHastane ? getOptions($conn, "SELECT B.ID, B.Ad FROM Bolum B JOIN Icerir I ON B.ID = I.Bolum_ID WHERE I.Hastane_ID = ?", [$secilenHastane]) : [];
$doktorlar = ($secilenBolum && $secilenHastane) ? getOptions($conn, "SELECT ID, Ad + ' ' + Soyad AS AdSoyad FROM Doktorlar WHERE Bolum_ID = ? AND Hastane_ID = ?", [$secilenBolum, $secilenHastane]) : [];
?><!DOCTYPE html><html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çocuğun İçin Randevu Al</title>
</head>
<body>
    <h2>Çocuğun İçin Randevu Al</h2>
    <form method="post" action="">
        <label>İl:</label>
        <select name="il" onchange="this.form.submit()">
            <option value="">Seçiniz</option>
            <?php foreach ($iller as $il) {
                $selected = $il['Il'] === $secilenIl ? 'selected' : '';
                echo "<option value='{$il['Il']}' $selected>{$il['Il']}</option>";
            } ?>
        </select><br><label>İlçe:</label>
    <select name="ilce" onchange="this.form.submit()">
        <option value="">Seçiniz</option>
        <?php foreach ($ilceler as $ilce) {
            $selected = $ilce['Ilce'] === $secilenIlce ? 'selected' : '';
            echo "<option value='{$ilce['Ilce']}' $selected>{$ilce['Ilce']}</option>";
        } ?>
    </select><br>

    <label>Hastane:</label>
    <select name="hastane" onchange="this.form.submit()">
        <option value="">Seçiniz</option>
        <?php foreach ($hastaneler as $h) {
            $selected = $h['ID'] == $secilenHastane ? 'selected' : '';
            echo "<option value='{$h['ID']}' $selected>{$h['Ad']}</option>";
        } ?>
    </select><br>

    <label>Bölüm:</label>
    <select name="bolum" onchange="this.form.submit()">
        <option value="">Seçiniz</option>
        <?php foreach ($bolumler as $b) {
            $selected = $b['ID'] == $secilenBolum ? 'selected' : '';
            echo "<option value='{$b['ID']}' $selected>{$b['Ad']}</option>";
        } ?>
    </select><br>

    <label>Doktor:</label>
    <select name="doktor" required>
        <option value="">Seçiniz</option>
        <?php foreach ($doktorlar as $d) {
            echo "<option value='{$d['ID']}'>{$d['AdSoyad']}</option>";
        } ?>
    </select><br>

    <label>Tarih:</label>
    <input type="date" name="tarih" required><br>

    <label>Saat:</label>
    <select name="saat" required>
        <?php
        for ($h = 8; $h <= 16; $h++) {
            foreach (["00", "30"] as $m) {
                $val = sprintf("%02d:%s:00", $h, $m);
                echo "<option value='$val'>$val</option>";
            }
        }
        ?>
    </select><br><br>

    <input type="hidden" name="cocuk_tc" value="<?php echo $_SESSION['cocuk_tc']; ?>">
    <input type="submit" formaction="cocuk_randevu.php" value="Randevu Al">
</form>

<form action="cocuk_anasayfa.php" method="post">
    <input type="hidden" name="cocuk_tc" value="<?php echo $_SESSION['cocuk_tc']; ?>">
    <button type="submit">⬅ Çocuğun Anasayfasına Dön</button>
</form>

</body>
</html>
<?php sqlsrv_close($conn); ?>