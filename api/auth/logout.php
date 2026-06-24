<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

// Ambil token dari header untuk dihapus
$headers    = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches);
$token = $matches[1] ?? '';

$stmt = $db->prepare("DELETE FROM sessions WHERE token = ? AND user_id = ?");
$stmt->bind_param('si', $token, $user_id);
$stmt->execute();
$stmt->close();
$db->close();

sendJSON(['success' => true, 'message' => 'Berhasil keluar']);
