<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id_ekskul']) || !isset($_POST['kelas'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$ekskul_id = $_POST['id_ekskul'];
$kelas = $_POST['kelas'];
$nama_siswa = $_SESSION['nama'];
$response = ['success' => false, 'message' => ''];

try {
    // 1. Check if already registered
    $check = $pdo->prepare("SELECT id_pendaftaran FROM pendaftaran WHERE id_user = :uid AND id_ekskul = :eid");
    $check->execute(['uid' => $user_id, 'eid' => $ekskul_id]);
    
    if ($check->rowCount() > 0) {
        $response['success'] = false;
        $response['message'] = "Anda sudah terdaftar di ekstrakurikuler ini.";
    } else {
        // 2. Insert with the new 'kelas' field
        $sql = "INSERT INTO pendaftaran (id_user, id_ekskul, kelas, created_at) VALUES (:uid, :eid, :kelas, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'uid'   => $user_id,
            'eid'   => $ekskul_id,
            'kelas' => $kelas
        ]);
        
        $response['success'] = true;
        $response['message'] = "Data pendaftaran Anda telah berhasil disimpan ke sistem.";
    }
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = "Database Error: " . $e->getMessage();
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
        .response-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            background-color: white;
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
            background-color: #4c51bf;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-link:hover { background-color: #2b6cb0; }
    </style>
</head>
<body>

<div class="response-container">
    <?php if ($response['success']): ?>
        <h2>✅ Pendaftaran Berhasil!</h2>
        <p>Terima kasih <b><?php echo htmlspecialchars($nama_siswa); ?></b> dari kelas <b><?php echo htmlspecialchars($kelas); ?></b>.</p>
        <p><?php echo $response['message']; ?></p>
    <?php else: ?>
        <h2>❌ Pendaftaran Gagal!</h2>
        <p>Mohon maaf, terjadi masalah:</p>
        <p style="font-weight: bold; color: #c53030;"><?php echo $response['message']; ?></p>
    <?php endif; ?>
    
    <a href="dashboard.php" class="back-link">Buka Dashboard</a>
    <a href="index.php" class="back-link" style="background-color: #a0aec0;">Kembali ke Home</a>
</div>

</body>
</html>