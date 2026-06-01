<?php
// dashboard/edit.php
require_once '../auth/auth_check.php';
check_login();
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data tugas dan pastikan milik user yang login (Isolasi Keamanan)
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
    die("Error: Tugas tidak ditemukan atau Anda tidak memiliki akses ke tugas ini.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $deadline = $_POST['deadline'] ?: null;

    if (empty($title)) {
        $error = 'Judul tugas wajib diisi!';
    } else {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, deadline = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$title, $description, $priority, $status, $deadline, $task_id, $user_id])) {
            header("Location: index.php");
            exit;
        } else {
            $error = 'Terjadi kesalahan sistem saat memperbarui tugas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas - To-Do App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container form-container">
        <div class="form-card">
            <h2>✏️ Edit Tugas</h2>
            <p class="subtitle">Sesuaikan progress atau detail tugas Anda</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="edit.php?id=<?= $task_id ?>" method="POST">
                <div class="form-group">
                    <label for="title">Judul Tugas <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required value="<?= safe($task['title']) ?>">
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"><?= safe($task['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="priority">Prioritas</label>
                    <select id="priority" name="priority">
                        <option value="Rendah" <?= $task['priority'] === 'Rendah' ? 'selected' : '' ?>>Rendah</option>
                        <option value="Sedang" <?= $task['priority'] === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                        <option value="Tinggi" <?= $task['priority'] === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status Tugas</label>
                    <select id="status" name="status">
                        <option value="Belum Selesai" <?= $task['status'] === 'Belum Selesai' ? 'selected' : '' ?>>Belum Selesai</option>
                        <option value="Selesai" <?= $task['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline" value="<?= $task['deadline'] ?>">
                </div>
                <div class="form-buttons">
                    <a href="index.php" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
