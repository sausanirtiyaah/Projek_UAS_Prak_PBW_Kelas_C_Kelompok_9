<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body          = getBody();
$nama          = trim($body['nama'] ?? '');
$kategori      = trim($body['kategori'] ?? '');
$waktu         = trim($body['waktu'] ?? '');
$tanggal       = trim($body['tanggal'] ?? date('Y-m-d'));
$auto_detected = isset($body['auto_detected']) ? (int) $body['auto_detected'] : 0;

if (empty($nama) || empty($kategori) || empty($waktu)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Nama, kategori, dan waktu makan wajib diisi'], 422);
}

$validKategori = ['sehat', 'cukup sehat', 'kurang sehat'];
if (!in_array($kategori, $validKategori)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Kategori tidak valid. Gunakan: sehat, cukup sehat, kurang sehat'], 422);
}

$validWaktu = ['pagi', 'siang', 'malam', 'snack'];
if (!in_array($waktu, $validWaktu)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Waktu tidak valid. Gunakan: pagi, siang, malam, snack'], 422);
}

$stmt = $db->prepare(
    "INSERT INTO makanan (user_id, nama, kategori, waktu, tanggal, auto_detected) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('issssi', $user_id, $nama, $kategori, $waktu, $tanggal, $auto_detected);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal mencatat makanan: ' . $db->error], 500);
}

$newId = $stmt->insert_id;
$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => ['id' => $newId], 'message' => 'Makanan berhasil dicatat'], 201);
