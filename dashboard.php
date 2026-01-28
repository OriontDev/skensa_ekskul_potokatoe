<?php
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK: Must be logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["nama"];
$user_role = $_SESSION["role"];
$my_ekskul = [];

try {
    if ($user_role === 'guru' || $user_role === 'pengurus') {
        $sql = "SELECT e.*, 
                string_agg(j.day || ' (' || TO_CHAR(j.start_time, 'HH24:MI') || '-' || TO_CHAR(j.end_time, 'HH24:MI') || ')', ', ' ORDER BY j.day) as jadwal_gabungan
                FROM ekskul e
                LEFT JOIN jadwal_ekskul j ON e.id = j.id_ekskul
                WHERE e.teacher_id = :uid
                GROUP BY e.id
                ORDER BY e.nama ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $my_ekskul = $stmt->fetchAll();
    } else {
        $sql = "SELECT e.*, p.kelas, p.tanggal_daftar,
                string_agg(j.day || ' (' || TO_CHAR(j.start_time, 'HH24:MI') || '-' || TO_CHAR(j.end_time, 'HH24:MI') || ')', ', ' ORDER BY j.day) as jadwal_gabungan
                FROM ekskul e
                JOIN pendaftaran p ON e.id = p.id_ekskul
                LEFT JOIN jadwal_ekskul j ON e.id = j.id_ekskul
                WHERE p.id_user = :uid
                GROUP BY e.id, p.kelas, p.tanggal_daftar
                ORDER BY e.nama ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $my_ekskul = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ekskul Skensa</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboardStyle.css">
    <style>
        .remove-schedule { color: #f56565; cursor: pointer; font-size: 0.8rem; font-weight: bold; margin-bottom: 10px; display: inline-block; }
        .btn-add-schedule { background: #edf2f7; border: 1px dashed #cbd5e0; width: 100%; padding: 8px; border-radius: 6px; cursor: pointer; color: #4a5568; margin-bottom: 20px; font-size: 0.85rem; transition: 0.2s; }
        .btn-add-schedule:hover { background: #e2e8f0; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">Ekskul Skensa</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><span class="menu-icon">🏠</span> <span class="menu-text">Home</span></a></li>
        <li><a href="dashboard.php" class="active"><span class="menu-icon">📊</span> <span class="menu-text">Dashboard</span></a></li>
        <li><a href="settings.php"><span class="menu-icon">⚙️</span> <span class="menu-text">Settings</span></a></li>
        <li><a href="help.php"><span class="menu-icon">❓</span> <span class="menu-text">Bantuan</span></a></li>
    </ul>
</aside>

<div class="main-wrapper">
    <header class="header">
        <div class="page-title">
            <h2>Dashboard <?php echo ucfirst($user_role); ?></h2>
        </div>
        <div class="auth-links">
            <span class="user-info">Halo, <b><?php echo htmlspecialchars($user_name); ?></b></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="content-area">
        <section class="stats-overview">
            <div class="stat-card">
                <h3>Total Ekskul Anda</h3>
                <p class="stat-number"><?php echo count($my_ekskul); ?></p>
            </div>
            <?php if ($user_role === 'pengurus'): ?>
            <button class="btn-create" onclick="openModal()">+ Tambah Ekskul Baru</button>
            <?php endif; ?>
        </section>

        <h3 class="section-subtitle"><?php echo ($user_role === 'student') ? "Ekskul yang Saya Ikuti" : "Ekskul yang Saya Kelola"; ?></h3>

        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="table-container">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Ekskul</th>
                        <th>Jadwal</th>
                        <?php if($user_role === 'student'): ?><th>Kelas</th><th>Tgl Daftar</th><?php endif; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_ekskul as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['nama']); ?></strong></td>
                            <td><?php echo $item['jadwal_gabungan'] ?: '<em>Belum diatur</em>'; ?></td>
                            <?php if($user_role === 'student'): ?>
                                <td><?php echo htmlspecialchars($item['kelas']); ?></td>
                                <td><?php echo date('d M Y', strtotime($item['tanggal_daftar'])); ?></td>
                            <?php endif; ?>
                            <td><a href="detail_ekskul.php?id=<?php echo $item['id']; ?>" class="btn-view">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div id="createModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Buat Ekskul Baru</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form action="proses_tambah_ekskul.php" method="POST">
                <div class="form-group">
                    <label>Nama Ekskul</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" required rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>URL Thumbnail</label>
                    <input type="text" name="thumbnail">
                </div>
                
                <hr style="border: 0; border-top: 1px solid #edf2f7; margin: 20px 0;">
                <h4 style="margin-bottom: 10px; color: #2d3748;">Pengaturan Jadwal</h4>
                
                <div id="schedule-container">
                    <div class="schedule-row">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Hari</label>
                                <select name="day[]" required>
                                    <option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Mulai</label><input type="time" name="start_time[]" required></div>
                            <div class="form-group"><label>Selesai</label><input type="time" name="end_time[]" required></div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-add-schedule" onclick="addScheduleRow()">+ Tambah Hari Lain</button>
                <button type="submit" class="btn-submit">Simpan Ekskul</button>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal() { document.getElementById('createModal').style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function closeModal() { document.getElementById('createModal').style.display = 'none'; document.body.style.overflow = 'auto'; }
    
    function addScheduleRow() {
        const container = document.getElementById('schedule-container');
        const newRow = document.createElement('div');
        newRow.className = 'schedule-row';
        newRow.innerHTML = `
            <span class="remove-schedule" onclick="this.parentElement.remove()">- Hapus Jadwal</span>
            <div class="form-row">
                <div class="form-group">
                    <select name="day[]" required>
                        <option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option>
                    </select>
                </div>
                <div class="form-group"><input type="time" name="start_time[]" required></div>
                <div class="form-group"><input type="time" name="end_time[]" required></div>
            </div>
        `;
        container.appendChild(newRow);
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('createModal')) closeModal();
    }
</script>

</body>
</html>