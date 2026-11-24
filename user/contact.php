<?php
/**
 * [FILE]: user/contact.php
 * [BAGIAN]: Halaman Kontak Kami & Form Pesan
 * [DESKRIPSI]: Menyediakan formulir pesan untuk pelanggan yang tersimpan ke tabel contacts
 * [JANGAN UBAH]: Sanitasi input prepared statement sebelum insert ke tabel contacts
 * [UBAH DI SINI]: Informasi kontak WhatsApp, email, dan alamat toko fisik
 * [TAMBAH DI SINI]: Input subjek pesan atau kategori pertanyaan
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

$success = "";
$error = "";

$name = "";
$email = "";
$message = "";

/* [AKSI]: Simpan Pesan Kontak Masuk (Pure PHP POST - No JS) */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $error = "Sesi keamanan formulir telah berakhir (CSRF). Silakan refresh dan coba lagi.";
    } else {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $message = trim($_POST["message"] ?? "");

        if ($name === "" || $email === "" || $message === "") {
            $error = "Semua data wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } else {
            $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $email, $message);
                if ($stmt->execute()) {
                    $success = "Pesan berhasil dikirim. Terima kasih sudah menghubungi kami.";
                    $name = "";
                    $email = "";
                    $message = "";
                } else {
                    $error = "Gagal mengirim pesan. Silakan coba lagi.";
                }
            } else {
                $error = "Terjadi kesalahan sistem.";
            }
        }
    }
}

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 32px;
        }

        .contact-info-card,
        .contact-form-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 32px;
        }

        .contact-title {
            font-family: var(--display-font);
            font-size: 28px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--tx);
            margin-bottom: 12px;
        }

        .contact-subtitle {
            color: var(--mu);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-top: 1px solid var(--ln);
        }

        .info-icon {
            font-size: 22px;
            color: var(--ac);
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--mu);
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            color: var(--tx);
            font-weight: 600;
        }

        .info-value a {
            color: var(--ac);
            text-decoration: none;
        }

        .info-value a:hover {
            text-decoration: underline;
        }

        @media (max-width: 800px) {
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
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
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="logout">KELUAR</a>
        <?php else: ?>
            <a href="login.php" style="color: var(--ac);">MASUK</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">

    <div class="breadcrumb">
        <a href="index.php">HOME</a>
        <span class="separator">/</span>
        <span>HUBUNGI KAMI</span>
    </div>

    <div class="contact-grid">
        <!-- INFO KONTAK -->
        <div class="contact-info-card">
            <h1 class="contact-title">KONTAK SECONDSPACE</h1>
            <p class="contact-subtitle">Punya pertanyaan seputar kurasi barang, ukuran, pengiriman, atau konfirmasi pesanan? Tim kami siap membantu kamu.</p>

            <div class="info-row">
                <div class="info-icon">[WA]</div>
                <div>
                    <div class="info-label">WhatsApp Customer Service</div>
                    <div class="info-value">
                        <a href="https://wa.me/<?= $whatsappNumber ?>?s=p" target="_blank" rel="noopener">+<?= $whatsappNumber ?></a>
                    </div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-icon">[IG]</div>
                <div>
                    <div class="info-label">Instagram Resmi</div>
                    <div class="info-value">
                        <a href="https://instagram.com/secondspace" target="_blank" rel="noopener">@secondspace</a>
                    </div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-icon">[HQ]</div>
                <div>
                    <div class="info-label">Studio & Drop Point</div>
                    <div class="info-value">Jakarta Selatan, DKI Jakarta, Indonesia</div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-icon">⏰</div>
                <div>
                    <div class="info-label">Jam Operasional</div>
                    <div class="info-value">Senin – Minggu: 10:00 – 22:00 WIB</div>
                </div>
            </div>
        </div>

        <!-- FORM KIRIM PESAN -->
        <div class="contact-form-card">
            <h2 class="contact-title">KIRIM PESAN</h2>
            <p class="contact-subtitle">Tinggalkan pesan dan email aktif kamu. Kami akan merespons secepat mungkin.</p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error" style="background: rgba(185, 28, 28, 0.2); border: 1px solid var(--alert); color: #fca5a5; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ""): ?>
                <div class="alert alert-success" style="background: rgba(124, 154, 109, 0.2); border: 1px solid var(--ac); color: var(--ac); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact.php">
                <?= csrf_field() ?>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 8px;">NAMA LENGKAP</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Masukkan nama kamu" required>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 8px;">EMAIL</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="email@contoh.com" required>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 8px;">PESAN / PERTANYAAN</label>
                    <textarea name="message" rows="5" placeholder="Tulis pesan atau pertanyaan kamu di sini..." required style="resize: vertical;"><?= htmlspecialchars($message) ?></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">KIRIM PESAN</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>
