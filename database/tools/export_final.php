<?php
// CLI Tool: Export database ke /database/toko_preloved_final.sql
require_once __DIR__ . '/../../db.php';

$exportFile = __DIR__ . '/../toko_preloved_final.sql';

$tables = [];
$tablesRes = $conn->query("SHOW TABLES");
while ($row = $tablesRes->fetch_row()) {
    $tables[] = $row[0];
}

$sqlDump = "-- ========================================================\n";
$sqlDump .= "-- Toko Preloved Final SQL Dump (Ready for phpMyAdmin)\n";
$sqlDump .= "-- Database: secondspace_preloved\n";
$sqlDump .= "-- Exported: " . date('Y-m-d H:i:s') . "\n";
$sqlDump .= "-- ========================================================\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sqlDump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$sqlDump .= "START TRANSACTION;\n";
$sqlDump .= "SET time_zone = '+00:00';\n\n";

foreach ($tables as $table) {
    // Structure
    $createRes = $conn->query("SHOW CREATE TABLE `$table`");
    $createRow = $createRes->fetch_row();
    $sqlDump .= "-- --------------------------------------------------------\n";
    $sqlDump .= "-- Struktur tabel `$table`\n";
    $sqlDump .= "-- --------------------------------------------------------\n";
    $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlDump .= $createRow[1] . ";\n\n";

    // Data
    $dataRes = $conn->query("SELECT * FROM `$table`");
    $rowCount = $dataRes->num_rows;
    if ($rowCount > 0) {
        $sqlDump .= "-- Data untuk tabel `$table`\n";
        $fieldsRes = $conn->query("SHOW COLUMNS FROM `$table`");
        $fields = [];
        while ($f = $fieldsRes->fetch_assoc()) {
            $fields[] = "`" . $f['Field'] . "`";
        }
        $fieldsList = implode(", ", $fields);

        while ($row = $dataRes->fetch_assoc()) {
            $values = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . $conn->real_escape_string($val) . "'";
                }
            }
            $sqlDump .= "INSERT INTO `$table` ($fieldsList) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sqlDump .= "\n";
    }
}

$sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
$sqlDump .= "COMMIT;\n";

file_put_contents($exportFile, $sqlDump);
echo "SUCCESS: Database diekspor ke: " . realpath($exportFile) . " (" . round(filesize($exportFile) / 1024, 2) . " KB)\n";
