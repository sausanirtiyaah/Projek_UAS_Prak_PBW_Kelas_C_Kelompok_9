<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

// Cek apakah sudah ada reminder hari ini
$stmtCek = $db->prepare("SELECT COUNT(*) AS total FROM reminders WHERE user_id = ? AND tanggal = CURDATE()");
$stmtCek->bind_param('i', $user_id);
$stmtCek->execute();
$cek = $stmtCek->get_result()->fetch_assoc();
$stmtCek->close();

// Auto-insert 5 reminder default jika belum ada hari ini
if ((int)$cek['total'] === 0) {
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

$stmt = $db->prepare(
    "SELECT id, teks, ikon, is_done, tanggal, created_at 
     FROM reminders 
     WHERE user_id = ? AND tanggal = CURDATE() 
     ORDER BY created_at ASC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reminders = [];
while ($row = $result->fetch_assoc()) {
    $row['is_done'] = (int) $row['is_done'];
    $reminders[] = $row;
}

$stmt->close();
$db->close();

sendJSON(['success' => true, 'data' => $reminders, 'message' => 'Reminder hari ini berhasil diambil']);
