<?php
/**
 * OPCR Consolidation Module
 * Generates office/department level performance summaries
 */

require_once 'config.php';
require_once 'db_connect.php';

class OPCRConsolidator {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate OPCR summary for a department
     */
    public function generateOPCRSummary($department_id, $rating_period) {
        $department = $this->getDepartmentData($department_id);
        $faculty_list = $this->getDepartmentFaculty($department_id);
        $summary = $this->calculateDepartmentSummary($department_id, $rating_period);
        
        return [
            'department' => $department,
            'faculty_count' => count($faculty_list),
            'summary' => $summary,
            'faculty_details' => $faculty_list
        ];
    }
    
    /**
     * Get department data
     */
    private function getDepartmentData($department_id) {
        $stmt = $this->db->prepare("SELECT * FROM department_list WHERE id = ?");
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dept = $result->fetch_assoc();
        $stmt->close();
        return $dept;
    }
    
    /**
     * Get all faculty in department
     */
    private function getDepartmentFaculty($department_id) {
        $stmt = $this->db->prepare("
            SELECT e.*, d.designation, p.position
            FROM employee_list e
            LEFT JOIN designation_list d ON e.designation_id = d.id
            LEFT JOIN position_list p ON e.position_id = p.id
            WHERE e.department_id = ?
        ");
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $faculty = [];
        while($row = $result->fetch_assoc()) {
            $faculty[] = $row;
        }
        $stmt->close();
        return $faculty;
    }
    
    /**
     * Calculate department performance summary
     */
    private function calculateDepartmentSummary($department_id, $rating_period) {
        list($semester, $year) = explode('-', $rating_period);
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT e.id) as total_faculty,
                AVG(r.efficiency) as avg_efficiency,
                AVG(r.timeliness) as avg_timeliness,
                AVG(r.quality) as avg_quality,
                COUNT(DISTINCT r.id) as total_ratings,
                SUM(CASE WHEN tp.progress = 'Verified' THEN 1 ELSE 0 END) as verified_tasks,
                COUNT(DISTINCT tp.id) as total_tasks
            FROM employee_list e
            LEFT JOIN ratings r ON e.id = r.employee_id AND r.year = ? AND r.semester = ?
            LEFT JOIN task_progress tp ON e.id = tp.faculty_id
            WHERE e.department_id = ?
        ");
        $stmt->bind_param('ssi', $year, $semester, $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        $stmt->close();
        
        // Calculate overall average
        $summary['overall_average'] = 0;
        if ($summary['avg_efficiency'] && $summary['avg_timeliness'] && $summary['avg_quality']) {
            $summary['overall_average'] = ($summary['avg_efficiency'] + $summary['avg_timeliness'] + $summary['avg_quality']) / 3;
        }
        
        return $summary;
    }
    
    /**
     * Save OPCR summary to database
     */
    public function saveOPCRSummary($department_id, $rating_period) {
        $data = $this->generateOPCRSummary($department_id, $rating_period);
        $summary = $data['summary'];
        
        $stmt = $this->db->prepare("
            INSERT INTO opcr_summary 
            (department_id, rating_period, total_faculty, avg_instruction, overall_average, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            total_faculty = VALUES(total_faculty),
            avg_instruction = VALUES(avg_instruction),
            overall_average = VALUES(overall_average),
            updated_at = NOW()
        ");
        
        $overall = $summary['overall_average'] ?? 0;
        $avg_instruction = $summary['avg_efficiency'] ?? 0;
        
        $stmt->bind_param('isidd', $department_id, $rating_period, $data['faculty_count'], $avg_instruction, $overall);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Generate OPCR report HTML
     */
    public function generateOPCRReport($department_id, $rating_period) {
        $data = $this->generateOPCRSummary($department_id, $rating_period);
        $dept = $data['department'];
        $summary = $data['summary'];
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>OPCR Summary - {$dept['department']}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #4CAF50; color: white; }
                .header { text-align: center; margin-bottom: 20px; }
                .summary-box { background-color: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .metric { display: inline-block; margin: 10px 20px; text-align: center; }
                .metric-value { font-size: 24px; font-weight: bold; color: #4CAF50; }
                .metric-label { font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>OFFICE PERFORMANCE COMMITMENT AND REVIEW (OPCR)</h2>
                <h3>DEPARTMENT: {$dept['department']}</h3>
                <h4>RATING PERIOD: {$rating_period}</h4>
            </div>
            
            <div class='summary-box'>
                <div class='metric'>
                    <div class='metric-value'>{$data['faculty_count']}</div>
                    <div class='metric-label'>Total Faculty</div>
                </div>
                <div class='metric'>
                    <div class='metric-value'>" . number_format($summary['overall_average'], 2) . "</div>
                    <div class='metric-label'>Overall Average</div>
                </div>
                <div class='metric'>
                    <div class='metric-value'>{$summary['total_tasks']}</div>
                    <div class='metric-label'>Total Tasks</div>
                </div>
                <div class='metric'>
                    <div class='metric-value'>{$summary['verified_tasks']}</div>
                    <div class='metric-label'>Verified Tasks</div>
                </div>
            </div>
            
            <h3>Performance Metrics</h3>
            <table>
                <tr>
                    <th>Metric</th>
                    <th>Score</th>
                    <th>Rating</th>
                </tr>
                <tr>
                    <td>Efficiency</td>
                    <td>" . number_format($summary['avg_efficiency'] ?? 0, 2) . "</td>
                    <td>" . $this->getAdjectivalRating($summary['avg_efficiency'] ?? 0) . "</td>
                </tr>
                <tr>
                    <td>Timeliness</td>
                    <td>" . number_format($summary['avg_timeliness'] ?? 0, 2) . "</td>
                    <td>" . $this->getAdjectivalRating($summary['avg_timeliness'] ?? 0) . "</td>
                </tr>
                <tr>
                    <td>Quality</td>
                    <td>" . number_format($summary['avg_quality'] ?? 0, 2) . "</td>
                    <td>" . $this->getAdjectivalRating($summary['avg_quality'] ?? 0) . "</td>
                </tr>
            </table>
            
            <h3>Faculty Members</h3>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Designation</th>
                </tr>";
        
        foreach($data['faculty_details'] as $faculty) {
            $name = $faculty['lastname'] . ', ' . $faculty['firstname'];
            $html .= "
                <tr>
                    <td>{$name}</td>
                    <td>{$faculty['position']}</td>
                    <td>{$faculty['designation']}</td>
                </tr>";
        }
        
        $html .= "
            </table>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Get adjectival rating
     */
    private function getAdjectivalRating($score) {
        if ($score >= 4.75) return 'Outstanding';
        if ($score >= 3.61) return 'Very Satisfactory';
        if ($score >= 2.61) return 'Satisfactory';
        if ($score >= 1.61) return 'Unsatisfactory';
        return 'Poor';
    }
}
