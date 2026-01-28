<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$ekskul_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_role = $_SESSION["role"];

try {
    // 1. FETCH EKSKUL DETAILS & SCHEDULES
    $sql_ekskul = "SELECT e.*, 
                   string_agg(j.day || ' (' || TO_CHAR(j.start_time, 'HH24:MI') || '-' || TO_CHAR(j.end_time, 'HH24:MI') || ')', '<br>' ORDER BY j.day) as jadwal_list
                   FROM ekskul e
                   LEFT JOIN jadwal_ekskul j ON e.id = j.id_ekskul
                   WHERE e.id = :eid
                   GROUP BY e.id";
    $stmt = $pdo->prepare($sql_ekskul);
    $stmt->execute(['eid' => $ekskul_id]);
    $ekskul = $stmt->fetch();

    if (!$ekskul) {
        die("Ekstrakurikuler tidak ditemukan.");
    }

    // 2. FETCH ENROLLED STUDENTS (Corrected Query)
    // Joining pendaftaran with users based on your provided screenshot
    $sql_students = "SELECT u.nama, p.created_at 
                     FROM pendaftaran p
                     JOIN users u ON p.id_user = u.id
                     WHERE p.id_ekskul = :eid
                     ORDER BY u.nama ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute(['eid' => $ekskul_id]);
    $students = $stmt_students->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail <?php echo htmlspecialchars($ekskul['nama']); ?> - Skensa</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="detail_ekskul_style.css">
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
            <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
        </div>
        <div class="auth-links">
            <span class="user-info">Halo, <b><?php echo htmlspecialchars($_SESSION["nama"]); ?></b></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="content-area">
        <section class="ekskul-hero">
            <div class="hero-content">
                <h1><?php echo htmlspecialchars($ekskul['nama']); ?></h1>
                <?php if ($user_role === 'pengurus' || $user_role === 'guru'): ?>
                    <a href="edit_ekskul.php?id=<?php echo $ekskul['id']; ?>" class="btn-edit">Edit Informasi</a>
                <?php endif; ?>
            </div>
            <div class="hero-banner" style="background-image: url('<?php echo !empty($ekskul['thumbnail']) ? htmlspecialchars($ekskul['thumbnail']) : 'https://via.placeholder.com/1200x300?text=No+Image'; ?>');"></div>
        </section>

        <div class="detail-grid">
            <div class="info-card description-card">
                <h3>Deskripsi</h3>
                <p><?php echo nl2br(htmlspecialchars($ekskul['deskripsi'])); ?></p>
            </div>

            <div class="info-card schedule-card">
                <h3>Jadwal Latihan</h3>
                <div class="schedule-items">
                    <?php echo $ekskul['jadwal_list'] ?: '<em>Belum ada jadwal tetap.</em>'; ?>
                </div>
            </div>
        </div>

        <section class="student-list-section">
            <div class="section-header">
                <h3>Daftar Siswa Terdaftar</h3>
                <span class="student-count"><?php echo count($students); ?> Siswa</span>
            </div>

            <div class="table-container">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Tanggal Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="3" class="text-center">Belum ada siswa yang mendaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $index => $s): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($s['nama']); ?></strong></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($s['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>