<?php
// Mulai sesi
session_start();

// Cek jika user sudah login, arahkan ke halaman utama (opsional)
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
}

require_once 'db_config.php';

// set semua variable ini jadi kosong
$nis = $password = "";
$nis_err = $password_err = $login_err = "";

// Proses form ketika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $stmt = $pdo->prepare("SELECT id, nis, nama, password_hash, role FROM users WHERE nis = :nis");
    $stmt->execute(['nis' => $nis]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
    } else {
        $error = "Invalid NIS or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun Siswa</title>
    <link rel="stylesheet" href="style.css"> 
    <style>

    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <h1>Masuk Akun Ekskul</h1>
        <p class="subtitle">Gunakan NIS dan password yang sudah terdaftar.</p>
    </div>
</header>

<div class="container main-content">
    <div class="form-container">
        <h2 class="section-title">Masuk ke Akun Anda</h2>

        <?php 
        if (!empty($login_err)) {
            echo '<div class="login-error">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="registration-form">
            
            <div class="form-group">
                <label for="nis">NIS (Nomor Induk Siswa):</label>
                <input type="text" id="nis" name="nis" value="<?php echo htmlspecialchars($nis); ?>" required placeholder="Masukkan NIS Anda">
                <span class="error-feedback"><?php echo $nis_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan Password Anda">
                <span class="error-feedback"><?php echo $password_err; ?></span>
            </div>
            
            <button type="submit" class="btn-submit">Masuk</button>
            <p style="margin-top: 15px; text-align: center;">Belum punya akun? <a href="register.php">Daftar sekarang</a>.</p>
        </form>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>