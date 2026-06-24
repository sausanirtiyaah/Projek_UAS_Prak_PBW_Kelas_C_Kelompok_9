<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body       = getBody();
$id         = isset($body['id']) ? (int) $body['id'] : 0;
$jam_tidur  = trim($body['jam_tidur'] ?? '');
$jam_bangun = trim($body['jam_bangun'] ?? '');
$tanggal    = trim($body['tanggal'] ?? '');
$catatan    = trim($body['catatan'] ?? '');

if (!$id || empty($jam_tidur) || empty($jam_bangun) || empty($tanggal)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Semua field wajib diisi'], 422);
}

// Hitung durasi_menit
$toMinutes = function($time) {
    list($h, $m) = explode(':', $time);
    return (int)$h * 60 + (int)$m;
};

$menitTidur  = $toMinutes($jam_tidur);
$menitBangun = $toMinutes($jam_bangun);

if ($menitBangun <= $menitTidur) {
    $menitBangun += 24 * 60; // tambah 24 jam
}

$durasi_menit = $menitBangun - $menitTidur;

$stmt = $db->prepare("UPDATE tidur SET jam_tidur = ?, jam_bangun = ?, durasi_menit = ?, catatan = ?, tanggal = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('ssissii', $jam_tidur, $jam_bangun, $durasi_menit, $catatan, $tanggal, $id, $user_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $cek = $db->prepare("SELECT id FROM tidur WHERE id = ? AND user_id = ?");
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
sendJSON(['success' => true, 'message' => 'Data tidur berhasil diperbarui']);
