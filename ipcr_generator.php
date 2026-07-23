<?php
/**
 * IPCR Form Generator
 * Uses computeRatingBreakdown() from rating_functions.php — the SAME computation
 * as rating.php — so the IPCR preview always matches the Rating page.
 */
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/rating_functions.php';

class IPCRGenerator {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Generate IPCR form HTML for a faculty member
     * @param int $faculty_id
     * @param string $rating_period_code  Primary code (from rating_period.code)
     * @param array $period_codes         All code variants for this semester+year
     */
    public function generateIPCR($faculty_id, $rating_period_code, $period_codes = null) {
        $faculty = $this->getFacultyData($faculty_id);
        if (!$faculty) return '<div class="alert alert-danger">Faculty not found.</div>';

        // Build all code variants if not provided
        if ($period_codes === null) {
            $period_codes = $this->getAllPeriodCodes($rating_period_code);
        }

        // Build period_filter SQL (same as period_builder.php)
        $period_filter = $this->buildPeriodFilter($period_codes);

        $position_id = intval($faculty['position_id'] ?? 0);
        $designation_id = intval($faculty['designation_id'] ?? 0);

        // Use the shared computation function — same as rating.php
        $breakdown = computeRatingBreakdown($this->db, $faculty_id, $position_id, $designation_id, $rating_period_code, $period_filter);

        if ($breakdown === null) {
            // No active sections — return empty so ipcr_view shows "no ratings"
            return '<div class="alert alert-warning">No verified ratings found for this period.</div>';
        }

        return $this->buildIPCRHTML($faculty, $breakdown, $rating_period_code, $period_codes);
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
     * Get all rating_period code variants for a given canonical code.
     */
    private function getAllPeriodCodes($primary_code) {
        $codes = [$primary_code];

        $stmt = $this->db->prepare("SELECT semester, year FROM rating_period WHERE code = ? LIMIT 1");
        $stmt->bind_param('s', $primary_code);
        $stmt->execute();
        $rp = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($rp) {
            $semester = $rp['semester'];
            $year = $rp['year'];

            // All codes from rating_period with same semester+year
            $stmt2 = $this->db->prepare("SELECT code FROM rating_period WHERE semester = ? AND year = ?");
            $stmt2->bind_param('ss', $semester, $year);
            $stmt2->execute();
            $r2 = $stmt2->get_result();
            while ($row = $r2->fetch_assoc()) {
                if (!empty($row['code'])) $codes[] = $row['code'];
            }
            $stmt2->close();

            // Short code (e.g. 1-2526)
            $short_code = '';
            $sem_num = '1';
            if (strpos($year, '-') !== false) {
                $parts = explode('-', $year);
                $short = substr($parts[0], -2) . substr($parts[1], -2);
                if (strpos($semester, '2nd') !== false) $sem_num = '2';
                elseif (stripos($semester, 'Summer') !== false) $sem_num = 'S';
                $short_code = $sem_num . '-' . $short;
                $codes[] = $short_code;
            }

            // "Semester Year" format
            $codes[] = $semester . ' ' . $year;

            // Data-driven: gather distinct codes from task_progress and ratings
            $sem_compact = str_replace(' ', '', $semester);
            $like1 = '%' . $sem_compact . '-' . $year . '%';
            $like2 = '%' . $short_code . '%';

            $stmt3 = $this->db->prepare("SELECT DISTINCT rating_period FROM task_progress WHERE rating_period <> '' AND (rating_period LIKE ? OR rating_period LIKE ?)");
            $stmt3->bind_param('ss', $like1, $like2);
            $stmt3->execute();
            $r3 = $stmt3->get_result();
            while ($row = $r3->fetch_assoc()) {
                if (!empty($row['rating_period'])) $codes[] = $row['rating_period'];
            }
            $stmt3->close();

            $stmt4 = $this->db->prepare("SELECT DISTINCT rating_period FROM ratings WHERE rating_period <> '' AND (rating_period LIKE ? OR rating_period LIKE ?)");
            $stmt4->bind_param('ss', $like1, $like2);
            $stmt4->execute();
            $r4 = $stmt4->get_result();
            while ($row = $r4->fetch_assoc()) {
                if (!empty($row['rating_period'])) $codes[] = $row['rating_period'];
            }
            $stmt4->close();
        }

        return array_values(array_unique(array_filter($codes)));
    }

    /**
     * Build SQL period filter fragment
     */
    private function buildPeriodFilter($period_codes) {
        if (empty($period_codes)) return " AND 0";
        $escaped = array_map(function($c) {
            return "'" . $this->db->real_escape_string($c) . "'";
        }, $period_codes);
        return " AND rating_period IN (" . implode(',', $escaped) . ")";
    }

    /**
     * Build the full IPCR HTML document using the rating breakdown
     */
    private function buildIPCRHTML($faculty, $b, $rating_period_code, $period_codes) {
        $cat_order = [
            'strategic' => 'Strategic Function',
            'core' => 'Core Function',
            'support' => 'Support Function'
        ];
        $subcat_names = [
            'instructions' => 'Instruction / Teaching Effectiveness',
            'research' => 'Research',
            'extension' => 'Extension',
            'General' => 'General'
        ];

        // ---- PART I: Weight Table ----
        $weight_table = '';
        $total_weight = 0;
        if ($b['show_strategic_pct']) {
            $total_weight += $b['str_pct'];
            $weight_table .= "<tr><td style='padding:6px 10px;'>Strategic Function</td><td style='padding:6px 10px; text-align:center; width:25%;'><strong>{$b['str_pct']}%</strong></td></tr>";
        }
        if ($b['show_instructions_pct'] || $b['show_research_pct'] || $b['show_extension_pct']) {
            $total_weight += $b['core_effective_pct'];
            $weight_table .= "<tr><td style='padding:6px 10px;'>Core Function</td><td style='padding:6px 10px; text-align:center; width:25%;'><strong>{$b['core_effective_pct']}%</strong></td></tr>";
        }
        if ($b['show_support_pct'] && $b['supp_pct'] > 0) {
            $total_weight += $b['supp_pct'];
            $weight_table .= "<tr><td style='padding:6px 10px;'>Support Function</td><td style='padding:6px 10px; text-align:center; width:25%;'><strong>{$b['supp_pct']}%</strong></td></tr>";
        }
        $weight_table .= "<tr style='background:#e8e8e8; font-weight:bold;'><td style='padding:6px 10px;'>TOTAL</td><td style='padding:6px 10px; text-align:center;'>{$total_weight}%</td></tr>";

        // ---- PART II: Performance Ratings (detail rows) ----
        $cat_rows = '';

        // Strategic
        if ($b['show_strategic'] && !empty($b['tasks_by_section']['strategic'])) {
            $cat_rows .= "<tr style='background:#f5f5f5; font-weight:bold;'><td colspan='6' style='padding:6px 8px;'>Strategic Function (Weight: {$b['str_pct']}%)</td></tr>";
            foreach ($b['tasks_by_section']['strategic'] as $task) {
                $ave_display = $task['has_submission'] ? $task['average'] : ($task['is_na'] ? 'N/A' : '-');
                $cat_rows .= "<tr style='background:#fafafa;'>
                    <td style='padding:5px 8px 5px 25px;'>" . htmlspecialchars($task['success_indicators']) . "</td>
                    <td class='text-center'>" . $task['efficiency'] . "</td>
                    <td class='text-center'>" . $task['timeliness'] . "</td>
                    <td class='text-center'>" . $task['quality'] . "</td>
                    <td class='text-center'><strong>" . $ave_display . "</strong></td>
                </tr>";
            }
            $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Strategic Function Average</b></td><td class='text-center'><b>{$b['str_ave']['ave']}</b></td></tr>";
        }

        // Core
        if ($b['show_instructions'] || $b['show_research'] || $b['show_extension']) {
            $cat_rows .= "<tr style='background:#f5f5f5; font-weight:bold;'><td colspan='6' style='padding:6px 8px;'>Core Function (Weight: {$b['core_effective_pct']}%)</td></tr>";

            // Instruction (TER + Instruction split)
            if ($b['show_instructions']) {
                $cat_rows .= "<tr style='background:#fafafa;'><td colspan='6' style='padding:5px 8px 5px 25px;'><b>A. INSTRUCTION ({$b['inst_pct']}%)</b></td></tr>";

                // TER sub-section
                $cat_rows .= "<tr style='background:#f8f8f8;'><td colspan='6' style='padding:4px 8px 4px 40px;'><b>A.1 Teaching Effectiveness ({$b['ter_split']}%) - TER</b></td></tr>";
                if (!empty($b['ter_tasks'])) {
                    foreach ($b['ter_tasks'] as $task) {
                        $ave_display = $task['has_submission'] ? $task['average'] : ($task['is_na'] ? 'N/A' : '-');
                        $cat_rows .= "<tr><td style='padding:4px 8px 4px 55px;'>" . htmlspecialchars($task['success_indicators']) . "</td>
                            <td class='text-center'>" . $task['efficiency'] . "</td>
                            <td class='text-center'>" . $task['timeliness'] . "</td>
                            <td class='text-center'>" . $task['quality'] . "</td>
                            <td class='text-center'><strong>" . $ave_display . "</strong></td></tr>";
                    }
                } else {
                    $cat_rows .= "<tr><td colspan='6' style='padding:4px 8px 4px 55px;' class='text-muted'><em>(No verified submissions)</em></td></tr>";
                }
                $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>TER</b></td><td class='text-center'><b>{$b['ter_ave']}</b></td></tr>";

                // Instruction sub-section
                $cat_rows .= "<tr style='background:#f8f8f8;'><td colspan='6' style='padding:4px 8px 4px 40px;'><b>A.2 Instructions ({$b['instr_split']}%)</b></td></tr>";
                if (!empty($b['instr_tasks'])) {
                    foreach ($b['instr_tasks'] as $task) {
                        $ave_display = $task['has_submission'] ? $task['average'] : ($task['is_na'] ? 'N/A' : '-');
                        $cat_rows .= "<tr><td style='padding:4px 8px 4px 55px;'>" . htmlspecialchars($task['success_indicators']) . "</td>
                            <td class='text-center'>" . $task['efficiency'] . "</td>
                            <td class='text-center'>" . $task['timeliness'] . "</td>
                            <td class='text-center'>" . $task['quality'] . "</td>
                            <td class='text-center'><strong>" . $ave_display . "</strong></td></tr>";
                    }
                } else {
                    $cat_rows .= "<tr><td colspan='6' style='padding:4px 8px 4px 55px;' class='text-muted'><em>(No verified submissions)</em></td></tr>";
                }
                $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Instructions (Sum ÷ {$b['inst_divisor']})</b></td><td class='text-center'><b>{$b['instruction_div']}</b></td></tr>";
                if ($b['inst_active']) {
                    $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Instruction (Average)</b></td><td class='text-center'><b>{$b['instruction_rating']}</b></td></tr>";
                }
            }

            // Research
            if ($b['show_research']) {
                $cat_rows .= "<tr style='background:#fafafa;'><td colspan='6' style='padding:5px 8px 5px 25px;'><b>B. RESEARCH ({$b['res_pct']}%)</b></td></tr>";
                if (!empty($b['tasks_by_section']['core_research'])) {
                    foreach ($b['tasks_by_section']['core_research'] as $task) {
                        $ave_display = $task['has_submission'] ? $task['average'] : ($task['is_na'] ? 'N/A' : '-');
                        $cat_rows .= "<tr><td style='padding:4px 8px 4px 40px;'>" . htmlspecialchars($task['success_indicators']) . "</td>
                            <td class='text-center'>" . $task['efficiency'] . "</td>
                            <td class='text-center'>" . $task['timeliness'] . "</td>
                            <td class='text-center'>" . $task['quality'] . "</td>
                            <td class='text-center'><strong>" . $ave_display . "</strong></td></tr>";
                    }
                } else {
                    $cat_rows .= "<tr><td colspan='6' style='padding:4px 8px 4px 40px;' class='text-muted'><em>(No verified submissions)</em></td></tr>";
                }
                $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Research (Average)</b></td><td class='text-center'><b>" . number_format($b['res_val'], 2) . "</b></td></tr>";
            }

            // Extension
            if ($b['show_extension']) {
                $cat_rows .= "<tr style='background:#fafafa;'><td colspan='6' style='padding:5px 8px 5px 25px;'><b>C. EXTENSION ({$b['ext_pct']}%)</b></td></tr>";
                if (!empty($b['tasks_by_section']['core_extension'])) {
                    foreach ($b['tasks_by_section']['core_extension'] as $task) {
                        $ave_display = $task['has_submission'] ? $task['average'] : ($task['is_na'] ? 'N/A' : '-');
                        $cat_rows .= "<tr><td style='padding:4px 8px 4px 40px;'>" . htmlspecialchars($task['success_indicators']) . "</td>
                            <td class='text-center'>" . $task['efficiency'] . "</td>
                            <td class='text-center'>" . $task['timeliness'] . "</td>
                            <td class='text-center'>" . $task['quality'] . "</td>
                            <td class='text-center'><strong>" . $ave_display . "</strong></td></tr>";
                    }
                } else {
                    $cat_rows .= "<tr><td colspan='6' style='padding:4px 8px 4px 40px;' class='text-muted'><em>(No verified submissions)</em></td></tr>";
                }
                $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Extension (Average)</b></td><td class='text-center'><b>" . number_format($b['ext_val'], 2) . "</b></td></tr>";
            }

            // Core function average
            $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Core Function Average</b></td><td class='text-center'><b>" . number_format(min(5.00, $b['core_function']), 2) . "</b></td></tr>";
        }

        // Support
        if ($b['show_support']) {
            $cat_rows .= "<tr style='background:#f5f5f5; font-weight:bold;'><td colspan='6' style='padding:6px 8px;'>Support Function (Weight: {$b['supp_pct']}%)</td></tr>";
            if (!empty($b['tasks_by_section']['support'])) {
                foreach ($b['tasks_by_section']['support'] as $task) {
                    $ave_display = $task['has_submission'] ? $task['average'] : ($task['is_na'] ? 'N/A' : '-');
                    $cat_rows .= "<tr><td style='padding:5px 8px 5px 25px;'>" . htmlspecialchars($task['success_indicators']) . "</td>
                        <td class='text-center'>" . $task['efficiency'] . "</td>
                        <td class='text-center'>" . $task['timeliness'] . "</td>
                        <td class='text-center'>" . $task['quality'] . "</td>
                        <td class='text-center'><strong>" . $ave_display . "</strong></td></tr>";
                }
            } else {
                $cat_rows .= "<tr><td colspan='6' class='text-muted text-center' style='padding:8px;'>(No verified submissions)</td></tr>";
            }
            if ($b['supp_active']) {
                $cat_rows .= "<tr class='table-info'><td colspan='4' style='text-align:right;'><b>Support Function Average</b></td><td class='text-center'><b>{$b['supp_ave']['ave']}</b></td></tr>";
            }
        }

        // ---- Overall Rating Table ----
        $overall_rows = '';
        $display_total_pct = 0;

        if ($b['show_strategic_pct']) {
            $display_total_pct += $b['str_pct'];
            $overall_rows .= "<tr><td style='text-align:left;'><b>Strategic Functions</b></td>
                <td class='text-center'>{$b['str_pct']}%</td>
                <td class='text-center'>" . ($b['str_active'] ? $b['str_ave']['ave'] : 'N/A') . "</td>
                <td class='text-center'>" . ($b['str_active'] ? number_format($b['str_portion'], 2) : '0.00') . "</td>
                <td class='text-center'>" . ($b['str_active'] ? getAdjectivalRating($b['str_val']) : 'NO RATING') . "</td></tr>";
        }

        if ($b['show_instructions_pct'] || $b['show_research_pct'] || $b['show_extension_pct']) {
            $display_total_pct += $b['core_effective_pct'];
            $core_avg_display = min(5.00, $b['core_function']);
            $overall_rows .= "<tr><td style='text-align:left;'><b>Core Functions</b></td>
                <td class='text-center'>{$b['core_effective_pct']}%</td>
                <td class='text-center'>" . ($b['core_weighted_pct'] > 0 ? number_format($core_avg_display, 2) : 'N/A') . "</td>
                <td class='text-center'>" . ($b['core_weighted_pct'] > 0 ? number_format($b['core_weighted'], 2) : '0.00') . "</td>
                <td class='text-center'>" . ($b['core_weighted_pct'] > 0 ? getAdjectivalRating($core_avg_display) : 'NO RATING') . "</td></tr>";

            if ($b['show_instructions_pct']) {
                $inst_display = $b['inst_active'] ? $b['instruction_rating'] : 'N/A';
                $overall_rows .= "<tr style='font-size:0.85rem; background-color:#f8f8f8;'><td style='text-align:left;'>&nbsp;&nbsp;&nbsp;&nbsp;Instruction</td>
                    <td class='text-center'>{$b['inst_pct']}%</td>
                    <td class='text-center'>{$inst_display}</td><td></td>
                    <td class='text-center'>" . ($b['inst_active'] ? getAdjectivalRating(floatval($b['instruction_rating'])) : 'NO RATING') . "</td></tr>";
            }
            if ($b['show_research_pct']) {
                $overall_rows .= "<tr style='font-size:0.85rem; background-color:#f8f8f8;'><td style='text-align:left;'>&nbsp;&nbsp;&nbsp;&nbsp;Research</td>
                    <td class='text-center'>{$b['res_pct']}%</td>
                    <td class='text-center'>" . ($b['res_active'] ? number_format($b['res_val'], 2) : 'N/A') . "</td><td></td>
                    <td class='text-center'>" . ($b['res_active'] ? getAdjectivalRating($b['res_val']) : 'NO RATING') . "</td></tr>";
            }
            if ($b['show_extension_pct']) {
                $overall_rows .= "<tr style='font-size:0.85rem; background-color:#f8f8f8;'><td style='text-align:left;'>&nbsp;&nbsp;&nbsp;&nbsp;Extension</td>
                    <td class='text-center'>{$b['ext_pct']}%</td>
                    <td class='text-center'>" . ($b['ext_active'] ? number_format($b['ext_val'], 2) : 'N/A') . "</td><td></td>
                    <td class='text-center'>" . ($b['ext_active'] ? getAdjectivalRating($b['ext_val']) : 'NO RATING') . "</td></tr>";
            }
        }

        if ($b['show_support_pct'] && $b['supp_pct'] > 0) {
            $display_total_pct += $b['supp_pct'];
            $overall_rows .= "<tr><td style='text-align:left;'><b>Support Functions</b></td>
                <td class='text-center'>{$b['supp_pct']}%</td>
                <td class='text-center'>" . ($b['supp_active'] ? $b['supp_ave']['ave'] : 'N/A') . "</td>
                <td class='text-center'>" . ($b['supp_active'] ? number_format($b['supp_portion'], 2) : '0.00') . "</td>
                <td class='text-center'>" . ($b['supp_active'] ? getAdjectivalRating($b['supp_val']) : 'NO RATING') . "</td></tr>";
        }

        $overall_rows .= "<tr style='font-weight:bold;'><td style='text-align:right;'>TOTAL</td>
            <td class='text-center'>" . number_format($display_total_pct, 0) . "%</td><td></td>
            <td class='text-center'>" . number_format($b['total_portion'], 2) . "</td>
            <td class='text-center'>" . getAdjectivalRating($b['total_portion']) . "</td></tr>";

        // ---- Part III: Summary of Accomplishments / MOV ----
        $indicator_rows = '';
        $num = 1;
        foreach ($b['all_tasks'] as $task) {
            $status = 'Not Submitted';
            if (!empty($task['date_verified'])) $status = 'Verified';
            elseif (!empty($task['progress'])) $status = $task['progress'];
            elseif (!empty($task['date_submitted'])) $status = 'Submitted';

            $sub_date = !empty($task['date_submitted']) ? date('M d, Y', strtotime($task['date_submitted'])) : '—';
            $mov = !empty($task['file_path']) ? 'Attached' : 'None';

            $cat_label = $cat_order[$task['category']] ?? ucfirst($task['category'] ?? '—');
            $sub_name = $subcat_names[$task['sub_category']] ?? ucfirst($task['sub_category'] ?? '—');

            $indicator_rows .= "<tr>
                <td class='text-center'>{$num}</td>
                <td>" . htmlspecialchars(strip_tags($task['success_indicators'])) . "</td>
                <td>{$cat_label}</td>
                <td>{$sub_name}</td>
                <td class='text-center'>{$sub_date}</td>
                <td class='text-center'>{$status}</td>
                <td class='text-center'>{$mov}</td>
            </tr>";
            $num++;
        }

        $period_label = $rating_period_code;
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
        .table-info { background-color: #d1ecf1; }
        @media print { .no-print { display: none !important; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>

<div class='header'>
    <div class='institution'>Republic of the Philippines</div>
    <div class='institution'>DR. EMILIO B. ESPINOSA SR. MEMORIAL STATE COLLEGE OF AGRICULTURE AND TECHNOLOGY</div>
    <div style='font-size:10px;'>DEBESMSCAT, Cabitan, Mandaon, Masbate</div>
    <h2>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
    <h3>SPMS Form — Rating Period: {$period_label}</h3>
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
    <thead><tr><th>Function / Category</th><th width='30%'>Weight (%)</th></tr></thead>
    <tbody>{$weight_table}</tbody>
</table>

<div class='declaration'>
    I, <strong>{$faculty['firstname']} {$faculty['middlename']} {$faculty['lastname']}</strong>,
    designated as <strong>{$faculty['position_name']}</strong>,
    commit to deliver and agree to be rated on the attainment of the above targets in accordance with the indicated measures
    for the period <strong>{$period_label}</strong>.
</div>

<p class='section-title'>PART II: PERFORMANCE RATINGS BY FUNCTION</p>
<table>
    <thead>
        <tr>
            <th width='34%'>Function / Indicator</th>
            <th width='11%'>E</th>
            <th width='11%'>T</th>
            <th width='11%'>Q</th>
            <th width='11%'>AVE</th>
        </tr>
    </thead>
    <tbody>
        {$cat_rows}
        <tr style='font-weight:bold; background:#e0e0e0;'>
            <td colspan='4' class='text-right'>OVERALL RATING</td>
            <td class='text-center'><strong>" . number_format($b['total_portion'], 2) . "</strong></td>
        </tr>
        <tr style='font-weight:bold; background:#e8e8e8;'>
            <td colspan='4' class='text-right'>Adjectival Rating</td>
            <td class='text-center'><strong>" . getAdjectivalRating($b['total_portion']) . "</strong></td>
        </tr>
    </tbody>
</table>

<p class='section-title'>PART III: SUMMARY OF ACCOMPLISHMENTS / MOV</p>
<table>
    <thead>
        <tr>
            <th width='4%'>#</th>
            <th width='30%'>Success Indicators / Tasks</th>
            <th width='14%'>Category</th>
            <th width='16%'>Sub-Category</th>
            <th width='14%'>Date Submitted</th>
            <th width='10%'>Status</th>
            <th width='8%'>MOV</th>
        </tr>
    </thead>
    <tbody>{$indicator_rows}</tbody>
</table>

<p class='section-title'>PART IV: ADJECTIVAL RATING SCALE</p>
<table class='rating-scale'>
    <thead><tr><th width='25%'>Range</th><th>Adjectival Rating</th><th width='50%'>Description</th></tr></thead>
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
     * Export to Excel
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

            // Auto-archive
            if ($period_id === null) {
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