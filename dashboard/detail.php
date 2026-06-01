<?php
// dashboard/detail.php
require_once '../auth/auth_check.php';
check_login();
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Mengambil detail tugas & memastikan isolasi hak akses
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
    die("Akses ditolak atau tugas tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tugas - To-Do App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container form-container">
        <div class="form-card detail-card">
            <span class="badge-priority priority-<?= strtolower(safe($task['priority'])) ?>">
                Prioritas: <?= safe($task['priority']) ?>
            </span>
            <span class="badge-status <?= $task['status'] === 'Selesai' ? 'status-done' : 'status-pending' ?>">
                <?= safe($task['status']) ?>
            </span>
            
            <h1 class="detail-title"><?= safe($task['title']) ?></h1>
            <p class="detail-time">Dibuat pada: <?= date('d M Y, H:i', strtotime($task['created_at'])) ?></p>
            <p class="detail-time">Update terakhir: <?= date('d M Y, H:i', strtotime($task['updated_at'])) ?></p>

            <hr class="divider">
            
            <div class="detail-content">
                <h3>Deskripsi Tugas:</h3>
                <p class="desc-text"><?= $task['description'] ? nl2br(safe($task['description'])) : '<em>Tidak ada deskripsi.</em>' ?></p>
            </div>

            <div class="detail-content">
                <h3>Batas Waktu (Deadline):</h3>
                <p class="deadline-text">
                    📅 <?= $task['deadline'] ? date('l, d F Y', strtotime($task['deadline'])) : 'Tanpa batas waktu (santai)' ?>
                </p>
            </div>

            <div class="form-buttons" style="margin-top: 30px;">
                <a href="index.php" class="btn btn-light">&larr; Kembali ke Dashboard</a>
                <a href="edit.php?id=<?= $task['id'] ?>" class="btn btn-primary">Edit Tugas</a>
            </div>
        </div>
    </div>
</body>
</html>
