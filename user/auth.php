<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    $currentUri = $_SERVER["REQUEST_URI"] ?? "";
    $redirectParam = "";
    if (!empty($currentUri)) {
        $basename = basename(parse_url($currentUri, PHP_URL_PATH));
        $query = parse_url($currentUri, PHP_URL_QUERY);
        $redirectTarget = $basename . ($query ? '?' . $query : '');
        $redirectParam = "?redirect=" . urlencode($redirectTarget);
    }
    header("Location: login.php" . $redirectParam);
    exit;
}
