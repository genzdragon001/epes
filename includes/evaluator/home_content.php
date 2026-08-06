<?php
// === EVALUATOR DASHBOARD (Dean + Dept Head) ===
require_once 'includes/rating_functions.php';
$eval_id = intval($_SESSION['login_id']);
$eval_dept_id = 0;
$is_dean = false;
$is_vp = false;

// Check if this is a merged faculty-evaluator (session-based) or legacy evaluator
if (!empty($_SESSION['is_evaluator'])) {
    $eval_role = $_SESSION['evaluator_role'] ?? '';
    $is_dean = ($eval_role === 'dean');
    $is_vp = ($eval_role === 'vp');
    $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ?");
    $stmt->bind_param("i", $eval_id);
    $stmt->execute();
    $stmt->bind_result($eval_dept_id);
    $stmt->fetch();
    $stmt->close();
} else {
    $stmt_type = $conn->prepare("SELECT type, department_id, designation_id FROM evaluator_list WHERE id=?");
    $stmt_type->bind_param("i", $eval_id);
    $stmt_type->execute();
    $stmt_type->bind_result($eval_type, $eval_dept_id, $eval_desig_id);
    $stmt_type->fetch();
    $stmt_type->close();
    $is_dean = ($eval_type == 1);
    // Fall back to employee_list.designation_id when evaluator_list has 0
    $effective_desig = intval($eval_desig_id ?? 0);
    if ($effective_desig === 0) {
        $eval_email = $conn->real_escape_string($_SESSION['login_email'] ?? '');
        $ed_q = $conn->query("SELECT designation_id FROM employee_list WHERE email = '$eval_email' LIMIT 1");
        if ($ed_q && $ed_q->num_rows > 0) {
            $effective_desig = intval($ed_q->fetch_assoc()['designation_id']);
        }
    }
    $is_vp = in_array($effective_desig, [4, 9, 10, 18, 19]);
}

// For VP: map session login_id (employee_list.id or evaluator_list.id) to
// the evaluator_list.id that employee_list.evaluator_id references.
$eval_list_id = $eval_id;
if ($is_vp) {
    $eval_email = $conn->real_escape_string($_SESSION['login_email'] ?? '');
    $eval_map_q = $conn->query("SELECT id FROM evaluator_list WHERE email = '$eval_email' LIMIT 1");
    if ($eval_map_q && $eval_map_q->num_rows > 0) {
        $eval_list_id = intval($eval_map_q->fetch_assoc()['id']);
    }
}

if($is_dean) {
    $total_faculty      = $conn->query("SELECT COUNT(*) FROM employee_list WHERE id != $eval_id")->fetch_row()[0];
    $total_submissions  = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.id!=$eval_id $period_filter")->fetch_row()[0];
    $verified           = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.id!=$eval_id AND tp.progress='Verified' $period_filter")->fetch_row()[0];
    $for_verif          = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.id!=$eval_id AND tp.progress='For Verification' $period_filter")->fetch_row()[0];
} elseif($is_vp) {
    // VP sees faculty explicitly assigned to them via evaluator_id
    $total_faculty      = $conn->query("SELECT COUNT(*) FROM employee_list WHERE evaluator_id=$eval_list_id")->fetch_row()[0];
    $total_submissions  = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.evaluator_id=$eval_list_id $period_filter")->fetch_row()[0];
    $verified           = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.evaluator_id=$eval_list_id AND tp.progress='Verified' $period_filter")->fetch_row()[0];
    $for_verif          = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.evaluator_id=$eval_list_id AND tp.progress='For Verification' $period_filter")->fetch_row()[0];
} else {
    $total_faculty      = $conn->query("SELECT COUNT(*) FROM employee_list WHERE department_id=$eval_dept_id")->fetch_row()[0];
    $total_submissions  = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.department_id=$eval_dept_id AND e.id != $eval_id $period_filter")->fetch_row()[0];
    $verified           = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.department_id=$eval_dept_id AND e.id != $eval_id AND tp.progress='Verified' $period_filter")->fetch_row()[0];
    $for_verif          = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.department_id=$eval_dept_id AND e.id != $eval_id AND tp.progress='For Verification' $period_filter")->fetch_row()[0];
}

$other_submissions = $total_submissions - $verified - $for_verif;
$completion_pct = $total_submissions > 0 ? round(($verified/$total_submissions)*100) : 0;

// Dean: Dept Head completion table
$dept_head_table = [];
if($is_dean) {
    $dhq = $conn->query("
        SELECT ev.id, ev.firstname, ev.lastname, ev.department_id,
               d.department as dept_name
        FROM evaluator_list ev
        LEFT JOIN department_list d ON ev.department_id = d.id
        WHERE ev.type = 0
          AND ev.id NOT IN (
            SELECT el.id FROM evaluator_list el
            LEFT JOIN employee_list em ON el.email = em.email
            WHERE el.designation_id IN (4, 9, 10, 18, 19)
               OR (el.designation_id = 0 AND em.designation_id IN (4, 9, 10, 18, 19))
          )
        ORDER BY d.department, ev.lastname, ev.firstname
    ");
    while($dh = $dhq->fetch_assoc()) {
        $dh_dept_id = (int)$dh['department_id'];
        $fac_cnt = $conn->query("SELECT COUNT(*) as cnt FROM employee_list WHERE department_id=$dh_dept_id")->fetch_assoc()['cnt'];
        $targets_total = 0;
        $fq = $conn->query("SELECT id, position_id, designation_id FROM employee_list WHERE department_id=$dh_dept_id");
        while($f = $fq->fetch_assoc()) {
            $fpos = (int)$f['position_id'];
            $fdes = (int)$f['designation_id'];
            $tq = $conn->query("SELECT COUNT(*) as cnt FROM task_list t WHERE t.is_active=1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id=0 OR t.academic_rank_id=$fpos) AND " . task_designation_match($fdes) . " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id=$fpos)");
            $targets_total += (int)$tq->fetch_assoc()['cnt'];
        }
        $sq = $conn->query("SELECT COUNT(DISTINCT tp.task_id) as submitted, SUM(CASE WHEN tp.progress='Verified' THEN 1 ELSE 0 END) as verified FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id = e.id WHERE e.department_id = $dh_dept_id $period_filter");
        $subs = $sq->fetch_assoc();
        $dept_head_table[] = [
            'name' => $dh['lastname'] . ', ' . $dh['firstname'],
            'program' => $dh['dept_name'] ?? ('Dept #' . $dh_dept_id),
            'faculty_count' => (int)$fac_cnt,
            'targets' => $targets_total,
            'submitted' => (int)$subs['submitted'],
            'verified' => (int)$subs['verified'],
            'completion_pct' => $targets_total > 0 ? round(((int)$subs['verified'] / $targets_total) * 100) : 0,
        ];
    }
}

// Dean: Faculty flagged for intervention — 3 consecutive rating periods with
// IPCR rating ≤ 3.60 (Satisfactory or below). Only faculty who actually have
// a computed rating in each period are counted; periods with no rating break
// the streak. Uses computeWeightedRating() for accuracy (matches rating.php).
$flagged_faculty = [];
if ($is_dean) {
    // Get the dean's college_office_id to find all departments under this college
    $dean_dept_id = $eval_dept_id;
    $college_q = $conn->query("SELECT college_office_id FROM department_list WHERE id = " . intval($dean_dept_id) . " LIMIT 1");
    $college_id = ($college_q && $college_q->num_rows > 0) ? intval($college_q->fetch_assoc()['college_office_id']) : 0;

    if ($college_id > 0) {
        // Get all rating periods ordered chronologically
        $all_periods = [];
        $apq = $conn->query("SELECT id, semester, year, code FROM rating_period ORDER BY year ASC, FIELD(semester, '1st Semester', '2nd Semester', 'Summer')");
        while ($ap = $apq->fetch_assoc()) $all_periods[] = $ap;

        // Build period filter and codes for each period (like period_builder does)
        $period_data = [];
        foreach ($all_periods as $ap) {
            $sem = $ap['semester']; $yr = $ap['year'];
            $codes = [$ap['code']];
            $codes[] = epes_short_code($sem, $yr);
            $codes[] = $sem . ' ' . $yr;
            // data-driven: match semester+year
            $sem_compact = str_replace(' ', '', $sem);
            $like = $conn->real_escape_string($sem_compact . '-' . $yr);
            $short = epes_short_code($sem, $yr);
            $dq = $conn->query("SELECT DISTINCT rating_period FROM task_progress WHERE rating_period <> '' AND (rating_period LIKE '%$like%' OR rating_period LIKE '%$short%')");
            while ($dq && $r = $dq->fetch_assoc()) $codes[] = $r['rating_period'];
            $rq = $conn->query("SELECT DISTINCT rating_period FROM ratings WHERE rating_period <> '' AND (rating_period LIKE '%$like%' OR rating_period LIKE '%$short%')");
            while ($rq && $r = $rq->fetch_assoc()) $codes[] = $r['rating_period'];
            $codes = array_values(array_unique(array_filter($codes)));
            $in = implode("','", array_map([$conn, 'real_escape_string'], $codes));
            $pf = " AND rating_period IN ('$in')";
            $period_data[] = [
                'id' => $ap['id'],
                'label' => $sem . ' ' . $yr,
                'codes' => $codes,
                'filter' => $pf,
            ];
        }

        // All faculty in departments under this college, excluding the dean themselves
        $cfq = $conn->query("
            SELECT e.id, e.firstname, e.lastname, e.position_id, COALESCE(e.designation_id,0) AS designation_id,
                   d.department AS dept_name
            FROM employee_list e
            LEFT JOIN department_list d ON e.department_id = d.id
            WHERE d.college_office_id = $college_id AND e.id != $eval_id
            ORDER BY d.department, e.lastname, e.firstname
        ");
        while ($f = $cfq->fetch_assoc()) {
            $fid = (int)$f['id'];
            $fpos = (int)$f['position_id'];
            $fdes = (int)$f['designation_id'];

            // Compute rating for each period — only periods where faculty has
            // verified submissions count. Missing rating breaks the streak.
            $streak = 0;
            $streak_details = [];
            $current_period_rating = null;

            foreach ($period_data as $pd) {
                $vq = $conn->query("SELECT COUNT(*) AS cnt FROM task_progress WHERE faculty_id=$fid AND progress='Verified' {$pd['filter']}");
                $vcount = $vq ? (int)$vq->fetch_assoc()['cnt'] : 0;
                if ($vcount === 0) {
                    $streak = 0;
                    $streak_details = [];
                    continue;
                }
                $rating = computeWeightedRating($conn, $fid, $fpos, $fdes, $pd['codes'][0] ?? '', $pd['filter']);
                if ($rating === null || $rating <= 0) {
                    $streak = 0;
                    $streak_details = [];
                    continue;
                }

                // Track the selected/current period rating for display
                if ($pd['label'] === $period_label) $current_period_rating = $rating;

                if ($rating <= 3.60) {
                    $streak++;
                    $streak_details[] = ['label' => $pd['label'], 'rating' => $rating];
                } else {
                    $streak = 0;
                    $streak_details = [];
                }
            }

            // Flag if 3 consecutive periods ≤ 3.60
            if ($streak >= 3) {
                $last_three = array_slice($streak_details, -3);
                $display_rating = $current_period_rating ?? $last_three[count($last_three)-1]['rating'];

                // Upsert into intervention_flags so admin/faculty_list also sees it
                $periods_json = json_encode(array_map(function($s) use ($conn) {
                    return $conn->real_escape_string($s['label']);
                }, $last_three));
                $ratings_json = json_encode($last_three);
                $stmt = $conn->prepare("INSERT IGNORE INTO intervention_flags (employee_id, flag_type, consecutive_periods, overall_ratings) VALUES (?, '3_CONSECUTIVE_LOW', ?, ?)");
                $stmt->bind_param('iss', $fid, $periods_json, $ratings_json);
                $stmt->execute();
                $stmt->close();

                $flagged_faculty[] = [
                    'name' => $f['lastname'] . ', ' . $f['firstname'],
                    'dept' => $f['dept_name'] ?? 'N/A',
                    'faculty_id' => $fid,
                    'rating' => $display_rating,
                    'adjectival' => getAdjectivalRating($display_rating),
                    'streak' => $streak,
                    'streak_details' => $last_three,
                ];
            }
        }
    }
}

// Faculty table data for dept head or VP
$fac_table = [];
if(!$is_dean) {
    if ($is_vp) {
        $fq = $conn->query("
            SELECT e.id, e.firstname, e.lastname, e.designation_id, e.position_id,
                   d.designation as designation_name, p.position as position_name
            FROM employee_list e
            LEFT JOIN designation_list d ON e.designation_id=d.id
            LEFT JOIN position_list p ON e.position_id=p.id
            WHERE e.evaluator_id=$eval_list_id
            ORDER BY e.lastname, e.firstname
        ");
    } else {
        $fq = $conn->query("
            SELECT e.id, e.firstname, e.lastname, e.designation_id, e.position_id,
                   d.designation as designation_name, p.position as position_name
            FROM employee_list e
            LEFT JOIN designation_list d ON e.designation_id=d.id
            LEFT JOIN position_list p ON e.position_id=p.id
            WHERE e.department_id=$eval_dept_id AND e.id != $eval_id
            ORDER BY e.lastname, e.firstname
        ");
    }
    while($f = $fq->fetch_assoc()) {
        $fid = (int)$f['id'];
        $fpos = (int)$f['position_id'];
        $fdes = (int)$f['designation_id'];
        $tq = $conn->query("SELECT COUNT(*) as cnt FROM task_list t WHERE t.is_active=1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id=0 OR t.academic_rank_id=$fpos) AND " . task_designation_match($fdes) . " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id=$fpos)");
        $targets = (int)$tq->fetch_assoc()['cnt'];
        $sq = $conn->query("SELECT COUNT(DISTINCT task_id) as submitted, SUM(CASE WHEN progress='Verified' THEN 1 ELSE 0 END) as verified FROM task_progress WHERE faculty_id=$fid $period_filter");
        $subs = $sq->fetch_assoc();
        $avg_rating = (!empty($active_period_code)) ? computeWeightedRating($conn, $fid, $fpos, $fdes, $active_period_code, $period_filter) : null;
        $fac_table[] = [
            'name' => $f['lastname'] . ', ' . $f['firstname'],
            'faculty_id' => $f['id'],
            'designation' => $f['designation_name'] ?? 'Faculty',
            'targets' => $targets,
            'submitted' => (int)$subs['submitted'],
            'verified' => (int)$subs['verified'],
            'completion_pct' => $targets > 0 ? round(((int)$subs['verified'] / $targets) * 100) : 0,
            'rating' => $avg_rating,
            'is_director' => ($fdes == 6),
        ];
    }
}

// Department-wide DPCR rating = average of all faculty ratings in the department (dept head view)
$dept_dpcr = null;
if (!$is_dean && !empty($fac_table)) {
    $dpcr_sum = 0; $dpcr_cnt = 0;
    foreach ($fac_table as $ft) {
        if ($ft['rating'] !== null) { $dpcr_sum += floatval($ft['rating']); $dpcr_cnt++; }
    }
    $dept_dpcr = $dpcr_cnt > 0 ? round($dpcr_sum / $dpcr_cnt, 2) : null;
}

// Recent activity
$recent_where = [];
if (!$is_dean && !$is_vp) $recent_where[] = "e.department_id=$eval_dept_id";
if ($is_vp) $recent_where[] = "e.evaluator_id=$eval_list_id";
if ($period_filter !== '') {
    $recent_where[] = "(" . substr($period_filter, strlen(" AND ")) . ")";
}
$recent_sql = "
    SELECT e.lastname, e.firstname, tp.progress, tp.date_created
    FROM task_progress tp
    INNER JOIN employee_list e ON tp.faculty_id=e.id
    " . (count($recent_where) ? "WHERE " . implode(" AND ", $recent_where) : "") . "
    ORDER BY tp.date_created DESC LIMIT 6
";
$recent = $conn->query($recent_sql);
?>

<!-- STAT TILES -->
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $total_faculty ?></div>
            <div class="stat-label">Faculty<?= $is_dean ? ' (All)' : ($is_vp ? ' (Assigned)' : ' (Dept)') ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-amber">
            <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $for_verif ?></div>
            <div class="stat-label">Awaiting Review</div>
            <?php if($for_verif > 0): ?><div class="stat-sub amber">needs attention</div><?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $verified ?></div>
            <div class="stat-label">Verified</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-teal">
            <div class="stat-icon teal"><i class="fas fa-chart-line"></i></div>
            <?php if(!$is_dean): ?>
            <div class="stat-value"><?= $dept_dpcr !== null ? number_format($dept_dpcr, 2) : '—' ?></div>
            <div class="stat-label">DPCR Rating</div>
            <div class="stat-sub <?= ($dept_dpcr ?? 0) >= 3.61 ? 'green' : (($dept_dpcr ?? 0) >= 2.61 ? 'amber' : 'red') ?>"><?= $dept_dpcr !== null ? getAdjectivalRating($dept_dpcr) : 'No rating' ?></div>
            <?php else: ?>
            <div class="stat-value"><?= $completion_pct ?>%</div>
            <div class="stat-label">Completion</div>
            <div class="stat-sub <?= $completion_pct >= 70 ? 'green' : ($completion_pct >= 40 ? 'amber' : 'red') ?>"><?= $verified ?>/<?= $total_submissions ?> verified</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- DEAN: Intervention Flag Panel — 3 consecutive periods Satisfactory or below -->
<?php if ($is_dean): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="chart-card" style="border-left:4px solid <?= empty($flagged_faculty) ? '#27ae60' : '#e74c3c' ?>;">
            <div class="chart-card-header" style="background:<?= empty($flagged_faculty) ? '#f0fff4' : '#fff5f5' ?>;">
                <span><i class="fas <?= empty($flagged_faculty) ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2" style="color:<?= empty($flagged_faculty) ? '#27ae60' : '#e74c3c' ?>;"></i>Intervention Flags — 3 Consecutive Periods Satisfactory or Below</span>
                <small class="text-muted"><?= count($flagged_faculty) ?> faculty flagged</small>
            </div>
            <?php if (!empty($flagged_faculty)): ?>
            <div class="card-body p-0">
                <div style="overflow-x:auto;">
                <table class="table table-sm table-flat mb-0" style="font-size:0.83rem;">
                    <thead>
                        <tr>
                            <th>Faculty</th>
                            <th>Department</th>
                            <th class="text-center">Current Rating</th>
                            <th>3-Period Streak</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flagged_faculty as $ff):
                            $r = floatval($ff['rating']);
                            if ($r >= 2.61) { $rcls = 'info'; $rcolor = '#3498db'; }
                            elseif ($r >= 1.61) { $rcls = 'warning'; $rcolor = '#e67e22'; }
                            else { $rcls = 'danger'; $rcolor = '#e74c3c'; }
                            $streak_str = implode(' → ', array_map(function($s) {
                                return $s['label'] . ' (' . number_format($s['rating'], 2) . ')';
                            }, $ff['streak_details']));
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ff['name']) ?></strong></td>
                            <td><?= htmlspecialchars($ff['dept']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $rcls ?>" style="font-size:0.75rem;" title="<?= htmlspecialchars($ff['adjectival']) ?>"><?= number_format($r, 2) ?></span>
                                <div style="font-size:0.7rem; color:<?= $rcolor ?>; margin-top:2px;"><?= htmlspecialchars($ff['adjectival']) ?></div>
                            </td>
                            <td style="font-size:0.75rem; color:#666;"><?= htmlspecialchars($streak_str) ?></td>
                            <td class="text-right">
                                <a href="index.php?page=evaluation&id=<?= $ff['faculty_id'] ?>" class="btn btn-sm btn-outline-danger" style="font-size:0.75rem;">
                                    <i class="fas fa-clipboard-list mr-1"></i> Review &amp; Intervene
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card-body text-center py-3">
                <p class="text-muted mb-0" style="font-size:0.85rem;"><i class="fas fa-check-circle mr-1" style="color:#27ae60;"></i> No faculty flagged — all rated faculty are performing above Satisfactory across consecutive periods.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CONTENT TABLE -->
<div class="row mb-3">
    <?php if($is_dean): ?>
    <!-- DEAN: Dept Head completion table -->
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-user-tie mr-2" style="color:#4361ee;"></i>Program / Department Heads</span>
                <small class="text-muted"><?= $period_label ?></small>
            </div>
            <div class="card-body p-0">
                <?php if(!empty($dept_head_table)): ?>
                <div style="overflow-x:auto;">
                <table class="table table-sm table-flat mb-0" style="font-size:0.83rem;">
                    <thead>
                        <tr>
                            <th>Dept Head</th>
                            <th>Program</th>
                            <th class="text-center">Faculty</th>
                            <th class="text-center">Targets</th>
                            <th class="text-center">Submitted</th>
                            <th class="text-center">Verified</th>
                            <th class="text-right">Completion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dept_head_table as $dh):
                            $bar_color = $dh['completion_pct'] >= 70 ? '#27ae60' : ($dh['completion_pct'] >= 40 ? '#f39c12' : '#e74c3c');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dh['name']) ?></strong></td>
                            <td><?= htmlspecialchars($dh['program']) ?></td>
                            <td class="text-center"><?= $dh['faculty_count'] ?></td>
                            <td class="text-center"><?= $dh['targets'] ?></td>
                            <td class="text-center"><?= $dh['submitted'] ?></td>
                            <td class="text-center"><?= $dh['verified'] ?></td>
                            <td class="text-right">
                                <div style="display:flex; align-items:center; gap:8px; justify-content:flex-end;">
                                    <div style="flex:1; max-width:80px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden;">
                                        <div style="width:<?= $dh['completion_pct'] ?>%; height:100%; background:<?= $bar_color ?>; border-radius:3px;"></div>
                                    </div>
                                    <span style="font-weight:700; font-size:0.8rem; color:<?= $bar_color ?>; min-width:36px; text-align:right;"><?= $dh['completion_pct'] ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No program/department heads found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- DEPT HEAD: Faculty completion table -->
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-user-graduate mr-2" style="color:#4361ee;"></i><?= $is_vp ? 'Assigned Faculty' : 'Faculty Completion' ?></span>
                <small class="text-muted"><?= $period_label ?></small>
            </div>
            <div class="card-body p-0">
                <?php if(!empty($fac_table)): ?>
                <div style="overflow-x:auto;">
                <table class="table table-sm table-flat mb-0" style="font-size:0.83rem;">
                    <thead>
                        <tr>
                            <th>Faculty</th>
                            <th>Designation</th>
                            <th class="text-center">Targets</th>
                            <th class="text-center">Submitted</th>
                            <th class="text-center">Verified</th>
                            <th class="text-center">Rating</th>
                            <th class="text-right">Completion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fac_table as $ft):
                            $bar_color = $ft['completion_pct'] >= 70 ? '#27ae60' : ($ft['completion_pct'] >= 40 ? '#f39c12' : '#e74c3c');
                        ?>
                        <tr onclick="window.location.href='index.php?page=evaluation&id=<?= $ft['faculty_id'] ?>'" style="cursor:pointer;">
                            <td>
                                <strong><?= htmlspecialchars($ft['name']) ?></strong>
                                <?php if($ft['is_director']): ?>
                                <span class="badge badge-info ml-1" style="font-size:0.65rem;">Director</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($ft['designation']) ?></td>
                            <td class="text-center"><?= $ft['targets'] ?></td>
                            <td class="text-center"><?= $ft['submitted'] ?></td>
                            <td class="text-center"><?= $ft['verified'] ?></td>
                            <td class="text-center">
                                <?php if($ft['rating'] !== null):
                                    $r = floatval($ft['rating']);
                                    if ($r >= 4.75) { $rcls = 'success'; $radj = 'Outstanding'; }
                                    elseif ($r >= 3.61) { $rcls = 'success'; $radj = 'Very Sat.'; }
                                    elseif ($r >= 2.61) { $rcls = 'info'; $radj = 'Satis.'; }
                                    elseif ($r >= 1.61) { $rcls = 'warning'; $radj = 'Unsat.'; }
                                    else { $rcls = 'danger'; $radj = 'Poor'; }
                                ?>
                                <span class="badge badge-<?= $rcls ?>" style="font-size:0.7rem;" title="<?= $radj ?>"><?= number_format($r, 2) ?></span>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:0.75rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div style="display:flex; align-items:center; gap:8px; justify-content:flex-end;">
                                    <div style="flex:1; max-width:80px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden;">
                                        <div style="width:<?= $ft['completion_pct'] ?>%; height:100%; background:<?= $bar_color ?>; border-radius:3px;"></div>
                                    </div>
                                    <span style="font-weight:700; font-size:0.8rem; color:<?= $bar_color ?>; min-width:36px; text-align:right;"><?= $ft['completion_pct'] ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0"><?= $is_vp ? 'No faculty assigned to you' : 'No faculty in department' ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- RECENT ACTIVITY -->
<div class="row mb-3">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-history mr-2" style="color:#1abc9c;"></i>Recent Activity</span>
            </div>
            <div class="card-body p-0">
                <?php if($recent && $recent->num_rows > 0): ?>
                <div class="activity-list">
                    <?php while($r = $recent->fetch_assoc()):
                        $dot = $r['progress'] == 'Verified' ? 'green' : ($r['progress'] == 'For Verification' ? 'amber' : '');
                        $time = date('M d', strtotime($r['date_created']));
                    ?>
                    <div class="activity-item">
                        <span class="activity-dot <?= $dot ?>"></span>
                        <span class="activity-name"><?= htmlspecialchars($r['lastname'] . ', ' . $r['firstname']) ?></span>
                        <span class="activity-status"><?= $r['progress'] ?></span>
                        <span class="activity-time"><?= $time ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
