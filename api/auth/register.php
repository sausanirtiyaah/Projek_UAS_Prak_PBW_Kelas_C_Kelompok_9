<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$body = getBody();
$name     = trim($body['name'] ?? '');
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if (empty($name) || empty($username) || empty($password)) {
    sendJSON(['success' => false, 'message' => 'Semua field wajib diisi'], 422);
}

if (strlen($username) < 3) {
    sendJSON(['success' => false, 'message' => 'Username minimal 3 karakter'], 422);
}

if (strlen($password) < 6) {
    sendJSON(['success' => false, 'message' => 'Password minimal 6 karakter'], 422);
}

$db = getDB();

$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Username sudah digunakan'], 409);
}
$stmt->close();

$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare("INSERT INTO users (name, username, password) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $name, $username, $hashed);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    sendJSON(['success' => false, 'message' => 'Gagal membuat akun: ' . $db->error], 500);
}

$user_id = $stmt->insert_id;
$stmt->close();

// Auto login: generate token
$token     = generateToken();
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

$stmt = $db->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $user_id, $token, $expiresAt);
$stmt->execute();
$stmt->close();

$db->close();

sendJSON([
    'success' => true, 
    'message' => 'Akun berhasil dibuat',
    'data' => [
        'token'    => $token,
        'name'     => $name,
        'username' => $username
    ]
], 201);
