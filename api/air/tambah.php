<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

// INSERT ON DUPLICATE KEY UPDATE untuk increment jumlah +1
$stmt = $db->prepare(
    "INSERT INTO air_minum (user_id, tanggal, jumlah, target) 
     VALUES (?, CURDATE(), 1, 8) 
     ON DUPLICATE KEY UPDATE jumlah = jumlah + 1"
);
$stmt->bind_param('i', $user_id);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal menambah data air: ' . $db->error], 500);
}
$stmt->close();

// Ambil data terbaru
$stmt = $db->prepare("SELECT id, jumlah, target, tanggal FROM air_minum WHERE user_id = ? AND tanggal = CURDATE()");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$air    = $result->fetch_assoc();
$air['jumlah'] = (int) $air['jumlah'];
$air['target'] = (int) $air['target'];

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $air, 'message' => 'Air minum berhasil ditambahkan']);
