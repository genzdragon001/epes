<?php
/**
 * DPCR Form Generator - Excel-aligned version
 * Generates PDF and print-ready DPCR forms (Department Performance Commitment and Review)
 * Uses cascading_ratings table (level='DP') for department-level aggregates
 */
require_once 'config.php';
require_once 'db_connect.php';

class DPCRGenerator {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate DPCR form HTML for a department in a given period
     */
    public function generateDPCR($department_id, $target_period_id) {
        $dept = $this->getDepartmentData($department_id);
        if (!$dept) return '<div class="alert alert-danger">Department not found.</div>';
        
        $period = $this->getPeriodData($target_period_id);
        if (!$period) return '<div class="alert alert-danger">Period not found.</div>';
        
        $dp_data = $this->getDPData($department_id, $target_period_id);
        $faculty_ratings = $this->getFacultyRatingsForDept($department_id, $period);
        $opcr_data = $this->getOPCRData($target_period_id);
        $allocations = $this->getAverageAllocations($department_id);
        
        return $this->buildDPCRHTML($dept, $period, $dp_data, $faculty_ratings, $opcr_data, $allocations);
    }
    
    /**
     * Get department info
     */
    private function getDepartmentData($department_id) {
        $stmt = $this->db->prepare("SELECT * FROM department_list WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dept = $result->fetch_assoc();
        $stmt->close();
        return $dept;
    }
    
    /**
     * Get rating period info
     */
    private function getPeriodData($period_id) {
        $stmt = $this->db->prepare("SELECT * FROM rating_period WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $period = $result->fetch_assoc();
        $stmt->close();
        return $period;
    }
    
    /**
     * Get DP cascading data for this department+period
     */
    private function getDPData($department_id, $target_period_id) {
        $stmt = $this->db->prepare("
            SELECT cr.*, rp.semester, rp.year
            FROM cascading_ratings cr
            LEFT JOIN rating_period rp ON cr.target_period_id = rp.id
            WHERE cr.department_id = ? AND cr.target_period_id = ? AND cr.level = 'DP'
            ORDER BY cr.computed_at DESC LIMIT 1
        ");
        $stmt->bind_param('ii', $department_id, $target_period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dp = $result->fetch_assoc();
        $stmt->close();
        return $dp;
    }
    
    /**
     * Get average percentage allocations for the department's faculty
     */
    private function getAverageAllocations($department_id) {
        $stmt = $this->db->prepare("
            SELECT category, sub_category, AVG(percentage) as pct
            FROM percentage_allocation pa
            INNER JOIN employee_list e ON e.position_id = pa.position_id
                AND (pa.designation_id IS NULL OR pa.designation_id = 0 OR pa.designation_id = e.designation_id)
            WHERE e.department_id = ? AND pa.is_active = 1
            GROUP BY category, sub_category
        ");
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $allocations = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['category'];
            if ($row['sub_category']) $key .= '_' . $row['sub_category'];
            $allocations[$key] = round(floatval($row['pct']), 2);
        }
        $stmt->close();
        return $allocations;
    }
    
    /**
     * Get individual faculty IPCR ratings for this department in this period
     */
    private function getFacultyRatingsForDept($department_id, $period) {
        $rating_period_code = $period['semester'] . '-' . $period['year'];
        
        $stmt = $this->db->prepare("
            SELECT 
                e.id as faculty_id,
                e.firstname, e.middlename, e.lastname,
                p.position as position_name,
                dg.designation as designation_name,
                t.category,
                t.sub_category,
                AVG(r.efficiency) as avg_efficiency,
                AVG(r.timeliness) as avg_timeliness,
                AVG(r.quality) as avg_quality,
                AVG((r.efficiency + r.timeliness + r.quality) / 3) as overall,
                COUNT(DISTINCT r.task_id) as rated_tasks
            FROM employee_list e
            INNER JOIN ratings r ON r.employee_id = e.id
            INNER JOIN task_list t ON r.task_id = t.id
            LEFT JOIN position_list p ON e.position_id = p.id
            LEFT JOIN designation_list dg ON e.designation_id = dg.id
            WHERE e.department_id = ?
              AND r.rating_period = ?
              AND r.efficiency > 0 AND r.timeliness > 0 AND r.quality > 0
            GROUP BY e.id, t.category, t.sub_category
            ORDER BY e.lastname, e.firstname, FIELD(t.category, 'strategic', 'core', 'support'), t.sub_category
        ");
        $stmt->bind_param('is', $department_id, $rating_period_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $faculty = [];
        while ($row = $result->fetch_assoc()) {
            $faculty[] = $row;
        }
        $stmt->close();
        return $faculty;
    }
    
    /**
     * Get OPCR office-wide data for the same period (for reference)
     */
    private function getOPCRData($target_period_id) {
        // Find the OPCR period with same semester+year
        $dp_period = $this->getPeriodData($target_period_id);
        if (!$dp_period) return null;
        
        $stmt = $this->db->prepare("
            SELECT id FROM rating_period 
            WHERE semester = ? AND year = ? 
            LIMIT 1
        ");
        $stmt->bind_param('ss', $dp_period['semester'], $dp_period['year']);
        $stmt->execute();
        $opcr_period = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$opcr_period) return null;
        
        $stmt = $this->db->prepare("
            SELECT overall_rating, avg_efficiency, avg_timeliness, avg_quality
            FROM cascading_ratings 
            WHERE target_period_id = ? AND level = 'OPCR'
            ORDER BY computed_at DESC LIMIT 1
        ");
        $stmt->bind_param('i', $opcr_period['id']);
        $stmt->execute();
        $opcr = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $opcr;
    }
    
    /**
     * Build the full DPCR HTML document
     */
    private function buildDPCRHTML($dept, $period, $dp_data, $faculty_ratings, $opcr_data, $allocations) {
        $period_label = $period['semester'] . ' ' . $period['year'];
        $date_range = '';
        if ($period['start_date'] || $period['end_date']) {
            $start = $period['start_date'] ? date('M d, Y', strtotime($period['start_date'])) : '—';
            $end   = $period['end_date']   ? date('M d, Y', strtotime($period['end_date']))   : '—';
            $date_range = "$start – $end";
        }
        
        // Weight table
        $cat_order = ['strategic' => 'Strategic Function', 'core' => 'Core Function', 'support' => 'Support Function'];
        $subcat_names = [
            'instructions' => 'Instruction / Teaching Effectiveness',
            'research' => 'Research',
            'extension' => 'Extension',
        ];
        
        $weight_table = '';
        $total_weight = 0;
        foreach (['strategic', 'core', 'support'] as $cat) {
            $pct = 0;
            if ($cat === 'strategic') $pct = $allocations['strategic'] ?? 0;
            elseif ($cat === 'core') {
                $pct = ($allocations['core_instructions'] ?? 0)
                     + ($allocations['core_research'] ?? 0)
                     + ($allocations['core_extension'] ?? 0);
                if ($pct == 0) $pct = $allocations['core'] ?? 0;
            } elseif ($cat === 'support') {
                $pct = $allocations['support'] ?? 0;
            }
            $total_weight += $pct;
            $label = $cat_order[$cat] ?? ucfirst($cat);
            $weight_table .= "
                <tr>
                    <td style='padding:6px 10px;'>{$label}</td>
                    <td style='padding:6px 10px; text-align:center; width:25%;'><strong>{$pct}%</strong></td>
                </tr>";
        }
        $weight_table .= "
                <tr style='background:#e8e8e8; font-weight:bold;'>
                    <td style='padding:6px 10px;'>TOTAL</td>
                    <td style='padding:6px 10px; text-align:center;'>{$total_weight}%</td>
                </tr>";
        
        // Department aggregate
        $dp_eff  = $dp_data ? number_format($dp_data['avg_efficiency'], 2) : '—';
        $dp_time = $dp_data ? number_format($dp_data['avg_timeliness'], 2) : '—';
        $dp_qual = $dp_data ? number_format($dp_data['avg_quality'], 2) : '—';
        $dp_overall = $dp_data ? number_format($dp_data['overall_rating'], 2) : '—';
        $dp_adj = $dp_data ? $this->getAdjectivalRating($dp_data['overall_rating']) : 'NO DATA';
        
        // Group faculty by category/sub-category for DPCR table
        $faculty_by_cat = [];
        $faculty_totals = [];
        foreach ($faculty_ratings as $f) {
            $fid = $f['faculty_id'];
            $cat = $f['category'];
            $sub = $f['sub_category'] ?: 'General';
            if (!isset($faculty_by_cat[$cat])) $faculty_by_cat[$cat] = [];
            if (!isset($faculty_by_cat[$cat][$sub])) $faculty_by_cat[$cat][$sub] = [];
            $faculty_by_cat[$cat][$sub][] = $f;
            
            if (!isset($faculty_totals[$fid])) {
                $faculty_totals[$fid] = [
                    'name' => $f['lastname'] . ', ' . $f['firstname'] . ' ' . $f['middlename'],
                    'position' => $f['position_name'] ?? '—',
                    'tasks' => 0,
                    'eff_sum' => 0, 'time_sum' => 0, 'qual_sum' => 0, 'count' => 0
                ];
            }
            $faculty_totals[$fid]['tasks'] += $f['rated_tasks'];
            $faculty_totals[$fid]['eff_sum'] += $f['avg_efficiency'] * $f['rated_tasks'];
            $faculty_totals[$fid]['time_sum'] += $f['avg_timeliness'] * $f['rated_tasks'];
            $faculty_totals[$fid]['qual_sum'] += $f['avg_quality'] * $f['rated_tasks'];
            $faculty_totals[$fid]['count'] += $f['rated_tasks'];
        }
        
        // DPCR rating rows by function category
        $cat_rows = '';
        foreach (['strategic', 'core', 'support'] as $cat) {
            if (!isset($faculty_by_cat[$cat])) continue;
            $cat_label = $cat_order[$cat] ?? ucfirst($cat);
            $cat_pct = 0;
            if ($cat === 'strategic') $cat_pct = $allocations['strategic'] ?? 0;
            elseif ($cat === 'core') {
                $cat_pct = ($allocations['core_instructions'] ?? 0)
                         + ($allocations['core_research'] ?? 0)
                         + ($allocations['core_extension'] ?? 0);
                if ($cat_pct == 0) $cat_pct = $allocations['core'] ?? 0;
            } elseif ($cat === 'support') $cat_pct = $allocations['support'] ?? 0;
            
            $cat_rows .= "
                <tr style='background:#f5f5f5; font-weight:bold;'>
                    <td colspan='7' style='padding:6px 8px;'>{$cat_label} (Weight: {$cat_pct}%)</td>
                </tr>";
            
            foreach ($faculty_by_cat[$cat] as $sub => $items) {
                $sub_name = $subcat_names[$sub] ?? ucfirst($sub);
                $sub_pct = $allocations[$cat . '_' . $sub] ?? 0;
                $cat_rows .= "
                <tr style='background:#fafafa;'>
                    <td colspan='7' style='padding:5px 8px 5px 25px;'><em>{$sub_name}</em> <small>({$sub_pct}%)</small></td>
                </tr>";
                
                $num = 1;
                foreach ($items as $f) {
                    $name = htmlspecialchars($f['lastname'] . ', ' . $f['firstname'] . ' ' . $f['middlename']);
                    $pos  = htmlspecialchars($f['position_name'] ?? '—');
                    $eff  = number_format($f['avg_efficiency'], 2);
                    $time = number_format($f['avg_timeliness'], 2);
                    $qual = number_format($f['avg_quality'], 2);
                    $over = number_format($f['overall'], 2);
                    $adj  = $this->getAdjectivalRating($f['overall']);
                    $tasks = $f['rated_tasks'];
                    
                    $cat_rows .= "
                <tr>
                    <td class='text-center'>{$num}</td>
                    <td><strong>{$name}</strong><br><small class='text-muted'>{$pos}</small></td>
                    <td class='text-center'>{$tasks}</td>
                    <td class='text-center'>{$eff}</td>
                    <td class='text-center'>{$time}</td>
                    <td class='text-center'>{$qual}</td>
                    <td class='text-center'><strong>{$over}</strong></td>
                    <td class='text-center'>{$adj}</td>
                </tr>";
                    $num++;
                }
            }
        }
        
        // OPCR reference row
        $opcr_display = '';
        if ($opcr_data) {
            $opcr_val = number_format($opcr_data['overall_rating'], 2);
            $opcr_adj = $this->getAdjectivalRating($opcr_data['overall_rating']);
            $opcr_display = "
            <tr style='background:#fff3cd;'>
                <td colspan='2'><strong>OPCR (Office-Wide Reference)</strong></td>
                <td class='text-center'>—</td>
                <td class='text-center'>" . number_format($opcr_data['avg_efficiency'], 2) . "</td>
                <td class='text-center'>" . number_format($opcr_data['avg_timeliness'], 2) . "</td>
                <td class='text-center'>" . number_format($opcr_data['avg_quality'], 2) . "</td>
                <td class='text-center'><strong>{$opcr_val}</strong></td>
                <td class='text-center'><strong>{$opcr_adj}</strong></td>
            </tr>";
        }
        
        // Overall department row
        $overall_row = "
            <tr style='font-weight:bold; background:#d4edda;'>
                <td colspan='2' class='text-right'><strong>DPCR — {$dept['department']} OVERALL</strong></td>
                <td class='text-center'>" . count($faculty_totals) . "</td>
                <td class='text-center'>{$dp_eff}</td>
                <td class='text-center'>{$dp_time}</td>
                <td class='text-center'>{$dp_qual}</td>
                <td class='text-center'><strong>{$dp_overall}</strong></td>
                <td class='text-center'><strong>{$dp_adj}</strong></td>
            </tr>";
        
        // Individual summary rows at the bottom
        $summary_rows = '';
        $num = 1;
        foreach ($faculty_totals as $fid => $t) {
            $cnt = max($t['count'], 1);
            $eff = number_format($t['eff_sum'] / $cnt, 2);
            $time = number_format($t['time_sum'] / $cnt, 2);
            $qual = number_format($t['qual_sum'] / $cnt, 2);
            $over = number_format(($t['eff_sum'] + $t['time_sum'] + $t['qual_sum']) / (3 * $cnt), 2);
            $adj = $this->getAdjectivalRating(($t['eff_sum'] + $t['time_sum'] + $t['qual_sum']) / (3 * $cnt));
            $summary_rows .= "
                <tr>
                    <td class='text-center'>{$num}</td>
                    <td><strong>" . htmlspecialchars($t['name']) . "</strong><br><small>" . htmlspecialchars($t['position']) . "</small></td>
                    <td class='text-center'>" . $t['tasks'] . "</td>
                    <td class='text-center'>{$eff}</td>
                    <td class='text-center'>{$time}</td>
                    <td class='text-center'>{$qual}</td>
                    <td class='text-center'><strong>{$over}</strong></td>
                    <td class='text-center'>{$adj}</td>
                </tr>";
            $num++;
        }
        if (empty($faculty_totals)) {
            $summary_rows = '<tr><td colspan="8" class="text-center text-muted py-3">No faculty ratings found for this period.</td></tr>';
        }
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>DPCR — {$dept['department']} — {$period_label}</title>
    <style>
        @page { size: A4 landscape; margin: 1.2cm; }
        body { font-family: Arial, sans-serif; font-size: 9.5px; color: #000; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header .institution { font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .header h2 { margin: 4px 0 0 0; font-size: 13px; font-weight: bold; }
        .header h3 { margin: 3px 0 0 0; font-size: 11px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        th, td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
        th { background-color: #e8e8e8; font-weight: bold; font-size: 9px; text-align: center; }
        .section-title { background-color: #d0d0d0; font-weight: bold; font-size: 10px; padding: 5px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .info-table td { border: 1px solid #000; padding: 3px 6px; font-size: 9.5px; }
        .info-table .label { font-weight: bold; width: 18%; background: #f0f0f0; }
        .weight-table { width: 55%; margin: 8px auto; }
        .weight-table td { padding: 4px 8px; }
        .signature-section { margin-top: 20px; }
        .signature-table td { border: none; padding: 8px 15px; text-align: center; vertical-align: top; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 35px auto 4px auto; }
        .rating-scale { font-size: 8px; margin-top: 8px; }
        .rating-scale td { border: 1px solid #000; padding: 2px 4px; }
        .declaration { margin: 10px 0; padding: 6px; border: 1px solid #000; font-size: 9px; }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class='header'>
    <div class='institution'>Republic of the Philippines</div>
    <div class='institution'>DR. EMILIO B. ESPINOSA SR. MEMORIAL STATE COLLEGE OF AGRICULTURE AND TECHNOLOGY</div>
    <div style='font-size:10px;'>DEBESMSCAT, Cabitan, Mandaon, Masbate</div>
    <h2>DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h2>
    <h3>SPMS Form — Rating Period: {$period_label}</h3>
    " . ($date_range ? "<div style='font-size:9px;'>Period: {$date_range}</div>" : "") . "
</div>

<table class='info-table'>
    <tr>
        <td class='label'>Department:</td>
        <td width='40%'><strong>{$dept['department']}</strong></td>
        <td class='label'>College:</td>
        <td width='40%'>College of Engineering and Computing Sciences</td>
    </tr>
    <tr>
        <td class='label'>Period Type:</td>
        <td>DP (Department Performance)</td>
        <td class='label'>Campus:</td>
        <td>DEBESMSCAT Main</td>
    </tr>
</table>

<p class='section-title'>PART I: EQUIVALENT WEIGHT OF FUNCTIONS</p>
<table class='weight-table'>
    <thead>
        <tr><th>Function / Category</th><th width='30%'>Weight (%)</th></tr>
    </thead>
    <tbody>
        {$weight_table}
    </tbody>
</table>

<div class='declaration'>
    I, <strong>Department Head, {$dept['department']}</strong>, commit to deliver the above department targets in accordance with the indicated measures for the period <strong>{$period_label}</strong>.
</div>

<p class='section-title'>PART II: PERFORMANCE RATINGS BY FUNCTION</p>
<table>
    <thead>
        <tr>
            <th class='text-center' width='4%'>#</th>
            <th width='30%'>Faculty / Position</th>
            <th class='text-center' width='8%'>Tasks</th>
            <th class='text-center' width='10%'>Efficiency</th>
            <th class='text-center' width='10%'>Timeliness</th>
            <th class='text-center' width='10%'>Quality</th>
            <th class='text-center' width='10%'>Average</th>
            <th class='text-center' width='14%'>Adjectival Rating</th>
        </tr>
    </thead>
    <tbody>
        {$cat_rows}
        {$overall_row}
        {$opcr_display}
    </tbody>
</table>

<p class='section-title'>PART III: FACULTY IPCR SUMMARY</p>
<table>
    <thead>
        <tr>
            <th class='text-center' width='4%'>#</th>
            <th width='30%'>Faculty Name / Position</th>
            <th class='text-center' width='8%'>Tasks</th>
            <th class='text-center' width='10%'>E</th>
            <th class='text-center' width='10%'>T</th>
            <th class='text-center' width='10%'>Q</th>
            <th class='text-center' width='10%'>AVE</th>
            <th class='text-center' width='14%'>Adjectival</th>
        </tr>
    </thead>
    <tbody>
        {$summary_rows}
    </tbody>
</table>

<p class='section-title'>PART IV: ADJECTIVAL RATING SCALE</p>
<table class='rating-scale'>
    <thead>
        <tr><th width='25%'>Range</th><th>Adjectival Rating</th><th width='50%'>Description</th></tr>
    </thead>
    <tbody>
        <tr><td class='text-center'>4.75 – 5.00</td><td class='text-center'><strong>OUTSTANDING</strong></td><td>Performance represents extraordinary achievement</td></tr>
        <tr><td class='text-center'>3.61 – 4.74</td><td class='text-center'><strong>VERY SATISFACTORY</strong></td><td>Performance exceeds expected results</td></tr>
        <tr><td class='text-center'>2.61 – 3.60</td><td class='text-center'><strong>SATISFACTORY</strong></td><td>Performance meets expected results</td></tr>
        <tr><td class='text-center'>1.61 – 2.60</td><td class='text-center'><strong>UNSATISFACTORY</strong></td><td>Performance partially meets expected results</td></tr>
        <tr><td class='text-center'>Below 1.61</td><td class='text-center'><strong>POOR</strong></td><td>Performance fails to meet expected results</td></tr>
    </tbody>
</table>

<div class='signature-section'>
    <table class='signature-table'>
        <tr>
            <td>
                <div style='font-size:9px; text-align:left; margin-bottom:30px;'>Prepared by:</div>
                <div class='signature-line'></div>
                <strong>Department Head, {$dept['department']}</strong><br>
                <small>Date: _______________</small>
            </td>
            <td>
                <div style='font-size:9px; text-align:left; margin-bottom:30px;'>Approved by:</div>
                <div class='signature-line'></div>
                <strong>Dean, College of Engineering and Computing Sciences</strong><br>
                <small>Date: _______________</small>
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
        if (!is_numeric($score) || $score <= 0) return 'NO RATING';
        $score = round($score, 2);
        if ($score >= 4.75) return 'OUTSTANDING';
        if ($score >= 3.61) return 'VERY SATISFACTORY';
        if ($score >= 2.61) return 'SATISFACTORY';
        if ($score >= 1.61) return 'UNSATISFACTORY';
        return 'POOR';
    }
    
    /**
     * Export to Excel (HTML table format readable by Excel/LibreOffice)
     */
    public function exportToExcel($department_id, $target_period_id) {
        $html = $this->generateDPCR($department_id, $target_period_id);
        if (empty($html)) return false;
        
        $dept = $this->getDepartmentData($department_id);
        $period = $this->getPeriodData($target_period_id);
        $dept_name = $dept ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $dept['department']) : 'dept';
        $period_label = $period ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $period['semester'] . '_' . $period['year']) : 'period';
        $filename = 'DPCR_' . $dept_name . '_' . $period_label . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>";
        echo "<head><meta charset='UTF-8'></head><body>";
        echo $html;
        echo "</body></html>";
        return true;
    }
    
    /**
     * Export to PDF using TCPDF
     */
    public function exportToPDF($department_id, $target_period_id, $output_mode = 'D') {
        $html = $this->generateDPCR($department_id, $target_period_id);
        
        if (!file_exists(__DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php')) {
            return false;
        }
        
        require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        
        $dept = $this->getDepartmentData($department_id);
        $period = $this->getPeriodData($target_period_id);
        $dept_name = $dept ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $dept['department']) : 'dept';
        $period_label = $period ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $period['semester'] . '_' . $period['year']) : 'period';
        $filename = 'DPCR_' . $dept_name . '_' . $period_label . '.pdf';
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('EPES System');
        $pdf->SetAuthor('DEBESMSCAT');
        $pdf->SetTitle('DPCR - ' . ($dept['department'] ?? '') . ' - ' . ($period['semester'] ?? '') . ' ' . ($period['year'] ?? ''));
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        if ($output_mode === 'D') {
            $pdf->Output($filename, 'D');
        } elseif ($output_mode === 'F') {
            $dir = __DIR__ . '/uploads/dpcr/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $full_path = $dir . $filename;
            $pdf->Output($full_path, 'F');
            
            // Auto-archive to performance_documents table
            require_once __DIR__ . '/document_archive_helper.php';
            $file_size = filesize($full_path);
            $generated_by = $_SESSION['login_id'] ?? null;
            $period_label = ($period['semester'] ?? '') . '-' . ($period['year'] ?? '');
            archive_document($this->db, 'DPCR', null, $department_id, $target_period_id, $period_label, $full_path, $file_size, $generated_by);
            
            return $full_path;
        } elseif ($output_mode === 'I') {
            $pdf->Output($filename, 'I');
        }
        
        return true;
    }
}
