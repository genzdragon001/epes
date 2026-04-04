<?php
/**
 * Historical Data Analytics Module
 * Multi-period performance comparisons and trend analysis
 */

require_once 'config.php';
require_once 'db_connect.php';

class HistoricalAnalytics {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get faculty performance history across periods
     */
    public function getFacultyPerformanceHistory($faculty_id, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                CONCAT(r.semester, ' ', r.year) as period,
                r.year as year,
                r.semester as semester,
                AVG(r.efficiency) as avg_efficiency,
                AVG(r.timeliness) as avg_timeliness,
                AVG(r.quality) as avg_quality,
                (AVG(r.efficiency) + AVG(r.timeliness) + AVG(r.quality)) / 3 as overall_score,
                COUNT(DISTINCT r.task_id) as total_tasks
            FROM ratings r
            WHERE r.employee_id = ?
            GROUP BY r.year, r.semester
            ORDER BY r.year DESC, FIELD(r.semester, '2nd Semester', '1st Semester', 'Summer') DESC
            LIMIT ?
        ");
        $stmt->bind_param('ii', $faculty_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $row['adjectival'] = $this->getAdjectivalRating($row['overall_score']);
            $history[] = $row;
        }
        $stmt->close();
        
        return array_reverse($history); // Chronological order
    }
    
    /**
     * Compare department performance across periods
     */
    public function getDepartmentComparison($department_id, $periods = 3) {
        $stmt = $this->db->prepare("
            SELECT 
                CONCAT(r.semester, ' ', r.year) as period,
                r.year,
                r.semester,
                COUNT(DISTINCT e.id) as faculty_count,
                AVG(r.efficiency) as avg_efficiency,
                AVG(r.timeliness) as avg_timeliness,
                AVG(r.quality) as avg_quality,
                (AVG(r.efficiency) + AVG(r.timeliness) + AVG(r.quality)) / 3 as overall_score
            FROM ratings r
            INNER JOIN employee_list e ON r.employee_id = e.id
            WHERE e.department_id = ?
            GROUP BY r.year, r.semester
            ORDER BY r.year DESC, FIELD(r.semester, '2nd Semester', '1st Semester', 'Summer') DESC
            LIMIT ?
        ");
        $stmt->bind_param('ii', $department_id, $periods);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $comparison = [];
        while ($row = $result->fetch_assoc()) {
            $comparison[] = $row;
        }
        $stmt->close();
        
        return array_reverse($comparison);
    }
    
    /**
     * Get institutional trends
     */
    public function getInstitutionalTrends($years = 3) {
        $stmt = $this->db->prepare("
            SELECT 
                r.year,
                COUNT(DISTINCT r.employee_id) as total_faculty,
                AVG(r.efficiency) as avg_efficiency,
                AVG(r.timeliness) as avg_timeliness,
                AVG(r.quality) as avg_quality,
                (AVG(r.efficiency) + AVG(r.timeliness) + AVG(r.quality)) / 3 as overall_score,
                COUNT(DISTINCT r.id) as total_ratings
            FROM ratings r
            WHERE r.year >= YEAR(CURDATE()) - ?
            GROUP BY r.year
            ORDER BY r.year DESC
        ");
        $stmt->bind_param('i', $years);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $trends = [];
        while ($row = $result->fetch_assoc()) {
            $trends[] = $row;
        }
        $stmt->close();
        
        return array_reverse($trends);
    }
    
    /**
     * Get performance trend visualization data
     */
    public function getTrendChartData($faculty_id = null, $department_id = null) {
        if ($faculty_id) {
            $data = $this->getFacultyPerformanceHistory($faculty_id, 10);
        } elseif ($department_id) {
            $data = $this->getDepartmentComparison($department_id, 10);
        } else {
            $data = $this->getInstitutionalTrends(5);
        }
        
        $chart_data = [
            'labels' => array_column($data, 'period'),
            'overall' => array_column($data, 'overall_score'),
            'efficiency' => array_column($data, 'avg_efficiency'),
            'timeliness' => array_column($data, 'avg_timeliness'),
            'quality' => array_column($data, 'avg_quality')
        ];
        
        return $chart_data;
    }
    
    /**
     * Calculate performance improvement rate
     */
    public function calculateImprovementRate($faculty_id) {
        $history = $this->getFacultyPerformanceHistory($faculty_id, 2);
        
        if (count($history) < 2) {
            return ['status' => 'insufficient_data', 'rate' => 0];
        }
        
        $current = end($history);
        $previous = $history[count($history) - 2];
        
        $improvement = $current['overall_score'] - $previous['overall_score'];
        $rate = ($previous['overall_score'] > 0) ? ($improvement / $previous['overall_score']) * 100 : 0;
        
        return [
            'status' => 'calculated',
            'rate' => round($rate, 2),
            'trend' => $rate > 0 ? 'improving' : ($rate < 0 ? 'declining' : 'stable'),
            'current_score' => $current['overall_score'],
            'previous_score' => $previous['overall_score']
        ];
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
    
    /**
     * Generate historical report HTML
     */
    public function generateHistoricalReport($faculty_id) {
        $faculty = $this->getFacultyData($faculty_id);
        $history = $this->getFacultyPerformanceHistory($faculty_id);
        $improvement = $this->calculateImprovementRate($faculty_id);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Performance History - {$faculty['lastname']}, {$faculty['firstname']}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #000; padding: 8px; text-align: center; }
                th { background-color: #4CAF50; color: white; }
                .header { text-align: center; margin-bottom: 20px; }
                .trend-up { color: green; font-weight: bold; }
                .trend-down { color: red; font-weight: bold; }
                .trend-stable { color: orange; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>PERFORMANCE HISTORY REPORT</h2>
                <h3>{$faculty['lastname']}, {$faculty['firstname']} {$faculty['middlename']}</h3>
                <p>{$faculty['position']} | {$faculty['department']}</p>
            </div>
            
            <h3>Performance Trend</h3>
            <table>
                <tr>
                    <th>Period</th>
                    <th>Efficiency</th>
                    <th>Timeliness</th>
                    <th>Quality</th>
                    <th>Overall Score</th>
                    <th>Rating</th>
                    <th>Tasks</th>
                </tr>";
        
        foreach ($history as $row) {
            $html .= "
                <tr>
                    <td>{$row['period']}</td>
                    <td>" . number_format($row['avg_efficiency'], 2) . "</td>
                    <td>" . number_format($row['avg_timeliness'], 2) . "</td>
                    <td>" . number_format($row['avg_quality'], 2) . "</td>
                    <td>" . number_format($row['overall_score'], 3) . "</td>
                    <td>{$row['adjectival']}</td>
                    <td>{$row['total_tasks']}</td>
                </tr>";
        }
        
        $trend_class = 'trend-' . $improvement['trend'];
        $html .= "
            </table>
            
            <h3>Improvement Analysis</h3>
            <p>
                <strong>Improvement Rate:</strong> 
                <span class='{$trend_class}'>" . ($improvement['rate'] > 0 ? '+' : '') . "{$improvement['rate']}%</span>
                ({$improvement['trend']})
            </p>
        </body>
        </html>";
        
        return $html;
    }
    
    private function getFacultyData($faculty_id) {
        $stmt = $this->db->prepare("
            SELECT e.*, d.department, p.position
            FROM employee_list e
            LEFT JOIN department_list d ON e.department_id = d.id
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
}
