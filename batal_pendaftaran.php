<?php
/**
 * batal_pendaftaran.php
 * Menghapus pendaftaran ekskul dari tabel 'pendaftaran'.
 */

session_start();
require_once 'db_config.php';

// Pastikan user sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$nis_siswa = $_SESSION['nis'];
$ekskul_to_cancel = '';
$response = ['success' => false, 'message' => ''];

// Cek apakah parameter ekskul dikirim melalui GET
if (isset($_GET['ekskul']) && !empty(trim($_GET['ekskul']))) {
    $ekskul_to_cancel = trim($_GET['ekskul']);

    // 1. Buat koneksi database
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        $response['message'] = 'Koneksi database gagal: ' . $conn->connect_error;
    } else {
        // 2. Persiapkan statement DELETE
        // Hanya hapus jika NIS dan Nama Ekskul cocok
        $sql_delete = "DELETE FROM pendaftaran WHERE nis = ? AND ekskul = ?";

        if ($stmt = $conn->prepare($sql_delete)) {
            $stmt->bind_param("ss", $nis_siswa, $ekskul_to_cancel);

            if ($stmt->execute()) {
                // Cek apakah ada baris yang benar-benar dihapus
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Pembatalan pendaftaran ekskul "' . htmlspecialchars($ekskul_to_cancel) . '" berhasil dilakukan.';
                } else {
                    $response['message'] = 'Pendaftaran ekskul "' . htmlspecialchars($ekskul_to_cancel) . '" tidak ditemukan. Mungkin sudah dibatalkan sebelumnya.';
                }
            } else {
                $response['message'] = 'Terjadi kesalahan saat eksekusi penghapusan: ' . $stmt->error;
            }

            $stmt->close();
        } else {
            $response['message'] = 'Kesalahan dalam menyiapkan query SQL (DELETE): ' . $conn->error;
        }

        $conn->close();
    }
} else {
    $response['message'] = 'Akses tidak valid. Tidak ada ekstrakurikuler yang ditentukan untuk dibatalkan.';
}

// =========================================================================
// TAMPILKAN HASIL RESPON KE PENGGUNA (Menggunakan template yang sama dengan simpan_pendaftaran)
// =========================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembatalan Pendaftaran</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        .response-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .response-container h2 {
            margin-top: 0;
            /* Merah untuk gagal, Hijau/Biru untuk sukses */
            color: <?php echo $response['success'] ? '#2c7a7b' : '#c53030'; ?>; 
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3182ce;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-link:hover {
            background-color: #2b6cb0;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <h1>Status Pembatalan</h1>
        <p class="subtitle">Informasi hasil pembatalan pendaftaran Anda.</p>
    </div>
</header>

<div class="container response-container">
    <?php if ($response['success']): ?>
        <h2>✅ Pembatalan Berhasil!</h2>
        <p style="font-weight: bold;"><?php echo $response['message']; ?></p>
        <p>Anda kini dapat mendaftar ke ekskul lain atau kembali ke halaman utama.</p>
    <?php else: ?>
        <h2>❌ Pembatalan Gagal!</h2>
        <p style="font-weight: bold; color: #c53030;"><?php echo $response['message']; ?></p>
        <p>Silakan coba lagi atau hubungi administrator.</p>
    <?php endif; ?>
    
    <a href="index.php" class="back-link">Kembali ke Daftar Ekskul</a>
</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>