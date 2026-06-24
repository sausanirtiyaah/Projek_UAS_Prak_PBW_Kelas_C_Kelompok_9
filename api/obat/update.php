<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body    = getBody();
$id      = isset($body['id']) ? (int) $body['id'] : 0;
$nama    = trim($body['nama'] ?? '');
$kegunaan = trim($body['kegunaan'] ?? '');

if (!$id || empty($nama) || empty($kegunaan)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'ID, nama, dan kegunaan wajib diisi'], 422);
}

$stmt = $db->prepare("UPDATE obat SET nama = ?, kegunaan = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('ssii', $nama, $kegunaan, $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    // Bisa jadi karena data sama, atau id salah/bukan milik user
    $cek = $db->prepare("SELECT id FROM obat WHERE id = ? AND user_id = ?");
    $cek->bind_param('ii', $id, $user_id);
    $cek->execute();
    if ($cek->get_result()->num_rows === 0) {
        $cek->close();
        $db->close();
        sendJSON(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda'], 404);
    }
    $cek->close();
}

$stmt->close();
$db->close();
sendJSON(['success' => true, 'message' => 'Obat berhasil diperbarui']);
