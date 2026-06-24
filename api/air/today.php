<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

// Cek apakah sudah ada record hari ini
$stmt = $db->prepare("SELECT id, jumlah, target, tanggal FROM air_minum WHERE user_id = ? AND tanggal = CURDATE()");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Buat record baru dengan jumlah=0 dan target=8
    $stmt->close();
    $insStmt = $db->prepare("INSERT INTO air_minum (user_id, tanggal, jumlah, target) VALUES (?, CURDATE(), 0, 8)");
    $insStmt->bind_param('i', $user_id);

    if (!$insStmt->execute()) {
        $insStmt->close();
        $db->close();
        sendJSON(['success' => false, 'message' => 'Gagal membuat record air: ' . $db->error], 500);
    }
    $insStmt->close();

    // Ambil data yang baru dibuat
    $stmt = $db->prepare("SELECT id, jumlah, target, tanggal FROM air_minum WHERE user_id = ? AND tanggal = CURDATE()");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$air = $result->fetch_assoc();
$air['jumlah'] = (int) $air['jumlah'];
$air['target'] = (int) $air['target'];

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $air, 'message' => 'Data air minum hari ini']);
