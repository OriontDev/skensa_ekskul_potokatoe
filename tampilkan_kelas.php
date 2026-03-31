<?php
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK
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
    // 2. QUERY UPDATE: Menggunakan string_agg untuk menggabungkan ekskul & nilai
    // Kita melakukan GROUP BY pada ID User dan NIS
    $sql = "SELECT 
                u.nama as nama_siswa, 
                u.nis, 
                string_agg(e.nama || ' : ' || COALESCE(n.nilai, 'Belum dinilai'), E'\n') as list_ekskul_nilai,
                MIN(p.created_at) as tanggal_daftar_awal
            FROM pendaftaran p
            JOIN users u ON p.id_user = u.id
            JOIN ekskul e ON p.id_ekskul = e.id
            LEFT JOIN nilai_ekskul n ON (p.id_ekskul = n.id_ekskul AND p.id_user = n.id_user)
            WHERE p.kelas = :kelas
            GROUP BY u.id, u.nama, u.nis
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
    <style>
        .btn-download {
            background-color: #38a169; color: white; padding: 10px 20px; border-radius: 6px;
            text-decoration: none; display: inline-flex; align-items: center; font-weight: 600;
            cursor: pointer; border: none; transition: 0.2s;
        }
        .btn-download:hover { background-color: #2f855a; }
        /* Style untuk list ekskul di dalam tabel */
        .ekskul-list { white-space: pre-line; font-size: 0.85rem; line-height: 1.4; }
        .ekskul-item { background: #ebf4ff; color: #4c51bf; padding: 2px 8px; border-radius: 4px; display: block; margin-bottom: 4px; font-weight: 600; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">Ekskul Skensa</div>
    <ul class="sidebar-menu">
        <li><a href="index.php"><span class="menu-icon">🏠</span> <span class="menu-text">Home</span></a></li>
        <li><a href="dashboard.php" class="active"><span class="menu-icon">📊</span> <span class="menu-text">Dashboard</span></a></li>
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
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <a href="dashboard.php" class="btn-add-schedule" style="text-decoration: none; display: inline-block; width: auto; padding: 10px 20px;">
                &larr; Kembali
            </a>
            
            <?php if (!empty($students)): ?>
                <button onclick="downloadCSV()" class="btn-download">📥 Download CSV</button>
            <?php endif; ?>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="table-container">
            <table class="dashboard-table" id="siswaTable">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Ekstrakurikuler & Nilai</th>
                        <th>Tgl Daftar Pertama</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px; color: #a0aec0;">Belum ada data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['nama_siswa']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nis'] ?? '-'); ?></td>
                                <td class="ekskul-list">
                                    <?php 
                                        // Memisahkan string yang digabung tadi untuk tampilan yang lebih rapi
                                        $items = explode("\n", $row['list_ekskul_nilai']);
                                        foreach($items as $item) {
                                            echo "<span class='ekskul-item'>".htmlspecialchars($item)."</span>";
                                        }
                                    ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_daftar_awal'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function downloadCSV() {
    const table = document.getElementById("siswaTable");
    let csv = [];
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            // Khusus untuk kolom ekskul, ganti baris baru menjadi koma agar CSV tidak berantakan
            let data = cols[j].innerText.trim().replace(/\n/g, ", ");
            data = data.replace(/"/g, '""'); // Escape double quotes
            row.push('"' + data + '"');
        }
        csv.push(row.join(";"));
    }

    const csvContent = "\uFEFF" + csv.join("\n"); // Tambahkan BOM agar Excel deteksi UTF-8
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    const fileName = "Data_Ekskul_<?php echo str_replace(' ', '_', $kelas_target); ?>.csv";
    
    link.setAttribute("href", url);
    link.setAttribute("download", fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

</body>
</html>