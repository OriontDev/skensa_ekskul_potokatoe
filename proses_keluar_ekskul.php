<?php
session_start();
require_once 'db_config.php';

// 1. Cek keamanan: Harus login dan harus student
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'student') {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_ekskul'])) {
    $user_id = $_SESSION["user_id"];
    $ekskul_id = (int)$_POST['id_ekskul'];

    try {
        // Mulai Transaksi agar penghapusan pendaftaran dan nilai berjalan bersamaan
        $pdo->beginTransaction();

        // 2. Hapus data nilai siswa di ekskul ini (jika ada)
        $sql_nilai = "DELETE FROM nilai_ekskul WHERE id_user = :uid AND id_ekskul = :eid";
        $stmt_nilai = $pdo->prepare($sql_nilai);
        $stmt_nilai->execute(['uid' => $user_id, 'eid' => $ekskul_id]);

        // 3. Hapus data pendaftaran
        $sql_daftar = "DELETE FROM pendaftaran WHERE id_user = :uid AND id_ekskul = :eid";
        $stmt_daftar = $pdo->prepare($sql_daftar);
        $stmt_daftar->execute(['uid' => $user_id, 'eid' => $ekskul_id]);

        // Komit perubahan
        $pdo->commit();

        // 4. Redirect kembali ke halaman detail dengan pesan sukses (opsional)
        header("location: detail_ekskul.php?id=" . $ekskul_id . "&status=left");
        exit;

    } catch (PDOException $e) {
        // Batalkan jika ada error
        $pdo->rollBack();
        die("Error saat mencoba keluar dari ekskul: " . $e->getMessage());
    }
} else {
    // Jika diakses tanpa POST atau tanpa ID
    header("location: dashboard.php");
    exit;
}