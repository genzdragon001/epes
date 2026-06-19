<?php
/**
 * Database Connection
 * Centralized database connection with security best practices
 */

if (!defined('DB_CONNECTED')) {
    define('DB_CONNECTED', true);
    
    require_once __DIR__ . '/config.php';
    
    // Ensure session is available for csrf_field() on pages that include db_connect first
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Enable mysqli error reporting for development (disable in production)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        global $conn;
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to prevent SQL injection and encoding issues
        $conn->set_charset(DB_CHARSET);
        
        // Set timezone
        $conn->query("SET time_zone = '+08:00'"); // Philippines timezone
        
    } catch (Exception $e) {
        // Log error
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Show user-friendly error (in production, show generic message)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            die("Database Error: " . $e->getMessage());
        } else {
            die("Database connection failed. Please contact system administrator.");
        }
    }
    
    /**
     * Get database connection instance
     * @return mysqli Database connection
     */
    function getDB() {
        global $conn;
        return $conn;
    }
    
    /**
     * Execute prepared statement safely
     * @param mysqli $conn Database connection
     * @param string $sql SQL query with placeholders
     * @param string $types Parameter types (s=string, i=integer, d=double, b=blob)
     * @param array $params Parameters to bind
     * @return mysqli_stmt|false Prepared statement or false on failure
     */
    function executePrepared($conn, $sql, $types = '', $params = []) {
        try {
            $stmt = $conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            return $stmt;
            
        } catch (Exception $e) {
            error_log("Prepared Statement Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Safe query execution with logging
     * @param mysqli $conn Database connection
     * @param string $sql SQL query
     * @return mysqli_result|bool Query result or false on failure
     */
    function safeQuery($conn, $sql) {
        try {
            $result = $conn->query($sql);
            
            if ($result === false) {
                error_log("SQL Error: " . $conn->error . " | Query: " . $sql);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }
}
