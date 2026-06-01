<?php
// dashboard/index.php
require_once '../auth/auth_check.php';
check_login();
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$profile_image = $_SESSION['profile_image'];

// --- Fitur Tambahan: Parameter Search & Filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';

// --- Fitur Tambahan: Pagination ---
$limit = 6; // Jumlah card per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Query Hitung Statistik User (Isolasi Data Aman) ---
$stat_stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'Belum Selesai' THEN 1 ELSE 0 END) as belum_selesai
    FROM tasks WHERE user_id = ?");
$stat_stmt->execute([$user_id]);
$stats = $stat_stmt->fetch();

$total_tasks = $stats['total'] ?? 0;
$completed_tasks = $stats['selesai'] ?? 0;
$pending_tasks = $stats['belum_selesai'] ?? 0;

// Hitung persentase progress tugas
$progress_percent = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// --- Fitur Tambahan: Notifikasi Deadline Hari Ini ---
$today = date('Y-m-d');
$deadline_stmt = $pdo->prepare("SELECT COUNT(*) as urgent FROM tasks WHERE user_id = ? AND deadline = ? AND status = 'Belum Selesai'");
$deadline_stmt->execute([$user_id, $today]);
$urgent_count = $deadline_stmt->fetch()['urgent'] ?? 0;

// --- Membangun Query Utama Tasks dengan Filter ---
$query_str = "SELECT * FROM tasks WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if ($search !== '') {
    $query_str .= " AND (title LIKE :search OR description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($filter_status !== '') {
    $query_str .= " AND status = :status";
    $params[':status'] = $filter_status;
}
if ($filter_priority !== '') {
    $query_str .= " AND priority = :priority";
    $params[':priority'] = $filter_priority;
}

// Hitung total data untuk pagination setelah difilter
$count_query = str_replace("SELECT *", "SELECT COUNT(*)", $query_str);
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_filtered = $count_stmt->fetchColumn();
$total_pages = ceil($total_filtered / $limit);

// Tambahkan limit dan offset
$query_str .= " ORDER BY deadline ASC, id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query_str);

// Bind params khusus untuk limit & offset karena harus integer
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
if ($search !== '') $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
if ($filter_status !== '') $stmt->bindValue(':status', $filter_status, PDO::PARAM_STR);
if ($filter_priority !== '') $stmt->bindValue(':priority', $filter_priority, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - To-Do App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Top Navbar -->
    <header class="navbar">
        <div class="nav-brand">📋 ToDoApp</div>
        <div class="nav-user">
            <span class="user-name">Halo, <strong><?= safe($username) ?></strong></span>
            <img src="../assets/images/<?= safe($profile_image) ?>" alt="Profil" class="user-avatar" onerror="this.src='../assets/images/default.png'">
            <a href="../auth/logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <div class="container main-dashboard">
        <!-- Notifikasi Urgent -->
        <?php if ($urgent_count > 0): ?>
            <div class="alert alert-warning animate-bounce">
                ⚠️ Kamu memiliki <strong><?= $urgent_count ?> tugas penting</strong> yang jatuh tempo HARI INI! Segera selesaikan.
            </div>
        <?php endif; ?>

        <!-- Grid Statistik Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Tugas</h3>
                <p class="stat-num"><?= $total_tasks ?></p>
            </div>
            <div class="stat-card completed">
                <h3>Tugas Selesai</h3>
                <p class="stat-num"><?= $completed_tasks ?></p>
            </div>
            <div class="stat-card pending">
                <h3>Tugas Pending</h3>
                <p class="stat-num"><?= $pending_tasks ?></p>
            </div>
        </div>

        <!-- Progress Bar Tugas -->
        <div class="progress-section">
            <div class="progress-info">
                <span>Progress Penyelesaian Tugas</span>
                <span><?= $progress_percent ?>%</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?= $progress_percent ?>%"></div>
            </div>
        </div>

        <!-- Bar Pencarian, Filter & Aksi -->
        <div class="action-bar">
            <form method="GET" action="index.php" class="filter-form">
                <input type="text" name="search" placeholder="Cari tugas..." value="<?= safe($search) ?>" class="input-search">
                
                <select name="status" class="select-filter">
                    <option value="">-- Semua Status --</option>
                    <option value="Belum Selesai" <?= $filter_status === 'Belum Selesai' ? 'selected' : '' ?>>Belum Selesai</option>
                    <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>

                <select name="priority" class="select-filter">
                    <option value="">-- Semua Prioritas --</option>
                    <option value="Rendah" <?= $filter_priority === 'Rendah' ? 'selected' : '' ?>>Rendah</option>
                    <option value="Sedang" <?= $filter_priority === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                    <option value="Tinggi" <?= $filter_priority === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
                </select>

                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search !== '' || $filter_status !== '' || $filter_priority !== ''): ?>
                    <a href="index.php" class="btn btn-light">Reset</a>
                <?php endif; ?>
            </form>
            <a href="create.php" class="btn btn-primary btn-add">+ Tambah Tugas Baru</a>
        </div>

        <!-- Render List Cards Tugas -->
        <div class="task-grid">
            <?php if (count($tasks) > 0): ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card <?= $task['status'] === 'Selesai' ? 'task-done' : '' ?>">
                        <div class="task-header">
                            <span class="badge-priority priority-<?= strtolower(safe($task['priority'])) ?>">
                                <?= safe($task['priority']) ?>
                            </span>
                            <span class="badge-status <?= $task['status'] === 'Selesai' ? 'status-done' : 'status-pending' ?>">
                                <?= safe($task['status']) ?>
                            </span>
                        </div>
                        <h3 class="task-title"><?= safe($task['title']) ?></h3>
                        <p class="task-desc"><?= nl2br(safe($task['description'])) ?></p>
                        
                        <div class="task-footer">
                            <div class="task-deadline">
                                📅 <?= $task['deadline'] ? date('d M Y', strtotime($task['deadline'])) : 'Tanpa Deadline' ?>
                            </div>
                            <div class="task-actions">
                                <a href="detail.php?id=<?= $task['id'] ?>" class="action-btn view-btn" title="Detail">👁️</a>
                                <a href="edit.php?id=<?= $task['id'] ?>" class="action-btn edit-btn" title="Edit">✏️</a>
                                <a href="delete.php?id=<?= $task['id'] ?>" class="action-btn delete-btn" title="Hapus" onclick="return confirmDelete(event, '<?= safe($task['title']) ?>')">❌</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p class="empty-emoji">🏝️</p>
                    <p>Tidak ada tugas yang ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Render Kontrol Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="index.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&priority=<?= urlencode($filter_priority) ?>" class="page-link">&laquo; Sebelum</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="index.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&priority=<?= urlencode($filter_priority) ?>" class="page-link <?= $page === $i ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="index.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&priority=<?= urlencode($filter_priority) ?>" class="page-link">Berikut &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tombol Dark Mode floating -->
    <button id="darkModeToggle" class="dark-mode-toggle" title="Aktifkan Mode Gelap">🌓</button>

    <script src="../assets/js/script.js"></script>
</body>
</html>
