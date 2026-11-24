<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Hanya akun dengan role admin atau ID 1 yang boleh masuk ke modul admin
$role = $_SESSION["role"] ?? "user";
$userId = (int)($_SESSION["user_id"] ?? 0);

if ($role !== "admin" && $userId !== 1) {
    header("Location: ../index.php");
    exit;
}

?>