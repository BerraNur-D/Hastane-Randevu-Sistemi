<?php
session_start();

$tc = $_POST['tc'];
$sifre = $_POST['sifre'];

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

// Sadece Hastalar tablosundan kontrol edilir
$sql = "SELECT * FROM Hastalar WHERE TC = ? AND Sifre = ?";
$stmt = sqlsrv_query($conn, $sql, array($tc, $sifre));

if ($stmt && sqlsrv_has_rows($stmt)) {
    $_SESSION['tc'] = $tc;
    $_SESSION['rol'] = 'hasta';
    header("Location: hasta_anasayfa.php");
    exit;
}

echo "Hatalı TC veya şifre! Sadece yetişkin hastalar giriş yapabilir.";
sqlsrv_close($conn);
?>