<?php
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK: Ensure user is authorized
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] !== 'guru' && $_SESSION["role"] !== 'pengurus')) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_ekskul = (int)$_POST['id'];
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $thumbnail = $_POST['thumbnail'];

    // Schedule arrays from the form
    $days = $_POST['day'] ?? [];
    $start_times = $_POST['start_time'] ?? [];
    $end_times = $_POST['end_time'] ?? [];

    try {
        // Start a transaction so if schedule fails, the info update also rolls back
        $pdo->beginTransaction();

        // 2. UPDATE BASIC EKSKUL INFO
        $sql_update = "UPDATE ekskul SET nama = :nama, deskripsi = :desk, thumbnail = :thumb WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'nama' => $nama,
            'desk' => $deskripsi,
            'thumb' => $thumbnail,
            'id' => $id_ekskul
        ]);

        // 3. REFRESH SCHEDULES
        // First, delete existing schedules for this ekskul
        $sql_del = "DELETE FROM jadwal_ekskul WHERE id_ekskul = :id";
        $stmt_del = $pdo->prepare($sql_del);
        $stmt_del->execute(['id' => $id_ekskul]);

        // Then, insert the new rows from the modal
        if (!empty($days)) {
            $sql_ins = "INSERT INTO jadwal_ekskul (id_ekskul, day, start_time, end_time) VALUES (:id, :day, :start, :end)";
            $stmt_ins = $pdo->prepare($sql_ins);

            for ($i = 0; $i < count($days); $i++) {
                // Only insert if both times are provided
                if (!empty($days[$i]) && !empty($start_times[$i]) && !empty($end_times[$i])) {
                    $stmt_ins->execute([
                        'id' => $id_ekskul,
                        'day' => $days[$i],
                        'start' => $start_times[$i],
                        'end' => $end_times[$i]
                    ]);
                }
            }
        }

        // Commit all changes
        $pdo->commit();
        
        // Redirect back to detail page with success
        header("Location: detail_ekskul.php?id=" . $id_ekskul . "&status=success");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error updating record: " . $e->getMessage());
    }
} else {
    header("location: dashboard.php");
    exit;
}