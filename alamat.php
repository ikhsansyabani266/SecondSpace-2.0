<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "db.php";

/** @var mysqli $conn */

$userId = $_SESSION["user_id"];
$message = "";
$error = "";

// SIMPAN ALAMAT BARU
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $recipientName = trim($_POST["recipient_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $postalCode = trim($_POST["postal_code"] ?? "");

    if ($recipientName === "" || $phone === "" || $address === "" || $province === "" || $city === "" || $postalCode === "") {
        $error = "Semua data alamat wajib diisi.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO addresses (user_id, recipient_name, phone, address, province, city, postal_code)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt) {
            $stmt->bind_param("issssss", $userId, $recipientName, $phone, $address, $province, $city, $postalCode);
            if ($stmt->execute()) {
                $message = "Alamat berhasil disimpan.";
            } else {
                $error = "Alamat gagal disimpan. Silakan coba lagi.";
            }
        }
    }
}

// AMBIL ALAMAT USER
$addresses = null;
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $addresses = $stmt->get_result();
}

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Alamat — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .address-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 32px;
        }

        .address-form-card,
        .address-list-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 32px;
        }

        .section-title {
            font-family: var(--display-font);
            font-size: 26px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--tx);
            margin-bottom: 8px;
        }

        .section-subtitle {
            color: var(--mu);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .address-item {
            background: var(--s2);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 16px;
            position: relative;
        }

        .address-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .address-recipient {
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 700;
            color: var(--tx);
            text-transform: uppercase;
        }

        .address-badge {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: var(--ac);
            color: #000000;
            padding: 3px 8px;
            border-radius: 999px;
        }

        .address-phone {
            font-size: 13px;
            color: var(--mu);
            margin-bottom: 10px;
        }

        .address-detail {
            font-size: 14px;
            color: var(--tx);
            line-height: 1.6;
        }

        .empty-address {
            text-align: center;
            padding: 40px 20px;
            color: var(--mu);
        }

        @media (max-width: 850px) {
            .address-layout {
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
        <a href="logout.php" class="logout">KELUAR</a>
    </div>
</nav>

<div class="container">

    <div class="breadcrumb">
        <a href="index.php">HOME</a>
        <span class="separator">/</span>
        <span>BUKU ALAMAT</span>
    </div>

    <div class="address-layout">
        <!-- FORM TAMBAH ALAMAT -->
        <div class="address-form-card">
            <h2 class="section-title">TAMBAH ALAMAT BARU</h2>
            <p class="section-subtitle">Simpan alamat pengiriman kamu untuk mempermudah proses checkout pesanan.</p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error" style="background: rgba(185, 28, 28, 0.2); border: 1px solid var(--alert); color: #fca5a5; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <div class="alert alert-success" style="background: rgba(124, 154, 109, 0.2); border: 1px solid var(--ac); color: var(--ac); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="alamat.php">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 6px;">NAMA PENERIMA</label>
                    <input type="text" name="recipient_name" placeholder="Nama Lengkap Penerima" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 6px;">NOMOR TELEPON / WHATSAPP</label>
                    <input type="text" name="phone" placeholder="08xxxxxxxxxx" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 6px;">ALAMAT LENGKAP</label>
                    <textarea name="address" rows="3" placeholder="Nama Jalan, Nomor Rumah, RT/RW, Patokan" required style="resize: vertical;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 6px;">KOTA / KABUPATEN</label>
                        <input type="text" name="city" placeholder="Contoh: Jakarta Selatan" required>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 6px;">PROVINSI</label>
                        <input type="text" name="province" placeholder="Contoh: DKI Jakarta" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tx); margin-bottom: 6px;">KODE POS</label>
                    <input type="text" name="postal_code" placeholder="Contoh: 12345" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">SIMPAN ALAMAT</button>
            </form>
        </div>

        <!-- DAFTAR ALAMAT TERSIMPAN -->
        <div class="address-list-card">
            <h2 class="section-title">ALAMAT TERSIMPAN</h2>
            <p class="section-subtitle">Daftar alamat yang siap kamu gunakan saat checkout pesanan.</p>

            <?php if ($addresses && $addresses->num_rows > 0): ?>
                <?php $i = 1; while ($addr = $addresses->fetch_assoc()): ?>
                    <div class="address-item">
                        <div class="address-item-header">
                            <span class="address-recipient"><?= htmlspecialchars($addr["recipient_name"]) ?></span>
                            <?php if ($i === 1): ?>
                                <span class="address-badge">UTAMA</span>
                            <?php endif; ?>
                        </div>
                        <div class="address-phone">📞 <?= htmlspecialchars($addr["phone"]) ?></div>
                        <div class="address-detail">
                            <?= nl2br(htmlspecialchars($addr["address"])) ?><br>
                            <?= htmlspecialchars($addr["city"]) ?>, <?= htmlspecialchars($addr["province"]) ?> <?= htmlspecialchars($addr["postal_code"]) ?>
                        </div>
                    </div>
                <?php $i++; endwhile; ?>
            <?php else: ?>
                <div class="empty-address">
                    <div style="font-size: 36px; margin-bottom: 12px;">📍</div>
                    <h3 style="color: var(--tx); font-family: var(--display-font); font-size: 20px; margin-bottom: 6px;">Belum Ada Alamat</h3>
                    <p>Silakan tambahkan alamat pengiriman kamu melalui formulir di sebelah kiri.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
