<?php include 'db_connect.php';

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
    $sql = "SELECT percentage FROM percentage_allocation WHERE position_id = $position_id";
    if ($designation_id && $designation_id > 0) {
        $sql .= " AND designation_id = $designation_id";
    } else {
        $sql .= " AND (designation_id IS NULL OR designation_id = 0)";
    }
    $sql .= " AND category = '$category'";
    if ($sub_category) {
        $sql .= " AND sub_category = '$sub_category'";
    } else {
        $sql .= " AND (sub_category IS NULL OR sub_category = '' OR sub_category = 'total')";
    }
    $sql .= " LIMIT 1";
    $qry = $conn->query($sql);
    if($qry && $qry->num_rows > 0) {
        return floatval($qry->fetch_assoc()['percentage']);
    }
    return 0;
}

function computeWeightedRating($conn, $faculty_id, $position_id, $designation_id, $period_code) {
    // ====================================================================
    // This function MUST mirror the over-all rating computation in
    // rating.php exactly. Any divergence will cause the faculty list
    // rating to differ from the individual IPCR rating page.
    // ====================================================================

    $is_cos = ($position_id == 19);
    $can_see_re = ($position_id >= 1 && $position_id <= 18);

    // --- Allocations (with same fallback as rating.php) ---
    $FACULTY_DESIG_ID = 3;
    $alloc_desig_id = intval($designation_id ?? 0);
    if ($alloc_desig_id > 0) {
        $chk = $conn->query("SELECT COUNT(*) AS n FROM percentage_allocation WHERE position_id = $position_id AND designation_id = $alloc_desig_id AND is_active = 1");
        $has_own_alloc = $chk ? (int)$chk->fetch_assoc()['n'] : 0;
        if ($has_own_alloc == 0 && !$is_cos) {
            $alloc_desig_id = $FACULTY_DESIG_ID;
        }
    }
    $desig_cond = ($alloc_desig_id > 0)
        ? "designation_id = " . intval($alloc_desig_id)
        : "(designation_id IS NULL OR designation_id = 0)";
    $allocations = [];
    $alloc_qry = $conn->query("SELECT * FROM percentage_allocation WHERE position_id = $position_id AND $desig_cond AND is_active = 1");
    while ($row = $alloc_qry->fetch_assoc()) {
        $key = $row['category'];
        if ($row['sub_category']) $key .= '_' . $row['sub_category'];
        if (!isset($allocations[$key])) $allocations[$key] = floatval($row['percentage']);
    }
    if (isset($allocations['core_instruction']) && !isset($allocations['core_instructions'])) {
        $allocations['core_instructions'] = $allocations['core_instruction'];
    }

    $str_pct = $allocations['strategic'] ?? 0;
    $core_pct = $allocations['core_total'] ?? 0;
    $res_pct = $allocations['core_research'] ?? 0;
    $ext_pct = $allocations['core_extension'] ?? 0;
    $supp_pct = $allocations['support'] ?? 0;
    $ter_pct = $allocations['core_ter'] ?? 0;
    $instr_pct_raw = $allocations['core_instructions'] ?? 0;
    $inst_pct = $ter_pct + $instr_pct_raw;

    // Strategic override for designated positions (same as rating.php)
    if (!$is_cos) {
        $has_strat_alloc = isset($allocations['strategic']) && $allocations['strategic'] > 0;
        if (!$has_strat_alloc && $designation_id > 0) {
            $desig_qry = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($designation_id));
            if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
                $dname = $desig_row['designation'];
                if (stripos($dname, 'Department Head') !== false || stripos($dname, 'Director') !== false ||
                    stripos($dname, 'Dean') !== false || stripos($dname, 'Vice President') !== false) {
                    $sa = $conn->query("SELECT percentage FROM percentage_allocation WHERE position_id = $position_id AND designation_id = " . intval($designation_id) . " AND category = 'strategic' AND is_active = 1 LIMIT 1");
                    if ($sa && $sar = $sa->fetch_assoc()) $str_pct = floatval($sar['percentage']);
                }
            }
        }
    }

    // core_total_display (same as rating.php)
    $core_total_display = getAllocation($conn, $position_id, $alloc_desig_id, 'core', null);
    if ($core_total_display == 0) $core_total_display = $core_pct;
    $core_effective_pct = $core_total_display;

    // --- Build task query (same filters as rating.php) ---
    $has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
    $has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && $can_see_re;
    $has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && $can_see_re;
    $has_support = isset($allocations['support']) && $allocations['support'] > 0;

    $cat_filters = ["t.category = 'strategic'"];
    if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
    if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
    if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
    if ($has_support) $cat_filters[] = "t.category = 'support'";

    $where = "t.is_active = 1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)";
    // Exemptions: by position OR designation (same as rating.php)
    if (!empty($designation_id) && $designation_id > 0) {
        $where .= " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = $position_id OR designation_id = " . intval($designation_id) . ")";
    } else {
        $where .= " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = $position_id)";
    }
    if ($is_cos) {
        $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
    } elseif (!empty($designation_id) && $designation_id > 0) {
        $where .= " AND " . task_designation_match($designation_id);
    } else {
        $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
    }
    $where .= " AND (" . implode(" OR ", $cat_filters) . ")";

    $qry = $conn->query("
        SELECT t.id, t.category, t.sub_category, t.quality as tq, t.timeliness as tt, t.efficiency as te,
               tp.progress, r.efficiency as re, r.timeliness as rt, r.quality as rq
        FROM task_list t
        LEFT JOIN task_progress tp ON tp.task_id = t.id AND tp.faculty_id = $faculty_id
        LEFT JOIN ratings r ON r.task_id = t.id AND r.employee_id = $faculty_id
        WHERE $where ORDER BY t.category, t.sub_category, t.id
    ");

    // --- Build task data (same structure as rating.php) ---
    $tasks_by_section = [
        'strategic' => [], 'core_instructions' => [],
        'core_research' => [], 'core_extension' => [], 'support' => []
    ];
    if ($qry) {
        while ($row = $qry->fetch_assoc()) {
            $cat = strtolower($row['category'] ?? '');
            $sub = strtolower($row['sub_category'] ?? '');
            $progress = $row['progress'] ?? null;
            $is_na = ($progress === 'N/A');
            $has_submission = !empty($progress) && $progress == 'Verified';

            if ($is_na) {
                $task_data = ['average' => 'N/A', 'has_submission' => false, 'is_na' => true, 'sub_category' => $row['sub_category'] ?? ''];
            } elseif (!$has_submission) {
                $task_data = ['average' => '0', 'has_submission' => false, 'is_na' => false, 'sub_category' => $row['sub_category'] ?? ''];
            } else {
                $re = (isset($row['re']) && is_numeric($row['re']) && $row['re'] > 0) ? (float)$row['re'] : null;
                $rt = (isset($row['rt']) && is_numeric($row['rt']) && $row['rt'] > 0) ? (float)$row['rt'] : null;
                $rq = (isset($row['rq']) && is_numeric($row['rq']) && $row['rq'] > 0) ? (float)$row['rq'] : null;
                $criteria = [];
                if ($row['te'] == 'Applicable' && $re !== null) $criteria[] = $re;
                if ($row['tt'] == 'Applicable' && $rt !== null) $criteria[] = $rt;
                if ($row['tq'] == 'Applicable' && $rq !== null) $criteria[] = $rq;
                $avg = count($criteria) > 0 ? number_format(array_sum($criteria) / count($criteria), 2) : '0';
                $task_data = ['average' => $avg, 'has_submission' => true, 'is_na' => false, 'sub_category' => $row['sub_category'] ?? ''];
            }

            if ($cat == 'strategic') $tasks_by_section['strategic'][] = $task_data;
            elseif ($cat == 'core') {
                if ($sub == 'research') $tasks_by_section['core_research'][] = $task_data;
                elseif ($sub == 'extension') $tasks_by_section['core_extension'][] = $task_data;
                else $tasks_by_section['core_instructions'][] = $task_data;
            } elseif ($cat == 'support') $tasks_by_section['support'][] = $task_data;
        }
    }

    // --- calcAverage (same as rating.php) ---
    $calcAverage = function($tasks) {
        $sum = 0; $count = 0;
        foreach ($tasks as $task) {
            if (isset($task['is_na']) && $task['is_na']) continue;
            if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average'])) {
                $sum += (float)$task['average'];
                $count++;
            }
        }
        return ['sum' => $sum, 'count' => $count, 'ave' => $count > 0 ? number_format($sum / $count, 2) : 0];
    };

    // --- calcInstructionRating (same as rating.php) ---
    $str_ave = $calcAverage($tasks_by_section['strategic']);
    $inst_ave = $calcAverage($tasks_by_section['core_instructions']);
    $res_ave = $calcAverage($tasks_by_section['core_research']);
    $ext_ave = $calcAverage($tasks_by_section['core_extension']);

    // Instruction rating: TER (50%) + Instruction (50%)
    $ter_sum = 0; $ter_count = 0;
    $instruction_sum = 0; $instruction_count = 0;
    foreach ($tasks_by_section['core_instructions'] as $task) {
        if (isset($task['is_na']) && $task['is_na']) continue;
        if (isset($task['has_submission']) && $task['has_submission'] && is_numeric($task['average'])) {
            $sub = strtolower($task['sub_category'] ?? '');
            if ($sub == 'ter') { $ter_sum += (float)$task['average']; $ter_count++; }
            elseif ($sub == 'instruction' || $sub == 'instructions') { $instruction_sum += (float)$task['average']; $instruction_count++; }
        }
    }
    $ter_ave = $ter_count > 0 ? $ter_sum / $ter_count : 0;
    $inst_divisor = $instruction_count > 0 ? $instruction_count : 1;
    $instruction_div = $instruction_count > 0 ? $instruction_sum / $inst_divisor : 0;
    $instruction_rating = ($ter_ave * 0.50) + ($instruction_div * 0.50);
    $inst_val = floatval(number_format($instruction_rating, 2));

    // --- calcResearchAverage (same as rating.php: divide by expected count) ---
    $res_val = 0;
    if (count($tasks_by_section['core_research']) > 0 || $has_research) {
        $research_task_qry = $conn->query("SELECT COUNT(*) as task_count FROM task_list t WHERE t.category = 'core' AND t.sub_category = 'research' AND t.is_active = 1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id) AND " . task_designation_match($designation_id > 0 ? $designation_id : 0) . " AND t.id NOT IN (SELECT tp.task_id FROM task_progress tp WHERE tp.faculty_id = $faculty_id AND tp.progress = 'N/A')");
        $expected_research_count = $research_task_qry ? (int)$research_task_qry->fetch_assoc()['task_count'] : 0;
        $r_divisor = $expected_research_count > 0 ? $expected_research_count : ($res_ave['count'] > 0 ? $res_ave['count'] : 1);
        $res_val = $res_ave['count'] > 0 ? $res_ave['sum'] / $r_divisor : 0;
        $res_val = floatval(number_format($res_val, 2));
    }

    // --- calcExtensionAverage (same as rating.php: divide by expected count) ---
    $ext_val = 0;
    if (count($tasks_by_section['core_extension']) > 0 || $has_extension) {
        $extension_task_qry = $conn->query("SELECT COUNT(*) as task_count FROM task_list t WHERE t.category = 'core' AND t.sub_category = 'extension' AND t.is_active = 1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id) AND " . task_designation_match($designation_id > 0 ? $designation_id : 0) . " AND t.id NOT IN (SELECT tp.task_id FROM task_progress tp WHERE tp.faculty_id = $faculty_id AND tp.progress = 'N/A')");
        $expected_extension_count = $extension_task_qry ? (int)$extension_task_qry->fetch_assoc()['task_count'] : 0;
        $e_divisor = $expected_extension_count > 0 ? $expected_extension_count : ($ext_ave['count'] > 0 ? $ext_ave['count'] : 1);
        $ext_val = $ext_ave['count'] > 0 ? $ext_ave['sum'] / $e_divisor : 0;
        $ext_val = floatval(number_format($ext_val, 2));
    }

    // --- Support average (same as rating.php: divide by all non-N/A tasks) ---
    $supp_sum = 0; $supp_count = 0;
    foreach ($tasks_by_section['support'] as $stask) {
        if (isset($stask['is_na']) && $stask['is_na']) continue;
        $supp_count++;
        if (isset($stask['has_submission']) && $stask['has_submission'] && is_numeric($stask['average'])) {
            $supp_sum += (float)$stask['average'];
        }
    }
    $supp_val = $supp_count > 0 ? floatval(number_format($supp_sum / $supp_count, 2)) : 0;

    // --- Section values & active flags ---
    $str_val = floatval($str_ave['ave']);
    $str_active = ($str_ave['count'] > 0);
    $inst_active = ($inst_ave['count'] > 0);
    $res_active = ($res_ave['count'] > 0);
    $ext_active = ($ext_ave['count'] > 0);
    $supp_active = ($supp_count > 0);

    if (!$str_active && !$inst_active && !$res_active && !$ext_active && !$supp_active) return null;

    // --- Core weighted average (same as rating.php) ---
    $show_instructions_pct = $core_pct > 0;
    $show_research_pct = $has_research;
    $show_extension_pct = $has_extension;

    $core_weighted_sum = 0;
    $core_total_sub_pct = 0;
    if ($show_instructions_pct) {
        $core_weighted_sum += ($inst_active ? $inst_val : 0) * $inst_pct;
        $core_total_sub_pct += $inst_pct;
    }
    if ($show_research_pct) {
        $core_weighted_sum += ($res_active ? $res_val : 0) * $res_pct;
        $core_total_sub_pct += $res_pct;
    }
    if ($show_extension_pct) {
        $core_weighted_sum += ($ext_active ? $ext_val : 0) * $ext_pct;
        $core_total_sub_pct += $ext_pct;
    }
    $core_function = $core_total_sub_pct > 0 ? $core_weighted_sum / $core_total_sub_pct : 0;
    $core_weighted = $core_function * ($core_effective_pct / 100);

    // --- Total: sum of portions (NO redistribution, same as rating.php) ---
    $str_portion = ($str_active ? $str_val * ($str_pct / 100) : 0);
    $core_portion = $core_weighted;
    $supp_portion = ($supp_active ? $supp_val * ($supp_pct / 100) : 0);
    $total = $str_portion + $core_portion + $supp_portion;

    return round($total, 2);
}
$login_type = $_SESSION['login_type'];
$eval_id = intval($_SESSION['login_id']);
$is_admin = ($login_type == 2);
$is_dean = false;
$is_dept_head = false;
$dept_id = 0;

if (!$is_admin) {
    // Check if this is a merged faculty-evaluator (session-based) or legacy evaluator
    if (!empty($_SESSION['is_evaluator'])) {
        $eval_role = $_SESSION['evaluator_role'] ?? '';
        $is_dean = ($eval_role === 'dean');
        $is_dept_head = ($eval_role === 'dept_head');
        // Get department from employee_list
        $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ?");
        $stmt->bind_param("i", $eval_id);
        $stmt->execute();
        $stmt->bind_result($dept_id);
        $stmt->fetch();
        $stmt->close();
    } else {
        // Legacy evaluator (login_type=1)
        $stmt_type = $conn->prepare("SELECT type, department_id FROM evaluator_list WHERE id = ?");
        $stmt_type->bind_param("i", $eval_id);
        $stmt_type->execute();
        $stmt_type->bind_result($eval_type, $dept_id);
        $stmt_type->fetch();
        $stmt_type->close();
        
        $is_dean = ($eval_type == 1);
        $is_dept_head = ($eval_type == 0);
    }
}

// Fetch all rating periods (for selector)
$all_periods = [];
$ap_qry = $conn->query("SELECT * FROM rating_period ORDER BY year DESC, id DESC");
while ($ap_qry && $r = $ap_qry->fetch_assoc()) {
    $key = $r['semester'] . '|' . $r['year'];
    if (!isset($all_periods[$key])) $all_periods[$key] = $r;
}
$all_periods = array_values($all_periods);

// Determine selected period (from query string, session, or default to active)
$active_period = null;
foreach ($all_periods as $p) {
    if (!empty($p['is_active'])) { $active_period = $p; break; }
}

$req_key = $_GET['period'] ?? '';
$selected_period = null;
if ($req_key !== '') {
    foreach ($all_periods as $p) {
        if (($p['semester'] . '|' . $p['year']) === $req_key) { $selected_period = $p; break; }
    }
}
if (!$selected_period) $selected_period = $active_period ?: (count($all_periods) ? $all_periods[0] : null);

// Current period code for faculty rating lookup
$active_period_code = '';
$period_filter_sql = '';  // SQL fragment for task_progress filtering
if ($selected_period) {
    $active_period_code = $selected_period['code'] ?? ($selected_period['semester'] . '-' . $selected_period['year']);
    // Build period filter matching all known rating_period formats in task_progress
    $sel_sem = $selected_period['semester'];
    $sel_yr  = $selected_period['year'];
    $short = '';
    if (strpos($sel_yr, '-') !== false) {
        $parts = explode('-', $sel_yr);
        $short = substr($parts[0], -2) . substr($parts[1], -2);
    }
    $sem_num = '1';
    if (strpos($sel_sem, '2nd') !== false) $sem_num = '2';
    elseif (stripos($sel_sem, 'Summer') !== false) $sem_num = 'S';
    $short_code = $sem_num . '-' . $short;

    // Gather all distinct rating_period values that match this semester/year
    $like_yr  = $conn->real_escape_string($sel_yr);
    $like_sht = $conn->real_escape_string($short_code);
    $period_codes = [];
    $dq = $conn->query("SELECT DISTINCT rating_period FROM task_progress WHERE rating_period <> '' AND (rating_period LIKE '%$like_yr%' OR rating_period LIKE '%$like_sht%')");
    while ($dq && $r = $dq->fetch_assoc()) $period_codes[] = $r['rating_period'];
    $rq = $conn->query("SELECT DISTINCT rating_period FROM ratings WHERE rating_period <> '' AND (rating_period LIKE '%$like_yr%' OR rating_period LIKE '%$like_sht%')");
    while ($rq && $r = $rq->fetch_assoc()) $period_codes[] = $r['rating_period'];
    $period_codes = array_values(array_unique(array_filter($period_codes)));

    if (!empty($period_codes)) {
        $in = implode("','", array_map([$conn, 'real_escape_string'], $period_codes));
        // Active period: also include legacy untagged rows (empty/NULL rating_period)
        // Previous periods: strict match only (don't include untagged legacy rows)
        $is_active_period = $active_period && ($selected_period['semester'] === $active_period['semester'] && $selected_period['year'] === $active_period['year']);
        if ($is_active_period) {
            $period_filter_sql = " AND (rating_period IN ('$in') OR rating_period = '' OR rating_period IS NULL)";
        } else {
            $period_filter_sql = " AND rating_period IN ('$in')";
        }
    }
}

// Intervention flags
$intervention_faculty = [];
$int_qry = $conn->query("SELECT employee_id FROM intervention_flags WHERE acknowledged = 0");
while ($int = $int_qry->fetch_assoc()) {
    $intervention_faculty[$int['employee_id']] = true;
}
$intervention_count = count($intervention_faculty);

// ---------- Build faculty data ----------
$faculty_data = [];

if($is_admin) {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        ORDER BY dep.department, e.lastname
    ");
} elseif($is_dean) {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        WHERE e.designation_id = 2
        ORDER BY dep.department, e.lastname
    ");
} elseif($is_dept_head) {
    // Dept Head sees all faculty in their department except themselves and the Dean.
    // Includes Faculty, Director, Research Head, Extension Head, and undesignated.
    // Only excludes: the dept head themselves (e.id != $eval_id) and Dean (designation_id=1).
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        WHERE e.department_id = $dept_id
          AND e.id != $eval_id
          AND (e.designation_id IS NULL OR e.designation_id = 0 OR e.designation_id != 1)
        ORDER BY e.lastname
    ");
} else {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        WHERE e.department_id = $dept_id
        ORDER BY e.lastname
    ");
}

$total_faculty = 0;
$total_rated = 0;
$dept_stats = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['id'];
        $total_faculty++;
        
        // Task progress stats (period-filtered)
        $stats = $conn->query("
            SELECT 
                COUNT(DISTINCT task_id) as total_tasks,
                SUM(CASE WHEN progress = 'Verified' THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN progress = 'For Verification' THEN 1 ELSE 0 END) as for_verification
            FROM task_progress WHERE faculty_id = $emp_id $period_filter_sql
        ")->fetch_assoc();
        $row['total_tasks'] = $stats['total_tasks'] ?? 0;
        $row['verified'] = $stats['verified'] ?? 0;
        $row['for_verification'] = $stats['for_verification'] ?? 0;
        $row['pending'] = $row['total_tasks'] - $row['verified'] - $row['for_verification'];
        
        // Weighted rating for current period (same logic as rating.php)
        if (!empty($active_period_code)) {
            $pos_id = $row['position_id'] ?? 0;
            $desig_id = $row['designation_id'] ?? 0;
            $row['avg_rating'] = computeWeightedRating($conn, $emp_id, $pos_id, $desig_id, $active_period_code);
            if ($row['avg_rating'] !== null) $total_rated++;
        } else {
            $row['avg_rating'] = null;
        }
        
        // Department stats accumulation
        $d_id = $row['department_id'] ?? 0;
        if (!isset($dept_stats[$d_id])) {
            $dept_stats[$d_id] = ['name' => $row['department'] ?? 'Unknown', 'count' => 0, 'rated' => 0];
        }
        $dept_stats[$d_id]['count']++;
        if ($row['avg_rating'] !== null) $dept_stats[$d_id]['rated']++;
        
        $faculty_data[] = $row;
    }
}
?>

<div class="col-lg-12">

    <!-- ===== PERIOD SELECTOR ===== -->
    <?php if (count($all_periods) > 0):
        $start = $selected_period['start_date'] ? date('M d, Y', strtotime($selected_period['start_date'])) : '—';
        $end = $selected_period['end_date'] ? date('M d, Y', strtotime($selected_period['end_date'])) : '—';
        $sel_key = $selected_period['semester'] . '|' . $selected_period['year'];
    ?>
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="info-box bg-gradient-primary">
                <span class="info-box-icon"><i class="fa fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Rating Period</span>
                    <span class="info-box-number"><?= htmlspecialchars($selected_period['semester']) ?> <?= htmlspecialchars($selected_period['year']) ?> <?= !empty($selected_period['is_active']) ? '<span class="badge badge-light ml-1">Current</span>' : '' ?></span>
                    <small>Designated: <?= $start ?> — <?= $end ?> | Non-Desig/COS: <?= $selected_period['non_desig_start_date'] ? date('M d, Y', strtotime($selected_period['non_desig_start_date'])) : $start ?> — <?= $selected_period['non_desig_end_date'] ? date('M d, Y', strtotime($selected_period['non_desig_end_date'])) : $end ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center justify-content-end">
            <label class="mr-2 mb-0 text-muted" style="font-size:0.85rem;"><i class="fa fa-filter"></i> Period:</label>
            <select id="period_selector" class="form-control form-control-sm"
                    onchange="window.location.href='index.php?page=faculty_list&period='+encodeURIComponent(this.value)"
                    style="width:auto; font-size:0.85rem; max-width:220px;">
                <?php foreach ($all_periods as $p):
                    $pkey = $p['semester'] . '|' . $p['year'];
                    $opt_label = $p['semester'] . ' ' . $p['year'] . (!empty($p['is_active']) ? ' (Current)' : '');
                ?>
                <option value="<?= htmlspecialchars($pkey) ?>" <?= $pkey === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== INTERVENTION WARNING ===== -->
    <?php if ($intervention_count > 0): ?>
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5><i class="icon fa fa-exclamation-triangle"></i> Intervention Required</h5>
        <strong><?= $intervention_count ?> faculty</strong> have 3 consecutive low IPCR ratings (SATISFACTORY or below) and need intervention review.
    </div>
    <?php endif; ?>

    <!-- ===== MAIN CARD ===== -->
    <div class="card card-outline card-success">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:6px;">
            <h5 class="card-title mb-0">
                <i class="fa fa-users"></i> 
                <?php 
                if($is_admin) echo 'All Faculty';
                elseif($is_dean) echo 'Department Heads';
                elseif($is_dept_head) echo 'Faculty Under My Department';
                else echo 'Faculty';
                ?>
            </h5>
            <span class="badge badge-light"><?= $total_faculty ?> faculty | <?= $total_rated ?> rated (<?= $active_period_code ?>)</span>
        </div>
        <div class="card-body">
            
            <?php if(count($faculty_data) > 0): ?>
            <!-- Search/Filter Bar -->
            <div class="search-bar">
                <div class="position-relative" style="max-width: 400px;">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" class="form-control" id="facultySearch" placeholder="Search by name, department, or designation..." onkeyup="filterFaculty()">
                </div>
            </div>

            <!-- ===== DESKTOP TABLE (md and up) ===== -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-striped table-bordered table-sm" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 30px;">#</th>
                            <th>Faculty Name</th>
                            <th><?= $is_admin ? 'Department / Position' : 'Designation' ?></th>
                            <th class="text-center" style="width: 60px;">Tasks</th>
                            <th class="text-center" style="width: 60px;">Verified</th>
                            <th class="text-center" style="width: 100px;">Rating (<?= $active_period_code ?: 'N/A' ?>)</th>
                            <?php if($is_admin || $is_dean || $is_dept_head): ?>
                            <th class="text-center" style="width: 80px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($faculty_data as $row): 
                            $flagged = isset($intervention_faculty[$row['id']]);
                            $avg_r = $row['avg_rating'];
                            if ($avg_r !== null) {
                                if ($avg_r >= 4.75) { $adj = 'Outstanding'; $cls = 'success'; }
                                elseif ($avg_r >= 3.61) { $adj = 'Very Satisfactory'; $cls = 'success'; }
                                elseif ($avg_r >= 2.61) { $adj = 'Satisfactory'; $cls = 'info'; }
                                elseif ($avg_r >= 1.61) { $adj = 'Unsatisfactory'; $cls = 'warning'; }
                                else { $adj = 'Poor'; $cls = 'danger'; }
                            }
                        ?>
                        <tr class="<?= $flagged ? 'table-warning' : '' ?> fac-row" data-search="<?= htmlspecialchars(strtolower($row['lastname'] . ' ' . $row['firstname'] . ' ' . ($row['department'] ?? '') . ' ' . ($row['designation'] ?? ''))) ?>" <?php if($is_admin || $is_dean || $is_dept_head): ?>onclick="window.location.href='index.php?page=evaluation&id=<?= $row['id'] ?>'"<?php endif; ?>>
                            <td class="text-center font-weight-bold"><?= $i++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']) ?></strong>
                                <?php if ($flagged): ?>
                                    <span class="badge badge-warning ml-1" title="3 consecutive low IPCR — needs intervention">
                                        <i class="fa fa-flag"></i> Intervention
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_admin): ?>
                                    <?= htmlspecialchars($row['department'] ?? 'N/A') ?>
                                    <?php if (!empty($row['position'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($row['position']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($row['designation'] ?? 'Faculty') ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $row['total_tasks'] ?></td>
                            <td class="text-center">
                                <?php if ($row['verified'] > 0): ?>
                                    <span class="badge badge-success"><?= $row['verified'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" style="vertical-align:middle;">
                                <?php if ($avg_r !== null): ?>
                                    <span class="badge badge-<?= $cls ?> font-weight-bold" style="font-size: 0.9rem; padding: 4px 10px; border-radius: 4px;">
                                        <?= number_format($avg_r, 2) ?>
                                    </span>
                                    <br><small class="text-muted" style="font-size: 0.7rem; font-weight: 600;"><?= $adj ?></small>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if($is_admin || $is_dean || $is_dept_head): ?>
                            <td class="text-center">
                                <a href="index.php?page=evaluation&id=<?= $row['id'] ?>" class="btn btn-sm btn-info" onclick="event.stopPropagation();">
                                    <i class="fa fa-search"></i> Check
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== MOBILE CARDS (below md) ===== -->
            <div class="d-md-none" id="mobileList">
                <?php $i = 1; foreach($faculty_data as $row): 
                    $flagged = isset($intervention_faculty[$row['id']]);
                    $avg_r = $row['avg_rating'];
                    if ($avg_r !== null) {
                        if ($avg_r >= 4.75) { $adj = 'Outstanding'; $cls = 'success'; }
                        elseif ($avg_r >= 3.61) { $adj = 'Very Satisfactory'; $cls = 'success'; }
                        elseif ($avg_r >= 2.61) { $adj = 'Satisfactory'; $cls = 'info'; }
                        elseif ($avg_r >= 1.61) { $adj = 'Unsatisfactory'; $cls = 'warning'; }
                        else { $adj = 'Poor'; $cls = 'danger'; }
                    }
                ?>
                <div class="fac-card <?= $flagged ? 'fac-card-flagged' : '' ?>" data-search="<?= htmlspecialchars(strtolower($row['lastname'] . ' ' . $row['firstname'] . ' ' . ($row['department'] ?? '') . ' ' . ($row['designation'] ?? ''))) ?>" <?php if($is_admin || $is_dean || $is_dept_head): ?>onclick="window.location.href='index.php?page=evaluation&id=<?= $row['id'] ?>'"<?php endif; ?>>
                    <div class="fac-card-top">
                        <div class="fac-card-name">
                            <strong><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></strong>
                            <?php if ($flagged): ?>
                                <span class="badge badge-warning" style="font-size:0.6rem;"><i class="fa fa-flag"></i></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($avg_r !== null): ?>
                            <span class="badge badge-<?= $cls ?> fac-card-rating"><?= number_format($avg_r, 2) ?></span>
                        <?php else: ?>
                            <span class="text-muted fac-card-rating">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="fac-card-meta">
                        <?php if ($is_admin): ?>
                            <span class="text-muted"><?= htmlspecialchars($row['department'] ?? 'N/A') ?><?php if (!empty($row['position'])): ?> · <?= htmlspecialchars($row['position']) ?><?php endif; ?></span>
                        <?php else: ?>
                            <span class="text-muted"><?= htmlspecialchars($row['designation'] ?? 'Faculty') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="fac-card-stats">
                        <span class="fac-stat"><i class="fa fa-tasks text-muted"></i> <?= $row['total_tasks'] ?> tasks</span>
                        <span class="fac-stat"><i class="fa fa-check-circle text-success"></i> <?= $row['verified'] ?> verified</span>
                        <?php if ($avg_r !== null): ?>
                            <span class="fac-stat text-<?= $cls ?>"><small><?= $adj ?></small></span>
                        <?php else: ?>
                            <span class="fac-stat text-muted"><small>Not Rated</small></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No records found</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== DEPARTMENT PERFORMANCE SUMMARY ===== -->
    <?php if ($is_admin && !empty($dept_stats)): ?>
    <div class="card card-outline card-info mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fa fa-chart-bar"></i> Department Summary (<?= $active_period_code ?>)</h5>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Department</th>
                        <th class="text-center">Faculty</th>
                        <th class="text-center">Rated</th>
                        <th class="text-center">Coverage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dept_stats as $ds): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ds['name']) ?></strong></td>
                        <td class="text-center"><?= $ds['count'] ?></td>
                        <td class="text-center"><?= $ds['rated'] ?></td>
                        <td class="text-center">
                            <div class="progress" style="height: 18px;">
                                <?php $pct = $ds['count'] > 0 ? round(($ds['rated'] / $ds['count']) * 100) : 0; ?>
                                <div class="progress-bar bg-info" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
    .card-title { margin: 0; font-weight: 600; }
    .info-box { min-height: 80px; }
    .info-box-icon { display: flex; align-items: center; justify-content: center; width: 70px; }
    .table td { vertical-align: middle; }
    .fac-row { cursor: pointer; transition: background 0.15s; }
    .fac-row:hover { background-color: rgba(23,162,184,0.08) !important; }
    .search-bar { margin-bottom: 12px; }
    .search-bar input { border-radius: 20px; padding-left: 36px; }
    .search-bar .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #adb5bd; }

    /* ---- Mobile card layout ---- */
    .fac-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: box-shadow 0.15s, border-color 0.15s;
    }
    .fac-card:hover { border-color: #17a2b8; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .fac-card-flagged { border-color: #ffc107; background: #fffdf5; }
    .fac-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .fac-card-name { flex: 1; font-size: 0.95rem; line-height: 1.3; }
    .fac-card-rating { font-size: 0.85rem; padding: 3px 8px; border-radius: 4px; white-space: nowrap; }
    .fac-card-meta { margin-top: 2px; font-size: 0.78rem; }
    .fac-card-stats { margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px 12px; font-size: 0.75rem; align-items: center; }
    .fac-stat { display: inline-flex; align-items: center; gap: 3px; }

    @media (max-width: 767px) {
        .search-bar input { font-size: 0.85rem; }
        .info-box { min-height: 60px; }
        .info-box-icon { width: 50px; }
        .info-box-number { font-size: 1rem; }
    }
</style>

<script>
function filterFaculty() {
    var input = document.getElementById('facultySearch');
    var filter = input.value.toLowerCase();

    // Desktop table rows
    var table = document.getElementById('list');
    if (table) {
        var rows = table.querySelectorAll('tbody tr');
        var visibleCount = 0;
        rows.forEach(function(row) {
            var searchData = (row.getAttribute('data-search') || '').toLowerCase();
            if (searchData.indexOf(filter) !== -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        var noResultRow = document.getElementById('noResultRow');
        if (visibleCount === 0 && !noResultRow) {
            var tbody = table.querySelector('tbody');
            var tr = document.createElement('tr');
            tr.id = 'noResultRow';
            var colCount = table.querySelector('thead tr').cells.length;
            tr.innerHTML = '<td colspan="' + colCount + '" class="text-center text-muted py-3">No matching faculty found</td>';
            tbody.appendChild(tr);
        } else if (visibleCount > 0 && noResultRow) {
            noResultRow.remove();
        }
    }

    // Mobile cards
    var mobileList = document.getElementById('mobileList');
    if (mobileList) {
        var cards = mobileList.querySelectorAll('.fac-card');
        var mobileVisible = 0;
        cards.forEach(function(card) {
            var searchData = (card.getAttribute('data-search') || '').toLowerCase();
            if (searchData.indexOf(filter) !== -1) {
                card.style.display = '';
                mobileVisible++;
            } else {
                card.style.display = 'none';
            }
        });
        var mobileNoResult = document.getElementById('mobileNoResult');
        if (mobileVisible === 0 && !mobileNoResult) {
            var div = document.createElement('div');
            div.id = 'mobileNoResult';
            div.className = 'text-center text-muted py-3';
            div.textContent = 'No matching faculty found';
            mobileList.appendChild(div);
        } else if (mobileVisible > 0 && mobileNoResult) {
            mobileNoResult.remove();
        }
    }
}
</script>
