<?php

function generateToken() {
    return bin2hex(random_bytes(32));
}

function verifyToken($db) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token tidak ditemukan atau format tidak valid']);
        exit;
    }

    $token = $matches[1];
    $stmt = $db->prepare("SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kedaluwarsa']);
        exit;
    }

    $row = $result->fetch_assoc();
    $stmt->close();
    return (int) $row['user_id'];
}

function sendJSON($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getBody() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
