<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

$body = getBody();
$id   = isset($body['id']) ? (int) $body['id'] : 0;

if ($id <= 0) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'ID reminder tidak valid'], 422);
}

// Cek reminder milik user dan ambil status is_done saat ini
$stmt = $db->prepare("SELECT id, is_done FROM reminders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Reminder tidak ditemukan'], 404);
}

$reminder = $result->fetch_assoc();
$stmt->close();

// Toggle is_done: 0 → 1, 1 → 0
$newStatus = $reminder['is_done'] == 0 ? 1 : 0;

$stmtUpdate = $db->prepare("UPDATE reminders SET is_done = ? WHERE id = ? AND user_id = ?");
$stmtUpdate->bind_param('iii', $newStatus, $id, $user_id);

if (!$stmtUpdate->execute()) {
    $stmtUpdate->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal mengupdate reminder: ' . $db->error], 500);
}

$stmtUpdate->close();
$db->close();

sendJSON([
    'success' => true,
    'data'    => ['id' => $id, 'is_done' => $newStatus],
    'message' => $newStatus === 1 ? 'Reminder ditandai selesai' : 'Reminder ditandai belum selesai'
]);
