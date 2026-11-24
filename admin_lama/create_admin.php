<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../db.php";

/** @var mysqli $conn */

// Cek apakah akun admin sudah ada
$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin' OR role = 'admin' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo "
        <h2>Akun Admin Sudah Ada</h2>
        <p>Akun admin default sudah terdaftar di database.</p>
        <p>Username: <strong>admin</strong> | Password: <strong>admin123</strong></p>
        <a href='index.php'>Masuk ke Dashboard Admin</a>
    ";
    exit;
}

$defaultAdminPass = password_hash("admin123", PASSWORD_DEFAULT);
$stmtInsert = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES ('Administrator', 'admin', ?, 'admin')");
$stmtInsert->bind_param("s", $defaultAdminPass);
$stmtInsert->execute();

echo "
    <h2>Pembuatan Akun Admin Berhasil</h2>
    <p>Akun admin berhasil dibuat.</p>
    <p>Username: <strong>admin</strong> | Password: <strong>admin123</strong></p>
    <a href='../login.php'>Login Sekarang</a>
";
?>
