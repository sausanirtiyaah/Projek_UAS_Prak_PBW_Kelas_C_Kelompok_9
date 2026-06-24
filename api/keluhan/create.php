<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body       = getBody();
$jenis      = trim($body['jenis'] ?? '');
$catatan    = trim($body['catatan'] ?? '');
$intensitas = trim($body['intensitas'] ?? '');
$tanggal    = trim($body['tanggal'] ?? date('Y-m-d'));

if (empty($jenis) || empty($intensitas)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Jenis keluhan dan intensitas wajib diisi'], 422);
}

$validIntensitas = ['ringan', 'sedang', 'berat'];
if (!in_array($intensitas, $validIntensitas)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Intensitas tidak valid. Gunakan: ringan, sedang, berat'], 422);
}

$stmt = $db->prepare(
    "INSERT INTO keluhan (user_id, jenis, catatan, intensitas, tanggal) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('issss', $user_id, $jenis, $catatan, $intensitas, $tanggal);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal mencatat keluhan: ' . $db->error], 500);
}

$newId = $stmt->insert_id;
$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => ['id' => $newId], 'message' => 'Keluhan berhasil dicatat'], 201);
