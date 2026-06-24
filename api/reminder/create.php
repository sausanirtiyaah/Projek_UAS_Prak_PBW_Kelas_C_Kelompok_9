<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body = getBody();
$teks = trim($body['teks'] ?? '');
$ikon = trim($body['ikon'] ?? '🔔');

if (empty($teks)) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Teks reminder wajib diisi'], 422);
}

// Cek apakah sudah ada reminder hari ini untuk user ini
$stmtCek = $db->prepare("SELECT COUNT(*) as total FROM reminders WHERE user_id = ? AND tanggal = CURDATE()");
$stmtCek->bind_param('i', $user_id);
$stmtCek->execute();
$resCek = $stmtCek->get_result();
$cek    = $resCek->fetch_assoc();
$stmtCek->close();

// Jika tabel kosong untuk hari ini, insert 5 reminder default dulu
if ((int) $cek['total'] === 0) {
    $defaults = [
        ['teks' => 'Minum Air',         'ikon' => '💧'],
        ['teks' => 'Makan Tepat Waktu', 'ikon' => '🍽️'],
        ['teks' => 'Cek Stok Obat',     'ikon' => '💊'],
        ['teks' => 'Istirahat Cukup',   'ikon' => '😴'],
        ['teks' => 'Olahraga Ringan',   'ikon' => '🏃'],
    ];

    $stmtDef = $db->prepare("INSERT INTO reminders (user_id, teks, ikon, is_done, tanggal) VALUES (?, ?, ?, 0, CURDATE())");
    foreach ($defaults as $def) {
        $stmtDef->bind_param('iss', $user_id, $def['teks'], $def['ikon']);
        $stmtDef->execute();
    }
    $stmtDef->close();
}

// Insert reminder baru
$stmt = $db->prepare("INSERT INTO reminders (user_id, teks, ikon, is_done, tanggal) VALUES (?, ?, ?, 0, CURDATE())");
$stmt->bind_param('iss', $user_id, $teks, $ikon);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal menambah reminder: ' . $db->error], 500);
}

$newId = $stmt->insert_id;
$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => ['id' => $newId], 'message' => 'Reminder berhasil ditambahkan'], 201);
