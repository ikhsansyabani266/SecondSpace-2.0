<?php
// Update require paths in user/*.php to use /shared/koneksi.php and /shared/fungsi.php
$userDir = __DIR__ . '/../../user';
$files = glob($userDir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace require db.php
    $updated = preg_replace(
        '/require_once\s+[\'"]db\.php[\'"];/i',
        "require_once __DIR__ . '/../shared/koneksi.php';\nrequire_once __DIR__ . '/../shared/fungsi.php';",
        $content
    );
    
    // If not found, check if it had require 'db.php'
    $updated = preg_replace(
        '/require\s+[\'"]db\.php[\'"];/i',
        "require_once __DIR__ . '/../shared/koneksi.php';\nrequire_once __DIR__ . '/../shared/fungsi.php';",
        $updated
    );

    if ($updated !== $content) {
        file_put_contents($file, $updated);
        echo "Updated DB require in: " . basename($file) . "\n";
    }
}
