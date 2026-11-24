<?php
$phpFiles = glob(__DIR__ . '/../../{*.php,user/*.php,admin/*.php,shared/*.php}', GLOB_BRACE);
$errors = [];
foreach ($phpFiles as $f) {
    $cmd = 'php -l "' . $f . '" 2>&1';
    $out = shell_exec($cmd);
    if (strpos($out, 'No syntax errors detected') === false) {
        $errors[] = basename(dirname($f)) . '/' . basename($f) . ": " . trim($out);
    }
}

if (empty($errors)) {
    echo "LINT BERHASIL: Semua file PHP (" . count($phpFiles) . " file) di root, user/, admin/, shared/ lolos uji syntax tanpa error!\n";
} else {
    echo "LINT GAGAL:\n" . implode("\n", $errors) . "\n";
}
