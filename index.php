<?php
    /**
     * index.php - Supabase Version
     */

    session_start();
    require_once 'db_config.php'; // Using the PDO connection

    // 1. INITIALIZE VARIABLES
    $ekskul_list_from_db = []; 
    $registered_ekskul_ids = []; 
    $is_logged_in = isset($_SESSION["user_id"]);
    $user_id = $is_logged_in ? $_SESSION["user_id"] : null;
    $user_name = $is_logged_in ? $_SESSION["nama"] : '';
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : ""; 
    $ekskul_selected_id = isset($_GET['ekskul_selected']) ? (int)$_GET['ekskul_selected'] : 0;

    try {
        // 2. FETCH EXTRACURRICULAR DATA
        $sql = "SELECT id, nama, deskripsi, jadwal, thumbnail FROM ekskul";
        if (!empty($search_query)) {
            $sql .= " WHERE nama ILIKE :search"; // ILIKE is case-insensitive in Postgres
        }
        $sql .= " ORDER BY nama ASC";

        $stmt = $pdo->prepare($sql);
        if (!empty($search_query)) {
            $stmt->execute(['search' => "%$search_query%"]);
        } else {
            $stmt->execute();
        }
        $ekskul_list_from_db = $stmt->fetchAll();

        // 3. FETCH USER REGISTRATIONS (IF LOGGED IN)
        if ($is_logged_in) {
            $sql_reg = "SELECT id_ekskul FROM pendaftaran WHERE id_user = :uid";
            $stmt_reg = $pdo->prepare($sql_reg);
            $stmt_reg->execute(['uid' => $user_id]);
            $registered_ekskul_ids = $stmt_reg->fetchAll(PDO::FETCH_COLUMN); // Gets array of IDs
        }

    } catch (PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ekstrakurikuler Sekolah</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <div class="container">
        <h1>Pendaftaran Ekstrakurikuler</h1>
        <p class="subtitle">Waktunya kembangkan minat dan bakatmu bersama Skensa!</p>
    </div>
    
    <div class="auth-links">
        <?php if ($is_logged_in): ?>
            <span>Halo, <b><?php echo htmlspecialchars($user_name); ?></b>!</span>
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
        <input type="text" name="search" placeholder="Cari ekskul..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Cari</button>
        <?php if (!empty($search_query)): ?>
            <button type="button" onclick="window.location.href='index.php'">Reset</button>
        <?php endif; ?>
    </form>
    
    <div class="ekskul-grid">
        <?php
        if (isset($error_message)) {
            echo "<p class='error-message'>$error_message</p>";
        } elseif (empty($ekskul_list_from_db)) {
            echo "<p class='info-message'>Tidak ditemukan ekstrakurikuler.</p>";
        }

        foreach ($ekskul_list_from_db as $ekskul):
            $is_registered = in_array($ekskul['id'], $registered_ekskul_ids);
        ?>
            <div class='ekskul-card'>
                <?php $thumb = htmlspecialchars($ekskul['thumbnail']) ?: 'https://placehold.co/400x150/4c51bf/ffffff?text=Ekskul+Skensa'; ?>
                <div class='card-thumbnail' style='background-image: url("<?php echo $thumb; ?>");'></div>
                
                <div class='card-content-wrapper'> 
                    <h3 class='card-title'><?php echo htmlspecialchars($ekskul['nama']); ?></h3>
                    <p class='card-schedule'><strong>Jadwal:</strong> <?php echo htmlspecialchars($ekskul['jadwal']); ?></p>
                    <p class='card-description'><?php echo htmlspecialchars($ekskul['deskripsi']); ?></p>
                    
                    <?php if ($is_logged_in): ?>
                        <?php if ($is_registered): ?>
                            <a href='batal_pendaftaran.php?id_ekskul=<?php echo $ekskul['id']; ?>' 
                               onclick='return confirm("Batalkan pendaftaran?");'
                               class='btn-register' style='background-color: #e53e3e;'>Batalkan</a> 
                        <?php else: ?>
                            <a href='index.php?ekskul_selected=<?php echo $ekskul['id']; ?>#form-pendaftaran' class='btn-register'>Daftar</a> 
                        <?php endif; ?>
                    <?php else: ?>
                        <a href='login.php' class='btn-register' style='background-color: #999;'>Login untuk Daftar</a>
                    <?php endif; ?>
                </div> 
            </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <h2 class="section-title" id="form-pendaftaran">Formulir Pendaftaran</h2>
    
    <?php if ($is_logged_in): ?>
    <form action="simpan_pendaftaran.php" method="POST" class="registration-form">
        <p class="info-message">Mendaftar sebagai: <b><?php echo htmlspecialchars($user_name); ?></b></p>
        
        <div class="form-group">
            <label for="kelas">Kelas:</label>
            <input type="text" id="kelas" name="kelas" required placeholder="Contoh: XI RPL 1">
        </div>
        
        <div class="form-group">
            <label for="id_ekskul">Pilih Ekstrakurikuler:</label>
            <select id="id_ekskul" name="id_ekskul" required>
                <option value="" disabled selected>-- Pilih Salah Satu --</option>
                <?php foreach ($ekskul_list_from_db as $ekskul): ?>
                    <?php 
                        $is_reg = in_array($ekskul['id'], $registered_ekskul_ids); 
                        $selected = ($ekskul_selected_id == $ekskul['id']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $ekskul['id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($ekskul['nama']) . ($is_reg ? ' (Sudah Terdaftar)' : ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn-submit">Kirim Pendaftaran</button>
    </form>
    <?php else: ?>
    <div class="info-message">
        <p>Silakan <a href="login.php">Masuk</a> atau <a href="register.php">Daftar</a> untuk mendaftar ekskul.</p>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>