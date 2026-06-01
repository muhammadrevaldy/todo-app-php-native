<?php
// auth/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi pembantu untuk mencegah XSS
if (!function_exists('safe')) {
    function safe($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Cek apakah pengguna sudah login
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}
?>
