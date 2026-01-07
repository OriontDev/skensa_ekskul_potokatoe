<?php
/**
 * index.php
 * Halaman utama yang menampilkan daftar ekstrakurikuler dan formulir pendaftaran.
 * Juga menangani logika pencarian dan status login pengguna.
 */

// =========================================================================
// 1. INIASIASI DAN KONFIGURASI AWAL
// =========================================================================

// Mulai sesi untuk mengakses dan menyimpan data pengguna (seperti status login, NIS, dan nama)
session_start();

// Pastikan file konfigurasi database disertakan agar kredensial database tersedia
require_once 'db_config.php';

// Inisialisasi variabel
$ekskul_list_from_db = []; 
$user_registrations = []; 
$registered_ekskul_names = []; 

// Cek status login pengguna
$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Ambil NIS dan Nama dari session jika sudah login
$nis_siswa = $is_logged_in ? htmlspecialchars($_SESSION["nis"]) : null; // DIDEFINISIKAN DI SINI
$user_name = $is_logged_in ? htmlspecialchars($_SESSION["nama"]) : '';
$search_query = ""; 

// Ambil nama ekskul yang dipilih dari URL jika ada untuk PRE-SELECTION
$ekskul_selected = isset($_GET['ekskul_selected']) ? trim($_GET['ekskul_selected']) : '';


// =========================================================================
// 2. TANGANI INPUT PENCARIAN (GET REQUEST)
// =========================================================================
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
}

// =========================================================================
// 3. KONEKSI KE DATABASE
// =========================================================================
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// =========================================================================
// 4. AMBIL DATA EKSTRAKURIKULER (DENGAN FILTER PENCARIAN)
// =========================================================================
$sql = "SELECT nama, deskripsi, jadwal, no_telp, thumbnail FROM ekskul";

if (!empty($search_query)) {
    $sql .= " WHERE nama LIKE ?"; 
}
$sql .= " ORDER BY nama ASC";

// === LOGIKA EKSEKUSI QUERY ===
if (!empty($search_query)) {
    if ($stmt = $conn->prepare($sql)) {
        $param_search = "%" . $search_query . "%";
        $stmt->bind_param("s", $param_search);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
        } else {
            $error_message = "Error saat eksekusi query: " . $stmt->error;
        }
        // Hapus $stmt->close() di sini agar koneksi tetap terbuka untuk Seksi 5
    } else {
        $error_message = "Error saat mempersiapkan query: " . $conn->error;
    }
} else {
    // KOREKSI: Lebih baik menggunakan prepared statement di sini juga untuk konsistensi, 
    // tetapi untuk saat ini, kita biarkan query biasa yang Anda buat (sudah aman tanpa input user)
    $result = $conn->query($sql);
}


// === PROSES HASIL QUERY ===
if (isset($result) && $result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ekskul_list_from_db[] = $row;
        }
    } else {
        $no_data_message = "Tidak ditemukan ekstrakurikuler dengan kata kunci '" . htmlspecialchars($search_query) . "'.";
    }
} elseif (!isset($error_message)) {
     $error_message = "Error saat mengambil data ekskul: " . $conn->error;
}

// =========================================================================
// 5. AMBIL DATA PENDAFTARAN USER (JIKA SUDAH LOGIN)
// =========================================================================
if ($is_logged_in) {
    $sql_reg = "SELECT ekskul FROM pendaftaran WHERE nis = ?";
    if ($stmt_reg = $conn->prepare($sql_reg)) {
        $stmt_reg->bind_param("s", $nis_siswa);
        if ($stmt_reg->execute()) {
            $result_reg = $stmt_reg->get_result();
            while ($row_reg = $result_reg->fetch_assoc()) {
                // Simpan nama ekskul yang sudah didaftarkan ke array untuk pengecekan cepat
                $registered_ekskul_names[] = $row_reg['ekskul'];
            }
        }
        $stmt_reg->close(); // Tutup statement registrasi
    }
}

// Tutup koneksi database setelah semua operasi selesai
$conn->close();
// =========================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ekstrakurikuler Sekolah</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>

<header class="header">
    <div class="container">
        <h1>Pendaftaran Ekstrakurikuler</h1>
        <p class="subtitle">Waktunya kembangkan minat dan bakatmu bersama Skensa!</p>
    </div>
    <img class="logo" src="skensa.png">
    
    <div class="auth-links">
        <?php if ($is_logged_in): ?>
            <span>Halo, <b><?php echo $user_name; ?></b>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php">Masuk</a>
            <a href="register.php">Daftar</a>
        <?php endif; ?>
    </div>
</header>

<div class="container main-content">
    
    <h2 class="section-title">Pilihan Kegiatan Ekskul</h2>
    
    <form action="index.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="Cari berdasarkan nama ekskul..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Cari</button>

        <?php if (!empty($search_query)): ?>
            <button type="button" onclick="window.location.href='index.php'">Reset</button>
        <?php endif; ?>
    </form>
    
    <?php if (!empty($search_query) && isset($ekskul_list_from_db) && count($ekskul_list_from_db) > 0): ?>
        <p class="info-message-search">Menampilkan hasil pencarian untuk: </b><?php echo htmlspecialchars($search_query); ?></b></p>
    <?php endif; ?>

    <div class="ekskul-grid">
        <?php
            if (isset($error_message)) {
                echo "<p class='error-message'>Error Database: " . $error_message . "</p>";
            } elseif (isset($no_data_message)) {
                echo "<p class='info-message'>" . $no_data_message . "</p>";
            }

            // LOOP PHP untuk menampilkan setiap ekskul yang ada di array $ekskul_list_from_db
            foreach ($ekskul_list_from_db as $ekskul) {
                // Tentukan status pendaftaran
                $ekskul_name = $ekskul['nama'];
                $is_registered = in_array($ekskul_name, $registered_ekskul_names); // Cek status registrasi

                echo "<div class='ekskul-card'>";
                
                // === Menampilkan Thumbnail ===
                $thumbnail_url = htmlspecialchars($ekskul['thumbnail']) ?: 'https://placehold.co/400x150/4c51bf/ffffff?text=Ekskul+Skensa';
                echo "<div class='card-thumbnail' style='background-image: url(\"" . $thumbnail_url . "\");'></div>";
                
                // === Konten Kartu ===
                echo "<div class='card-content-wrapper'>"; 
                echo "<h3 class='card-title'>" . htmlspecialchars($ekskul_name) . "</h3>";
                echo "<p class='card-schedule'><strong>Jadwal:</strong> " . htmlspecialchars($ekskul['jadwal']) . "</p>";
                echo "<p class='card-description'>" . htmlspecialchars($ekskul['deskripsi']) . "</p>";
                echo "<p class='card-description'> No Telp Pengurus : " . htmlspecialchars($ekskul['no_telp']) . "</p>";
                
                // === LOGIC TOMBOL DAFTAR / BATALKAN ===
                if ($is_logged_in) {
                    // jadikan sebuah url yang nanti akan ditambahkan
                    $ekskul_name_url = urlencode($ekskul_name);
                    
                    if ($is_registered) {
                        // Jika sudah terdaftar, tampilkan tombol Batalkan Pendaftaran
                        echo "<a href='batal_pendaftaran.php?ekskul=" . $ekskul_name_url . "' 
                               onclick='return confirm(\"Apakah Anda yakin ingin membatalkan pendaftaran di ekskul " . addslashes($ekskul_name) . "?\");'
                               class='btn-register' style='background-color: #e53e3e;'>Batalkan Pendaftaran</a>"; 
                    } else {
                        // Jika belum terdaftar, tampilkan tombol Daftar (mengarah ke form)
                        echo "<a href='index.php?ekskul_selected=" . $ekskul_name_url . "#form-pendaftaran' class='btn-register'>Daftar Ekskul</a>"; 
                    }
                } else {
                    echo "<a href='login.php' class='btn-register' style='background-color: #999;'>Login untuk Daftar</a>";
                }

                echo "</div>"; 
                echo "</div>";
            }
        ?>
    </div>

    <hr>

    <h2 class="section-title" id="form-pendaftaran">Yuk, Isi Formulir Pendaftaran!</h2>
    
    <?php if ($is_logged_in): ?>
    <form action="simpan_pendaftaran.php" method="POST" class="registration-form">
        <p class="info-message">Anda terdaftar sebagai <b><?php echo $user_name; ?></b> (NIS: <b><?php echo htmlspecialchars($_SESSION["nis"]); ?></b>).</p>
        
        <div class="form-group">
            <label for="kelas"> Kelas:</label>
            <input type="text" id="kelas" name="kelas" required placeholder="Contoh: XI RPL 1">
        </div>
        
        <div class="form-group">
            <label for="ekskul"> Pilih Ekstrakurikuler:</label>
            <select id="ekskul" name="ekskul" required>
                <option value="" disabled selected>-- Pilih Salah Satu --</option>
                <?php
                    foreach ($ekskul_list_from_db as $ekskul) {
                        $ekskul_name = $ekskul['nama'];
                        $is_registered = in_array($ekskul_name, $registered_ekskul_names);
                        $selected = ($ekskul_selected === $ekskul_name) ? ' selected' : ''; 
                        
                        // Tampilkan status "Sudah Terdaftar" di dropdown
                        $status_label = $is_registered ? ' (Sudah Terdaftar)' : ''; 
                        
                        echo "<option value='" . htmlspecialchars($ekskul_name) . "'" . $selected . ">" . htmlspecialchars($ekskul_name) . $status_label . "</option>";
                    }
                ?>
            </select>
        </div>
        
        <button type="submit" class="btn-submit">Kirim Pendaftaran</button>
    </form>
    <?php else: ?>
    <div class="info-message" style="padding: 20px; background-color: #f7fafc; border-left: 5px solid #4c51bf;">
        <p style="margin: 0;"><b>Anda harus masuk (login) untuk dapat mengisi formulir pendaftaran.</b></p>
        <p style="margin: 10px 0 0 0;">Silakan <a href="login.php" style="font-weight: bold; color: #4c51bf;">Masuk</a> atau <a href="register.php" style="font-weight: bold; color: #4c51bf;">Daftar Akun Baru</a>.</p>
    </div>
    <?php endif; ?>

</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>