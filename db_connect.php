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
     * Match a task to a faculty designation, accounting for BOTH the legacy
     * single-int column (task_list.designation_id) and the multi-designation
     * junction table (task_designations). A task matches when its legacy
     * designation is NULL/0 ("All"), equals the faculty designation, OR it has
     * a junction row for the faculty designation.
     *
     * When $employee_id is provided, also matches tasks for any additional
     * designations the employee holds via the employee_designations junction
     * table (e.g. Director TAEx + Extension Head).
     * @param mixed $desig_val    The int designation id of the faculty (primary)
     * @param int   $employee_id  Optional employee id for multi-designation lookup
     * @return string SQL fragment (no leading AND)
     */
    function task_designation_match($desig_val, $employee_id = 0) {
        $desig_val = intval($desig_val);
        $sql = "(t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $desig_val
                 OR EXISTS (SELECT 1 FROM task_designations td WHERE td.task_id = t.id AND td.designation_id = $desig_val)";
        if ($employee_id > 0) {
            $sql .= " OR EXISTS (SELECT 1 FROM employee_designations ed JOIN task_designations td ON td.designation_id = ed.designation_id AND td.task_id = t.id WHERE ed.employee_id = $employee_id)";
        }
        $sql .= ")";
        return $sql;
    }
    
    /**
     * Resolve the on-disk path for an uploaded MOV/submission file.
     *
     * The task_progress.file_path column is inconsistent across the dataset:
     * most legacy rows store the path WITH the extension (e.g. "uploads/xxx.pdf"),
     * while newer rows store it WITHOUT (e.g. "uploads/xxx"). This helper returns
     * the real existing file path so view/download links always work, instead of
     * blindly appending "." . file_type (which produced broken double-extension
     * links like "uploads/xxx.pdf.pdf").
     *
     * @param string $file_path  Stored task_progress.file_path (may or may not include extension)
     * @param string $file_type  Stored task_progress.file_type (extension without dot)
     * @return string|null       Existing web path, or null if no file can be resolved
     */
    function epes_real_file_path($file_path, $file_type = '') {
        $file_path = (string)($file_path ?? '');
        $file_type = strtolower(trim((string)($file_type ?? ''), '.'));
        if ($file_path === '') return null;

        // Normalise to a web path (strip any leading drive/absolute prefix; keep "uploads/...")
        $web = $file_path;
        if (strpos($web, './') === 0) $web = substr($web, 2);
        $web = ltrim($web, '/');

        // 1. Stored path already points at an existing file -> use as-is
        if (file_exists($web)) return $web;

        // 2. Extension already present but file missing -> nothing else to try
        if ($file_type !== '' && substr($web, -strlen('.' . $file_type)) === '.' . $file_type) {
            return null;
        }

        // 3. No extension stored -> try appending file_type (newer convention)
        if ($file_type !== '') {
            $candidate = $web . '.' . $file_type;
            if (file_exists($candidate)) return $candidate;
        }
        return null;
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
