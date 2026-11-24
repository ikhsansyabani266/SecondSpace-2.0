<?php
// [FILE] login.php — Pintu Masuk Autentikasi Khusus Pengelola Toko (Admin)
// Dipakai oleh: Administrator SecondSpace
// Tabel database disentuh: users
// Fungsi bersama: /shared/config.php, /shared/koneksi.php, /shared/fungsi.php

require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

// [BAGIAN: Cek Sesi Eksisting]
if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["role"] ?? "user";
    $userId = (int)($_SESSION["user_id"] ?? 0);
    if ($role === "admin" || $userId === 1) {
        header("Location: index.php");
        exit;
    }
}
// [/BAGIAN: Cek Sesi Eksisting]

$errorMsg = "";

// [AKSI: Proses Autentikasi Admin POST]
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // [KEAMANAN: Verifikasi Token CSRF]
    if (!csrf_verify()) {
        $errorMsg = "Sesi keamanan form telah berakhir. Silakan muat ulang halaman.";
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        // [VALIDASI: Kelengkapan Input]
        if ($username === "" || $password === "") {
            $errorMsg = "Username dan password wajib diisi.";
        } else {
            // [QUERY: Cari Akun Admin di Tabel users]
            $stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                $admin = $res ? $res->fetch_assoc() : null;
                $stmt->close();

                // [KEAMANAN: Verifikasi Hash Password & Hak Akses]
                if ($admin && password_verify($password, $admin["password"])) {
                    $role = $admin["role"] ?? ($admin["id"] == 1 ? "admin" : "user");
                    if ($role !== "admin" && (int)$admin["id"] !== 1) {
                        $errorMsg = "Akses ditolak: Akun Anda tidak memiliki wewenang administrator.";
                    } else {
                        // [JANGAN UBAH: Regenerasi ID Sesi untuk Cegah Session Fixation]
                        session_regenerate_id(true);
                        $_SESSION["user_id"]  = (int)$admin["id"];
                        $_SESSION["username"] = $admin["username"];
                        $_SESSION["name"]     = $admin["name"];
                        $_SESSION["role"]     = "admin";

                        flash_set("success", "Selamat datang kembali, " . htmlspecialchars($admin["name"]) . "!");
                        header("Location: index.php");
                        exit;
                    }
                } else {
                    $errorMsg = "Username atau password yang Anda masukkan salah.";
                }
            } else {
                $errorMsg = "Terjadi gangguan sistem koneksi.";
            }
        }
    }
}
// [/AKSI: Proses Autentikasi Admin POST]
?>
<!-- [TAMPILAN: Halaman Login Admin Streetwear] -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access — <?= NAMA_TOKO ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .login-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #070809;
            background-image: radial-gradient(circle at 50% 30%, rgba(124, 154, 109, 0.08) 0%, transparent 70%);
        }
        .login-box {
            width: 100%;
            max-width: 420px;
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: var(--radius-lg);
            padding: 40px 32px;
            box-shadow: var(--shadow-lg);
        }
        .login-brand {
            font-family: var(--body-font);
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 0.05em;
            color: var(--tx);
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 6px;
        }
        .login-brand span {
            color: var(--ac);
        }
        .login-sub {
            text-align: center;
            color: var(--mu);
            font-size: 13px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="login-screen">

<div class="login-box">
    <!-- [BAGIAN: Brand Header] -->
    <div class="login-brand"><?= NAMA_TOKO ?><span>.</span></div>
    <div class="login-sub">Portal Manajemen Toko Preloved</div>
    <!-- [/BAGIAN: Brand Header] -->

    <!-- [BAGIAN: Notifikasi Error] -->
    <?php if (!empty($errorMsg)): ?>
        <div class="flash-msg error">
            <span>⚠️</span> <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>
    <!-- [/BAGIAN: Notifikasi Error] -->

    <!-- [BAGIAN: Formulir Login POST] -->
    <form method="POST" action="login.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="username">Username Admin</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group" style="margin-bottom: 28px;">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 14px;">
            MASUK KE DASHBOARD ADMIN
        </button>
    </form>
    <!-- [/BAGIAN: Formulir Login POST] -->

    <div style="margin-top: 24px; text-align: center; font-size: 12px; color: #555;">
        <a href="../user/index.php" style="color: var(--mu); text-decoration: none;">&larr; Kembali ke Halaman Toko</a>
    </div>
</div>

</body>
</html>
<!-- [/TAMPILAN: Halaman Login Admin Streetwear] -->
