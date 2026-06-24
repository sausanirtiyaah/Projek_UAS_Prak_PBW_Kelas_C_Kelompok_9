<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$stmt = $db->prepare("SELECT id, nama, kegunaan, stok, created_at FROM obat WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$obat = [];
while ($row = $result->fetch_assoc()) {
    $row['stok'] = (int) $row['stok'];
    $obat[] = $row;
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $obat, 'message' => 'Data obat berhasil diambil']);
