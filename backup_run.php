<?php
/**
 * Automated Daily Backup Script
 * Run via Windows Task Scheduler or cron:
 *   php C:\xampp\htdocs\epes\backup_run.php
 * 
 * Creates a full database dump, rotates old backups (30-day retention),
 * and logs results to system_backups table.
 */

// No session needed — standalone CLI/cron script
require_once __DIR__ . '/db_connect.php';

// Config
$backup_dir = __DIR__ . '/backups/';
$retention_days = 30;  // Keep backups for 30 days
$keep_min = 10;        // Always keep at least 10 most recent

// Create backup directory
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Ensure .htaccess protection
$htaccess = $backup_dir . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

$timestamp = date('Y-m-d_H-i-s');
$filename = "epes_backup_{$timestamp}.sql";
$filepath = $backup_dir . $filename;

try {
    // Get all tables
    $tables_result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $tables_result->fetch_array()) {
        $tables[] = $row[0];
    }

    $sql = "-- EPES Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . " (Asia/Manila)\n";
    $sql .= "-- Type: Full (automated daily)\n\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET time_zone = \"+08:00\";\n\n";
    $sql .= "START TRANSACTION;\n\n";

    foreach ($tables as $table) {
        // Skip backup log table itself (avoid recursion)
        if ($table === 'system_backups') continue;
        
        $sql .= "--\n-- Table structure for `$table`\n--\n\n";
        
        // CREATE TABLE
        $create_result = $conn->query("SHOW CREATE TABLE `$table`");
        $create_row = $create_result->fetch_array();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create_row[1] . ";\n\n";
        
        // Data dump
        $data_result = $conn->query("SELECT * FROM `$table`");
        if ($data_result && $data_result->num_rows > 0) {
            $sql .= "-- Dumping data for `$table`\n\n";
            while ($row = $data_result->fetch_assoc()) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = "NULL";
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "COMMIT;\n";

    // Write backup file
    if (file_put_contents($filepath, $sql)) {
        $file_size = filesize($filepath);
        
        // Log to database
        $stmt = $conn->prepare("INSERT INTO system_backups (backup_file, backup_size, backup_type, status, created_by) VALUES (?, ?, 'Full', 'Success', NULL)");
        $stmt->bind_param('si', $filename, $file_size);
        $stmt->execute();
        $stmt->close();
        
        // Rotate old backups
        $files = glob($backup_dir . 'epes_backup_*.sql');
        if (count($files) > $keep_min) {
            // Sort by modification time (newest first)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Delete files older than retention_days, but keep at least keep_min
            $cutoff = time() - ($retention_days * 86400);
            $deleted = 0;
            for ($i = $keep_min; $i < count($files); $i++) {
                if (filemtime($files[$i]) < $cutoff) {
                    unlink($files[$i]);
                    $deleted++;
                }
            }
        }
        
        echo "OK: Backup created — {$filename} (" . number_format($file_size) . " bytes)\n";
        if (isset($deleted) && $deleted > 0) {
            echo "OK: Rotated {$deleted} old backup(s)\n";
        }
        exit(0);
    } else {
        throw new Exception("Failed to write backup file");
    }
} catch (Exception $e) {
    // Log failure
    $stmt = $conn->prepare("INSERT INTO system_backups (backup_file, backup_size, backup_type, status, error_message) VALUES (?, 0, 'Full', 'Failed', ?)");
    $error_msg = $e->getMessage();
    $stmt->bind_param('ss', $filename, $error_msg);
    $stmt->execute();
    $stmt->close();
    
    echo "FAIL: " . $e->getMessage() . "\n";
    exit(1);
}
