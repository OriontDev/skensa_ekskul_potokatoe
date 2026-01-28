<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $sid = (int)$_POST['student_id'];
    $eid = (int)$_POST['ekskul_id'];
    $nilai = $_POST['nilai'];

    try {
        // Upsert logic: Update if exists, Insert if not
        $sql = "INSERT INTO nilai_ekskul (id_user, id_ekskul, nilai) 
                VALUES (:sid, :eid, :nilai)
                ON CONFLICT (id_user, id_ekskul) 
                DO UPDATE SET nilai = EXCLUDED.nilai";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['sid' => $sid, 'eid' => $eid, 'nilai' => $nilai]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>