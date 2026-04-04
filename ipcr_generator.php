<?php
/**
 * IPCR Form Generator
 * Generates PDF IPCR forms for faculty performance evaluation
 */

require_once 'config.php';
require_once 'db_connect.php';

class IPCRGenerator {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate IPCR form for a faculty member
     */
    public function generateIPCR($faculty_id, $rating_period) {
        $faculty = $this->getFacultyData($faculty_id);
        $ratings = $this->getFacultyRatings($faculty_id, $rating_period);
        $tasks = $this->getFacultyTasks($faculty_id, $rating_period);
        
        $html = $this->buildIPCRHTML($faculty, $ratings, $tasks, $rating_period);
        
        return $html;
    }
    
    /**
     * Get faculty member data
     */
    private function getFacultyData($faculty_id) {
        $stmt = $this->db->prepare("
            SELECT e.*, d.department, dg.designation, p.position
            FROM employee_list e
            LEFT JOIN department_list d ON e.department_id = d.id
            LEFT JOIN designation_list dg ON e.designation_id = dg.id
            LEFT JOIN position_list p ON e.position_id = p.id
            WHERE e.id = ?
        ");
        $stmt->bind_param('i', $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $faculty = $result->fetch_assoc();
        $stmt->close();
        return $faculty;
    }
    
    /**
     * Get faculty ratings for the period
     */
    private function getFacultyRatings($faculty_id, $rating_period) {
        list($semester, $year) = explode('-', $rating_period);
        
        $stmt = $this->db->prepare("
            SELECT 
                AVG(r.efficiency) as avg_efficiency,
                AVG(r.timeliness) as avg_timeliness,
                AVG(r.quality) as avg_quality,
                COUNT(DISTINCT r.task_id) as total_tasks
            FROM ratings r
            INNER JOIN task_list t ON r.task_id = t.id
            WHERE r.employee_id = ? AND r.year = ? AND r.semester = ?
        ");
        $stmt->bind_param('iss', $faculty_id, $year, $semester);
        $stmt->execute();
        $result = $stmt->get_result();
        $ratings = $result->fetch_assoc();
        $stmt->close();
        
        return $ratings;
    }
    
    /**
     * Get faculty tasks for the period
     */
    private function getFacultyTasks($faculty_id, $rating_period) {
        list($semester, $year) = explode('-', $rating_period);
        
        $stmt = $this->db->prepare("
            SELECT t.*, tp.progress, tp.date_created
            FROM task_list t
            LEFT JOIN task_progress tp ON t.id = tp.task_id AND tp.faculty_id = ?
            WHERE t.is_active = 1
            ORDER BY t.category, t.sub_category
        ");
        $stmt->bind_param('i', $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        while($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        $stmt->close();
        
        return $tasks;
    }
    
    /**
     * Build IPCR HTML
     */
    private function buildIPCRHTML($faculty, $ratings, $tasks, $rating_period) {
        $overall_score = ($ratings['avg_efficiency'] + $ratings['avg_timeliness'] + $ratings['avg_quality']) / 3;
        $adjectival = $this->getAdjectivalRating($overall_score);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>IPCR Form - {$faculty['firstname']} {$faculty['lastname']}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #000; padding: 8px; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .header { text-align: center; margin-bottom: 20px; }
                .section-title { background-color: #e0e0e0; font-weight: bold; padding: 5px; }
                .rating-box { width: 60px; text-align: center; }
                .signature-section { margin-top: 30px; }
                .signature-line { border-top: 1px solid #000; width: 200px; margin-top: 50px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
                <h3>RATING PERIOD: {$rating_period}</h3>
            </div>
            
            <table>
                <tr>
                    <td width='20%'><strong>Name:</strong></td>
                    <td width='30%'>{$faculty['lastname']}, {$faculty['firstname']} {$faculty['middlename']}</td>
                    <td width='20%'><strong>Department:</strong></td>
                    <td width='30%'>{$faculty['department']}</td>
                </tr>
                <tr>
                    <td><strong>Position:</strong></td>
                    <td>{$faculty['position']}</td>
                    <td><strong>Designation:</strong></td>
                    <td>{$faculty['designation']}</td>
                </tr>
            </table>
            
            <h3>PART I: PERFORMANCE RATINGS</h3>
            <table>
                <thead>
                    <tr class='section-title'>
                        <th>CRITERIA</th>
                        <th class='rating-box'>EFFICIENCY</th>
                        <th class='rating-box'>TIMELINESS</th>
                        <th class='rating-box'>QUALITY</th>
                        <th class='rating-box'>AVERAGE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Instruction</td>
                        <td class='rating-box'>" . number_format($ratings['avg_efficiency'], 2) . "</td>
                        <td class='rating-box'>" . number_format($ratings['avg_timeliness'], 2) . "</td>
                        <td class='rating-box'>" . number_format($ratings['avg_quality'], 2) . "</td>
                        <td class='rating-box'>" . number_format($overall_score, 2) . "</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>PART II: TASK ACCOMPLISHMENTS</h3>
            <table>
                <thead>
                    <tr class='section-title'>
                        <th>TASK</th>
                        <th>CATEGORY</th>
                        <th>STATUS</th>
                        <th>DATE SUBMITTED</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach($tasks as $task) {
            $html .= "
                    <tr>
                        <td>{$task['task']}</td>
                        <td>{$task['category']} - {$task['sub_category']}</td>
                        <td>{$task['progress']}</td>
                        <td>" . date('M d, Y', strtotime($task['date_created'])) . "</td>
                    </tr>";
        }
        
        $html .= "
                </tbody>
            </table>
            
            <h3>PART III: OVERALL RATING</h3>
            <table>
                <tr>
                    <td width='50%'><strong>OVERALL SCORE:</strong></td>
                    <td width='50%'>" . number_format($overall_score, 3) . "</td>
                </tr>
                <tr>
                    <td><strong>ADJECTIVAL RATING:</strong></td>
                    <td><strong>{$adjectival}</strong></td>
                </tr>
            </table>
            
            <div class='signature-section'>
                <table>
                    <tr>
                        <td align='center'>
                            <div class='signature-line'></div>
                            <strong>Faculty Member</strong><br>
                            Date: _______________
                        </td>
                        <td align='center'>
                            <div class='signature-line'></div>
                            <strong>Evaluator/Dean</strong><br>
                            Date: _______________
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Get adjectival rating
     */
    private function getAdjectivalRating($score) {
        if ($score >= 4.75) return 'OUTSTANDING';
        if ($score >= 3.61) return 'VERY SATISFACTORY';
        if ($score >= 2.61) return 'SATISFACTORY';
        if ($score >= 1.61) return 'UNSATISFACTORY';
        return 'POOR';
    }
    
    /**
     * Export to PDF (requires TCPDF or similar library)
     */
    public function exportToPDF($faculty_id, $rating_period, $filename = null) {
        $html = $this->generateIPCR($faculty_id, $rating_period);
        
        // If TCPDF is available
        if (file_exists('vendor/tecnickcom/tcpdf/tcpdf.php')) {
            require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
            
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('EPES System');
            $pdf->SetAuthor('EPES');
            $pdf->SetTitle('IPCR Form');
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            
            if ($filename === null) {
                $filename = 'IPCR_' . date('YmdHis') . '.pdf';
            }
            
            $pdf->Output($filename, 'D');
            return true;
        }
        
        return false;
    }
}
