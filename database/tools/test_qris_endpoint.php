<?php
$_GET['nominal'] = 145000;
ob_start();
require __DIR__ . '/../../user/qris_image.php';
$bin = ob_get_clean();

echo "Endpoint test success! PNG size: " . strlen($bin) . " bytes\n";
