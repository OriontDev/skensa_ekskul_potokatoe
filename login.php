<?php
// Mulai sesi
session_start();

// Cek jika user sudah login, arahkan ke halaman utama
if(isset($_SESSION["user_id"])){
    header("location: index.php");
    exit;
}

require_once 'db_config.php';

$nis = $password = "";
$nis_err = $password_err = $login_err = "";

// Proses form ketika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil dan validasi input
    $nis = trim($_POST["nis"]);
    $password = trim($_POST["password"]);

    if (empty($nis)) {
        $nis_err = "Mohon masukkan NIS / NIP.";
    }
    if (empty($password)) {
        $password_err = "Mohon masukkan password.";
    }

    // 2. Cek kredensial di database
    if (empty($nis_err) && empty($password_err)) {
        try {
            // Persiapkan SELECT statement (Menggunakan PDO)
            $sql = "SELECT id, nis, nama, password_hash, role FROM users WHERE nis = :nis";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['nis' => $nis]);
            $user = $stmt->fetch();

            if ($user) {
                // Verifikasi password
                if (password_verify($password, $user['password_hash'])) {
                    // Password benar, mulai sesi baru
                    $_SESSION["user_id"] = $user['id'];
                    $_SESSION["nis"] = $user['nis'];
                    $_SESSION["nama"] = $user['nama'];
                    $_SESSION["role"] = $user['role'];
                    
                    // Alihkan user ke halaman utama
                    header("location: index.php");
                    exit;
                } else {
                    $login_err = "Password yang Anda masukkan salah.";
                }
            } else {
                $login_err = "NIS / NIP tidak ditemukan.";
            }
        } catch (PDOException $e) {
            $login_err = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun - Ekskul Skensa</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

<header class="header">
    <div class="container">
        <h1>Masuk Akun Ekskul</h1>
        <p class="subtitle">Gunakan NIS / NIP dan password Anda.</p>
    </div>
</header>

<div class="container main-content">
    <div class="form-container">
        <h2 class="section-title" style="text-align: center;">Masuk ke Akun</h2>

        <?php 
        if (!empty($login_err)) {
            echo '<div class="error-feedback" style="background: #fed7d7; padding: 10px; border-radius: 5px; margin-bottom: 15px; color: #c53030;">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="registration-form">
            
            <div class="form-group">
                <label for="nis">NIS / NIP (Siswa / Guru):</label>
                <input type="text" id="nis" name="nis" value="<?php echo htmlspecialchars($nis); ?>" required placeholder="Masukkan NIS atau NIP Anda">
                <span class="error-feedback" style="color: red; font-size: 0.8em;"><?php echo $nis_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan Password Anda">
                <span class="error-feedback" style="color: red; font-size: 0.8em;"><?php echo $password_err; ?></span>
            </div>
            
            <button type="submit" class="btn-submit">Masuk ke Sistem</button>
            <p style="margin-top: 20px; text-align: center;">Belum punya akun? <a href="register.php">Daftar sekarang</a>.</p>
        </form>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>