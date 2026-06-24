<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body      = getBody();
$jam_tidur = trim($body['jam_tidur'] ?? '');
$jam_bangun = trim($body['jam_bangun'] ?? '');
$tanggal   = trim($body['tanggal'] ?? date('Y-m-d'));
$catatan   = trim($body['catatan'] ?? '');

if (empty($jam_tidur) || empty($jam_bangun)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Jam tidur dan jam bangun wajib diisi'], 422);
}

// Hitung durasi_menit
// Konversi ke menit dari tengah malam
$toMinutes = function($time) {
    list($h, $m) = explode(':', $time);
    return (int)$h * 60 + (int)$m;
};

$menitTidur  = $toMinutes($jam_tidur);
$menitBangun = $toMinutes($jam_bangun);

// Jika jam_bangun < jam_tidur berarti melewati tengah malam
if ($menitBangun <= $menitTidur) {
    $menitBangun += 24 * 60; // tambah 24 jam
}

$durasi_menit = $menitBangun - $menitTidur;

$stmt = $db->prepare(
    "INSERT INTO tidur (user_id, jam_tidur, jam_bangun, durasi_menit, tanggal, catatan) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('ississ', $user_id, $jam_tidur, $jam_bangun, $durasi_menit, $tanggal, $catatan);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal mencatat tidur: ' . $db->error], 500);
}

$newId = $stmt->insert_id;
$stmt->close();
$db->close();

sendJSON([
    'success' => true,
    'data'    => ['id' => $newId, 'durasi_menit' => $durasi_menit],
    'message' => 'Data tidur berhasil dicatat'
], 201);
