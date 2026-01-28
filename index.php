<?php
    /**
     * index.php - Supabase Version with Sidebar & Multi-Table Schedule
     */

    session_start();
    require_once 'db_config.php'; 

    $ekskul_list_from_db = []; 
    $registered_ekskul_ids = []; 
    $is_logged_in = isset($_SESSION["user_id"]);
    $user_id = $is_logged_in ? $_SESSION["user_id"] : null;
    $user_name = $is_logged_in ? $_SESSION["nama"] : '';
    $user_role = $is_logged_in ? $_SESSION["role"] : ''; 
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : ""; 
    $ekskul_selected_id = isset($_GET['ekskul_selected']) ? (int)$_GET['ekskul_selected'] : 0;

    try {
        // Updated Query: Joins ekskul with jadwal_ekskul and aggregates the days/times
        // We use string_agg for PostgreSQL to combine multiple schedule rows into one string
        $sql = "SELECT e.id, e.nama, e.deskripsi, e.thumbnail, 
                string_agg(j.day || ' (' || TO_CHAR(j.start_time, 'HH24:MI') || '-' || TO_CHAR(j.end_time, 'HH24:MI') || ')', ', ' ORDER BY j.day) as jadwal_gabungan
                FROM ekskul e
                LEFT JOIN jadwal_ekskul j ON e.id = j.id_ekskul";

        if (!empty($search_query)) {
            $sql .= " WHERE e.nama ILIKE :search"; 
        }

        $sql .= " GROUP BY e.id, e.nama, e.deskripsi, e.thumbnail ORDER BY e.nama ASC";

        $stmt = $pdo->prepare($sql);
        if (!empty($search_query)) {
            $stmt->execute(['search' => "%$search_query%"]);
        } else {
            $stmt->execute();
        }
        $ekskul_list_from_db = $stmt->fetchAll();

        // Fetch user registrations
        if ($is_logged_in) {
            $sql_reg = "SELECT id_ekskul FROM pendaftaran WHERE id_user = :uid";
            $stmt_reg = $pdo->prepare($sql_reg);
            $stmt_reg->execute(['uid' => $user_id]);
            $registered_ekskul_ids = $stmt_reg->fetchAll(PDO::FETCH_COLUMN);
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
    <title>Ekskul Skensa - Home</title>
    <link rel="stylesheet" href="style.css?v=1.1">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        Ekskul Skensa
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="active"><span class="menu-icon">🏠</span> <span class="menu-text">Home</span></a></li>
        
        <?php if ($is_logged_in): ?>
            <li><a href="dashboard.php"><span class="menu-icon">📊</span> <span class="menu-text">Dashboard</span></a></li>
            <li><a href="settings.php"><span class="menu-icon">⚙️</span> <span class="menu-text">Settings</span></a></li>
        <?php endif; ?>
        
        <li><a href="help.php"><span class="menu-icon">❓</span> <span class="menu-text">Bantuan</span></a></li>
    </ul>
    <div class="sidebar-footer" style="padding: 20px; font-size: 0.8rem; color: #a0aec0; text-align: center;">
        v1.0.2-2025
    </div>
</aside>

<div class="main-wrapper">
    
    <header class="header">
        <div>
            <h1 style="font-size: 1.5rem; color: #2d3748; margin: 0;">Selamat Datang, <?php echo $is_logged_in ? htmlspecialchars($user_name) : 'Tamu'; ?></h1>
        </div>
        
        <div class="auth-links">
            <?php if ($is_logged_in): ?>
                <span class="role-badge" style="background: #edf2f7; padding: 5px 12px; border-radius: 15px; margin-right: 10px; font-size: 0.85rem; font-weight: bold; color: #4a5568;">
                    Role: <?php echo ucfirst($user_role); ?>
                </span>
                <a href="logout.php" class="logout-btn" style="background: #e53e3e; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem;">Logout</a>
            <?php else: ?>
                <a href="login.php" style="margin-right: 15px; text-decoration: none; color: #4c51bf; font-weight: bold;">Masuk</a>
                <a href="register.php" class="btn-register" style="padding: 8px 15px; background: #4c51bf; color: white; border-radius: 5px; text-decoration: none;">Daftar</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="content-area">
        <h2 class="section-title">Pilihan Kegiatan Ekskul</h2>
        
        <form action="index.php" method="GET" class="search-form" style="margin-bottom: 30px; display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Cari ekskul..." value="<?php echo htmlspecialchars($search_query); ?>" style="padding: 10px; width: 100%; max-width: 400px; border: 1px solid #ddd; border-radius: 5px;">
            <button type="submit" style="padding: 10px 20px; background: #4c51bf; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Cari</button>
            <?php if (!empty($search_query)): ?>
                <button type="button" onclick="window.location.href='index.php'" style="padding: 10px; background: #cbd5e0; border: none; border-radius: 5px; cursor: pointer;">Reset</button>
            <?php endif; ?>
        </form>
        
        <div class="ekskul-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
            <?php
            if (isset($error_message)) {
                echo "<p class='error-message' style='color: red;'>$error_message</p>";
            } elseif (empty($ekskul_list_from_db)) {
                echo "<p class='info-message'>Tidak ditemukan ekstrakurikuler.</p>";
            }

            foreach ($ekskul_list_from_db as $ekskul):
                $is_registered = in_array($ekskul['id'], $registered_ekskul_ids);
            ?>
                <div class='ekskul-card' style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                    <?php $thumb = htmlspecialchars($ekskul['thumbnail']) ?: 'https://placehold.co/400x150/4c51bf/ffffff?text=Ekskul+Skensa'; ?>
                    <div class='card-thumbnail' style='background-image: url("<?php echo $thumb; ?>"); height: 160px; background-size: cover; background-position: center;'></div>
                    
                    <div class='card-content-wrapper' style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;"> 
                        <h3 class='card-title' style="margin-top: 0; color: #2d3748;"><?php echo htmlspecialchars($ekskul['nama']); ?></h3>
                        
                        <p class='card-schedule' style="font-size: 0.9rem; color: #4a5568; margin-bottom: 10px;">
                            <strong>Jadwal:</strong> 
                            <?php echo $ekskul['jadwal_gabungan'] ? htmlspecialchars($ekskul['jadwal_gabungan']) : '<em>Belum ada jadwal</em>'; ?>
                        </p>
                        
                        <p class='card-description' style="font-size: 0.9rem; color: #718096; line-height: 1.5; margin-bottom: 20px;">
                            <?php echo htmlspecialchars($ekskul['deskripsi']); ?>
                        </p>
                        
                        <div style="margin-top: auto;">
                            <?php if ($is_logged_in): ?>
                                <?php if ($is_registered): ?>
                                    <a href='batal_pendaftaran.php?id_ekskul=<?php echo $ekskul['id']; ?>' 
                                       onclick='return confirm("Batalkan pendaftaran?");'
                                       class='btn-register' style='display: block; text-align: center; padding: 10px; background-color: #e53e3e; color: white; border-radius: 5px; text-decoration: none; font-weight: bold;'>Batalkan</a> 
                                <?php else: ?>
                                    <a href='index.php?ekskul_selected=<?php echo $ekskul['id']; ?>#form-pendaftaran' class='btn-register' style='display: block; text-align: center; padding: 10px; background-color: #4c51bf; color: white; border-radius: 5px; text-decoration: none; font-weight: bold;'>Daftar</a> 
                                <?php endif; ?>
                            <?php else: ?>
                                <a href='login.php' class='btn-register' style='display: block; text-align: center; padding: 10px; background-color: #a0aec0; color: white; border-radius: 5px; text-decoration: none; font-weight: bold;'>Login untuk Daftar</a>
                            <?php endif; ?>
                        </div>
                    </div> 
                </div>
            <?php endforeach; ?>
        </div>

        <hr style="margin: 60px 0; border: 0; border-top: 2px solid #edf2f7;">

        <h2 class="section-title" id="form-pendaftaran">Formulir Pendaftaran</h2>
        
        <?php if ($is_logged_in): ?>
        <form action="simpan_pendaftaran.php" method="POST" class="registration-form" style="max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
            <p class="info-message" style="margin-top: 0; padding-bottom: 15px; border-bottom: 1px solid #edf2f7;">Mendaftar sebagai: <b style="color: #4c51bf;"><?php echo htmlspecialchars($user_name); ?></b></p>
            
            <div class="form-group" style="margin: 20px 0;">
                <label for="kelas" style="display: block; margin-bottom: 8px; font-weight: bold; color: #4a5568;">Kelas:</label>
                <input type="text" id="kelas" name="kelas" required placeholder="Contoh: XI RPL 1" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
            </div>
            
            <div class="form-group" style="margin: 20px 0;">
                <label for="id_ekskul" style="display: block; margin-bottom: 8px; font-weight: bold; color: #4a5568;">Pilih Ekstrakurikuler:</label>
                <select id="id_ekskul" name="id_ekskul" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; background-color: white;">
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
            
            <button type="submit" class="btn-submit" style="width: 100%; padding: 14px; background: #4c51bf; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: background 0.2s;">Kirim Pendaftaran</button>
        </form>
        <?php else: ?>
        <div class="info-message" style="background: #ebf4ff; padding: 25px; border-radius: 8px; border-left: 5px solid #4c51bf; color: #2c5282;">
            <p style="margin: 0;">Silakan <a href="login.php" style="color: #4c51bf; font-weight: bold; text-decoration: underline;">Masuk</a> atau <a href="register.php" style="color: #4c51bf; font-weight: bold; text-decoration: underline;">Daftar</a> untuk mendaftar ekskul.</p>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer" style="margin-top: 50px; padding: 30px; text-align: center; color: #a0aec0; border-top: 1px solid #edf2f7; font-size: 0.85rem;">
        <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
    </footer>
</div>

</body>
</html>