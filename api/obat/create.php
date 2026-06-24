<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body    = getBody();
$nama    = trim($body['nama'] ?? '');
$kegunaan = trim($body['kegunaan'] ?? '');
$stok    = isset($body['stok']) ? (int) $body['stok'] : 0;

if (empty($nama) || empty($kegunaan)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Nama dan kegunaan obat wajib diisi'], 422);
}

if ($stok < 0) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Stok tidak boleh negatif'], 422);
}

$stmt = $db->prepare("INSERT INTO obat (user_id, nama, kegunaan, stok) VALUES (?, ?, ?, ?)");
$stmt->bind_param('issi', $user_id, $nama, $kegunaan, $stok);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal menambah obat: ' . $db->error], 500);
}

$newId = $stmt->insert_id;
$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => ['id' => $newId], 'message' => 'Obat berhasil ditambahkan'], 201);
