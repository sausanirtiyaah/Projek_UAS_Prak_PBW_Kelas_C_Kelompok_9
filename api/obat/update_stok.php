<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body  = getBody();
$id    = isset($body['id']) ? (int) $body['id'] : 0;
$delta = isset($body['delta']) ? (int) $body['delta'] : 0;

if ($id <= 0) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'ID obat tidak valid'], 422);
}

// Pastikan obat milik user
$stmt = $db->prepare("SELECT id, stok FROM obat WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Obat tidak ditemukan'], 404);
}

$obat = $result->fetch_assoc();
$stmt->close();

// Hitung stok baru: MAX(0, stok + delta)
$newStok = max(0, (int) $obat['stok'] + $delta);

$stmt = $db->prepare("UPDATE obat SET stok = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('iii', $newStok, $id, $user_id);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal mengupdate stok: ' . $db->error], 500);
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => ['stok' => $newStok], 'message' => 'Stok berhasil diperbarui']);
