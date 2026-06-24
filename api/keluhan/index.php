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
    "SELECT id, jenis, catatan, intensitas, tanggal, created_at 
     FROM keluhan 
     WHERE user_id = ? 
     ORDER BY tanggal DESC, created_at DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$keluhan = [];
while ($row = $result->fetch_assoc()) {
    $keluhan[] = $row;
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $keluhan, 'message' => 'Data keluhan berhasil diambil']);
