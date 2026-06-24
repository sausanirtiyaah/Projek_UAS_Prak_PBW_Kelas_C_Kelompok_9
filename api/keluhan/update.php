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
$jenis   = trim($body['jenis'] ?? '');
$intensitas = trim($body['intensitas'] ?? '');
$catatan = trim($body['catatan'] ?? '');
$tanggal = trim($body['tanggal'] ?? '');

if (!$id || empty($jenis) || empty($intensitas) || empty($tanggal)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Semua field wajib diisi'], 422);
}

$validIntensitas = ['ringan', 'sedang', 'berat'];
if (!in_array($intensitas, $validIntensitas)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Intensitas tidak valid'], 422);
}

$stmt = $db->prepare("UPDATE keluhan SET jenis = ?, intensitas = ?, catatan = ?, tanggal = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('ssssii', $jenis, $intensitas, $catatan, $tanggal, $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $cek = $db->prepare("SELECT id FROM keluhan WHERE id = ? AND user_id = ?");
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
sendJSON(['success' => true, 'message' => 'Keluhan berhasil diperbarui']);
