<?php
// dashboard/delete.php
require_once '../auth/auth_check.php';
check_login();
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($task_id > 0) {
    // Validasi kepemilikan data sebelum di-delete
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
}

header("Location: index.php");
exit;
?>
