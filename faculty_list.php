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
    // Fetch percentage allocations
    $allocations = [];
    $desig_cond = ($designation_id && $designation_id > 0)
        ? "designation_id = " . intval($designation_id)
        : "(designation_id IS NULL OR designation_id = 0)";
    $alloc_qry = $conn->query("SELECT * FROM percentage_allocation WHERE position_id = $position_id AND $desig_cond AND is_active = 1");
    while ($row = $alloc_qry->fetch_assoc()) {
        $key = $row['category'];
        if ($row['sub_category']) $key .= '_' . $row['sub_category'];
        $allocations[$key] = floatval($row['percentage']);
    }
    // Fallback: if no allocations found, try designation_id=3 (Faculty)
    if (empty($allocations) && $designation_id != 3) {
        $fallback_qry = $conn->query("SELECT * FROM percentage_allocation WHERE position_id = $position_id AND designation_id = 3 AND is_active = 1");
        while ($row = $fallback_qry->fetch_assoc()) {
            $key = $row['category'];
            if ($row['sub_category']) $key .= '_' . $row['sub_category'];
            $allocations[$key] = floatval($row['percentage']);
        }
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

    $is_cos = ($position_id == 19);
    $can_see_re = ($position_id >= 1 && $position_id <= 18);

    // Strategic override for designated positions
    if (!$is_cos && (!isset($allocations['strategic']) || $allocations['strategic'] == 0) && $designation_id > 0) {
        $desig_qry = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($designation_id));
        if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
            $dname = $desig_row['designation'];
            if (stripos($dname, 'Department Head') !== false || stripos($dname, 'Director') !== false ||
                stripos($dname, 'Dean') !== false || stripos($dname, 'Vice President') !== false) {
                $sa = $conn->query("SELECT percentage FROM percentage_allocation WHERE position_id = $position_id AND designation_id = $designation_id AND category = 'strategic' AND is_active = 1 LIMIT 1");
                if ($sa && $sar = $sa->fetch_assoc()) $str_pct = floatval($sar['percentage']);
            }
        }
    }

    // Build task filters
    $cat_filters = ["t.category = 'strategic'"];
    if (($allocations['core_instructions'] ?? 0) > 0) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
    if (($allocations['core_research'] ?? 0) > 0 && $can_see_re) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
    if (($allocations['core_extension'] ?? 0) > 0 && $can_see_re) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
    if (($allocations['support'] ?? 0) > 0) $cat_filters[] = "t.category = 'support'";

    $where = "t.is_active = 1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)";
    $where .= " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = $position_id)";
    if ($is_cos) $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
    elseif ($designation_id > 0) $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $designation_id)";
    else $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0)";
    if (!empty($cat_filters)) $where .= " AND (" . implode(" OR ", $cat_filters) . ")";

    $qry = $conn->query("
        SELECT t.id, t.category, t.sub_category, t.quality as tq, t.timeliness as tt, t.efficiency as te,
               tp.progress, r.efficiency as re, r.timeliness as rt, r.quality as rq
        FROM task_list t
        LEFT JOIN task_progress tp ON tp.task_id = t.id AND tp.faculty_id = $faculty_id
        LEFT JOIN ratings r ON r.task_id = t.id AND r.employee_id = $faculty_id AND r.rating_period = '$period_code'
        WHERE $where ORDER BY t.category, t.sub_category, t.id
    ");

    $sections = ['str' => [], 'inst' => [], 'res' => [], 'ext' => [], 'supp' => []];
    while ($row = $qry->fetch_assoc()) {
        $cat = strtolower($row['category'] ?? '');
        $sub = strtolower($row['sub_category'] ?? '');
        $progress = $row['progress'] ?? null;
        if ($progress === 'N/A' || $progress !== 'Verified') continue;

        $re = (isset($row['re']) && is_numeric($row['re']) && $row['re'] > 0) ? (float)$row['re'] : null;
        $rt = (isset($row['rt']) && is_numeric($row['rt']) && $row['rt'] > 0) ? (float)$row['rt'] : null;
        $rq = (isset($row['rq']) && is_numeric($row['rq']) && $row['rq'] > 0) ? (float)$row['rq'] : null;
        $criteria = [];
        if ($row['te'] == 'Applicable' && $re !== null) $criteria[] = $re;
        if ($row['tt'] == 'Applicable' && $rt !== null) $criteria[] = $rt;
        if ($row['tq'] == 'Applicable' && $rq !== null) $criteria[] = $rq;
        $avg = count($criteria) > 0 ? array_sum($criteria) / count($criteria) : 0;

        if ($cat == 'strategic') $sections['str'][] = $avg;
        elseif ($cat == 'core') {
            if ($sub == 'research') $sections['res'][] = $avg;
            elseif ($sub == 'extension') $sections['ext'][] = $avg;
            else $sections['inst'][] = $avg;
        } elseif ($cat == 'support') $sections['supp'][] = $avg;
    }

    // Averages per section
    $str_val = count($sections['str']) > 0 ? array_sum($sections['str']) / count($sections['str']) : 0;
    $inst_val = count($sections['inst']) > 0 ? array_sum($sections['inst']) / count($sections['inst']) : 0;
    $res_val = count($sections['res']) > 0 ? array_sum($sections['res']) / count($sections['res']) : 0;
    $ext_val = count($sections['ext']) > 0 ? array_sum($sections['ext']) / count($sections['ext']) : 0;
    $supp_val = count($sections['supp']) > 0 ? array_sum($sections['supp']) / count($sections['supp']) : 0;

    $str_active = count($sections['str']) > 0;
    $inst_active = count($sections['inst']) > 0;
    $res_active = count($sections['res']) > 0;
    $ext_active = count($sections['ext']) > 0;
    $supp_active = count($sections['supp']) > 0;
    if (!$str_active && !$inst_active && !$res_active && !$ext_active && !$supp_active) return null;

    // Core weighted average
    $cw_sum = 0; $cw_pct = 0;
    if ($inst_active) { $cw_sum += $inst_val * $inst_pct; $cw_pct += $inst_pct; }
    if ($res_active) { $cw_sum += $res_val * $res_pct; $cw_pct += $res_pct; }
    if ($ext_active) { $cw_sum += $ext_val * $ext_pct; $cw_pct += $ext_pct; }
    $core_fn = $cw_pct > 0 ? $cw_sum / $cw_pct : 0;

    $core_eff = $core_pct > 0 ? $core_pct : getAllocation($conn, $position_id, $designation_id, 'core', null);
    if ($core_eff == 0) $core_eff = $core_pct;

    $spc = $str_active ? $str_pct : 0;
    $cpc = $cw_pct > 0 ? $core_eff : 0;
    $sppc = $supp_active ? $supp_pct : 0;
    $tap = $spc + $cpc + $sppc;

    if ($tap > 0) {
        $total = (($str_val * $spc) + ($core_fn * $cpc) + ($supp_val * $sppc)) / $tap;
    } else {
        $total = 0;
    }
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

// Fetch current rating periods
$rp_qry = $conn->query("
    SELECT * FROM rating_period 
    WHERE is_active = 1
    ORDER BY id DESC
    LIMIT 1
");
$active_period = $rp_qry->fetch_assoc();

// Current period code for faculty rating lookup
$active_period_code = '';
if ($active_period) {
    $active_period_code = $active_period['code'] ?? ($active_period['semester'] . '-' . $active_period['year']);
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
               dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        ORDER BY dep.department, e.lastname
    ");
} elseif($is_dean) {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        WHERE e.designation_id = 2
        ORDER BY dep.department, e.lastname
    ");
} elseif($is_dept_head) {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        LEFT JOIN evaluator_list ev ON e.firstname = ev.firstname AND e.lastname = ev.lastname 
            AND ev.type = '0' AND ev.department_id = $dept_id
        WHERE e.department_id = $dept_id AND ev.id IS NULL
        ORDER BY e.lastname
    ");
} else {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               dl.designation, dep.department, e.position_id, p.position
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
        
        // Task progress stats
        $stats = $conn->query("
            SELECT 
                COUNT(DISTINCT task_id) as total_tasks,
                SUM(CASE WHEN progress = 'Verified' THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN progress = 'For Verification' THEN 1 ELSE 0 END) as for_verification
            FROM task_progress WHERE faculty_id = $emp_id
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

    <!-- ===== ACTIVE PERIOD OVERVIEW ===== -->
    <?php if ($active_period): 
        $start = $active_period['start_date'] ? date('M d, Y', strtotime($active_period['start_date'])) : '—';
        $end = $active_period['end_date'] ? date('M d, Y', strtotime($active_period['end_date'])) : '—';
    ?>
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="info-box bg-gradient-primary">
                <span class="info-box-icon"><i class="fa fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Rating Period</span>
                    <span class="info-box-number"><?= htmlspecialchars($active_period['semester']) ?> <?= htmlspecialchars($active_period['year']) ?></span>
                    <small>Designated: <?= $start ?> — <?= $end ?> | Non-Desig/COS: <?= $active_period['non_desig_start_date'] ? date('M d, Y', strtotime($active_period['non_desig_start_date'])) : $start ?> — <?= $active_period['non_desig_end_date'] ? date('M d, Y', strtotime($active_period['non_desig_end_date'])) : $end ?></small>
                </div>
            </div>
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
        <div class="card-header d-flex justify-content-between align-items-center">
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

            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered table-sm" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 30px;">#</th>
                            <th>Faculty Name</th>
                            <th><?= $is_admin ? 'Department / Position' : 'Designation' ?></th>
                            <th class="text-center" style="width: 70px;">Tasks</th>
                            <th class="text-center" style="width: 70px;">Verified</th>
                            <th class="text-center" style="width: 110px;">Rating (<?= $active_period_code ?: 'N/A' ?>)</th>
                            <th class="text-center" style="width: 80px;">Status</th>
                            <?php if($is_admin || $is_dean || $is_dept_head): ?>
                            <th class="text-center" style="width: 140px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($faculty_data as $row): 
                            $flagged = isset($intervention_faculty[$row['id']]);
                            // Determine adjectival rating
                            $avg_r = $row['avg_rating'];
                            if ($avg_r !== null) {
                                if ($avg_r >= 4.75) { $adj = 'Outstanding'; $cls = 'success'; }
                                elseif ($avg_r >= 3.61) { $adj = 'Very Satisfactory'; $cls = 'success'; }
                                elseif ($avg_r >= 2.61) { $adj = 'Satisfactory'; $cls = 'info'; }
                                elseif ($avg_r >= 1.61) { $adj = 'Unsatisfactory'; $cls = 'warning'; }
                                else { $adj = 'Poor'; $cls = 'danger'; }
                            }
                        ?>
                        <tr class="<?= $flagged ? 'table-warning' : '' ?> fac-row" <?php if($is_admin || $is_dean || $is_dept_head): ?>onclick="window.location.href='index.php?page=evaluation&id=<?= $row['id'] ?>'"<?php endif; ?>>
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
                                    <span class="badge badge-<?= $cls ?> font-weight-bold" style="font-size: 1.2rem; padding: 6px 12px;">
                                        <?= number_format($avg_r, 2) ?>
                                    </span>
                                    <br><small class="text-muted font-weight-bold"><?= $adj ?></small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($flagged): ?>
                                    <span class="badge badge-warning"><i class="fa fa-exclamation"></i> Needs Review</span>
                                <?php elseif ($avg_r !== null): ?>
                                    <span class="badge badge-<?= $cls ?>"><?= $adj ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Not Rated</span>
                                <?php endif; ?>
                            </td>
                            <?php if($is_admin || $is_dean || $is_dept_head): ?>
                            <td class="text-center">
                                <a href="index.php?page=evaluation&id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fa fa-search"></i> Check
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
    @media (max-width: 767px) {
        .search-bar input { font-size: 0.85rem; }
    }
</style>

<script>
function filterFaculty() {
    var input = document.getElementById('facultySearch');
    var filter = input.value.toLowerCase();
    var table = document.getElementById('list');
    var rows = table.querySelectorAll('tbody tr');
    var visibleCount = 0;
    
    rows.forEach(function(row) {
        var name = row.querySelector('td:nth-child(2)');
        var dept = row.querySelector('td:nth-child(3)');
        if (!name || !dept) return;
        
        var nameText = name.textContent.toLowerCase();
        var deptText = dept.textContent.toLowerCase();
        
        if (nameText.indexOf(filter) !== -1 || deptText.indexOf(filter) !== -1) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show "no results" message if all hidden
    var noResultRow = document.getElementById('noResultRow');
    if (visibleCount === 0 && !noResultRow) {
        var tbody = table.querySelector('tbody');
        var tr = document.createElement('tr');
        tr.id = 'noResultRow';
        tr.innerHTML = '<td colspan="9" class="text-center text-muted py-3">No matching faculty found</td>';
        tbody.appendChild(tr);
    } else if (visibleCount > 0 && noResultRow) {
        noResultRow.remove();
    }
}
</script>
