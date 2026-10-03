<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

class DatabaseDumper
{
    /**
     * Export full MySQL database schema and data to a file.
     *
     * @param string $outputPath Absolute file path where .sql file will be saved.
     * @return bool
     */
    public static function dump(string $outputPath): bool
    {
        $dir = dirname($outputPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = DB::connection()->getPdo();
        $databaseName = DB::connection()->getDatabaseName();

        $handle = fopen($outputPath, 'w+');
        if (!$handle) {
            return false;
        }

        // Header comments
        fwrite($handle, "-- Al-Madina Battery System Database Backup\n");
        fwrite($handle, "-- Database: `{$databaseName}`\n");
        fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- ------------------------------------------------------\n\n");

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

        // Fetch tables
        $tables = [];
        $stmt = $pdo->query('SHOW TABLES');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            // Write Drop table
            fwrite($handle, "-- ------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for table `{$table}`\n");
            fwrite($handle, "-- ------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

            // Write Create table
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
            if (isset($createRow['Create Table'])) {
                fwrite($handle, $createRow['Create Table'] . ";\n\n");
            }

            // Write Data
            fwrite($handle, "-- Dumping data for table `{$table}`\n");
            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            
            $columnCount = $dataStmt->columnCount();
            $rows = [];
            $batchSize = 250;
            $count = 0;

            while ($row = $dataStmt->fetch(PDO::FETCH_NUM)) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = "NULL";
                    } elseif (is_numeric($val) && !is_string($val)) {
                        $values[] = $val;
                    } else {
                        $values[] = $pdo->quote($val);
                    }
                }
                $rows[] = "(" . implode(",", $values) . ")";
                $count++;

                if (count($rows) >= $batchSize) {
                    fwrite($handle, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $rows) . ";\n");
                    $rows = [];
                }
            }

            if (count($rows) > 0) {
                fwrite($handle, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $rows) . ";\n");
            }

            fwrite($handle, "\n\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return true;
    }
}
