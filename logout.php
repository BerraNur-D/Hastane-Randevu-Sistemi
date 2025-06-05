<?php
session_start();

// Eğer çocuk oturumu varsa, sadece onu sonlandır ve hastaya geri dön
if (isset($_SESSION['cocuk_tc'])) {
    unset($_SESSION['cocuk_tc']); // Çocuk oturumu kapatılır
    header("Location: hasta_anasayfa.php"); // Ebeveynin paneline yönlendirilir
    exit;
}

// Eğer hasta çıkış yapıyorsa, tüm oturum sonlandırılır
session_unset();     // Oturumdaki tüm veriler silinir
session_destroy();   // Oturum tamamen sonlanır

header("Location: login.html"); // Giriş sayfasına yönlendirilir
exit;
?>