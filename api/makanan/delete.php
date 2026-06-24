<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body = getBody();
$id   = isset($body['id']) ? (int) $body['id'] : 0;

if ($id <= 0) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'ID makanan tidak valid'], 422);
}

// Cek record milik user
$stmt = $db->prepare("SELECT id FROM makanan WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Data makanan tidak ditemukan'], 404);
}
$stmt->close();

$stmt = $db->prepare("DELETE FROM makanan WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal menghapus makanan: ' . $db->error], 500);
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'message' => 'Data makanan berhasil dihapus']);
