<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] !== 'pengurus' && $_SESSION["role"] !== 'guru')) {
    header("location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $thumbnail = trim($_POST['thumbnail']);
    $teacher_id = $_SESSION["user_id"];

    // Schedule data (Arrays)
    $days = $_POST['day'];
    $starts = $_POST['start_time'];
    $ends = $_POST['end_time'];

    try {
        $pdo->beginTransaction();

        // 1. Insert Ekskul
        $sql_ekskul = "INSERT INTO ekskul (nama, deskripsi, thumbnail, teacher_id) 
                       VALUES (:nama, :deskripsi, :thumbnail, :tid) RETURNING id";
        $stmt = $pdo->prepare($sql_ekskul);
        $stmt->execute([
            'nama' => $nama,
            'deskripsi' => $deskripsi,
            'thumbnail' => $thumbnail,
            'tid' => $teacher_id
        ]);
        $new_id = $stmt->fetchColumn();

        // 2. Insert Multiple Schedules
        $sql_jadwal = "INSERT INTO jadwal_ekskul (id_ekskul, day, start_time, end_time) 
                       VALUES (:eid, :day, :start, :end)";
        $stmt_j = $pdo->prepare($sql_jadwal);

        foreach ($days as $i => $day) {
            // Only insert if day and times are provided
            if (!empty($day) && !empty($starts[$i])) {
                $stmt_j->execute([
                    'eid'   => $new_id,
                    'day'   => $day,
                    'start' => $starts[$i],
                    'end'   => $ends[$i]
                ]);
            }
        }

        $pdo->commit();
        header("location: dashboard.php?status=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}