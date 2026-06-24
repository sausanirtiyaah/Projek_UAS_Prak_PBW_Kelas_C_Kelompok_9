<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$body     = getBody();
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if (empty($username) || empty($password)) {
    sendJSON(['success' => false, 'message' => 'Username dan password wajib diisi'], 422);
}

$db = getDB();

$stmt = $db->prepare("SELECT id, name, username, password FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Username atau password salah'], 401);
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password'])) {
    $db->close();
    sendJSON(['success' => false, 'message' => 'Username atau password salah'], 401);
}

// Generate token dan simpan ke sessions
$token     = generateToken();
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

$stmt = $db->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $user['id'], $token, $expiresAt);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal membuat sesi: ' . $db->error], 500);
}

$stmt->close();
$db->close();

sendJSON([
    'success' => true,
    'message' => 'Login berhasil',
    'data' => [
        'token'    => $token,
        'name'     => $user['name'],
        'username' => $user['username'],
    ]
]);
