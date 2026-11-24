<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

/** @var mysqli $conn */

// Validasi Whitelist Redirect
function getSafeRedirect($target): string {
    if (empty($target)) return "checkout.php";
    $path = parse_url($target, PHP_URL_PATH);
    $query = parse_url($target, PHP_URL_QUERY);
    $base = basename((string)$path);
    $allowed = ["checkout.php", "keranjang.php", "produk.php", "detail.php", "index.php"];

    if (in_array($base, $allowed, true)) {
        if ($base === "detail.php" && !empty($query)) {
            parse_str($query, $params);
            if (isset($params["id"]) && is_numeric($params["id"])) {
                return "detail.php?id=" . intval($params["id"]);
            }
        }
        return $base;
    }
    return "checkout.php";
}

$redirectTarget = getSafeRedirect($_GET["redirect"] ?? $_POST["redirect"] ?? "checkout.php");

// Jika sudah login, langsung arahkan ke tujuan
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin" || (int)$_SESSION["user_id"] === 1) {
        header("Location: admin/index.php");
        exit;
    }
    header("Location: " . $redirectTarget);
    exit;
}

$registerError = "";

// Handler Form Register
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["reg_name"] ?? "");
    $username = trim($_POST["reg_username"] ?? "");
    $password = $_POST["reg_password"] ?? "";
    $confirmPassword = $_POST["reg_confirm"] ?? "";

    if ($name === "" || $username === "" || $password === "") {
        $registerError = "Semua kolom pendaftaran wajib diisi.";
    } elseif ($password !== $confirmPassword) {
        $registerError = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $registerError = "Password minimal 6 karakter.";
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if ($stmtCheck) {
            $stmtCheck->bind_param("s", $username);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            if ($resCheck && $resCheck->num_rows > 0) {
                $registerError = "Username sudah digunakan. Silakan pilih username lain.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmtIns = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmtIns) {
                    $stmtIns->bind_param("sss", $name, $username, $hashed);
                    if ($stmtIns->execute()) {
                        $newId = (int)$conn->insert_id;
                        $stmtIns->close();

                        // Login otomatis dan tetap simpan keranjang
                        $existingCart = $_SESSION["cart"] ?? [];
                        session_regenerate_id(true);

                        $_SESSION["user_id"] = $newId;
                        $_SESSION["username"] = $username;
                        $_SESSION["name"] = $name;
                        $_SESSION["role"] = "user";
                        $_SESSION["cart"] = $existingCart;

                        header("Location: " . $redirectTarget);
                        exit;
                    } else {
                        $registerError = "Gagal membuat akun, silakan coba lagi.";
                    }
                }
            }
            $stmtCheck->close();
        }
    }
}

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun Baru — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .auth-wrapper {
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px 80px;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 6px;
            padding: 38px 32px;
            box-sizing: border-box;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .auth-eyebrow {
            display: inline-block;
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--ac);
            margin-bottom: 8px;
        }

        .auth-title {
            font-family: var(--display-font);
            font-size: 32px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: var(--tx);
            margin: 0 0 8px 0;
            line-height: 1.1;
        }

        .auth-subtitle {
            color: var(--mu);
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        .cart-safe-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(124, 154, 109, 0.12);
            border: 1px solid rgba(124, 154, 109, 0.3);
            color: var(--tx);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 14px;
        }

        .alert-error {
            background: rgba(185, 28, 28, 0.15);
            border: 1px solid var(--alert);
            color: #FCA5A5;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.4;
            text-align: center;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            color: var(--tx);
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            height: 44px;
            background: var(--bg);
            border: 1px solid var(--ln);
            border-radius: 4px;
            color: var(--tx);
            padding: 0 14px;
            font-size: 14px;
            font-family: var(--body-font);
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus {
            border-color: var(--ac);
        }

        .btn-auth-submit {
            width: 100%;
            height: 46px;
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-auth-submit:hover {
            background: var(--ach);
            border-color: var(--ach);
            transform: translateY(-1px);
        }

        .auth-divider {
            position: relative;
            text-align: center;
            margin: 24px 0 18px;
        }

        .auth-divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: var(--ln);
            z-index: 1;
        }

        .auth-divider span {
            position: relative;
            z-index: 2;
            background: var(--s1);
            padding: 0 12px;
            color: var(--mu);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .btn-switch-auth {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 44px;
            background: transparent;
            color: var(--tx);
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .btn-switch-auth:hover {
            border-color: var(--tx);
            background: rgba(255, 255, 255, 0.03);
        }
    </style>
</head>
<body>

<!-- GLOBAL NAVBAR PILL -->
<nav class="navbar">
    <a href="index.php" class="logo">
        <span class="logo-second">SECOND</span><span class="logo-space">SPΛCE</span><span class="logo-reg">®</span>
    </a>
    <div class="nav-menu">
        <a href="index.php" class="nav-dashboard">DASHBOARD</a>
        <a href="produk.php">PRODUK</a>
        <a href="keranjang.php">
            KERANJANG
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <a href="pesanan.php" class="nav-pesanan">PESANAN</a>
        <a href="login.php" class="active">MASUK</a>
    </div>
</nav>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-header">
            <span class="auth-eyebrow">REGISTRASI AKUN</span>
            <h1 class="auth-title">BUAT AKUN BARU</h1>
            <p class="auth-subtitle">Daftar instan dalam hitungan detik untuk menyelesaikan pesanan.</p>
            <?php if ($cartCount > 0): ?>
                <div class="cart-safe-pill">
                    🔒 <?= $cartCount ?> produk di keranjang tetap aman
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($registerError)): ?>
            <div class="alert-error"><?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">

            <div class="form-group">
                <label for="reg_name">Nama Lengkap</label>
                <input type="text" id="reg_name" name="reg_name" placeholder="Nama lengkap penerima" required autofocus>
            </div>

            <div class="form-group">
                <label for="reg_username">Username</label>
                <input type="text" id="reg_username" name="reg_username" placeholder="Pilih username unik" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="reg_password">Password</label>
                <input type="password" id="reg_password" name="reg_password" placeholder="Minimal 6 karakter" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="reg_confirm">Konfirmasi Password</label>
                <input type="password" id="reg_confirm" name="reg_confirm" placeholder="Ulangi password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-auth-submit">DAFTAR SEKARANG</button>
        </form>

        <div class="auth-divider">
            <span>Sudah Punya Akun?</span>
        </div>

        <a href="login.php?redirect=<?= urlencode($redirectTarget) ?>" class="btn-switch-auth">
            MASUK KE AKUN
        </a>

    </div>
</div>

</body>
</html>
