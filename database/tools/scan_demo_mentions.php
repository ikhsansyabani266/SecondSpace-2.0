<?php
// Scan for demo/sementara/ilustrasi in user and admin files
$dir = new RecursiveDirectoryIterator(__DIR__ . '/../../');
$iter = new RecursiveIteratorIterator($dir);
$regex = new RegexIterator($iter, '/^.+\.(php|html|js)$/i', RecursiveRegexIterator::GET_MATCH);

$keywords = ['demo', 'sementara', 'ilustrasi'];
$results = [];

foreach ($regex as $file) {
    $filePath = str_replace('\\', '/', $file[0]);
    if (strpos($filePath, '/.git/') !== false || strpos($filePath, '/database/backup/') !== false || strpos($filePath, '/database/tools/') !== false) {
        continue;
    }
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    foreach ($lines as $num => $line) {
        foreach ($keywords as $kw) {
            if (stripos($line, $kw) !== false) {
                // Ignore is_demo column references if neutral
                $results[] = sprintf("%s:%d: %s", basename($filePath), $num + 1, trim($line));
                break;
            }
        }
    }
}

echo "DITEMUKAN " . count($results) . " KEMUNCULAN:\n";
foreach (array_slice($results, 0, 40) as $r) {
    echo $r . "\n";
}
