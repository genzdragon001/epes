<?php 
include 'db_connect.php';

function getAdjectivalRating($score) {
    if (!is_numeric($score) || $score <= 0) return "NO RATING";
    $score = round($score, 2);
    if ($score >= 4.75) return "OUTSTANDING";
    if ($score >= 3.61) return "VERY SATISFACTORY";
    if ($score >= 2.61) return "SATISFACTORY";
    if ($score >= 1.61) return "UNSATISFACTORY";
    if ($score <= 1.60) return "POOR";
    return "NO RATING";
}

function getAllocation($conn, $position_id, $designation_id, $category, $sub_category = null) {
    $sql = "SELECT percentage FROM percentage_allocation 
            WHERE position_id = $position_id";
    
    if ($designation_id && $designation_id > 0) {
        $sql .= " AND designation_id = $designation_id";
    } else {
        $sql .= " AND (designation_id IS NULL OR designation_id = 0)";
    }
    
    $sql .= " AND category = '$category'";
    
    if ($sub_category) {
        $sql .= " AND sub_category = '$sub_category'";
    } else {
        // Match either NULL, empty, or 'total' (for core_total entries)
        $sql .= " AND (sub_category IS NULL OR sub_category = '' OR sub_category = 'total')";
    }
    
    $sql .= " LIMIT 1";
    $qry = $conn->query($sql);
    if($qry && $qry->num_rows > 0) {
        return floatval($qry->fetch_assoc()['percentage']);
    }
    return 0;
}

$faculty_id = intval($_SESSION['login_id'] ?? 0);

$emp_qry = $conn->query("SELECT e.*, p.position as position_name, d.designation as designation_name, dept.department as department_name
    FROM employee_list e 
    LEFT JOIN position_list p ON e.position_id = p.id 
    LEFT JOIN designation_list d ON e.designation_id = d.id 
    LEFT JOIN department_list dept ON e.department_id = dept.id
    WHERE e.id = $faculty_id LIMIT 1");
$emp_data = $emp_qry->fetch_assoc();
$emp_position_id = $emp_data['position_id'] ?? 0;
$emp_designation_id = $emp_data['designation_id'] ?? null;
$position_name = $emp_data['position_name'] ?? 'Unknown';
$designation_name = $emp_data['designation_name'] ?? null;
$department_name = $emp_data['department_name'] ?? 'N/A';
$faculty_fullname = trim(($emp_data['lastname'] ?? '') . ', ' . ($emp_data['firstname'] ?? '') . ' ' . ($emp_data['middlename'] ?? ''));
$isDesignated = !empty($emp_designation_id);
$is_cos = ($emp_position_id == 19);

$can_see_research_extension = ($emp_position_id >= 1 && $emp_position_id <= 18);

$categories = [];
$cat_qry = $conn->query("SELECT * FROM function_categories WHERE is_active = 1 ORDER BY category, id");
while ($row = $cat_qry->fetch_assoc()) {
    $categories[$row['category']][] = $row;
}

$allocations = [];
$position_ids = $emp_position_id > 0 ? "$emp_position_id, 0" : "0";
$desig_id = intval($emp_designation_id ?? 0);
//var_dump($emp_designation_id);
if ($emp_designation_id && $emp_designation_id > 0) {
    $desig_condition = "designation_id = " . intval($emp_designation_id);
} else {
    $desig_condition = "(designation_id IS NULL OR designation_id = 0)";
}
$alloc_qry = $conn->query("
    SELECT * FROM percentage_allocation 
    WHERE position_id = $emp_position_id
    AND $desig_condition
    AND is_active = 1
    ORDER BY position_id ASC
");
$num_rows = $alloc_qry ? $alloc_qry->num_rows : 0;
?>
<!-- DEBUG: position_id=<?php echo $emp_position_id; ?>, designation_id=<?php echo $emp_designation_id; ?>, rows=<?php echo $num_rows; ?> -->
<?php
// Build allocations array with normalized keys
// DB uses: core_total, core_instruction, core_research, core_extension, core_ter, strategic, support
// Code expects: core_total, core_instructions, core_research, core_extension, strategic, support
while ($row = $alloc_qry->fetch_assoc()) {
    $key = $row['category'];
    if ($row['sub_category']) {
        $key .= '_' . $row['sub_category'];
    }
    if (!isset($allocations[$key])) {
        $allocations[$key] = floatval($row['percentage']);
    }
}
// Normalize: map core_instruction -> core_instructions (plural) for consistency
if (isset($allocations['core_instruction']) && !isset($allocations['core_instructions'])) {
    $allocations['core_instructions'] = $allocations['core_instruction'];
}

//var_dump($allocations);
$has_support = isset($allocations['support']) && $allocations['support'] > 0;
?>
<!-- DEBUG: is_cos=<?php echo $is_cos; ?>, support=<?php echo $allocations['support'] ?? 'null'; ?>, has_support=<?php echo $has_support; ?> -->
<?php
$has_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0;
$has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
$has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && $can_see_research_extension;
$has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && $can_see_research_extension;

// Always include strategic tasks (show even without verified submission)
$cat_filters[] = "t.category = 'strategic'";
if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
if ($has_support) $cat_filters[] = "t.category = 'support'";

$where = "t.is_active = 1";
$where .= " AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $emp_position_id)";
$where .= " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = $emp_position_id)";

if ($is_cos) {
    $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
} elseif (!empty($emp_designation_id) && $emp_designation_id > 0) {
    $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = " . intval($emp_designation_id) . ")";
} else {
    $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
}

if (!empty($cat_filters)) {
    $where .= " AND (" . implode(" OR ", $cat_filters) . ")";
}

$qry = $conn->query("
    SELECT 
        t.id as task_id,
        t.category,
        t.sub_category,
        t.success_indicators,
        t.targets_measures,
        t.quality as task_quality,
        t.timeliness as task_timeliness,
        t.efficiency as task_efficiency,
        tp.progress,
        tp.file_path,
        tp.file_type,
        tp.date_verified,
        r.efficiency as rating_efficiency,
        r.timeliness as rating_timeliness,
        r.quality as rating_quality,
        ev.firstname as evaluator_firstname,
        ev.lastname as evaluator_lastname
    FROM task_list t
    LEFT JOIN task_progress tp ON tp.task_id = t.id AND tp.faculty_id = $faculty_id
    LEFT JOIN ratings r ON r.task_id = t.id AND r.employee_id = $faculty_id
    LEFT JOIN evaluator_list ev ON r.evaluator_id = ev.id
    WHERE $where
    ORDER BY t.category, t.sub_category, t.id
");

$tasks_by_section = [
    'strategic' => [],
    'core_instructions' => [],
    'core_research' => [],
    'core_extension' => [],
    'support' => []
];

if ($qry) {
    while ($row = $qry->fetch_assoc()) {
        $category = strtolower($row['category'] ?? '');
        $sub_category = strtolower($row['sub_category'] ?? '');
        
        $progress = $row['progress'] ?? null;
        $is_na = ($progress === 'N/A');
        $has_submission = !empty($progress) && $progress == 'Verified';
        
        if ($is_na) {
            // N/A: show N/A in all rating columns, exclude from computations
            $task_data = [
                'task_id' => $row['task_id'],
                'success_indicators' => $row['success_indicators'] ?? '',
                'targets_measures' => $row['targets_measures'] ?? '',
                'average' => 'N/A',
                'efficiency' => 'N/A',
                'timeliness' => 'N/A',
                'quality' => 'N/A',
                'evaluator' => '',
                'has_submission' => false,
                'is_na' => true,
                'sub_category' => $row['sub_category'] ?? ''
            ];
        } elseif (!$has_submission) {
            $task_data = [
                'task_id' => $row['task_id'],
                'success_indicators' => $row['success_indicators'] ?? '',
                'targets_measures' => $row['targets_measures'] ?? '',
                'average' => '0',
                'efficiency' => '-',
                'timeliness' => '-',
                'quality' => '-',
                'evaluator' => '',
                'has_submission' => false,
                'is_na' => false,
                'sub_category' => $row['sub_category'] ?? ''
            ];
        } else {
            $rating_eff = (isset($row['rating_efficiency']) && is_numeric($row['rating_efficiency']) && $row['rating_efficiency'] > 0) ? (float)$row['rating_efficiency'] : null;
            $rating_time = (isset($row['rating_timeliness']) && is_numeric($row['rating_timeliness']) && $row['rating_timeliness'] > 0) ? (float)$row['rating_timeliness'] : null;
            $rating_qual = (isset($row['rating_quality']) && is_numeric($row['rating_quality']) && $row['rating_quality'] > 0) ? (float)$row['rating_quality'] : null;
            
            $criteria = [];
            if ($row['task_quality'] == 'Applicable' && $rating_qual !== null) $criteria['quality'] = $rating_qual;
            if ($row['task_efficiency'] == 'Applicable' && $rating_eff !== null) $criteria['efficiency'] = $rating_eff;
            if ($row['task_timeliness'] == 'Applicable' && $rating_time !== null) $criteria['timeliness'] = $rating_time;
            
            $average = (count($criteria) > 0) ? number_format(array_sum($criteria) / count($criteria), 2) : '0';
            
            $task_data = [
                'task_id' => $row['task_id'],
                'success_indicators' => $row['success_indicators'] ?? '',
                'targets_measures' => $row['targets_measures'] ?? '',
                'average' => $average,
                'efficiency' => $rating_eff !== null ? $rating_eff : '-',
                'timeliness' => $rating_time !== null ? $rating_time : '-',
                'quality' => $rating_qual !== null ? $rating_qual : '-',
                'evaluator' => trim(($row['evaluator_firstname'] ?? '') . ' ' . ($row['evaluator_lastname'] ?? '')),
                'sub_category' => $row['sub_category'] ?? '',
                'has_submission' => true,
                'is_na' => false
            ];
        }
        
        if ($category == 'strategic') {
            $tasks_by_section['strategic'][] = $task_data;
        } elseif ($category == 'core') {
            if ($sub_category == 'research') {
                $tasks_by_section['core_research'][] = $task_data;
            } elseif ($sub_category == 'extension') {
                $tasks_by_section['core_extension'][] = $task_data;
            } else {
                $tasks_by_section['core_instructions'][] = $task_data;
            }
        } elseif ($category == 'support') {
            $tasks_by_section['support'][] = $task_data;
        }
    }
}
//var_dump($tasks_by_section['core_instructions']);
function calcAverage($tasks) {
    $sum = 0;
    $count = 0;
    foreach ($tasks as $task) {
        if (isset($task['is_na']) && $task['is_na']) continue;
        if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average'])) {
            $sum += (float)$task['average'];
            $count++;
        }
    }
    return [
        'sum' => $sum,
        'count' => $count,
        'ave' => $count > 0 ? number_format($sum / $count, 2) : 0
    ];
}

function calcInstructionRating($tasks, $conn, $position_id, $designation_id, $faculty_id = 0) {
    $ter_sum = 0;
    $ter_count = 0;
    $instruction_sum = 0;
    $instruction_count = 0;
    
    foreach ($tasks as $task) {
        if (isset($task['is_na']) && $task['is_na']) continue;
        if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average'])) {
            $sub = strtolower($task['sub_category'] ?? '');
            if ($sub == 'ter') {
                $ter_sum += (float)$task['average'];
                $ter_count++;
            } elseif ($sub == 'instruction' || $sub == 'instructions') {
                $instruction_sum += (float)$task['average'];
                $instruction_count++;
            }
        }
    }
    
    $ter_ave = $ter_count > 0 ? $ter_sum / $ter_count : 0;
    
    $desig_cond = $designation_id ? "= $designation_id" : "IS NULL";
    $instr_task_qry = $conn->query("
        SELECT COUNT(*) as task_count FROM task_list t
        WHERE t.category = 'core' 
        AND (t.sub_category = 'instruction' OR t.sub_category = 'instructions')
        AND t.is_active = 1
        AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
        AND t.id NOT IN (
            SELECT tp.task_id FROM task_progress tp 
            WHERE tp.faculty_id = " . intval($faculty_id) . " AND tp.progress = 'N/A'
        )
    "); 
    $total_instr_count = $instr_task_qry && $instr_task_qry->num_rows > 0 ? (int)$instr_task_qry->fetch_assoc()['task_count'] : 0;
    
    if ($position_id == 19) {
        $exempt_qry = $conn->query("
            SELECT COUNT(*) as exempt_count FROM target_exemptions te
            INNER JOIN task_list tl ON te.task_id = tl.id
            WHERE te.position_id = $position_id
            AND (tl.sub_category = 'instruction' OR tl.sub_category = 'instructions')
        ");
        $exempt_count = $exempt_qry && $exempt_qry->num_rows > 0 ? (int)$exempt_qry->fetch_assoc()['exempt_count'] : 0;
        
        // Also subtract N/A tasks from expected count
        $na_qry = $conn->query("
            SELECT COUNT(*) as na_count FROM task_progress tp
            INNER JOIN task_list tl ON tp.task_id = tl.id
            WHERE tp.faculty_id = " . intval($faculty_id) . " AND tp.progress = 'N/A'
            AND (tl.sub_category = 'instruction' OR tl.sub_category = 'instructions')
        ");
        $na_count = $na_qry && $na_qry->num_rows > 0 ? (int)$na_qry->fetch_assoc()['na_count'] : 0;
        $expected_instr_count = $total_instr_count - $exempt_count - $na_count;
        if ($expected_instr_count < 0) $expected_instr_count = 0;
    } else {
        // Subtract N/A tasks from expected count
        $na_qry = $conn->query("
            SELECT COUNT(*) as na_count FROM task_progress tp
            INNER JOIN task_list tl ON tp.task_id = tl.id
            WHERE tp.faculty_id = " . intval($faculty_id) . " AND tp.progress = 'N/A'
            AND (tl.sub_category = 'instruction' OR tl.sub_category = 'instructions')
        ");
        $na_count = $na_qry && $na_qry->num_rows > 0 ? (int)$na_qry->fetch_assoc()['na_count'] : 0;
        $expected_instr_count = $total_instr_count - $na_count;
        if ($expected_instr_count < 0) $expected_instr_count = 0;
    }
    
    $divisor = $expected_instr_count > 0 ? $expected_instr_count : ($instruction_count > 0 ? $instruction_count : 1);
    
    $instruction_div = $instruction_count > 0 ? $instruction_sum / $divisor : 0;
    
    $instruction_rating = ($ter_ave * 0.50) + ($instruction_div * 0.50);
    
    return [
        'ter_ave' => number_format($ter_ave, 2),
        'instruction_sum' => number_format($instruction_sum, 2),
        'instruction_count' => $instruction_count,
        'expected_count' => $expected_instr_count,
        'divisor' => $divisor,
        'instruction_div' => number_format($instruction_div, 2),
        'instruction_rating' => number_format($instruction_rating, 2),
        'ter_count' => $ter_count
    ];
}

$str_ave = calcAverage($tasks_by_section['strategic']);
$inst_rating = calcInstructionRating($tasks_by_section['core_instructions'], $conn, $emp_position_id, $emp_designation_id, $faculty_id);
$inst_ave = calcAverage($tasks_by_section['core_instructions']);

$res_ave = calcAverage($tasks_by_section['core_research']);
$ext_ave = calcAverage($tasks_by_section['core_extension']);

function calcResearchAverage($tasks, $conn, $position_id, $designation_id, $faculty_id = 0) {
    $sum = 0;
    $count = 0;
    foreach ($tasks as $task) {
        if (isset($task['is_na']) && $task['is_na']) continue;
        if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average'])) {
            $sum += (float)$task['average'];
            $count++;
        }
    }
    
    $desig_cond = $designation_id ? "= $designation_id" : "IS NULL";
    $research_task_qry = $conn->query("
        SELECT COUNT(*) as task_count FROM task_list t
        WHERE t.category = 'core' 
        AND t.sub_category = 'research'
        AND t.is_active = 1
        AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
        AND t.designation_id $desig_cond
        AND t.id NOT IN (
            SELECT tp.task_id FROM task_progress tp 
            WHERE tp.faculty_id = " . intval($faculty_id) . " AND tp.progress = 'N/A'
        )
    ");
    $expected_research_count = $research_task_qry && $research_task_qry->num_rows > 0 ? (int)$research_task_qry->fetch_assoc()['task_count'] : 0;
    $divisor = $expected_research_count > 0 ? $expected_research_count : ($count > 0 ? $count : 1);
    
    $research_ave = $count > 0 ? $sum / $divisor : 0;
    
    return [
        'sum' => number_format($sum, 2),
        'count' => $count,
        'expected_count' => $expected_research_count,
        'divisor' => $divisor,
        'ave' => number_format($research_ave, 2)
    ];
}

function calcExtensionAverage($tasks, $conn, $position_id, $designation_id, $faculty_id = 0) {
    $sum = 0;
    $count = 0;
    foreach ($tasks as $task) {
        if (isset($task['is_na']) && $task['is_na']) continue;
        if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average'])) {
            $sum += (float)$task['average'];
            $count++;
        }
    }
    
    $desig_cond = $designation_id ? "= $designation_id" : "IS NULL";
    $extension_task_qry = $conn->query("
        SELECT COUNT(*) as task_count FROM task_list t
        WHERE t.category = 'core' 
        AND t.sub_category = 'extension'
        AND t.is_active = 1
        AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
        AND t.designation_id $desig_cond
        AND t.id NOT IN (
            SELECT tp.task_id FROM task_progress tp 
            WHERE tp.faculty_id = " . intval($faculty_id) . " AND tp.progress = 'N/A'
        )
    ");
    $expected_extension_count = $extension_task_qry && $extension_task_qry->num_rows > 0 ? (int)$extension_task_qry->fetch_assoc()['task_count'] : 0;
    $divisor = $expected_extension_count > 0 ? $expected_extension_count : ($count > 0 ? $count : 1);
    
    $extension_ave = $count > 0 ? $sum / $divisor : 0;
    
    return [
        'sum' => number_format($sum, 2),
        'count' => $count,
        'expected_count' => $expected_extension_count,
        'divisor' => $divisor,
        'ave' => number_format($extension_ave, 2)
    ];
}

$res_rating = calcResearchAverage($tasks_by_section['core_research'], $conn, $emp_position_id, $emp_designation_id, $faculty_id);
$ext_rating = calcExtensionAverage($tasks_by_section['core_extension'], $conn, $emp_position_id, $emp_designation_id, $faculty_id);

$supp_sum = 0;
$supp_count = 0;
foreach ($tasks_by_section['support'] as $stask) {
    if (isset($stask['is_na']) && $stask['is_na']) continue;
    $supp_count++;
    if (isset($stask['has_submission']) && $stask['has_submission'] && is_numeric($stask['average'])) {
        $supp_sum += (float)$stask['average'];
    }
}
$supp_ave = [
    'sum' => $supp_sum,
    'count' => $supp_count,
    'ave' => $supp_count > 0 ? number_format($supp_sum / $supp_count, 2) : 0
];

$has_any_ratings = $str_ave['count'] > 0 || $inst_ave['count'] > 0 || $res_ave['count'] > 0 || $ext_ave['count'] > 0 || $supp_ave['count'] > 0;
$total_verified = $str_ave['count'] + $inst_ave['count'] + $res_ave['count'] + $ext_ave['count'] + $supp_ave['count'];
?>

<?php if(!$has_any_ratings): ?>
                <div class="alert alert-info">No verified ratings found. Ratings will appear here once evaluators have verified your submissions.</div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> Showing <?php echo $total_verified; ?> verified submission(s) with ratings
                </div>
            <?php endif; ?>

<!-- IPCR FORM HEADER -->
<div class="ipcr-header" style="text-align:center; border-bottom: 3px solid #1a1a2e; padding-bottom: 12px; margin-bottom: 15px;">
    <div style="font-size: 0.85rem; font-weight: bold; text-transform: uppercase; color: #1a1a2e;">Republic of the Philippines</div>
    <div style="font-size: 1.0rem; font-weight: bold; text-transform: uppercase; color: #1a1a2e; line-height: 1.3;">DR. EMILIO B. ESPINOSA SR. MEMORIAL STATE COLLEGE OF AGRICULTURE AND TECHNOLOGY</div>
    <div style="font-size: 0.8rem; color: #555;">DEBESMSCAT, Cabitan, Mandaon, Masbate</div>
    <div style="font-size: 0.7rem; color: #777; margin-top: 2px;">Office of the Vice President for Academic Affairs</div>
    <h3 style="font-size: 1.15rem; font-weight: bold; margin: 8px 0 2px 0; color: #1a1a2e;">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h3>
    <div style="font-size: 0.8rem; color: #555;">SPMS Form &middot; Rating Period: <?php echo htmlspecialchars($_SESSION['rating_period'] ?? 'N/A'); ?></div>
</div>

<!-- IPCR INFO TABLE -->
<table class="table table-bordered table-sm mb-3" style="font-size: 0.82rem;">
    <tr>
        <td style="width:12%; background:#f8f9fa; font-weight:bold;">Name:</td>
        <td style="width:38%;"><strong><?php echo htmlspecialchars($faculty_fullname); ?></strong></td>
        <td style="width:14%; background:#f8f9fa; font-weight:bold;">Department:</td>
        <td style="width:36%;"><?php echo htmlspecialchars($department_name); ?></td>
    </tr>
    <tr>
        <td style="background:#f8f9fa; font-weight:bold;">Position:</td>
        <td><?php echo htmlspecialchars($position_name); ?></td>
        <td style="background:#f8f9fa; font-weight:bold;">Designation:</td>
        <td><?php echo $isDesignated ? htmlspecialchars($designation_name) : 'Faculty'; ?></td>
    </tr>
</table>

<!-- ACTION BUTTONS -->
<div class="d-flex justify-content-end mb-3" style="gap: 8px;">
    <button class="btn btn-info btn-sm" onclick="printPreviewIPCR()"><i class="fa fa-print mr-1"></i> Print Preview</button>
    <button class="btn btn-danger btn-sm" onclick="downloadPDF()"><i class="fa fa-file-pdf mr-1"></i> Download PDF</button>
    <button class="btn btn-success btn-sm" onclick="downloadExcel()"><i class="fa fa-file-excel mr-1"></i> Download Excel</button>
</div>
<div id="ipcrContent" class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <h5 class="card-title mb-0"><b>Performance Evaluation</b></h5>
        </div>
        <div class="card-body">

            <?php
            // === Percentage variables (used in both detail table and over-all rating) ===
            $str_pct = $allocations['strategic'] ?? 0;
            $core_pct = $allocations['core_total'] ?? 0;
            $res_pct = $allocations['core_research'] ?? 0;
            $ext_pct = $allocations['core_extension'] ?? 0;
            $supp_pct = $allocations['support'] ?? 0;
            $ter_pct = $allocations['core_ter'] ?? 0;
            $instr_pct_raw = $allocations['core_instructions'] ?? $allocations['core_instruction'] ?? 0;
            // Within-core instruction percentage = TER + Instruction sub-percentages
            $inst_pct = $ter_pct + $instr_pct_raw;
            // TER and Instruction percentages — use raw DB values directly
            $ter_split = $ter_pct;
            $instr_split = $instr_pct_raw;

            // Handle designated positions (Dean/Director/Head/VP) that need strategic override
            if (!$is_cos) {
                $has_strat_alloc = isset($allocations['strategic']) && $allocations['strategic'] > 0;
                if ($has_strat_alloc) {
                    $str_pct = $allocations['strategic'];
                } elseif ($emp_designation_id > 0) {
                    $desig_qry_str = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($emp_designation_id));
                    if ($desig_qry_str && $desig_row_str = $desig_qry_str->fetch_assoc()) {
                        if (stripos($desig_row_str['designation'], 'Department Head') !== false ||
                            stripos($desig_row_str['designation'], 'Director') !== false ||
                            stripos($desig_row_str['designation'], 'Dean') !== false ||
                            stripos($desig_row_str['designation'], 'Vice President') !== false) {
                            $str_alloc_qry = $conn->query("SELECT percentage FROM percentage_allocation WHERE position_id = " . intval($emp_position_id) . " AND designation_id = " . intval($emp_designation_id) . " AND category = 'strategic' AND is_active = 1 LIMIT 1");
                            if ($str_alloc_qry && $str_alloc_row = $str_alloc_qry->fetch_assoc()) {
                                $str_pct = floatval($str_alloc_row['percentage']);
                            }
                        }
                    }
                }
            }

            $core_total_display = getAllocation($conn, $emp_position_id, $emp_designation_id, 'core', null);
            if ($core_total_display == 0) {
                $core_total_display = $core_pct;
            }
            ?>

            <table class="table table-bordered table-sm" id="list">
                <thead class="bg-dark text-white text-center">
                    <tr>
                        <th width="15%">MAJOR FINAL OUTPUT</th>
                        <th width="40%">SUCCESS INDICATORS (TARGETS + MEASURES)</th>
                        <th class="text-center" width="9%">E</th>
                        <th class="text-center" width="9%">T</th>
                        <th class="text-center" width="9%">Q</th>
                        <th class="text-center" width="9%">AVE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $show_strategic = false;
                    if (!$is_cos) {
                        $show_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0;
                        $str_display_pct = $allocations['strategic'] ?? 0;
                        if ($emp_designation_id > 0 && !$show_strategic) {
                            $desig_qry = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($emp_designation_id));
                            if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
                                if (stripos($desig_row['designation'], 'Department Head') !== false || 
                                    stripos($desig_row['designation'], 'Director') !== false ||
                                    stripos($desig_row['designation'], 'Dean') !== false ||
                                    stripos($desig_row['designation'], 'Vice President') !== false) {
                                    $show_strategic = true;
                                    $str_pct_alloc = $conn->query("SELECT percentage FROM percentage_allocation 
                                        WHERE position_id = " . intval($emp_position_id) . " 
                                        AND designation_id = " . intval($emp_designation_id) . " 
                                        AND category = 'strategic' 
                                        AND is_active = 1 LIMIT 1");
                                    if ($str_pct_alloc && $str_row = $str_pct_alloc->fetch_assoc()) {
                                        $str_display_pct = floatval($str_row['percentage']);
                                    }
                                }
                            }
                        }
                    }
                    //echo $show_strategic;
                    $show_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
                  
                    $show_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && $can_see_research_extension;
                   //echo $show_research;
                    $show_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && $can_see_research_extension;
                    $show_support = isset($allocations['support']) && $allocations['support'] > 0;
                    
                    //var_dump($allocations);
                  
                    
                    ?>
              


<?php if ($show_strategic): ?>
<tr class="bg-light">
    <td colspan="6">
        <b>STRATEGIC FUNCTIONS (<?php echo $str_pct; ?>%)</b>
    </td>
</tr>

<?php if (!empty($tasks_by_section['strategic'])): ?>
    <?php 
        $strategic_tasks = $tasks_by_section['strategic'];
        $rowspan = max(2, count($strategic_tasks));
        $first_row = true;
    ?>

    <?php foreach ($strategic_tasks as $task): ?>
        <?php 
            $ave_display = (!empty($task['has_submission'])) ? $task['average'] : (isset($task['is_na']) && $task['is_na'] ? 'N/A' : '-'); 
        ?>
        <tr>

            <?php if ($first_row): ?>
                <td rowspan="<?php echo $rowspan; ?>" class="align-middle font-weight-bold text-center">
                    KRA 2: QUALITY AND RELEVANCE OF INSTRUCTION
                </td>
                <?php $first_row = false; ?>
            <?php endif; ?>

            <td>
                <div><?php echo htmlspecialchars($task['success_indicators']); ?></div>
                <small class="text-muted">
                    <i><?php echo htmlspecialchars($task['targets_measures']); ?></i>
                </small>

                <?php if (!empty($task['evaluator'])): ?>
                    <br>
                    <small class="text-success">
                        <i class="fa fa-user-check"></i> 
                        <?php echo htmlspecialchars($task['evaluator']); ?>
                    </small>
                <?php endif; ?>
            </td>

            <td class="text-center"><?php echo $task['efficiency']; ?></td>
            <td class="text-center"><?php echo $task['timeliness']; ?></td>
            <td class="text-center"><?php echo $task['quality']; ?></td>
            <td class="text-center"><b><?php echo $ave_display; ?></b></td>
        </tr>
    <?php endforeach; ?>

  
    <tr class="table-info">
        <td class="text-end"><b>Strategic Function Average</b></td>
        <td></td><td></td>
        <td></td>
        <td></td>
        <td class="text-center"><b><?php echo $str_ave['ave']; ?></b></td>
    </tr>
   

<?php else: ?>
<tr>
    <td colspan="6" class="text-muted text-center">
        (No verified submissions)
    </td>
</tr>


<?php endif; ?>

<?php endif; ?>
 <?php if ($has_any_ratings): ?>
            <div class="row mt-4">
                <div class="col-md-8">
                    <?php 
                    $str_val = floatval($str_ave['ave']);
                    $inst_val = floatval($inst_ave['ave']);
                    $res_val = floatval($res_ave['ave']);
                    $ext_val = floatval($ext_ave['ave']);
                    $supp_val = floatval($supp_ave['ave']);
                    
                    // Percentages already set above (before the detail table)
                    // $str_pct, $core_pct, $res_pct, $ext_pct, $supp_pct, $inst_pct are all available
                    
                    // Determine if designated (has strategic allocation or is Dean/Director/Head/VP)
                    $is_designated = false;
                    $show_strategic_pct = false;
                    if (!$is_cos) {
                        $show_strategic_pct = ($str_pct > 0);
                        if ($show_strategic_pct) {
                            $is_designated = true;
                        }
                        if ($emp_designation_id > 0 && !$show_strategic_pct) {
                            $desig_qry2 = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($emp_designation_id));
                            if ($desig_qry2 && $desig_row2 = $desig_qry2->fetch_assoc()) {
                                if (stripos($desig_row2['designation'], 'Department Head') !== false || 
                                    stripos($desig_row2['designation'], 'Director') !== false ||
                                    stripos($desig_row2['designation'], 'Dean') !== false ||
                                    stripos($desig_row2['designation'], 'Vice President') !== false) {
                                    $show_strategic_pct = true;
                                    $is_designated = true;
                                    // $str_pct already set from the top block
                                }
                            }
                        }
                    }
                    
                    $show_instructions_pct = $core_pct > 0;
                    $show_research_pct = isset($allocations['core_research']) && $allocations['core_research'] > 0 && $can_see_research_extension;
                    $show_extension_pct = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && $can_see_research_extension;
                    $show_support_pct = isset($allocations['support']) && $allocations['support'] > 0;
                    
                    // Determine which categories actually have verified submissions
                    $str_active = ($str_ave['count'] > 0);
                    $inst_active = ($inst_ave['count'] > 0);
                    $res_active = ($res_ave['count'] > 0);
                    $ext_active = ($ext_ave['count'] > 0);
                    $supp_active = ($supp_ave['count'] > 0);
                    
                    // For COS, use instruction_rating instead of inst_ave
                    if ($emp_position_id == 19) {
                        $inst_val = floatval($inst_rating['instruction_rating']);
                    }
                    
                    // core_total_pct already computed as $core_total_display above
                    $core_total_pct = $core_total_display;
                    
                    // Compute core_function as a WEIGHTED average of sub-categories
                    // Sub-category percentages are WITHIN core (sum to 100% of core):
                    //   Instruction (TER+Instructions) = 70%, Research = 10%, Extension = 20%
                    $core_weighted_sum = 0;
                    $core_weighted_pct = 0;
                    
                    if ($show_instructions_pct && $inst_active) {
                        $core_weighted_sum += $inst_val * $inst_pct;
                        $core_weighted_pct += $inst_pct;
                    }
                    if ($show_research_pct && $res_active) {
                        $core_weighted_sum += $res_val * $res_pct;
                        $core_weighted_pct += $res_pct;
                    }
                    if ($show_extension_pct && $ext_active) {
                        $core_weighted_sum += $ext_val * $ext_pct;
                        $core_weighted_pct += $ext_pct;
                    }
                    
                    // core_function = weighted average of active sub-categories (within core, out of 100)
                    $core_function = $core_weighted_pct > 0 ? $core_weighted_sum / $core_weighted_pct : 0;
                    
                    // The effective core percentage = the full core_total_pct (e.g. 60% if designated, 90% if not)
                    $core_effective_pct = $core_total_pct;
                    $core_weighted = $core_function * ($core_effective_pct / 100);
                    
                    // Compute total: Strategic + Core + Support (percentages sum to 100)
                    // Only include categories that have verified submissions; redistribute if any missing
                    $str_pct_calc = ($show_strategic_pct && $str_active) ? $str_pct : 0;
                    $core_pct_calc = ($core_weighted_pct > 0) ? $core_effective_pct : 0;
                    $supp_pct_calc = ($show_support_pct && $supp_active) ? $supp_pct : 0;
                    
                    $total_active_pct = $str_pct_calc + $core_pct_calc + $supp_pct_calc;
                    
                    if ($total_active_pct > 0) {
                        // Redistribute: scale to 100% so faculty isn't penalized for missing categories
                        $total = (($str_val * $str_pct_calc) + ($core_function * $core_pct_calc) + ($supp_val * $supp_pct_calc)) / $total_active_pct;
                    } else {
                        $total = 0;
                    }
                    ?>

                    <?php if ($show_instructions || $show_research || $show_extension): ?>
                    <tr class="bg-light">
                        <td colspan="6"><b>CORE FUNCTIONS (<?php echo $core_total_display; ?>%)</b></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($show_instructions): ?>
                    <tr>
                        <td rowspan="<?php echo max(3, count($tasks_by_section['core_instructions']) + 6); ?>" class="align-middle font-weight-bold text-center">
                            MFO 1. Higher Education<br>MFO 2. Advanced Education
                        </td>
                        <td colspan="5"><b>A. INSTRUCTION (<?php echo $inst_pct; ?>%)</b></td>
                    </tr>
                    
                    <tr class="table-light">
                        <td colspan="5"><b>A.1 Teaching Effectiveness (<?php echo $ter_split; ?>%) - TER</b></td>
                    </tr>
                        <?php 
                        $ter_tasks = [];
                        $instr_tasks = [];
                        foreach($tasks_by_section['core_instructions'] as $task) {
                            $sub = strtolower($task['sub_category'] ?? '');
                            if ($sub == 'ter') {
                                $ter_tasks[] = $task;
                            } else {
                                $instr_tasks[] = $task;
                            }
                        }
                        ?>
                        <?php if (!empty($ter_tasks)): ?>
                        <?php foreach($ter_tasks as $task): ?>
                        <?php $ave_display = (isset($task['has_submission']) && $task['has_submission']) ? $task['average'] : (isset($task['is_na']) && $task['is_na'] ? 'N/A' : '-'); ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($task['success_indicators']); ?></div>
                                <small class="text-muted"><i><?php echo htmlspecialchars($task['targets_measures']); ?></i></small>
                                <?php if(!empty($task['evaluator'])): ?>
                                <br><small class="text-success"><i class="fa fa-user-check"></i> <?php echo htmlspecialchars($task['evaluator']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $task['efficiency']; ?></td>
                            <td class="text-center"><?php echo $task['timeliness']; ?></td>
                            <td class="text-center"><?php echo $task['quality']; ?></td>
                            <td class="text-center"><b><?php echo $ave_display; ?></b></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted"><em>(No verified submissions)</em></td></tr>
                        <?php endif; ?>
                        <tr class="table-info">
                            <td class="text-end"><b>TER</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center"><b><?php echo $inst_rating['ter_ave']; ?></b></td>
                        </tr>
                    
                    <tr class="table-light">
                        <td colspan="5"><b>A.2 Instructions (<?php echo $instr_split; ?>%)</b></td>
                    </tr>
                        <?php if (!empty($instr_tasks)): ?>
                        <?php foreach($instr_tasks as $task): ?>
                        <?php $ave_display = (isset($task['has_submission']) && $task['has_submission']) ? $task['average'] : (isset($task['is_na']) && $task['is_na'] ? 'N/A' : '-'); ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($task['success_indicators']); ?></div>
                                <small class="text-muted"><i><?php echo htmlspecialchars($task['targets_measures']); ?></i></small>
                                <?php if(!empty($task['evaluator'])): ?>
                                <br><small class="text-success"><i class="fa fa-user-check"></i> <?php echo htmlspecialchars($task['evaluator']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $task['efficiency']; ?></td>
                            <td class="text-center"><?php echo $task['timeliness']; ?></td>
                            <td class="text-center"><?php echo $task['quality']; ?></td>
                            <td class="text-center"><b><?php echo $ave_display; ?></b></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted"><em>(No verified submissions)</em></td></tr>
                        <?php endif; ?>
                        <tr class="table-info">
                            <td class="text-end"><b>Instructions (Sum ÷ <?php echo $inst_rating['divisor']; ?>)</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center"><b><?php echo $inst_rating['instruction_div']; ?></b></td>
                        </tr>
                        
                        <?php if ($inst_ave['count'] > 0): ?>
                        <tr class="table-info">
                            <td class="text-end"><b>Instruction(Average)</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center"><b><?php echo $inst_rating['instruction_rating']; ?></b></td>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($show_research): ?>
                    <tr>
                        <td rowspan="<?php echo max(2, count($tasks_by_section['core_research']) + 2); ?>" class="align-middle font-weight-bold text-center">
                            MFO 3. Research and Development
                        </td>
                        <td colspan="5"><b>B. RESEARCH (<?php echo $res_pct; ?>%)</b></td>
                    </tr>
                        <?php if (!empty($tasks_by_section['core_research'])): ?>
                        <?php foreach($tasks_by_section['core_research'] as $task): ?>
                        <?php $ave_display = (isset($task['has_submission']) && $task['has_submission']) ? $task['average'] : (isset($task['is_na']) && $task['is_na'] ? 'N/A' : '-'); ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($task['success_indicators']); ?></div>
                                <small class="text-muted"><i><?php echo htmlspecialchars($task['targets_measures']); ?></i></small>
                                <?php if(!empty($task['evaluator'])): ?>
                                <br><small class="text-success"><i class="fa fa-user-check"></i> <?php echo htmlspecialchars($task['evaluator']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $task['efficiency']; ?></td>
                            <td class="text-center"><?php echo $task['timeliness']; ?></td>
                            <td class="text-center"><?php echo $task['quality']; ?></td>
                            <td class="text-center"><b><?php echo $ave_display; ?></b></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted"><em>(No verified submissions)</em></td></tr>
                        <?php endif; ?>
                       
                        <tr class="table-info">
                            <td colspan="4" class="text-end"><b>Research (Average) </b></td>
                            <td class="text-center"><b><?php echo $res_rating['ave']; ?></b></td>
                        
                        
                        </tr>

                    <?php endif; ?>

                    <?php if ($show_extension): ?>
                    <tr>
                        <td rowspan="<?php echo max(2, count($tasks_by_section['core_extension']) + 2); ?>" class="align-middle font-weight-bold text-center">
                            MFO 4. Extension Services
                        </td>
                        <td colspan="5"><b>C. EXTENSION (<?php echo $ext_pct; ?>%)</b></td>
                    </tr>
                        <?php if (!empty($tasks_by_section['core_extension'])): ?>
                        <?php foreach($tasks_by_section['core_extension'] as $task): ?>
                        <?php $ave_display = (isset($task['has_submission']) && $task['has_submission']) ? $task['average'] : (isset($task['is_na']) && $task['is_na'] ? 'N/A' : '-'); ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($task['success_indicators']); ?></div>
                                <small class="text-muted"><i><?php echo htmlspecialchars($task['targets_measures']); ?></i></small>
                                <?php if(!empty($task['evaluator'])): ?>
                                <br><small class="text-success"><i class="fa fa-user-check"></i> <?php echo htmlspecialchars($task['evaluator']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $task['efficiency']; ?></td>
                            <td class="text-center"><?php echo $task['timeliness']; ?></td>
                            <td class="text-center"><?php echo $task['quality']; ?></td>
                            <td class="text-center"><b><?php echo $ave_display; ?></b></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted"><em>(No verified submissions)</em></td></tr>
                        <?php endif; ?>
                       
                        <tr class="table-info">
                            <td colspan="4" class="text-end"><b>Extension Services(Average)</b></td>
                            <td class="text-center"><b><?php echo $ext_rating['ave']; ?></b></td>
                        </tr>
                       
                    <?php endif; ?>
 <tr class="table-info">
                            <td colspan="5" class="text-end"><b>Core Function Average</b></td>
                            <td class="text-center"><b><?php echo $core_function; ?></b></td>
                        </tr>
                    <?php if ($show_support): ?>
                    <tr class="bg-light">
                        <td colspan="6"><b>SUPPORT FUNCTIONS (<?php echo $supp_pct; ?>%)</b></td>
                    </tr>
                        <?php if (!empty($tasks_by_section['support'])): ?>
                        <?php foreach($tasks_by_section['support'] as $task): ?>
                        <?php $ave_display = (isset($task['has_submission']) && $task['has_submission']) ? $task['average'] : (isset($task['is_na']) && $task['is_na'] ? 'N/A' : '-'); ?>
                        <tr>
                            <td></td>
                            <td>
                                <div><?php echo htmlspecialchars($task['success_indicators']); ?></div>
                                <small class="text-muted"><i><?php echo htmlspecialchars($task['targets_measures']); ?></i></small>
                                <?php if(!empty($task['evaluator'])): ?>
                                <br><small class="text-success"><i class="fa fa-user-check"></i> <?php echo htmlspecialchars($task['evaluator']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $task['efficiency']; ?></td>
                            <td class="text-center"><?php echo $task['timeliness']; ?></td>
                            <td class="text-center"><?php echo $task['quality']; ?></td>
                            <td class="text-center"><b><?php echo $ave_display; ?></b></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        
                        <tr><td colspan="6" class="text-muted text-center">(No verified submissions)</td></tr>
                        <?php endif; ?>
                        <?php if ($supp_ave['count'] > 0): ?>
                        <tr class="table-info">
                            <td colspan="5" class="text-end"><b>Support Function Average</b></td>
                            <td class="text-center"><b><?php echo $supp_ave['ave']; ?></b></td>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="small text-muted mt-2"><b>E</b> = Efficiency | <b>T</b> = Timeliness | <b>Q</b> = Quality</p>

           
                    <table class="table table-bordered text-center align-middle">
                        <thead>
                            <tr><th colspan="5" class="bg-dark text-white">OVER-ALL RATING</th></tr>
                            <tr>
                                <th>Component</th>
                                <th>% Weight</th>
                                <th>Average of Actual Rating</th>
                                <th>Portion of Rating</th>
                                <th>Adjectival</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php 
                                
                        // var_dump($show_strategic_pct);
                                ?>
                            <?php if ($show_strategic_pct && $str_active): ?>
                            <tr>
                                <td class="text-left"><b>Strategic Functions</b></td>
                                <td><?php echo $str_pct_calc; ?>%</td>
                                <td><?php echo $str_ave['ave']; ?></td>
                                <td><?php echo number_format($str_val * ($str_pct_calc / 100), 2); ?></td>
                                <td><?php echo getAdjectivalRating($str_val); ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($core_weighted_pct > 0): ?>
                            <?php 
                                $core_avg_display = min(5.00, $core_function);
                            ?>
                            <tr>
                                <td class="text-left"><b>Core Functions</b></td>
                                <td><?php echo $core_pct_calc; ?>%</td>
                                <td><?php echo number_format($core_avg_display, 2); ?></td>
                                <td><?php echo number_format($core_weighted, 2); ?></td>
                                <td><?php echo getAdjectivalRating($core_avg_display); ?></td>
                            </tr>
                            <?php if ($show_instructions_pct && $inst_active): ?>
                            <tr style="font-size: 0.85rem; background-color: #f8f8f8;">
                                <td class="text-left">&nbsp;&nbsp;&nbsp;&nbsp;Instruction</td>
                                <td><?php echo $inst_pct; ?>%</td>
                                <td><?php echo $emp_position_id == 19 ? $inst_rating['instruction_rating'] : $inst_ave['ave']; ?></td>
                                <td></td>
                                <td><?php echo getAdjectivalRating(floatval($emp_position_id == 19 ? $inst_rating['instruction_rating'] : $inst_ave['ave'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($show_research_pct && $res_active): ?>
                            <tr style="font-size: 0.85rem; background-color: #f8f8f8;">
                                <td class="text-left">&nbsp;&nbsp;&nbsp;&nbsp;Research</td>
                                <td><?php echo $res_pct; ?>%</td>
                                <td><?php echo $res_rating['ave']; ?></td>
                                <td></td>
                                <td><?php echo getAdjectivalRating(floatval($res_rating['ave'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($show_extension_pct && $ext_active): ?>
                            <tr style="font-size: 0.85rem; background-color: #f8f8f8;">
                                <td class="text-left">&nbsp;&nbsp;&nbsp;&nbsp;Extension</td>
                                <td><?php echo $ext_pct; ?>%</td>
                                <td><?php echo $ext_rating['ave']; ?></td>
                                <td></td>
                                <td><?php echo getAdjectivalRating(floatval($ext_rating['ave'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php endif; ?>
                           
                            <?php if ($supp_pct_calc > 0 && $supp_active): ?>
                            <tr>
                                <td class="text-left"><b>Support Functions</b></td>
                                <td><?php echo $supp_pct_calc; ?>%</td>
                                <td><?php echo $supp_ave['ave']; ?></td>
                                <td><?php echo number_format($supp_val * ($supp_pct_calc / 100), 2); ?></td>
                                <td><?php echo getAdjectivalRating($supp_val); ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr style="font-weight:bold;">
                                <td class="text-right">TOTAL</td>
                                <td><?php echo $total_active_pct > 0 ? number_format($total_active_pct, 0) : 0; ?>%</td>
                                <td></td>
                                <td><?php echo number_format($total, 2); ?></td>
                                <td><?php echo getAdjectivalRating($total); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-4">
                    <table class="table table-bordered text-center" style="font-size: 0.75rem;">
                        <thead>
                            <tr>
                                <th colspan="3" class="bg-dark text-white">OVER-ALL RATING</th>
                            </tr>
                            <tr>
                                <th>Range</th>
                                <th>Adjectival</th>
                                <th>Final Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>4.75 - 5.00</td><td><b>OUTSTANDING</b></td><td rowspan="5" style="vertical-align: middle; font-weight: bold; font-size: 1.1rem;"><?php echo number_format($total, 2); ?><br><small><?php echo getAdjectivalRating($total); ?></small></td></tr>
                            <tr><td>3.61 - 4.74</td><td><b>VERY SATISFACTORY</b></td></tr>
                            <tr><td>2.61 - 3.60</td><td><b>SATISFACTORY</b></td></tr>
                            <tr><td>1.61 - 2.60</td><td><b>UNSATISFACTORY</b></td></tr>
                            <tr><td>Below 1.61</td><td><b>POOR</b></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php //endif; ?>

            <?php
            $stmt_comment = $conn->prepare("SELECT comment_text FROM comments WHERE employee_id = ? ORDER BY id DESC LIMIT 1");
$stmt_comment->bind_param("i", $faculty_id);
$stmt_comment->execute();
$comment_qry = $stmt_comment->get_result();
            $comment = ($comment_qry && $comment_qry->num_rows > 0) ? htmlspecialchars($comment_qry->fetch_assoc()['comment_text']) : "<i>No comment yet.</i>";
            ?>

            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <tr>
                    <td style="border:1px solid #000; height:120px; vertical-align:top; padding:10px;">
                        <b>Comments and Recommendations:</b>
                        <br><br>
                        <?php echo $comment; ?>
                    </td>
                </tr>
            </table>

            <!-- SIGNATURE SECTION -->
            <table style="width:100%; border-collapse:collapse; margin-top:30px;">
                <tr>
                    <td style="width:33%; text-align:center; vertical-align:top; border:none; padding:10px;">
                        <div style="font-size:0.8rem; text-align:left; margin-bottom:35px;">Conforme:</div>
                        <div style="border-top:1px solid #000; width:200px; margin:0 auto 4px;"></div>
                        <strong><?php echo strtoupper(trim(($emp_data['firstname'] ?? '') . ' ' . substr($emp_data['middlename'] ?? '', 0, 1) . ' ' . ($emp_data['lastname'] ?? ''))); ?></strong><br>
                        <small><?php echo htmlspecialchars($position_name); ?></small><br>
                        <small>Date: _______________</small>
                    </td>
                    <td style="width:33%; text-align:center; vertical-align:top; border:none; padding:10px;">
                        <div style="font-size:0.8rem; text-align:left; margin-bottom:35px;">Reviewed by:</div>
                        <div style="border-top:1px solid #000; width:200px; margin:0 auto 4px;"></div>
                        <strong>ROWELYN M. RAMISO, MSIT, MPA</strong><br>
                        <small>Dean</small><br>
                        <small>Date: _______________</small>
                    </td>
                    <td style="width:33%; text-align:center; vertical-align:top; border:none; padding:10px;">
                        <div style="font-size:0.8rem; text-align:left; margin-bottom:35px;">Approved by:</div>
                        <div style="border-top:1px solid #000; width:200px; margin:0 auto 4px;"></div>
                        <strong>ELREEN A. DELAVIN, Ed.D.E.L</strong><br>
                        <small>Vice President for Academic Affairs</small><br>
                        <small>Date: _______________</small>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
function buildPrintHTML() {
    var content = document.getElementById('ipcrContent').innerHTML;
    var header = document.querySelector('.ipcr-header').outerHTML;
    var infoTable = document.querySelectorAll('table.table-bordered.table-sm')[0] ? document.querySelectorAll('table.table-bordered.table-sm')[0].outerHTML : '';
    
    return '<html><head><title>IPCR Form - <?php echo addslashes($faculty_fullname); ?></title>' +
        '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">' +
        '<style>' +
        '@page { size: A4 landscape; margin: 1cm; }' +
        'body { padding: 10px; font-family: Arial, sans-serif; font-size: 10px; color: #000; }' +
        '.ipcr-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }' +
        '.ipcr-header div { font-size: 11px; font-weight: bold; text-transform: uppercase; }' +
        '.ipcr-header h3 { font-size: 14px; font-weight: bold; margin: 6px 0 2px; }' +
        'table { width: 100%; border-collapse: collapse; }' +
        'th, td { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; font-size: 9.5px; }' +
        'th { background-color: #e8e8e8; font-weight: bold; text-align: center; }' +
        '.bg-dark { background-color: #343a40 !important; color: white !important; }' +
        '.bg-light { background-color: #f8f9fa !important; }' +
        '.table-info { background-color: #d1ecf1 !important; }' +
        '.badge { font-size: 8px; padding: 2px 5px; border-radius: 3px; }' +
        '.btn { display: none !important; }' +
        '.no-print { display: none !important; }' +
        '</style></head><body>' +
        header + infoTable + content +
        '</body></html>';
}

function printPreviewIPCR() {
    var html = buildPrintHTML();
    var printWindow = window.open('', '', 'height=900,width=1200');
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = function() {
        printWindow.print();
    };
}

function downloadPDF() {
    var html = buildPrintHTML();
    var printWindow = window.open('', '', 'height=900,width=1200');
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = function() {
        printWindow.print();
    };
}

function downloadExcel() {
    var tableHTML = '<table border="1">';
    // Header rows
    tableHTML += '<tr><td colspan="6" style="font-weight:bold; text-align:center; font-size:14px;">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</td></tr>';
    tableHTML += '<tr><td colspan="6" style="text-align:center;">DR. EMILIO B. ESPINOSA SR. MEMORIAL STATE COLLEGE OF AGRICULTURE AND TECHNOLOGY</td></tr>';
    tableHTML += '<tr><td colspan="6" style="text-align:center;">Rating Period: <?php echo addslashes($_SESSION["rating_period"] ?? "N/A"); ?></td></tr>';
    tableHTML += '<tr><td colspan="6"></td></tr>';
    tableHTML += '<tr><td><b>Name:</b></td><td><?php echo addslashes($faculty_fullname); ?></td><td><b>Department:</b></td><td><?php echo addslashes($department_name); ?></td><td><b>Position:</b></td><td><?php echo addslashes($position_name); ?></td></tr>';
    tableHTML += '<tr><td colspan="6"></td></tr>';
    // Main rating table
    var mainTable = document.getElementById('list');
    if (mainTable) {
        for (var i = 0; i < mainTable.rows.length; i++) {
            var row = mainTable.rows[i];
            tableHTML += '<tr>';
            for (var j = 0; j < row.cells.length; j++) {
                var cellText = row.cells[j].textContent.trim().replace(/\s+/g, ' ');
                var isHeader = row.parentNode.tagName === 'THEAD';
                tableHTML += '<td' + (isHeader ? ' style="font-weight:bold; background:#e8e8e8;"' : '') + '>' + cellText + '</td>';
            }
            tableHTML += '</tr>';
        }
    }
    // Over-all rating table
    var ratingTables = document.querySelectorAll('.table.table-bordered.text-center');
    ratingTables.forEach(function(t) {
        for (var i = 0; i < t.rows.length; i++) {
            var row = t.rows[i];
            tableHTML += '<tr>';
            for (var j = 0; j < row.cells.length; j++) {
                var cellText = row.cells[j].textContent.trim().replace(/\s+/g, ' ');
                var isHeader = row.parentNode.tagName === 'THEAD';
                tableHTML += '<td' + (isHeader ? ' style="font-weight:bold; background:#e8e8e8;"' : '') + '>' + cellText + '</td>';
            }
            tableHTML += '</tr>';
        }
    });
    tableHTML += '</table>';
    
    var blob = new Blob(['\ufeff' + tableHTML], { type: 'application/vnd.ms-excel' });
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'IPCR_<?php echo preg_replace("/[^a-zA-Z0-9_]/", "", $faculty_fullname); ?>_<?php echo ($_SESSION["rating_period"] ?? "unknown"); ?>.xls';
    link.click();
}
</script>
<?php endif; ?>
