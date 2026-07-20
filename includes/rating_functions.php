<?php
// Shared rating computation functions — used by faculty_list.php, home_content.php, etc.
// Prevent redeclaration if already included by another file.
if (!function_exists('getAdjectivalRating')) {
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
}

if (!function_exists('getAllocation')) {
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
}

if (!function_exists('computeWeightedRating')) {
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

}

