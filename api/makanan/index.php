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
    "SELECT id, nama, kategori, waktu, tanggal, auto_detected, created_at 
     FROM makanan 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 30"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$makanan = [];
while ($row = $result->fetch_assoc()) {
    $row['auto_detected'] = (int) $row['auto_detected'];
    $makanan[] = $row;
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $makanan, 'message' => 'Riwayat makanan berhasil diambil']);
