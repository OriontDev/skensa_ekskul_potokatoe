<?php
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK: Hanya Guru yang bisa akses
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'guru') {
    header("location: login.php");
    exit;
}

$user_name = $_SESSION["nama"];
$user_role = $_SESSION["role"];
$kelas_target = isset($_GET['kelas']) ? $_GET['kelas'] : '';

if (empty($kelas_target)) {
    header("location: dashboard.php");
    exit;
}

$students = [];
$error = null;

try {
    // 2. QUERY UPDATE: Join ke tabel nilai_ekskul
    // Kita gunakan LEFT JOIN agar siswa yang belum punya nilai tetap muncul di list
    $sql = "SELECT 
                u.nama as nama_siswa, 
                u.nis, 
                e.nama as nama_ekskul, 
                p.created_at as tanggal_daftar,
                n.nilai as nilai_siswa
            FROM pendaftaran p
            JOIN users u ON p.id_user = u.id
            JOIN ekskul e ON p.id_ekskul = e.id
            LEFT JOIN nilai_ekskul n ON (p.id_ekskul = n.id_ekskul AND p.id_user = n.id_user)
            WHERE p.kelas = :kelas
            ORDER BY u.nama ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['kelas' => $kelas_target]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa Kelas <?php echo htmlspecialchars($kelas_target); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboardStyle.css">
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
            <h2>Daftar Siswa Kelas: <?php echo htmlspecialchars($kelas_target); ?></h2>
        </div>
        <div class="auth-links">
            <span class="user-info">Halo, <b><?php echo htmlspecialchars($user_name); ?></b></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="content-area">
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" class="btn-add-schedule" style="text-decoration: none; display: inline-block; width: auto; padding: 10px 20px;">
                &larr; Kembali ke Panel Kelas
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <h3 class="section-subtitle">Data Pendaftaran & Nilai</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Ekstrakurikuler : Nilai</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #a0aec0;">
                                Belum ada siswa dari kelas ini yang mendaftar ekskul.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['nama_siswa']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nis'] ?? '-'); ?></td>
                                <td>
                                    <span style="background: #ebf4ff; color: #4c51bf; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                        <?php 
                                            echo htmlspecialchars($row['nama_ekskul']); 
                                            echo " : ";
                                            // Cek jika nilai ada, jika tidak tampilkan strip atau "Belum Dinilai"
                                            echo $row['nilai_siswa'] ? htmlspecialchars($row['nilai_siswa']) : '<span style="color: #a0aec0; font-weight: normal;">Belum dinilai</span>'; 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_daftar'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>