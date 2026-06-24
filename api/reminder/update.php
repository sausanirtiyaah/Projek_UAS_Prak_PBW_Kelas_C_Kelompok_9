<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body = getBody();
$id   = isset($body['id']) ? (int) $body['id'] : 0;
$teks = trim($body['teks'] ?? '');
$ikon = trim($body['ikon'] ?? '🔔');

if (!$id || empty($teks)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Teks reminder wajib diisi'], 422);
}

$stmt = $db->prepare("UPDATE reminders SET teks = ?, ikon = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('ssii', $teks, $ikon, $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $cek = $db->prepare("SELECT id FROM reminders WHERE id = ? AND user_id = ?");
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
sendJSON(['success' => true, 'message' => 'Reminder berhasil diperbarui']);
