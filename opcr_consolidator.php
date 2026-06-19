<?php
/**
 * OPCR Form Generator - Excel-aligned version
 * Generates PDF and print-ready OPCR forms (Office Performance Commitment and Review)
 * Uses cascading_ratings table (level='OPCR') for office-wide aggregate
 * and level='DP' rows for per-department breakdown.
 */
require_once 'config.php';
require_once 'db_connect.php';

class OPCRGenerator {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate OPCR form HTML for an OPCR target period
     */
    public function generateOPCR($target_period_id) {
        $period = $this->getPeriodData($target_period_id);
        if (!$period) return '<div class="alert alert-danger">Period not found.</div>';
        
        $opcr_data = $this->getOPCRData($target_period_id);
        $dept_breakdown = $this->getDepartmentBreakdown($target_period_id);
        $allocations = $this->getAverageAllocations();
        
        return $this->buildOPCRHTML($period, $opcr_data, $dept_breakdown, $allocations);
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
     * Get office-wide OPCR aggregate
     */
    private function getOPCRData($target_period_id) {
        $stmt = $this->db->prepare("
            SELECT cr.*, rp.semester, rp.year
            FROM cascading_ratings cr
            LEFT JOIN rating_period rp ON cr.target_period_id = rp.id
            WHERE cr.target_period_id = ? AND cr.level = 'OPCR'
            ORDER BY cr.computed_at DESC LIMIT 1
        ");
        $stmt->bind_param('i', $target_period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $opcr = $result->fetch_assoc();
        $stmt->close();
        return $opcr;
    }
    
    /**
     * Get average percentage allocations across all active positions
     */
    private function getAverageAllocations() {
        $qry = $this->db->query("
            SELECT category, sub_category, AVG(percentage) as pct
            FROM percentage_allocation
            WHERE is_active = 1
            GROUP BY category, sub_category
        ");
        $allocations = [];
        while ($row = $qry->fetch_assoc()) {
            $key = $row['category'];
            if ($row['sub_category']) $key .= '_' . $row['sub_category'];
            $allocations[$key] = round(floatval($row['pct']), 2);
        }
        return $allocations;
    }
    
    /**
     * Get per-department DP breakdown for the same OPCR period
     */
    private function getDepartmentBreakdown($target_period_id) {
        $stmt = $this->db->prepare("
            SELECT 
                cr.department_id,
                d.department as department_name,
                cr.avg_efficiency,
                cr.avg_timeliness,
                cr.avg_quality,
                cr.overall_rating,
                COALESCE(fc.faculty_count, 0) AS faculty_count
            FROM cascading_ratings cr
            LEFT JOIN department_list d ON cr.department_id = d.id
            LEFT JOIN (
                SELECT e.department_id, COUNT(DISTINCT e.id) AS faculty_count
                FROM employee_list e
                INNER JOIN ratings r ON r.employee_id = e.id
                INNER JOIN rating_period rp ON rp.id = ?
                WHERE r.rating_period = CONCAT(rp.semester, '-', rp.year)
                  AND r.period_type = 'IPCR'
                GROUP BY e.department_id
            ) fc ON fc.department_id = cr.department_id
            WHERE cr.target_period_id = ? AND cr.level = 'DP'
            ORDER BY cr.overall_rating DESC, d.department ASC
        ");
        $stmt->bind_param('ii', $target_period_id, $target_period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
        $stmt->close();
        return $departments;
    }
    
    /**
     * Build the full OPCR HTML document
     */
    private function buildOPCRHTML($period, $opcr_data, $dept_breakdown, $allocations) {
        $period_label = $period['semester'] . ' ' . $period['year'];
        $date_range = '';
        if ($period['start_date'] || $period['end_date']) {
            $start = $period['start_date'] ? date('M d, Y', strtotime($period['start_date'])) : '—';
            $end   = $period['end_date']   ? date('M d, Y', strtotime($period['end_date']))   : '—';
            $date_range = "$start – $end";
        }
        
        // Weight table
        $cat_order = ['strategic' => 'Strategic Function', 'core' => 'Core Function', 'support' => 'Support Function'];
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
            } elseif ($cat === 'support') $pct = $allocations['support'] ?? 0;
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
        
        $opcr_eff  = $opcr_data ? number_format($opcr_data['avg_efficiency'], 2) : '—';
        $opcr_time = $opcr_data ? number_format($opcr_data['avg_timeliness'], 2) : '—';
        $opcr_qual = $opcr_data ? number_format($opcr_data['avg_quality'], 2) : '—';
        $opcr_overall = $opcr_data ? number_format($opcr_data['overall_rating'], 2) : '—';
        $opcr_adj = $opcr_data ? $this->getAdjectivalRating($opcr_data['overall_rating']) : 'NO DATA';
        $total_faculty = $opcr_data ? intval($opcr_data['faculty_count']) : 0;
        
        // Department rows
        $dept_rows = '';
        $num = 1;
        foreach ($dept_breakdown as $d) {
            $dept_name = htmlspecialchars($d['department_name'] ?? 'Unknown Department');
            $eff  = number_format($d['avg_efficiency'], 2);
            $time = number_format($d['avg_timeliness'], 2);
            $qual = number_format($d['avg_quality'], 2);
            $over = number_format($d['overall_rating'], 2);
            $adj  = $this->getAdjectivalRating($d['overall_rating']);
            $fc   = intval($d['faculty_count']);
            
            $dept_rows .= "
                <tr>
                    <td class='text-center'>{$num}</td>
                    <td>{$dept_name}</td>
                    <td class='text-center'>{$fc}</td>
                    <td class='text-center'>{$eff}</td>
                    <td class='text-center'>{$time}</td>
                    <td class='text-center'>{$qual}</td>
                    <td class='text-center'><strong>{$over}</strong></td>
                    <td class='text-center'>{$adj}</td>
                </tr>";
            $num++;
        }
        
        if (empty($dept_breakdown)) {
            $dept_rows = "
                <tr>
                    <td colspan='8' class='text-center text-muted'>No department-level DP data available. Run cascade compute first.</td>
                </tr>";
        }
        
        $html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>OPCR - {$period_label}</title>
    <style>
        @page { size: A4 landscape; margin: 1.2cm; }
        body { font-family: Arial, sans-serif; font-size: 9.5px; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header .institution { font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .header h2 { margin: 4px 0 0 0; font-size: 13px; font-weight: bold; }
        .header h3 { margin: 3px 0 0 0; font-size: 11px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        th, td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
        th { background-color: #e8e8e8; text-align: center; font-weight: bold; font-size: 9px; }
        .text-center { text-align: center; }
        .section-title { background-color: #d0d0d0; font-weight: bold; font-size: 10px; padding: 5px; }
        .weight-table { width: 55%; margin: 8px auto; }
        .weight-table td { padding: 4px 8px; }
        .summary-row { font-weight: bold; background: #d4edda; }
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
    <h2>OFFICE PERFORMANCE COMMITMENT AND REVIEW (OPCR)</h2>
    <h3>SPMS Form — Office-Wide Performance Summary</h3>
    <p style='font-size:9px;'><strong>Rating Period:</strong> {$period_label}" . ($date_range ? " <strong>Date Range:</strong> {$date_range}" : '') . "</p>
</div>

<table class='info-table'>
    <tr>
        <td class='label'>Office:</td>
        <td width='40%'><strong>Office of the Vice President for Academic Affairs / College of Engineering and Computing Sciences</strong></td>
        <td class='label'>Period Type:</td>
        <td width='40%'>OPCR (Office Performance)</td>
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
    I, the undersigned Head of Office, commit to deliver the above office-wide performance targets in accordance with the indicated measures for the period <strong>{$period_label}</strong>.
</div>

<p class='section-title'>PART II: OFFICE-WIDE PERFORMANCE SUMMARY</p>
<table>
    <thead>
        <tr>
            <th class='text-center' width='4%'>#</th>
            <th width='30%'>Office / Category</th>
            <th class='text-center' width='8%'>Faculty</th>
            <th class='text-center' width='10%'>Efficiency</th>
            <th class='text-center' width='10%'>Timeliness</th>
            <th class='text-center' width='10%'>Quality</th>
            <th class='text-center' width='10%'>Average</th>
            <th class='text-center' width='14%'>Adjectival Rating</th>
        </tr>
    </thead>
    <tbody>
        <tr class='summary-row'>
            <td class='text-center'>1</td>
            <td><strong>OPCR — College / Office-Wide</strong></td>
            <td class='text-center'>{$total_faculty}</td>
            <td class='text-center'>{$opcr_eff}</td>
            <td class='text-center'>{$opcr_time}</td>
            <td class='text-center'>{$opcr_qual}</td>
            <td class='text-center'><strong>{$opcr_overall}</strong></td>
            <td class='text-center'><strong>{$opcr_adj}</strong></td>
        </tr>
    </tbody>
</table>

<p class='section-title'>PART III: DEPARTMENT PERFORMANCE BREAKDOWN</p>
<table>
    <thead>
        <tr>
            <th class='text-center' width='4%'>#</th>
            <th width='30%'>Department</th>
            <th class='text-center' width='8%'>Faculty</th>
            <th class='text-center' width='10%'>E</th>
            <th class='text-center' width='10%'>T</th>
            <th class='text-center' width='10%'>Q</th>
            <th class='text-center' width='10%'>AVE</th>
            <th class='text-center' width='14%'>Adjectival</th>
        </tr>
    </thead>
    <tbody>
        {$dept_rows}
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
                <strong>Performance Management Team Head</strong><br>
                <small>Date: _______________</small>
            </td>
            <td>
                <div style='font-size:9px; text-align:left; margin-bottom:30px;'>Approved by:</div>
                <div class='signature-line'></div>
                <strong>Dean / OIC, College of Engineering and Computing Sciences</strong><br>
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
    public function exportToExcel($target_period_id) {
        $html = $this->generateOPCR($target_period_id);
        if (empty($html)) return false;
        
        $period = $this->getPeriodData($target_period_id);
        $period_label = $period ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $period['semester'] . '_' . $period['year']) : 'period';
        $filename = 'OPCR_' . $period_label . '.xls';
        
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
    public function exportToPDF($target_period_id, $output_mode = 'D') {
        $html = $this->generateOPCR($target_period_id);
        
        if (!file_exists(__DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php')) {
            return false;
        }
        
        require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        
        $period = $this->getPeriodData($target_period_id);
        $period_label = $period ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $period['semester'] . '_' . $period['year']) : 'period';
        $filename = 'OPCR_' . $period_label . '.pdf';
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('EPES System');
        $pdf->SetAuthor('DEBESMSCAT');
        $pdf->SetTitle('OPCR - ' . ($period['semester'] ?? '') . ' ' . ($period['year'] ?? ''));
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        if ($output_mode === 'D') {
            $pdf->Output($filename, 'D');
        } elseif ($output_mode === 'F') {
            $dir = __DIR__ . '/uploads/opcr/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $full_path = $dir . $filename;
            $pdf->Output($full_path, 'F');
            
            // Auto-archive to performance_documents table
            require_once __DIR__ . '/document_archive_helper.php';
            $file_size = filesize($full_path);
            $generated_by = $_SESSION['login_id'] ?? null;
            $rp_label = ($period['semester'] ?? '') . '-' . ($period['year'] ?? '');
            archive_document($this->db, 'OPCR', null, null, $target_period_id, $rp_label, $full_path, $file_size, $generated_by);
            
            return $full_path;
        } elseif ($output_mode === 'I') {
            $pdf->Output($filename, 'I');
        }
        
        return true;
    }
}
