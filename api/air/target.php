<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body   = getBody();
$target = isset($body['target']) ? (int) $body['target'] : null;

if ($target === null || $target < 1) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Target harus berupa angka minimal 1'], 422);
}

// INSERT ON DUPLICATE KEY UPDATE untuk update target hari ini
$stmt = $db->prepare(
    "INSERT INTO air_minum (user_id, tanggal, jumlah, target) 
     VALUES (?, CURDATE(), 0, ?) 
     ON DUPLICATE KEY UPDATE target = VALUES(target)"
);
$stmt->bind_param('ii', $user_id, $target);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal mengupdate target: ' . $db->error], 500);
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

sendJSON(['success' => true, 'data' => $air, 'message' => 'Target air minum berhasil diperbarui']);
