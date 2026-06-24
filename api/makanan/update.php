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
$kategori = trim($body['kategori'] ?? '');
$waktu   = trim($body['waktu'] ?? '');
$tanggal = trim($body['tanggal'] ?? '');

if (!$id || empty($nama) || empty($kategori) || empty($waktu) || empty($tanggal)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Semua field wajib diisi'], 422);
}

$validKategori = ['sehat', 'cukup sehat', 'kurang sehat'];
if (!in_array($kategori, $validKategori)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Kategori tidak valid'], 422);
}

$validWaktu = ['pagi', 'siang', 'malam', 'snack'];
if (!in_array($waktu, $validWaktu)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Waktu tidak valid'], 422);
}

$stmt = $db->prepare("UPDATE makanan SET nama = ?, kategori = ?, waktu = ?, tanggal = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('ssssii', $nama, $kategori, $waktu, $tanggal, $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $cek = $db->prepare("SELECT id FROM makanan WHERE id = ? AND user_id = ?");
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
sendJSON(['success' => true, 'message' => 'Makanan berhasil diperbarui']);
