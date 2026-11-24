<?php
// Script Audit JavaScript di FASE 1
$files = glob(__DIR__ . '/../../{*.php,admin/*.php,*.html,*.js}', GLOB_BRACE);

$jsFindings = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $relFile = basename(dirname($file)) === 'admin' ? 'admin/' . basename($file) : basename($file);

    foreach ($lines as $lineNum => $line) {
        $num = $lineNum + 1;
        // Cek tag script
        if (stripos($line, '<script') !== false) {
            $jsFindings[] = ['file' => $relFile, 'line' => $num, 'type' => '<script> tag', 'code' => trim($line)];
        }
        // Cek event inline onclick, onchange, dll
        if (preg_match('/\b(on\w+)\s*=/i', $line, $matches)) {
            $jsFindings[] = ['file' => $relFile, 'line' => $num, 'type' => 'Inline event (' . $matches[1] . ')', 'code' => trim($line)];
        }
        // Cek href="javascript:..."
        if (stripos($line, 'javascript:') !== false) {
            $jsFindings[] = ['file' => $relFile, 'line' => $num, 'type' => 'href="javascript:..."', 'code' => trim($line)];
        }
    }
}

echo "=== TEMUAN PENGGUNAAN JAVASCRIPT ===\n";
echo "Total temuan: " . count($jsFindings) . "\n\n";
foreach ($jsFindings as $idx => $f) {
    echo sprintf("[%02d] %s:%d [%s]\n     %s\n", $idx + 1, $f['file'], $f['line'], $f['type'], mb_strimwidth($f['code'], 0, 100, "..."));
}
