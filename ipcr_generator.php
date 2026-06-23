<?php
/**
 * IPCR Form Generator - Excel-aligned version
 * Generates PDF and print-ready IPCR forms for faculty performance evaluation
 */
require_once 'config.php';
require_once 'db_connect.php';

class IPCRGenerator {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate IPCR form HTML for a faculty member
     */
    public function generateIPCR($faculty_id, $rating_period_code) {
        $faculty = $this->getFacultyData($faculty_id);
        if (!$faculty) return '<div class="alert alert-danger">Faculty not found.</div>';
        
        $ratings = $this->getFacultyRatings($faculty_id, $rating_period_code);
        $tasks   = $this->getFacultyTasks($faculty_id, $rating_period_code);
        $allocations = $this->getAllocations($faculty['position_id'], $faculty['designation_id']);
        
        return $this->buildIPCRHTML($faculty, $ratings, $tasks, $allocations, $rating_period_code);
    }
    
    /**
     * Get faculty member data
     */
    private function getFacultyData($faculty_id) {
        $stmt = $this->db->prepare("
            SELECT e.*, d.department, dg.designation, p.position as position_name
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
    private function getFacultyRatings($faculty_id, $rating_period_code) {
        $stmt = $this->db->prepare("
            SELECT 
                r.task_id,
                r.efficiency,
                r.timeliness,
                r.quality,
                t.category,
                t.sub_category,
                t.success_indicators,
                t.targets_measures,
                t.deadline,
                tp.date_submitted,
                tp.date_verified,
                tp.file_path
            FROM ratings r
            INNER JOIN task_list t ON r.task_id = t.id
            LEFT JOIN task_progress tp ON tp.task_id = t.id AND tp.faculty_id = r.employee_id AND tp.rating_period = r.rating_period
            WHERE r.employee_id = ? 
              AND r.rating_period = ?
              AND r.efficiency > 0 AND r.timeliness > 0 AND r.quality > 0
            ORDER BY FIELD(t.category, 'strategic', 'core', 'support'), t.sub_category, t.id
        ");
        $stmt->bind_param('is', $faculty_id, $rating_period_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $ratings = [];
        while ($row = $result->fetch_assoc()) {
            $ratings[] = $row;
        }
        $stmt->close();
        return $ratings;
    }
    
    /**
     * Get faculty task progress for the period
     */
    private function getFacultyTasks($faculty_id, $rating_period_code) {
        $stmt = $this->db->prepare("
            SELECT t.*, tp.progress, tp.date_created, tp.date_verified, tp.date_submitted,
                   tp.file_path, tp.file_type
            FROM task_list t
            LEFT JOIN task_progress tp ON t.id = tp.task_id AND tp.faculty_id = ? AND tp.rating_period = ?
            WHERE t.is_active = 1
            ORDER BY FIELD(t.category, 'strategic', 'core', 'support'), t.sub_category, t.id
        ");
        $stmt->bind_param('is', $faculty_id, $rating_period_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        $stmt->close();
        return $tasks;
    }
    
    /**
     * Get percentage allocations for this faculty's position/designation
     */
    private function getAllocations($position_id, $designation_id) {
        $allocations = [];
        
        $desig_cond = ($designation_id && $designation_id > 0) 
            ? "designation_id = " . intval($designation_id)
            : "(designation_id IS NULL OR designation_id = 0)";
        
        $qry = $this->db->query("
            SELECT category, sub_category, percentage 
            FROM percentage_allocation 
            WHERE position_id = " . intval($position_id) . "
            AND $desig_cond
            AND is_active = 1
        ");
        
        while ($row = $qry->fetch_assoc()) {
            // Skip legacy 'core/instructions' rows that are now computed from TER + Instruction
            if (strtolower($row['category']) === 'core' && strtolower($row['sub_category']) === 'instructions') {
                continue;
            }
            $key = $row['category'];
            if ($row['sub_category']) $key .= '_' . $row['sub_category'];
            $allocations[$key] = floatval($row['percentage']);
        }
        return $allocations;
    }
    
    /**
     * Build allocation map from flat allocations array
     * Returns: [category => ['total_pct' => X, 'subcategories' => [sub => pct]]]
     */
    private function buildAllocationMap($allocations) {
        $map = [];
        foreach ($allocations as $key => $pct) {
            if (strpos($key, '_') !== false) {
                list($cat, $sub) = explode('_', $key, 2);
            } else {
                $cat = $key;
                $sub = 'General';
            }
            $cat = strtolower($cat);
            $sub = strtolower($sub);
            
            if (!isset($map[$cat])) {
                $map[$cat] = ['total_pct' => 0, 'subcategories' => []];
            }
            
            // 'core/total' defines the Core function weight in PART I
            if ($cat === 'core' && $sub === 'total') {
                $map[$cat]['total_pct'] = $pct;
                continue;
            }
            
            // Merge TER + Instruction into single "instructions" subcategory
            if ($cat === 'core' && in_array($sub, ['ter', 'instruction'])) {
                $sub = 'instructions';
            }
            
            $map[$cat]['subcategories'][$sub] = ($map[$cat]['subcategories'][$sub] ?? 0) + $pct;
        }
        
        // If no explicit total was set, derive category total from subcategories
        foreach ($map as &$cat_data) {
            if ($cat_data['total_pct'] == 0) {
                $cat_data['total_pct'] = array_sum($cat_data['subcategories']);
            }
            uksort($cat_data['subcategories'], function($a, $b) {
                $order = ['instructions' => 1, 'research' => 2, 'extension' => 3];
                $oa = $order[$a] ?? 99;
                $ob = $order[$b] ?? 99;
                return $oa <=> $ob;
            });
        }
        return $map;
    }
    
    /**
     * Build the full IPCR HTML document
     */
    private function buildIPCRHTML($faculty, $ratings, $tasks, $allocations, $rating_period_code) {
        // Group ratings by category and sub-category
        $by_category = [];
        $by_subcategory = [];
        foreach ($ratings as $r) {
            $cat = $r['category'];
            $sub = $r['sub_category'] ?: 'General';
            if (!isset($by_category[$cat])) $by_category[$cat] = [];
            if (!isset($by_subcategory[$cat])) $by_subcategory[$cat] = [];
            if (!isset($by_subcategory[$cat][$sub])) $by_subcategory[$cat][$sub] = [];
            $by_category[$cat][] = $r;
            $by_subcategory[$cat][$sub][] = $r;
        }
        
        // Category display names and order
        $cat_order = ['strategic' => 'Strategic Function', 'core' => 'Core Function', 'support' => 'Support Function'];
        $subcat_names = [
            'instructions' => 'Instruction / Teaching Effectiveness',
            'research' => 'Research',
            'extension' => 'Extension',
            'General' => 'General'
        ];
        
        // Build allocation structure from this faculty's position/designation
        $allocation_map = $this->buildAllocationMap($allocations);
        
        // Weight table computation - driven by allocation_map
        $weight_table = '';
        $total_weight = 0;
        foreach (['strategic', 'core', 'support'] as $cat) {
            if (!isset($allocation_map[$cat])) continue;
            $cat_data = $allocation_map[$cat];
            $pct = $cat_data['total_pct'];
            $total_weight += $pct;
            $label = $cat_order[$cat] ?? ucfirst($cat);
            $weight_table .= "
                <tr>
                    <td style='padding:6px 10px;'>{$label}</td>
                    <td style='padding:6px 10px; text-align:center; width:25%;'><strong>{$pct}%</strong></td>
                </tr>";
        }
        if (empty($weight_table)) {
            // Fallback if no allocation data
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
        }
        $weight_table .= "
                <tr style='background:#e8e8e8; font-weight:bold;'>
                    <td style='padding:6px 10px;'>TOTAL</td>
                    <td style='padding:6px 10px; text-align:center;'>{$total_weight}%</td>
                </tr>";
        
        // Compute category averages
        $cat_avgs = [];
        foreach ($by_category as $cat => $items) {
            $sum_e = $sum_t = $sum_q = 0;
            $cnt = count($items);
            foreach ($items as $r) {
                $sum_e += $r['efficiency'];
                $sum_t += $r['timeliness'];
                $sum_q += $r['quality'];
            }
            $cat_avgs[$cat] = [
                'count' => $cnt,
                'efficiency' => $cnt > 0 ? round($sum_e / $cnt, 2) : 0,
                'timeliness' => $cnt > 0 ? round($sum_t / $cnt, 2) : 0,
                'quality'    => $cnt > 0 ? round($sum_q / $cnt, 2) : 0,
                'average'    => $cnt > 0 ? round(($sum_e + $sum_t + $sum_q) / (3 * $cnt), 2) : 0,
            ];
        }
        
        // Overall average across all ratings
        $total_cnt = count($ratings);
        $sum_all_e = $sum_all_t = $sum_all_q = 0;
        foreach ($ratings as $r) {
            $sum_all_e += $r['efficiency'];
            $sum_all_t += $r['timeliness'];
            $sum_all_q += $r['quality'];
        }
        $overall_e = $total_cnt > 0 ? round($sum_all_e / $total_cnt, 2) : 0;
        $overall_t = $total_cnt > 0 ? round($sum_all_t / $total_cnt, 2) : 0;
        $overall_q = $total_cnt > 0 ? round($sum_all_q / $total_cnt, 2) : 0;
        $overall_score = $total_cnt > 0 ? round(($sum_all_e + $sum_all_t + $sum_all_q) / (3 * $total_cnt), 2) : 0;
        $adjectival = $this->getAdjectivalRating($overall_score);
        
        // Category rows for main rating table
        $cat_rows = '';
        foreach (['strategic', 'core', 'support'] as $cat) {
            if (!isset($allocation_map[$cat])) continue;
            $cat_data = $allocation_map[$cat];
            $pct = $cat_data['total_pct'];
            $cat_label = $cat_order[$cat] ?? ucfirst($cat);
            $cat_rows .= "
                <tr style='background:#f5f5f5; font-weight:bold;'>
                    <td colspan='7' style='padding:6px 8px;'>{$cat_label} (Weight: {$pct}%)</td>
                </tr>";
            
            // Sub-category rows from allocation_map
            if (!empty($cat_data['subcategories'])) {
                foreach ($cat_data['subcategories'] as $sub => $sub_pct) {
                    $sub_items = $by_subcategory[$cat][$sub] ?? [];
                    $sub_cnt = count($sub_items);
                    $sub_e = $sub_t = $sub_q = 0;
                    foreach ($sub_items as $r) {
                        $sub_e += $r['efficiency'];
                        $sub_t += $r['timeliness'];
                        $sub_q += $r['quality'];
                    }
                    $sub_eff = $sub_cnt > 0 ? round($sub_e / $sub_cnt, 2) : 0;
                    $sub_time = $sub_cnt > 0 ? round($sub_t / $sub_cnt, 2) : 0;
                    $sub_qual = $sub_cnt > 0 ? round($sub_q / $sub_cnt, 2) : 0;
                    $sub_avg = $sub_cnt > 0 ? round(($sub_e + $sub_t + $sub_q) / (3 * $sub_cnt), 2) : 0;
                    $sub_name = $subcat_names[$sub] ?? ucfirst($sub);
                    
                    $cat_rows .= "
                <tr style='background:#fafafa;'>
                    <td style='padding:5px 8px 5px 25px;'><em>{$sub_name}</em> <small>({$sub_pct}%)</small></td>
                    <td class='text-center'>{$sub_cnt}</td>
                    <td class='text-center'>" . number_format($sub_eff, 2) . "</td>
                    <td class='text-center'>" . number_format($sub_time, 2) . "</td>
                    <td class='text-center'>" . number_format($sub_qual, 2) . "</td>
                    <td class='text-center'><strong>" . number_format($sub_avg, 2) . "</strong></td>
                    <td class='text-center'>" . $this->getAdjectivalRating($sub_avg) . "</td>
                </tr>";
                }
            } elseif (isset($cat_avgs[$cat])) {
                // No subcategories: show category-level row
                $a = $cat_avgs[$cat];
                $cat_rows .= "
                <tr style='background:#fafafa;'>
                    <td style='padding:5px 8px 5px 25px;'><em>General</em></td>
                    <td class='text-center'>{$a['count']}</td>
                    <td class='text-center'>" . number_format($a['efficiency'], 2) . "</td>
                    <td class='text-center'>" . number_format($a['timeliness'], 2) . "</td>
                    <td class='text-center'>" . number_format($a['quality'], 2) . "</td>
                    <td class='text-center'><strong>" . number_format($a['average'], 2) . "</strong></td>
                    <td class='text-center'>" . $this->getAdjectivalRating($a['average']) . "</td>
                </tr>";
            }
        }
        
        // Fallback: if no allocation_map, use old category grouping
        if (empty($cat_rows)) {
            foreach (['strategic', 'core', 'support'] as $cat) {
                if (!isset($cat_avgs[$cat])) continue;
                $a = $cat_avgs[$cat];
                $pct = 0;
                if ($cat === 'strategic') $pct = $allocations['strategic'] ?? 0;
                elseif ($cat === 'core') {
                    $pct = ($allocations['core_instructions'] ?? 0)
                         + ($allocations['core_research'] ?? 0)
                         + ($allocations['core_extension'] ?? 0);
                    if ($pct == 0) $pct = $allocations['core'] ?? 0;
                } elseif ($cat === 'support') $pct = $allocations['support'] ?? 0;
                
                $cat_label = $cat_order[$cat] ?? ucfirst($cat);
                $cat_rows .= "
                <tr style='background:#f5f5f5; font-weight:bold;'>
                    <td colspan='7' style='padding:6px 8px;'>{$cat_label} (Weight: {$pct}%)</td>
                </tr>";
                
                if (isset($by_subcategory[$cat]) && !empty($by_subcategory[$cat])) {
                    foreach ($by_subcategory[$cat] as $sub => $sub_items) {
                        $sub_cnt = count($sub_items);
                        $sub_e = $sub_t = $sub_q = 0;
                        foreach ($sub_items as $r) {
                            $sub_e += $r['efficiency'];
                            $sub_t += $r['timeliness'];
                            $sub_q += $r['quality'];
                        }
                        $sub_eff = $sub_cnt > 0 ? round($sub_e / $sub_cnt, 2) : 0;
                        $sub_time = $sub_cnt > 0 ? round($sub_t / $sub_cnt, 2) : 0;
                        $sub_qual = $sub_cnt > 0 ? round($sub_q / $sub_cnt, 2) : 0;
                        $sub_avg = $sub_cnt > 0 ? round(($sub_e + $sub_t + $sub_q) / (3 * $sub_cnt), 2) : 0;
                        $sub_pct = $allocations[$cat . '_' . $sub] ?? 0;
                        $sub_name = $subcat_names[$sub] ?? ucfirst($sub);
                        
                        $cat_rows .= "
                <tr style='background:#fafafa;'>
                    <td style='padding:5px 8px 5px 25px;'><em>{$sub_name}</em> <small>({$sub_pct}%)</small></td>
                    <td class='text-center'>{$sub_cnt}</td>
                    <td class='text-center'>" . number_format($sub_eff, 2) . "</td>
                    <td class='text-center'>" . number_format($sub_time, 2) . "</td>
                    <td class='text-center'>" . number_format($sub_qual, 2) . "</td>
                    <td class='text-center'><strong>" . number_format($sub_avg, 2) . "</strong></td>
                    <td class='text-center'>" . $this->getAdjectivalRating($sub_avg) . "</td>
                </tr>";
                    }
                }
            }
        }
        
        // Indicator accomplishment rows (matches Excel summary sheets)
        $indicator_rows = '';
        $num = 1;
        foreach ($tasks as $task) {
            $status = !empty($task['date_submitted']) ? 'Submitted' : 'Not Submitted';
            if (!empty($task['date_verified'])) $status = 'Verified';
            elseif (!empty($task['progress'])) $status = $task['progress'];
            
            $sub_date = !empty($task['date_submitted']) ? date('M d, Y', strtotime($task['date_submitted'])) : '—';
            $deadline = !empty($task['deadline']) ? date('M d, Y', strtotime($task['deadline'])) : '—';
            $mov = !empty($task['file_path']) ? 'Attached' : 'None';
            
            $cat_label = $cat_order[$task['category']] ?? ucfirst($task['category'] ?? '—');
            $sub_name = $subcat_names[$task['sub_category']] ?? ucfirst($task['sub_category'] ?? '—');
            
            $indicator_rows .= "
                <tr>
                    <td class='text-center'>{$num}</td>
                    <td>" . htmlspecialchars(strip_tags($task['success_indicators'])) . "</td>
                    <td>{$cat_label}</td>
                    <td>{$sub_name}</td>
                    <td class='text-center'>{$sub_date}</td>
                    <td class='text-center'>{$deadline}</td>
                    <td class='text-center'>{$status}</td>
                    <td class='text-center'>{$mov}</td>
                </tr>";
            $num++;
        }
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>IPCR Form - {$faculty['lastname']}, {$faculty['firstname']}</title>
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
        .info-table .label { font-weight: bold; width: 14%; background: #f0f0f0; }
        .weight-table { width: 55%; margin: 8px auto; }
        .weight-table td { padding: 4px 8px; }
        .signature-section { margin-top: 20px; }
        .signature-table td { border: none; padding: 8px 15px; text-align: center; vertical-align: top; }
        .signature-line { border-top: 1px solid #000; width: 180px; margin: 35px auto 4px auto; }
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
    <h2>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
    <h3>SPMS Form — Rating Period: {$rating_period_code}</h3>
</div>

<table class='info-table'>
    <tr>
        <td class='label'>Name:</td>
        <td width='35%'><strong>{$faculty['lastname']}, {$faculty['firstname']} {$faculty['middlename']}</strong></td>
        <td class='label'>Department:</td>
        <td width='35%'>{$faculty['department']}</td>
    </tr>
    <tr>
        <td class='label'>Position:</td>
        <td>{$faculty['position_name']}</td>
        <td class='label'>Designation:</td>
        <td>{$faculty['designation']}</td>
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
    I, <strong>{$faculty['firstname']} {$faculty['middlename']} {$faculty['lastname']}</strong>, 
    designated as <strong>{$faculty['position_name']}</strong>, 
    commit to deliver and agree to be rated on the attainment of the above targets in accordance with the indicated measures 
    for the period <strong>{$rating_period_code}</strong>.
</div>

<p class='section-title'>PART II: PERFORMANCE RATINGS BY FUNCTION</p>
<table>
    <thead>
        <tr>
            <th width='28%'>Function / Indicator</th>
            <th width='8%'># of Items</th>
            <th width='10%'>Efficiency</th>
            <th width='10%'>Timeliness</th>
            <th width='10%'>Quality</th>
            <th width='10%'>Average</th>
            <th width='14%'>Adjectival Rating</th>
        </tr>
    </thead>
    <tbody>
        {$cat_rows}
        <tr style='font-weight:bold; background:#e0e0e0;'>
            <td class='text-right'>OVERALL RATING</td>
            <td class='text-center'>{$total_cnt}</td>
            <td class='text-center'>" . number_format($overall_e, 2) . "</td>
            <td class='text-center'>" . number_format($overall_t, 2) . "</td>
            <td class='text-center'>" . number_format($overall_q, 2) . "</td>
            <td class='text-center'><strong>" . number_format($overall_score, 2) . "</strong></td>
            <td class='text-center'><strong>{$adjectival}</strong></td>
        </tr>
    </tbody>
</table>

<p class='section-title'>PART III: SUMMARY OF ACCOMPLISHMENTS / MOV</p>
<table>
    <thead>
        <tr>
            <th width='4%'>#</th>
            <th width='28%'>Success Indicators / Tasks</th>
            <th width='14%'>Category</th>
            <th width='16%'>Sub-Category</th>
            <th width='12%'>Date Submitted</th>
            <th width='12%'>Deadline</th>
            <th width='8%'>Status</th>
            <th width='8%'>MOV</th>
        </tr>
    </thead>
    <tbody>
        {$indicator_rows}
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
                <div style='font-size:9px; text-align:left; margin-bottom:30px;'>Conforme:</div>
                <div class='signature-line'></div>
                <strong>{$faculty['firstname']} {$faculty['middlename']} {$faculty['lastname']}</strong><br>
                <small>Faculty Member</small><br>
                <small>Date: _______________</small>
            </td>
            <td>
                <div style='font-size:9px; text-align:left; margin-bottom:30px;'>Reviewed by:</div>
                <div class='signature-line'></div>
                <strong>Program Head / Immediate Supervisor</strong><br>
                <small>Date: _______________</small>
            </td>
            <td>
                <div style='font-size:9px; text-align:left; margin-bottom:30px;'>Approved by:</div>
                <div class='signature-line'></div>
                <strong>Dean</strong><br>
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
    public function exportToExcel($faculty_id, $rating_period_code) {
        $html = $this->generateIPCR($faculty_id, $rating_period_code);
        if (empty($html)) return false;
        
        $faculty = $this->getFacultyData($faculty_id);
        $name = $faculty ? ($faculty['lastname'] . '_' . $faculty['firstname']) : 'faculty';
        $filename = 'IPCR_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '_' . 
                    preg_replace('/[^a-zA-Z0-9_-]/', '_', $rating_period_code) . '.xls';
        
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
    public function exportToPDF($faculty_id, $rating_period_code, $output_mode = 'D', $period_id = null) {
        $html = $this->generateIPCR($faculty_id, $rating_period_code);
        
        if (!file_exists(__DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php')) {
            return false;
        }
        
        require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        
        $faculty = $this->getFacultyData($faculty_id);
        $name = $faculty ? ($faculty['lastname'] . '_' . $faculty['firstname']) : 'faculty';
        $filename = 'IPCR_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '_' . 
                    preg_replace('/[^a-zA-Z0-9_-]/', '_', $rating_period_code) . '.pdf';
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('EPES System');
        $pdf->SetAuthor('DEBESMSCAT');
        $pdf->SetTitle('IPCR Form - ' . ($faculty['lastname'] ?? '') . ', ' . ($faculty['firstname'] ?? ''));
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        if ($output_mode === 'D') {
            $pdf->Output($filename, 'D');
        } elseif ($output_mode === 'F') {
            $dir = __DIR__ . '/uploads/ipcr/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $full_path = $dir . $filename;
            $pdf->Output($full_path, 'F');
            
            // Auto-archive to performance_documents table
            if ($period_id === null) {
                // Look up period_id from code
                $stmt = $this->db->prepare("SELECT id FROM rating_period WHERE CONCAT(semester, '-', year) = ? LIMIT 1");
                $stmt->bind_param('s', $rating_period_code);
                $stmt->execute();
                $rp = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $period_id = $rp ? $rp['id'] : 0;
            }
            if ($period_id > 0) {
                require_once __DIR__ . '/document_archive_helper.php';
                $file_size = filesize($full_path);
                $generated_by = $_SESSION['login_id'] ?? null;
                archive_document($this->db, 'IPCR', $faculty_id, null, $period_id, $rating_period_code, $full_path, $file_size, $generated_by);
            }
            
            return $full_path;
        } elseif ($output_mode === 'I') {
            $pdf->Output($filename, 'I');
        }
        
        return true;
    }
}
