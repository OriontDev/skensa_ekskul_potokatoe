<?php
    session_start();
    require_once 'db_config.php';

    if (!isset($_SESSION['user_id']) || !isset($_POST['id_ekskul'])) {
        header("Location: index.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $ekskul_id = $_POST['id_ekskul'];

    try {
        // Check if already registered
        $check = $pdo->prepare("SELECT id_pendaftaran FROM pendaftaran WHERE id_user = :uid AND id_ekskul = :eid");
        $check->execute(['uid' => $user_id, 'eid' => $ekskul_id]);
        
        if ($check->rowCount() == 0) {
            $sql = "INSERT INTO pendaftaran (id_user, id_ekskul) VALUES (:uid, :eid)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['uid' => $user_id, 'eid' => $ekskul_id]);
        }
        header("Location: index.php?status=success");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pendaftaran</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Gaya tambahan opsional untuk respons */
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
        <h1>Status Pendaftaran</h1>
        <p class="subtitle">Informasi hasil pengiriman formulir Anda.</p>
    </div>
</header>

<div class="container response-container">
    <?php if ($response['success']): ?>
        <h2>✅ Pendaftaran Berhasil!</h2>
        <p>Terima kasih **<?php echo htmlspecialchars($nama_siswa); ?>** (NIS: <?php echo htmlspecialchars($nis_siswa); ?>) dari kelas **<?php echo htmlspecialchars($kelas); ?>**.</p>
        <p><?php echo $response['message']; ?></p>
    <?php else: ?>
        <h2>❌ Pendaftaran Gagal!</h2>
        <p>Mohon maaf, terjadi masalah:</p>
        <p style="font-weight: bold; color: #c53030;"><?php echo $response['message']; ?></p>
    <?php endif; ?>
    
    <a href="index.php" class="back-link">Kembali ke Halaman Utama</a>
</div>

<footer class="footer">
    <p>&copy; 2025 Made Oriont Fedora // 24 // XI-RPL 1.</p>
</footer>

</body>
</html>