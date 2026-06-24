<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
}

$db      = getDB();
$user_id = verifyToken($db);

// ============================================================
// 1. Total air minum & rata target 7 hari terakhir
// ============================================================
$stmtAir = $db->prepare(
    "SELECT COALESCE(SUM(jumlah), 0) AS total_air, COALESCE(AVG(target), 8) AS rata_target_air
     FROM air_minum
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
);
$stmtAir->bind_param('i', $user_id);
$stmtAir->execute();
$resAir      = $stmtAir->get_result()->fetch_assoc();
$stmtAir->close();

// ============================================================
// 2. Total makan sehat & kurang sehat 7 hari terakhir
// ============================================================
$stmtMakan = $db->prepare(
    "SELECT 
        SUM(CASE WHEN kategori = 'sehat' THEN 1 ELSE 0 END) AS total_makan_sehat,
        SUM(CASE WHEN kategori = 'kurang sehat' THEN 1 ELSE 0 END) AS total_makan_kurang
     FROM makanan
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
);
$stmtMakan->bind_param('i', $user_id);
$stmtMakan->execute();
$resMakan = $stmtMakan->get_result()->fetch_assoc();
$stmtMakan->close();

// ============================================================
// 3. Total mie instan (nama mengandung kata kunci mie instan)
// ============================================================
$stmtMie = $db->prepare(
    "SELECT COUNT(*) AS total_mie FROM makanan
     WHERE user_id = ? 
       AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       AND (LOWER(nama) LIKE '%mie instan%' 
         OR LOWER(nama) LIKE '%mi instan%' 
         OR LOWER(nama) LIKE '%mie%instan%'
         OR LOWER(nama) LIKE '%indomie%'
         OR LOWER(nama) LIKE '%supermi%')"
);
$stmtMie->bind_param('i', $user_id);
$stmtMie->execute();
$resMie = $stmtMie->get_result()->fetch_assoc();
$stmtMie->close();

// ============================================================
// 4. Total keluhan 7 hari terakhir
// ============================================================
$stmtKeluhan = $db->prepare(
    "SELECT COUNT(*) AS total_keluhan FROM keluhan
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
);
$stmtKeluhan->bind_param('i', $user_id);
$stmtKeluhan->execute();
$resKeluhan = $stmtKeluhan->get_result()->fetch_assoc();
$stmtKeluhan->close();

// ============================================================
// 5. Rata-rata tidur 7 hari terakhir
// ============================================================
$stmtTidur = $db->prepare(
    "SELECT COALESCE(AVG(durasi_menit), 0) AS rata_tidur_menit FROM tidur
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
);
$stmtTidur->bind_param('i', $user_id);
$stmtTidur->execute();
$resTidur = $stmtTidur->get_result()->fetch_assoc();
$stmtTidur->close();

// ============================================================
// 6. Total reminder selesai 7 hari terakhir
// ============================================================
$stmtReminder = $db->prepare(
    "SELECT COALESCE(SUM(is_done), 0) AS total_reminder_done FROM reminders
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
);
$stmtReminder->bind_param('i', $user_id);
$stmtReminder->execute();
$resReminder = $stmtReminder->get_result()->fetch_assoc();
$stmtReminder->close();

// ============================================================
// 7. Detail air per hari (7 hari)
// ============================================================
$stmtDetailAir = $db->prepare(
    "SELECT tanggal, jumlah, target FROM air_minum
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     ORDER BY tanggal ASC"
);
$stmtDetailAir->bind_param('i', $user_id);
$stmtDetailAir->execute();
$resDetailAir = $stmtDetailAir->get_result();
$detailAir = [];
while ($row = $resDetailAir->fetch_assoc()) {
    $row['jumlah'] = (int) $row['jumlah'];
    $row['target'] = (int) $row['target'];
    $detailAir[] = $row;
}
$stmtDetailAir->close();

// ============================================================
// 8. Detail tidur per hari (7 hari)
// ============================================================
$stmtDetailTidur = $db->prepare(
    "SELECT tanggal, durasi_menit FROM tidur
     WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     ORDER BY tanggal ASC"
);
$stmtDetailTidur->bind_param('i', $user_id);
$stmtDetailTidur->execute();
$resDetailTidur = $stmtDetailTidur->get_result();
$detailTidur = [];
while ($row = $resDetailTidur->fetch_assoc()) {
    $row['durasi_menit'] = (int) $row['durasi_menit'];
    $detailTidur[] = $row;
}
$stmtDetailTidur->close();
$db->close();

// ============================================================
// 9. Kalkulasi Skor Kesehatan (0–100)
// ============================================================
$rataTidurMenit    = (float) $resTidur['rata_tidur_menit'];
$totalAir          = (int)   $resAir['total_air'];
$rataTargetAir     = (float) $resAir['rata_target_air'];
$totalMakanSehat   = (int)   $resMakan['total_makan_sehat'];
$totalMakanKurang  = (int)   $resMakan['total_makan_kurang'];

// Skor tidur: ideal 420–540 menit (7–9 jam), maks 40 poin
$skorTidur = 0;
if ($rataTidurMenit >= 420 && $rataTidurMenit <= 540) {
    $skorTidur = 40;
} elseif ($rataTidurMenit > 0) {
    $ideal = 480; // 8 jam
    $selisih = abs($rataTidurMenit - $ideal);
    $skorTidur = max(0, 40 - ($selisih / $ideal) * 40);
}

// Skor air: rata jumlah/target * 35 poin
$skorAir = 0;
if ($rataTargetAir > 0) {
    $rataJumlahAir = count($detailAir) > 0 ? array_sum(array_column($detailAir, 'jumlah')) / count($detailAir) : 0;
    $skorAir = min(35, ($rataJumlahAir / $rataTargetAir) * 35);
}

// Skor makan sehat: 25 poin berdasarkan rasio sehat/(sehat+kurang sehat)
$skorMakan = 0;
$totalMakanAll = $totalMakanSehat + $totalMakanKurang;
if ($totalMakanAll > 0) {
    $skorMakan = ($totalMakanSehat / $totalMakanAll) * 25;
}

$skorKesehatan = (int) round($skorTidur + $skorAir + $skorMakan);
$skorKesehatan = max(0, min(100, $skorKesehatan));

// ============================================================
// Response
// ============================================================
sendJSON([
    'success' => true,
    'message' => 'Ringkasan mingguan berhasil diambil',
    'data' => [
        'total_air'           => (int)   $resAir['total_air'],
        'rata_target_air'     => round((float) $resAir['rata_target_air'], 1),
        'total_makan_sehat'   => (int)   $resMakan['total_makan_sehat'],
        'total_makan_kurang'  => (int)   $resMakan['total_makan_kurang'],
        'total_mie'           => (int)   $resMie['total_mie'],
        'total_keluhan'       => (int)   $resKeluhan['total_keluhan'],
        'rata_tidur_menit'    => round((float) $resTidur['rata_tidur_menit'], 1),
        'total_reminder_done' => (int)   $resReminder['total_reminder_done'],
        'skor_kesehatan'      => $skorKesehatan,
        'detail_air'          => $detailAir,
        'detail_tidur'        => $detailTidur,
    ]
]);
