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

$faculty_id = intval($_SESSION['login_id'] ?? 0);

$emp_qry = $conn->query("SELECT e.*, p.position as position_name, d.designation as designation_name 
    FROM employee_list e 
    LEFT JOIN position_list p ON e.position_id = p.id 
    LEFT JOIN designation_list d ON e.designation_id = d.id 
    WHERE e.id = $faculty_id LIMIT 1");
$emp_data = $emp_qry->fetch_assoc();
$emp_position_id = intval($emp_data['position_id'] ?? 0);
$emp_designation_id = $emp_data['designation_id'] ?? null;
$position_name = $emp_data['position_name'] ?? 'Unknown';
$is_instructor = ($emp_position_id >= 1 && $emp_position_id <= 18);
$is_cos = ($emp_position_id == 19);
$has_designation = ($emp_designation_id && $emp_designation_id > 0);

// Get allocations (same as rating.php)
$allocations = [];
$desig_id = intval($emp_designation_id ?? 0);
$can_see_research_extension = ($emp_position_id >= 1 && $emp_position_id <= 18);

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
while ($row = $alloc_qry->fetch_assoc()) {
    $key = $row['category'];
    if ($row['sub_category']) {
        $key .= '_' . $row['sub_category'];
    }
    $allocations[$key] = floatval($row['percentage']);
}

// Define category flags (same as rating.php)
$has_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0;
$has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
$has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && $can_see_research_extension;
$has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && $can_see_research_extension;
$has_support = isset($allocations['support']) && $allocations['support'] > 0;

// Special handling for Department Head/Director - same as rating.php line 452-468
$show_strategic = $has_strategic;
$str_display_pct = $allocations['strategic'] ?? 0;

// Check if user is Department Head or Director (any designation_id with "Head" or "Director" in name)
$is_dept_head_or_director = false;
if ($emp_designation_id && $emp_designation_id > 0) {
    $desig_qry = $conn->query("SELECT designation FROM designation_list WHERE id = $emp_designation_id");
    if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
        if (stripos($desig_row['designation'], 'Department Head') !== false || 
            stripos($desig_row['designation'], 'Director') !== false) {
            $is_dept_head_or_director = true;
        }
    }
}

// If Department Head/Director and no strategic allocation found, fetch it specifically
if ($is_dept_head_or_director && !$show_strategic) {
    $str_pct_alloc = $conn->query("SELECT percentage FROM percentage_allocation 
        WHERE position_id = $emp_position_id 
        AND designation_id = $emp_designation_id 
        AND category = 'strategic' 
        AND is_active = 1 LIMIT 1");
    if ($str_pct_alloc && $str_row = $str_pct_alloc->fetch_assoc()) {
        $str_display_pct = floatval($str_row['percentage']);
        $allocations['strategic'] = $str_display_pct;
        $has_strategic = true;
        $show_strategic = true;
    }
}
$alloc_qry = $conn->query("
    SELECT * FROM percentage_allocation 
    WHERE position_id = $emp_position_id
    AND $desig_condition
    AND is_active = 1
    ORDER BY position_id ASC
");
while ($row = $alloc_qry->fetch_assoc()) {
    $key = $row['category'];
    if ($row['sub_category']) {
        $key .= '_' . $row['sub_category'];
    }
    $allocations[$key] = floatval($row['percentage']);
}

$rating_periods = [];
$rp_qry = $conn->query("SELECT * FROM rating_period ORDER BY year DESC, semester DESC");
while ($row = $rp_qry->fetch_assoc()) {
    $rating_periods[] = $row;
}

$faculty_id = intval($_SESSION['login_id'] ?? 0);

$selected_period = $_GET['period'] ?? '';
$where_clause = "WHERE r.employee_id = $faculty_id";
if (!empty($selected_period)) {
    $selected_period = $conn->real_escape_string($selected_period);
    $where_clause .= " AND r.rating_period = '$selected_period'";
}

$ratings_qry = $conn->query("
    SELECT r.*, COALESCE(t.major_output, t.success_indicators) as task_name,
           t.efficiency as task_efficiency, t.timeliness as task_timeliness, t.quality as task_quality,
           ev.firstname as evaluator_firstname, ev.lastname as evaluator_lastname,
           rp.semester, rp.year, rp.code as period_code
    FROM ratings r
    LEFT JOIN task_list t ON r.task_id = t.id
    LEFT JOIN evaluator_list ev ON r.evaluator_id = ev.id
    LEFT JOIN rating_period rp ON r.rating_period = rp.code
    $where_clause
    ORDER BY r.date_created DESC, r.rating_period DESC
");

$movs_qry = $conn->query("
    SELECT tp.*, COALESCE(t.major_output, t.success_indicators) as task_name,
           rp.semester, rp.year, rp.code as period_code
    FROM task_progress tp
    LEFT JOIN task_list t ON tp.task_id = t.id
    LEFT JOIN rating_period rp ON tp.rating_period = rp.code
    WHERE tp.faculty_id = $faculty_id
    " . (!empty($selected_period) ? " AND tp.rating_period = '$selected_period'" : "") . "
    ORDER BY tp.date_created DESC, tp.rating_period DESC
");
?>

<style>
    .archive-section {
        margin-bottom: 30px;
    }
    .archive-card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .archive-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px 8px 0 0;
        font-weight: 600;
    }
    .period-filter {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .rating-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-outstanding { background: #28a745; color: white; }
    .badge-very-satisfactory { background: #20c997; color: white; }
    .badge-satisfactory { background: #17a2b8; color: white; }
    .badge-unsatisfactory { background: #ffc107; color: black; }
    .badge-poor { background: #dc3545; color: white; }
    .badge-no-rating { background: #6c757d; color: white; }
    .mov-file-link {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
    }
    .mov-file-link:hover {
        text-decoration: underline;
    }
    .archive-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #667eea;
    }
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
        margin-top: 5px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h4 class="card-title"><b>Archives - My Performance History</b></h4>
                </div>
                <div class="card-body">
                    <div class="period-filter">
                        <form method="GET" class="form-inline">
                            <input type="hidden" name="page" value="archives">
                            <label for="period" class="mr-2">Filter by Rating Period:</label>
                            <select name="period" id="period" class="form-control mr-2" onchange="this.form.submit()">
                                <option value="">All Periods</option>
                                <?php foreach($rating_periods as $rp): ?>
                                <option value="<?php echo $rp['code'] ?>" <?php echo $selected_period === $rp['code'] ? 'selected' : '' ?>>
                                    <?php echo $rp['semester'] . ' ' . $rp['year'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(!empty($selected_period)): ?>
                            <a href="?page=archives" class="btn btn-secondary btn-sm">Clear Filter</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php
                    $total_ratings = $ratings_qry->num_rows;
                    $total_movs = $movs_qry->num_rows;
                    
                    // EXACT SAME LOGIC AS rating.php - query task_list first, then match with ratings
                    $cat_filters = [];
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
                    } elseif ($emp_designation_id && $emp_designation_id > 0) {
                        $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $emp_designation_id)";
                    } else {
                        $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
                    }
                    
                    if (!empty($cat_filters)) {
                        $where .= " AND (" . implode(" OR ", $cat_filters) . ")";
                    }
                    
                    // Query task_list first (same as rating.php line 127-150)
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
                            
                            $has_submission = !empty($row['progress']) && $row['progress'] == 'Verified';
                            
                            if (!$has_submission) {
                                $task_data = [
                                    'task_id' => $row['task_id'],
                                    'average' => '0',
                                    'has_submission' => false,
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
                                    'average' => $average,
                                    'has_submission' => true,
                                    'sub_category' => $row['sub_category'] ?? ''
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
                    
                    // Use EXACT SAME calculation functions as rating.php
                    function calcAverage($tasks) {
                        $sum = 0; $count = 0;
                        foreach ($tasks as $task) {
                            if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average']) && $task['average'] > 0) {
                                $sum += (float)$task['average'];
                                $count++;
                            }
                        }
                        return [
                            'sum' => $sum,
                            'count' => $count,
                            'ave' => $count > 0 ? $sum / $count : 0
                        ];
                    }
                    
                    function calcInstructionRating($tasks, $conn, $position_id, $designation_id) {
                        $ter_sum = 0; $ter_count = 0;
                        $instruction_sum = 0; $instruction_count = 0;
                        
                        foreach ($tasks as $task) {
                            if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average']) && $task['average'] > 0) {
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
                        
                        $instr_task_qry = $conn->query("
                            SELECT COUNT(*) as task_count FROM task_list 
                            WHERE category = 'core' 
                            AND (sub_category = 'instruction' OR sub_category = 'instructions')
                            AND is_active = 1
                            AND (academic_rank_id IS NULL OR academic_rank_id = 0 OR academic_rank_id = $position_id)
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
                            $expected_instr_count = $total_instr_count - $exempt_count;
                        } else {
                            $expected_instr_count = $total_instr_count;
                        }
                        
                        $divisor = $expected_instr_count > 0 ? $expected_instr_count : ($instruction_count > 0 ? $instruction_count : 1);
                        $instruction_div = $instruction_count > 0 ? $instruction_sum / $divisor : 0;
                        $instruction_rating = ($ter_ave * 0.50) + ($instruction_div * 0.50);
                        
                        return [
                            'ter_ave' => $ter_ave,
                            'instruction_sum' => $instruction_sum,
                            'instruction_count' => $instruction_count,
                            'expected_count' => $expected_instr_count,
                            'divisor' => $divisor,
                            'instruction_div' => $instruction_div,
                            'instruction_rating' => $instruction_rating,
                            'ter_count' => $ter_count
                        ];
                    }
                    
                    function calcResearchAverage($tasks, $conn, $position_id, $designation_id) {
                        $sum = 0; $count = 0;
                        foreach ($tasks as $task) {
                            if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average']) && $task['average'] > 0) {
                                $sum += (float)$task['average'];
                                $count++;
                            }
                        }
                        
                        $research_task_qry = $conn->query("
                            SELECT COUNT(*) as task_count FROM task_list 
                            WHERE category = 'core' AND sub_category = 'research'
                            AND is_active = 1
                            AND (academic_rank_id IS NULL OR academic_rank_id = 0 OR academic_rank_id = $position_id)
                        ");
                        $total_research_count = $research_task_qry && $research_task_qry->num_rows > 0 ? (int)$research_task_qry->fetch_assoc()['task_count'] : 0;
                        
                        if ($position_id == 19) {
                            $exempt_qry = $conn->query("
                                SELECT COUNT(*) as exempt_count FROM target_exemptions te
                                INNER JOIN task_list tl ON te.task_id = tl.id
                                WHERE te.position_id = $position_id
                                AND tl.sub_category = 'research'
                            ");
                            $exempt_count = $exempt_qry && $exempt_qry->num_rows > 0 ? (int)$exempt_qry->fetch_assoc()['exempt_count'] : 0;
                            $expected_research_count = $total_research_count - $exempt_count;
                        } else {
                            $expected_research_count = $total_research_count;
                        }
                        
                        $divisor = $expected_research_count > 0 ? $expected_research_count : ($count > 0 ? $count : 1);
                        $research_ave = $count > 0 ? $sum / $divisor : 0;
                        
                        return ['ave' => $research_ave, 'count' => $count, 'expected_count' => $expected_research_count];
                    }
                    
                    function calcExtensionAverage($tasks, $conn, $position_id, $designation_id) {
                        $sum = 0; $count = 0;
                        foreach ($tasks as $task) {
                            if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average']) && $task['average'] > 0) {
                                $sum += (float)$task['average'];
                                $count++;
                            }
                        }
                        
                        $ext_task_qry = $conn->query("
                            SELECT COUNT(*) as task_count FROM task_list 
                            WHERE category = 'core' AND sub_category = 'extension'
                            AND is_active = 1
                            AND (academic_rank_id IS NULL OR academic_rank_id = 0 OR academic_rank_id = $position_id)
                        ");
                        $total_ext_count = $ext_task_qry && $ext_task_qry->num_rows > 0 ? (int)$ext_task_qry->fetch_assoc()['task_count'] : 0;
                        
                        if ($position_id == 19) {
                            $exempt_qry = $conn->query("
                                SELECT COUNT(*) as exempt_count FROM target_exemptions te
                                INNER JOIN task_list tl ON te.task_id = tl.id
                                WHERE te.position_id = $position_id
                                AND tl.sub_category = 'extension'
                            ");
                            $exempt_count = $exempt_qry && $exempt_qry->num_rows > 0 ? (int)$exempt_qry->fetch_assoc()['exempt_count'] : 0;
                            $expected_ext_count = $total_ext_count - $exempt_count;
                        } else {
                            $expected_ext_count = $total_ext_count;
                        }
                        
                        $divisor = $expected_ext_count > 0 ? $expected_ext_count : ($count > 0 ? $count : 1);
                        $ext_ave = $count > 0 ? $sum / $divisor : 0;
                        
                        return ['ave' => $ext_ave, 'count' => $count, 'expected_count' => $expected_ext_count];
                    }
                    
                    // Calculate averages using the EXACT SAME functions as rating.php
                    $str_ave = calcAverage($tasks_by_section['strategic']);
                    $inst_rating = calcInstructionRating($tasks_by_section['core_instructions'], $conn, $emp_position_id, $emp_designation_id);
                    $inst_ave = calcAverage($tasks_by_section['core_instructions']);
                    $res_ave = calcResearchAverage($tasks_by_section['core_research'], $conn, $emp_position_id, $emp_designation_id);
                    $ext_ave = calcExtensionAverage($tasks_by_section['core_extension'], $conn, $emp_position_id, $emp_designation_id);
                    $supp_ave = calcAverage($tasks_by_section['support']);
                    
                    // EXACT SAME CALCULATION AS rating.php (lines 558-658)
                    $str_val = floatval($str_ave['ave']);
                    $inst_val = floatval($inst_ave['ave']);
                    $res_val = floatval($res_ave['ave']);
                    $ext_val = floatval($ext_ave['ave']);
                    $supp_val = floatval($supp_ave['ave']);
                    
                    $str_pct = $allocations['strategic'] ?? 0;
                    $inst_pct = $allocations['core_instructions'] ?? 0;
                    $core_pct  = $allocations['core_total'] ?? 0;
                    $res_pct = $allocations['core_research'] ?? 0;
                    $ext_pct = $allocations['core_extension'] ?? 0;
                    $supp_pct = $allocations['support'] ?? 0;
                    
                    $show_strategic_pct = false;
                    if (!$is_cos) {
                        $show_strategic_pct = isset($allocations['strategic']) && $allocations['strategic'] > 0;
                        if ($emp_designation_id > 0 && !$show_strategic_pct) {
                            $desig_qry2 = $conn->query("SELECT designation FROM designation_list WHERE id = $emp_designation_id");
                            if ($desig_qry2 && $desig_row2 = $desig_qry2->fetch_assoc()) {
                                if (stripos($desig_row2['designation'], 'Head') !== false || stripos($desig_row2['designation'], 'Director') !== false) {
                                    $show_strategic_pct = true;
                                    $str_pct_alloc = $conn->query("SELECT percentage FROM percentage_allocation 
                                        WHERE position_id = $emp_position_id 
                                        AND designation_id = $emp_designation_id 
                                        AND category = 'strategic' 
                                        AND is_active = 1 LIMIT 1");
                                    if ($str_pct_alloc && $str_row = $str_pct_alloc->fetch_assoc()) {
                                        $str_pct = floatval($str_row['percentage']);
                                    }
                                }
                            }
                        }
                    }
                    $show_instructions_pct = $core_pct > 0;
                    $show_research_pct = isset($allocations['core_research']) && $allocations['core_research'] > 0 && $can_see_research_extension;
                    $show_extension_pct = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && $can_see_research_extension;
                    $show_support_pct = isset($allocations['support']) && $allocations['support'] > 0;
                    
                    $core_count = 0;
                    $core_val_sum = 0;
                    if ($show_instructions_pct && $inst_ave['count'] > 0) {
                        $core_count += $inst_ave['count'];
                        $core_val_sum += $inst_val;
                    }
                    if ($show_research_pct && $res_ave['count'] > 0) {
                        $core_count += $res_ave['count'];
                        $core_val_sum += $res_val;
                    }
                    if ($show_extension_pct && $ext_ave['count'] > 0) {
                        $core_count += $ext_ave['count'];
                        $core_val_sum += $ext_val;
                    }
                    
                    if ($emp_position_id == 19) {
                        $core_sum = 0;
                        $core_total_count = 0;
                        
                        if ($show_instructions_pct && $inst_ave['count'] > 0) {
                            $inst_val = floatval($inst_rating['instruction_rating']);
                            $core_sum += $inst_val;
                            $core_total_count += 1;
                        }
                        if ($show_research_pct && $res_ave['count'] > 0) {
                            $core_sum += floatval($res_ave['ave']) * $res_ave['count'];
                            $core_total_count += $res_ave['count'];
                        }
                        if ($show_extension_pct && $ext_ave['count'] > 0) {
                            $core_sum += floatval($ext_ave['ave']) * $ext_ave['count'];
                            $core_total_count += $ext_ave['count'];
                        }
                        $core_function = $core_total_count > 0 ? $core_sum / $core_total_count : 0;
                    } else {
                        $core_sum = 0;
                        $core_total_count = 0;
                        if ($show_instructions_pct && $inst_ave['count'] > 0) {
                            $core_sum += floatval($inst_rating['instruction_rating']);
                            $core_total_count++;
                        }
                        if ($show_research_pct && $res_ave['count'] > 0) {
                            $core_sum += floatval($res_ave['ave']);
                            $core_total_count++;
                        }
                        if ($show_extension_pct && $ext_ave['count'] > 0) {
                            $core_sum += floatval($ext_ave['ave']);
                            $core_total_count++;
                        }
                        $core_function = $core_total_count > 0 ? $core_sum / $core_total_count : 0;
                    }
                    $core_weighted = $core_function * ($core_pct / 100);
                    $str_pct_calc = $show_strategic_pct ? $str_pct : 0;
                    $supp_pct_calc = $show_support_pct ? $supp_pct : 0;
                    $total = ($str_val * ($str_pct_calc / 100)) + $core_weighted + ($supp_val * ($supp_pct_calc / 100));
                    
                    error_log("=== ARCHIVES CALCULATION ===");
                    error_log("Position: $emp_position_id, Designation: $emp_designation_id, is_instructor: $is_instructor, is_cos: $is_cos, has_designation: $has_designation");
                    if ($has_designation) {
                        error_log("Formula: WITH designation - Strategic + Core + Support");
                        error_log("Strategic: pct=$str_pct, contrib=" . ($str_val * ($str_pct / 100)));
                        error_log("Core: function=$core_function, pct=$core_pct, contrib=" . ($core_function * ($core_pct / 100)));
                        error_log("Support: pct=$supp_pct, contrib=" . ($supp_val * ($supp_pct / 100)));
                    } else {
                        error_log("Formula: WITHOUT designation (Instructor/COS) - Core(90%)(Instr 60% + Res 20% + Ext 20%) + Support(10%)");
                        error_log("Instruction: rating={$inst_rating['instruction_rating']} × 0.60 = " . ($inst_rating['instruction_rating'] * 0.60));
                        error_log("Research: {$res_ave['ave']} × 0.20 = " . ($res_ave['ave'] * 0.20));
                        error_log("Extension: {$ext_ave['ave']} × 0.20 = " . ($ext_ave['ave'] * 0.20));
                        error_log("Core Function: $core_function × 0.90 = " . ($core_function * 0.90));
                        error_log("Support: {$supp_ave['ave']} × 0.10 = " . ($supp_ave['ave'] * 0.10));
                    }
                    error_log("FINAL TOTAL: $total");
                    
                    $avg_score = $total;
                    ?>

                    <div class="archive-section">
                        <div class="archive-header">
                            <i class="fas fa-star mr-2"></i>Previous Ratings
                        </div>
                        <div class="card archive-card">
                            <div class="card-body">
                                <?php if($ratings_qry->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Task/Activity</th>
                                                <th>Rating Period</th>
                                                <th>Efficiency</th>
                                                <th>Timeliness</th>
                                                <th>Quality</th>
                                                <th>Average</th>
                                                <th>Adjectival Rating</th>
                                                <th>Rated By</th>
                                                <th>Date Rated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $ratings_qry->data_seek(0);
                                            while($row = $ratings_qry->fetch_assoc()): 
                                                // Apply same logic as rating.php - only count applicable criteria
                                                $rating_eff = (isset($row['efficiency']) && is_numeric($row['efficiency']) && $row['efficiency'] > 0) ? (float)$row['efficiency'] : null;
                                                $rating_time = (isset($row['timeliness']) && is_numeric($row['timeliness']) && $row['timeliness'] > 0) ? (float)$row['timeliness'] : null;
                                                $rating_qual = (isset($row['quality']) && is_numeric($row['quality']) && $row['quality'] > 0) ? (float)$row['quality'] : null;
                                                
                                                $criteria = [];
                                                if ($row['task_quality'] == 'Applicable' && $rating_qual !== null) $criteria['quality'] = $rating_qual;
                                                if ($row['task_efficiency'] == 'Applicable' && $rating_eff !== null) $criteria['efficiency'] = $rating_eff;
                                                if ($row['task_timeliness'] == 'Applicable' && $rating_time !== null) $criteria['timeliness'] = $rating_time;
                                                
                                                $avg = (count($criteria) > 0) ? array_sum($criteria) / count($criteria) : 0;
                                                $adj_rating = getAdjectivalRating($avg);
                                                
                                                $badge_class = 'badge-no-rating';
                                                if (strpos($adj_rating, 'OUTSTANDING') !== false) $badge_class = 'badge-outstanding';
                                                elseif (strpos($adj_rating, 'VERY SATISFACTORY') !== false) $badge_class = 'badge-very-satisfactory';
                                                elseif (strpos($adj_rating, 'SATISFACTORY') !== false) $badge_class = 'badge-satisfactory';
                                                elseif (strpos($adj_rating, 'UNSATISFACTORY') !== false) $badge_class = 'badge-unsatisfactory';
                                                elseif (strpos($adj_rating, 'POOR') !== false) $badge_class = 'badge-poor';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['task_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['semester'] ?? '') ?> 
                                                    <?php echo htmlspecialchars($row['year'] ?? '') ?>
                                                    <?php if(!empty($row['period_code'])): ?>
                                                    <small class="text-muted">(<?php echo $row['period_code'] ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row['task_efficiency'] == 'Applicable' && isset($row['efficiency']) ? number_format($row['efficiency'], 2) : '-' ?></td>
                                                <td><?php echo $row['task_timeliness'] == 'Applicable' && isset($row['timeliness']) ? number_format($row['timeliness'], 2) : '-' ?></td>
                                                <td><?php echo $row['task_quality'] == 'Applicable' && isset($row['quality']) ? number_format($row['quality'], 2) : '-' ?></td>
                                                <td><strong><?php echo number_format($avg, 2) ?></strong></td>
                                                <td><span class="rating-badge <?php echo $badge_class ?>"><?php echo $adj_rating ?></span></td>
                                                <td><?php echo htmlspecialchars(($row['evaluator_firstname'] ?? '') . ' ' . ($row['evaluator_lastname'] ?? 'Evaluator')) ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>No ratings found<?php echo !empty($selected_period) ? ' for this period' : '' ?>.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="archive-section">
                        <div class="archive-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-folder mr-2"></i>Submitted MOVs
                        </div>
                        <div class="card archive-card">
                            <div class="card-body">
                                <?php if($movs_qry->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Task/Activity</th>
                                                <th>Rating Period</th>
                                                <th>MOV File</th>
                                                <th>Status</th>
                                                <th>Date Submitted</th>
                                                <th>Date Verified</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $movs_qry->data_seek(0);
                                            while($row = $movs_qry->fetch_assoc()): 
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['task_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['semester'] ?? '') ?> 
                                                    <?php echo htmlspecialchars($row['year'] ?? '') ?>
                                                    <?php if(!empty($row['period_code'])): ?>
                                                    <small class="text-muted">(<?php echo $row['period_code'] ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if(!empty($row['file_path'])): ?>
                                                    <?php 
                                                    $fullPath = $row['file_path'];
                                                    if (!empty($row['file_type']) && strpos($fullPath, '.' . $row['file_type']) === false) {
                                                        $fullPath .= '.' . $row['file_type'];
                                                    }
                                                    ?>
                                                    <a href="<?php echo htmlspecialchars($fullPath) ?>" 
                                                       class="mov-file-link" 
                                                       download="<?php echo 'MOV_' . $row['id'] . '.' . htmlspecialchars($row['file_type']) ?>">
                                                        <i class="fas fa-download mr-1"></i>
                                                        Download <?php echo strtoupper(htmlspecialchars($row['file_type'])) ?>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">No file</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'badge-secondary';
                                                    if ($row['progress'] === 'Verified') $status_class = 'badge-success';
                                                    elseif ($row['progress'] === 'For Verification') $status_class = 'badge-warning';
                                                    elseif ($row['progress'] === 'Completed') $status_class = 'badge-info';
                                                    ?>
                                                    <span class="badge <?php echo $status_class ?>">
                                                        <?php echo htmlspecialchars($row['progress']) ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
                                                <td>
                                                    <?php if(!empty($row['date_verified'])): ?>
                                                    <?php echo date('M d, Y', strtotime($row['date_verified'])) ?>
                                                    <?php else: ?>
                                                    <span class="text-muted">Not verified</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>No MOVs found<?php echo !empty($selected_period) ? ' for this period' : '' ?>.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('.nav-archives').addClass('active');
});
</script>
