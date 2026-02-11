<?php
    require_once 'db_config.php'; 

    $nis = $nama = $no_telp = $role = "";
    $nis_err = $nama_err = $password_err = $confirm_password_err = $no_telp_err = $role_err = $error = "";
    $registration_success = false;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // 1. Collect and sanitize input
        $nis = trim($_POST["nis"]);
        $nama = trim($_POST["nama"]);
        $password = trim($_POST["password"]);
        $no_telp = trim($_POST["no_telp"]); 
        $role = trim($_POST["role"]); // Capture the new role field

        // 2. Simple Validation for Role
        $allowed_roles = ['student', 'pengurus', 'guru'];
        if (!in_array($role, $allowed_roles)) {
            $role_err = "Pilih peran yang valid.";
        }

        // 3. Hash the password for security
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        if (empty($role_err)) {
            try {
                // 4. Check if NIS already exists
                $checkSql = "SELECT id FROM users WHERE nis = :nis";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute(['nis' => $nis]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "NIS ini sudah terdaftar. Silakan gunakan NIS lain.";
                } else {
                    // 5. Prepare the INSERT statement (Role is now a variable)
                    $sql = "INSERT INTO users (nis, nama, password_hash, no_telp, role) 
                            VALUES (:nis, :nama, :pass, :telp, :role)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'nis'  => $nis,
                        'nama' => $nama,
                        'pass' => $password_hashed,
                        'telp' => $no_telp,
                        'role' => $role
                    ]);

                    // 6. Redirect on success
                    header("Location: login.php?registration=success");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Siswa</title>
    <link rel="stylesheet" href="registerLogin.css"> 
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't affect width */
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <h1>Pendaftaran Akun Ekskul</h1>
        <p class="subtitle">Daftarkan akun Anda untuk dapat masuk ke sistem.</p>
    </div>
</header>

<div class="container main-content">
    <div class="form-container">
        <h2 class="section-title">Daftar Akun Baru</h2>

        <?php if (!empty($error)): ?>
            <div class="error-feedback" style="margin-bottom: 15px; color: red;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="registration-form">
            
            <div class="form-group">
                <label for="role">Daftar Sebagai:</label>
                <select name="role" id="role" required>
                    <option value="" disabled <?php echo ($role == "") ? "selected" : ""; ?>>-- Pilih Peran --</option>
                    <option value="student" <?php echo ($role == "student") ? "selected" : ""; ?>>Siswa (Student)</option>
                    <option value="pengurus" <?php echo ($role == "pengurus") ? "selected" : ""; ?>>Pengurus Ekskul</option>
                    <option value="guru" <?php echo ($role == "guru") ? "selected" : ""; ?>>Guru / Pembina</option>
                </select>
                <span class="error-feedback"><?php echo $role_err; ?></span>
            </div>

            <div class="form-group">
                <label for="nis">NIS / NIP:</label>
                <input type="text" id="nis" name="nis" value="<?php echo htmlspecialchars($nis); ?>" required placeholder="Contoh: 12345678">
                <span class="error-feedback"><?php echo $nis_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Lengkap:</label>
                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" required placeholder="Contoh: Budi Santoso">
                <span class="error-feedback"><?php echo $nama_err; ?></span>
            </div>

            <div class="form-group">
                <label for="no_telp">No Telp:</label>
                <input type="text" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($no_telp); ?>" required placeholder="Contoh: 0823555743">
                <span class="error-feedback"><?php echo $no_telp_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required placeholder="Minimal 6 karakter">
                <span class="error-feedback"><?php echo $password_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi Password">
                <span class="error-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            
            <button type="submit" class="btn-submit">Daftar Sekarang</button>
            <p style="margin-top: 15px; text-align: center;">Sudah punya akun? <a href="login.php">Masuk di sini</a>.</p>
        </form>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>