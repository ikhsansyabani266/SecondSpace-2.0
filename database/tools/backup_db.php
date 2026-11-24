<?php
// CLI Tool: Full database backup script via PHP
require_once __DIR__ . '/../../db.php';

$backupDir = __DIR__ . '/../backup';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$dateStr = date('Y-m-d_His');
$backupFile = $backupDir . '/backup_secondspace_preloved_' . $dateStr . '.sql';

$tables = [];
$tablesRes = $conn->query("SHOW TABLES");
while ($row = $tablesRes->fetch_row()) {
    $tables[] = $row[0];
}

$sqlDump = "-- Database Backup: secondspace_preloved\n";
$sqlDump .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
$sqlDump .= "-- Host: 127.0.0.1 (Herd / XAMPP MySQL)\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sqlDump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$sqlDump .= "START TRANSACTION;\n";
$sqlDump .= "SET time_zone = '+00:00';\n\n";

foreach ($tables as $table) {
    // Structure
    $createRes = $conn->query("SHOW CREATE TABLE `$table`");
    $createRow = $createRes->fetch_row();
    $sqlDump .= "-- --------------------------------------------------------\n";
    $sqlDump .= "-- Table structure for `$table`\n";
    $sqlDump .= "-- --------------------------------------------------------\n";
    $sqlDump .= $createRow[1] . ";\n\n";

    // Data
    $dataRes = $conn->query("SELECT * FROM `$table`");
    $rowCount = $dataRes->num_rows;
    if ($rowCount > 0) {
        $sqlDump .= "-- Dumping data for `$table`\n";
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

file_put_contents($backupFile, $sqlDump);
echo "SUCCESS: Backup created at: " . realpath($backupFile) . " (" . round(filesize($backupFile) / 1024, 2) . " KB)\n";
