<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

$ekskul_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION["user_id"];
$user_role = $_SESSION["role"];

try {
    // 1. FETCH EKSKUL DETAILS & SCHEDULES (Using teacher_id to identify pengurus)
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

    // 2. FETCH INDIVIDUAL SCHEDULES FOR THE EDIT MODAL
    $sql_raw_schedules = "SELECT * FROM jadwal_ekskul WHERE id_ekskul = :eid ORDER BY day";
    $stmt_sch = $pdo->prepare($sql_raw_schedules);
    $stmt_sch->execute(['eid' => $ekskul_id]);
    $raw_schedules = $stmt_sch->fetchAll();

    // 3. FETCH ENROLLED STUDENTS WITH GRADES
    $sql_students = "SELECT u.id as student_id, u.nama, p.created_at, n.nilai 
                     FROM pendaftaran p
                     JOIN users u ON p.id_user = u.id
                     LEFT JOIN nilai_ekskul n ON (n.id_user = u.id AND n.id_ekskul = p.id_ekskul)
                     WHERE p.id_ekskul = :eid
                     ORDER BY u.nama ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute(['eid' => $ekskul_id]);
    $students = $stmt_students->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Logic: Is this user the assigned pengurus for this specific extra?
$is_assigned_pengurus = ($user_role === 'pengurus' && $user_id == $ekskul['teacher_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail <?php echo htmlspecialchars($ekskul['nama']); ?> - Skensa</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboardStyle.css"> 
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
                    <button onclick="openEditModal()" class="btn-edit-trigger">Edit Informasi</button>
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
                            <th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="4" class="text-center">Belum ada siswa yang mendaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $index => $s): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($s['nama']); ?></strong></td>
                                    <td><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <?php if ($is_assigned_pengurus): ?>
                                            <select class="select-nilai" 
                                                    onchange="updateNilai(<?php echo $s['student_id']; ?>, <?php echo $ekskul_id; ?>, this.value)">
                                                <option value="">- Pilih -</option>
                                                <?php $options = ['Sangat Baik', 'Baik', 'Cukup', 'Buruk'];
                                                foreach($options as $opt): ?>
                                                    <option value="<?php echo $opt; ?>" <?php echo ($s['nilai'] == $opt) ? 'selected' : ''; ?>>
                                                        <?php echo $opt; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="badge-nilai"><?php echo $s['nilai'] ?: '-'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Ekskul: <?php echo htmlspecialchars($ekskul['nama']); ?></h3>
            <span class="close-btn" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form action="proses_edit_ekskul.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $ekskul['id']; ?>">
                
                <div class="form-group">
                    <label>Nama Ekskul</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($ekskul['nama']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" required rows="4"><?php echo htmlspecialchars($ekskul['deskripsi']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>URL Thumbnail</label>
                    <input type="text" name="thumbnail" value="<?php echo htmlspecialchars($ekskul['thumbnail']); ?>">
                </div>

                <hr class="form-divider">
                <h4>Pengaturan Jadwal</h4>
                
                <div id="edit-schedule-container">
                    <?php foreach ($raw_schedules as $sch): ?>
                    <div class="schedule-row">
                        <span class="remove-schedule" onclick="this.parentElement.remove()">- Hapus Jadwal</span>
                        <div class="form-row">
                            <div class="form-group">
                                <select name="day[]" required>
                                    <?php $days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu']; 
                                    foreach($days as $d): ?>
                                        <option value="<?php echo $d; ?>" <?php echo ($sch['day'] == $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><input type="time" name="start_time[]" value="<?php echo substr($sch['start_time'],0,5); ?>" required></div>
                            <div class="form-group"><input type="time" name="end_time[]" value="<?php echo substr($sch['end_time'],0,5); ?>" required></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="btn-add-schedule" onclick="addEditScheduleRow()">+ Tambah Hari Lain</button>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditModal() { 
        document.getElementById('editModal').style.display = 'flex'; 
        document.body.style.overflow = 'hidden'; 
    }
    function closeEditModal() { 
        document.getElementById('editModal').style.display = 'none'; 
        document.body.style.overflow = 'auto'; 
    }
    
    function addEditScheduleRow() {
        const container = document.getElementById('edit-schedule-container');
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

    // AJAX to update Nilai
    function updateNilai(studentId, ekskulId, val) {
        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('ekskul_id', ekskulId);
        formData.append('nilai', val);

        fetch('update_nilai.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) alert('Gagal mengupdate nilai.');
        })
        .catch(error => console.error('Error:', error));
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) closeEditModal();
    }
</script>

</body>
</html>