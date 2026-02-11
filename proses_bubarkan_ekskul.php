<?php
session_start();
require_once 'db_config.php';

// 1. KEAMANAN: Cek login
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// 2. KEAMANAN: Hanya Pengurus/Guru yang bisa membubarkan
if ($_SESSION["role"] !== 'pengurus' && $_SESSION["role"] !== 'guru') {
    die("Akses ditolak: Anda tidak memiliki izin untuk membubarkan ekskul.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_ekskul'])) {
    $ekskul_id = (int)$_POST['id_ekskul'];
    $user_id = $_SESSION["user_id"];

    try {
        // Mulai transaksi database
        $pdo->beginTransaction();

        // 3. VALIDASI KEPEMILIKAN: Cek apakah user benar-benar pengelola ekskul ini
        $sql_check = "SELECT teacher_id FROM ekskul WHERE id = :eid";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute(['eid' => $ekskul_id]);
        $ekskul = $stmt_check->fetch();

        if (!$ekskul || $ekskul['teacher_id'] != $user_id) {
            throw new Exception("Anda tidak memiliki otoritas atas ekskul ini.");
        }

        // 4. PENGHAPUSAN DATA TERKAIT (Cascading Manual)
        // Hapus Nilai Siswa
        $sql_delete_nilai = "DELETE FROM nilai_ekskul WHERE id_ekskul = :eid";
        $pdo->prepare($sql_delete_nilai)->execute(['eid' => $ekskul_id]);

        // Hapus Data Pendaftaran Siswa
        $sql_delete_pendaftaran = "DELETE FROM pendaftaran WHERE id_ekskul = :eid";
        $pdo->prepare($sql_delete_pendaftaran)->execute(['eid' => $ekskul_id]);

        // Hapus Jadwal Ekskul
        $sql_delete_jadwal = "DELETE FROM jadwal_ekskul WHERE id_ekskul = :eid";
        $pdo->prepare($sql_delete_jadwal)->execute(['eid' => $ekskul_id]);

        // 5. HAPUS DATA UTAMA EKSKUL
        $sql_delete_ekskul = "DELETE FROM ekskul WHERE id = :eid";
        $pdo->prepare($sql_delete_ekskul)->execute(['eid' => $ekskul_id]);

        // Jika semua berhasil, simpan perubahan permanen
        $pdo->commit();

        // Redirect ke dashboard dengan pesan sukses
        header("location: dashboard.php?status=deleted");
        exit;

    } catch (Exception $e) {
        // Jika ada error, batalkan semua perubahan di database
        $pdo->rollBack();
        die("Gagal membubarkan ekskul: " . $e->getMessage());
    }
} else {
    // Jika diakses langsung tanpa POST
    header("location: dashboard.php");
    exit;
}