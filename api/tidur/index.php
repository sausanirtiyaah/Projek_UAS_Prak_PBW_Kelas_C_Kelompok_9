<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$stmt = $db->prepare(
    "SELECT id, jam_tidur, jam_bangun, durasi_menit, tanggal, catatan, created_at 
     FROM tidur 
     WHERE user_id = ? 
       AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     ORDER BY tanggal DESC 
     LIMIT 14"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tidur = [];
while ($row = $result->fetch_assoc()) {
    $row['durasi_menit'] = (int) $row['durasi_menit'];
    $tidur[] = $row;
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $tidur, 'message' => 'Riwayat tidur 14 hari berhasil diambil']);
