<?php
/**
 * Database Backup Module
 * Automated backup functionality for EPES database
 */

require_once 'config.php';
require_once 'db_connect.php';

class DatabaseBackup {
    private $db;
    private $backup_dir;
    
    public function __construct() {
        $this->db = getDB();
        $this->backup_dir = __DIR__ . '/backups/';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Create full database backup
     */
    public function createBackup($backup_type = 'Full') {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "epes_backup_{$timestamp}.sql";
        $filepath = $this->backup_dir . $filename;
        
        try {
            // Get all tables
            $tables = $this->getAllTables();
            
            $sql = "-- EPES Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Type: {$backup_type}\n\n";
            $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $sql .= "SET time_zone = \"+08:00\";\n\n";
            $sql .= "START TRANSACTION;\n\n";
            
            foreach ($tables as $table) {
                $sql .= $this->exportTable($table);
            }
            
            $sql .= "\nCOMMIT;\n";
            
            // Write to file
            if (file_put_contents($filepath, $sql)) {
                $file_size = filesize($filepath);
                
                // Log backup
                $this->logBackup($filename, $file_size, $backup_type, 'Success');
                
                // Clean old backups (keep last 10)
                $this->cleanOldBackups();
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => $file_size
                ];
            }
            
            return ['success' => false, 'error' => 'Failed to write backup file'];
            
        } catch (Exception $e) {
            error_log("Backup Error: " . $e->getMessage());
            $this->logBackup($filename, 0, $backup_type, 'Failed', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get all tables from database
     */
    private function getAllTables() {
        $result = $this->db->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        return $tables;
    }
    
    /**
     * Export single table structure and data
     */
    private function exportTable($table) {
        $sql = "--\n-- Table structure for table `$table`\n--\n\n";
        
        // Get CREATE TABLE statement
        $result = $this->db->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_array();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";
        
        // Get table data
        $result = $this->db->query("SELECT * FROM `$table`");
        if ($result && $result->num_rows > 0) {
            $sql .= "--\n-- Dumping data for table `$table`\n--\n\n";
            
            while ($row = $result->fetch_assoc()) {
                $sql .= "INSERT INTO `$table` VALUES (";
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = "NULL";
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } else {
                        $values[] = "'" . $this->db->real_escape_string($value) . "'";
                    }
                }
                $sql .= implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
        return $sql;
    }
    
    /**
     * Log backup to database
     */
    private function logBackup($filename, $size, $type, $status, $error = null) {
        $created_by = $_SESSION['login_id'] ?? null;
        
        $stmt = $this->db->prepare("
            INSERT INTO system_backups (backup_file, backup_size, backup_type, status, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sisss', $filename, $size, $type, $status, $created_by);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Clean old backups, keep last 10
     */
    private function cleanOldBackups() {
        $files = glob($this->backup_dir . '*.sql');
        
        if (count($files) > 10) {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Delete all but the 10 most recent
            for ($i = 10; $i < count($files); $i++) {
                unlink($files[$i]);
            }
        }
    }
    
    /**
     * Get backup history
     */
    public function getBackupHistory($limit = 20) {
        $result = $this->db->query("
            SELECT * FROM system_backups 
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        
        $backups = [];
        while ($row = $result->fetch_assoc()) {
            $backups[] = $row;
        }
        
        return $backups;
    }
    
    /**
     * Restore from backup file
     */
    public function restoreBackup($filename) {
        $filepath = $this->backup_dir . $filename;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }
        
        try {
            $sql = file_get_contents($filepath);
            
            // Split into individual queries
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($queries as $query) {
                if (empty($query) || strpos($query, '--') === 0) {
                    continue;
                }
                
                if (!$this->db->query($query)) {
                    throw new Exception("Error executing query: " . $this->db->error);
                }
            }
            
            return ['success' => true, 'message' => 'Database restored successfully'];
            
        } catch (Exception $e) {
            error_log("Restore Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup($filename) {
        $filepath = $this->backup_dir . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }
    
    /**
     * Schedule automatic backup
     */
    public function scheduleAutoBackup($frequency = 'daily') {
        // This would integrate with system cron
        // For now, just log the request
        $next_backup = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        error_log("Auto-backup scheduled: {$frequency}, next: {$next_backup}");
        
        return true;
    }
}
