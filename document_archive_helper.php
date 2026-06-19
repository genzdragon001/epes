<?php
/**
 * Document Archive Helper
 * Records generated IPCR/DPCR/OPCR documents in performance_documents table
 */

/**
 * Archive a generated document
 * @param mysqli  $conn
 * @param string  $doc_type    'IPCR', 'DPCR', or 'OPCR'
 * @param int     $faculty_id  Faculty ID (null for DPCR/OPCR)
 * @param int     $dept_id     Department ID (null for IPCR/OPCR)
 * @param int     $period_id   rating_period.id
 * @param string  $period_label e.g. "1st Semester-2024-2025"
 * @param string  $file_path   Full path to saved PDF
 * @param int     $file_size   Bytes
 * @param int     $generated_by User ID who generated it
 */
function archive_document($conn, $doc_type, $faculty_id, $dept_id, $period_id, $period_label, $file_path, $file_size, $generated_by) {
    $stmt = $conn->prepare("
        INSERT INTO performance_documents 
        (document_type, faculty_id, department_id, rating_period_id, rating_period_label, file_path, file_size, generated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            file_path = VALUES(file_path),
            file_size = VALUES(file_size),
            generated_by = VALUES(generated_by),
            generated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param('siiissii', $doc_type, $faculty_id, $dept_id, $period_id, $period_label, $file_path, $file_size, $generated_by);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get archived documents with filters
 */
function get_archived_documents($conn, $doc_type = null, $faculty_id = null, $dept_id = null, $period_id = null, $limit = 50) {
    $where = [];
    $params = [];
    $types = '';
    
    if ($doc_type) {
        $where[] = "pd.document_type = ?";
        $params[] = $doc_type;
        $types .= 's';
    }
    if ($faculty_id) {
        $where[] = "pd.faculty_id = ?";
        $params[] = $faculty_id;
        $types .= 'i';
    }
    if ($dept_id) {
        $where[] = "pd.department_id = ?";
        $params[] = $dept_id;
        $types .= 'i';
    }
    if ($period_id) {
        $where[] = "pd.rating_period_id = ?";
        $params[] = $period_id;
        $types .= 'i';
    }
    
    $sql = "
        SELECT pd.*, 
               CONCAT(el.lastname, ', ', el.firstname) as faculty_name,
               dl.department as dept_name,
               rp.semester, rp.year
        FROM performance_documents pd
        LEFT JOIN employee_list el ON pd.faculty_id = el.id
        LEFT JOIN department_list dl ON pd.department_id = dl.id
        LEFT JOIN rating_period rp ON pd.rating_period_id = rp.id
    ";
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY pd.generated_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $docs = [];
    while ($row = $result->fetch_assoc()) {
        $docs[] = $row;
    }
    $stmt->close();
    return $docs;
}
