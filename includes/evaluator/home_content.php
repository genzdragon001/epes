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
    $is_vp   = ($eval_desig_id == 4);
}

if($is_dean) {
    $total_faculty      = $conn->query("SELECT COUNT(*) FROM employee_list WHERE id != $eval_id")->fetch_row()[0];
    $total_submissions  = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.id!=$eval_id $period_filter")->fetch_row()[0];
    $verified           = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.id!=$eval_id AND tp.progress='Verified' $period_filter")->fetch_row()[0];
    $for_verif          = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id=e.id WHERE e.id!=$eval_id AND tp.progress='For Verification' $period_filter")->fetch_row()[0];
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

// Faculty table data for dept head
$fac_table = [];
if(!$is_dean) {
    $fq = $conn->query("
        SELECT e.id, e.firstname, e.lastname, e.designation_id, e.position_id,
               d.designation as designation_name, p.position as position_name
        FROM employee_list e
        LEFT JOIN designation_list d ON e.designation_id=d.id
        LEFT JOIN position_list p ON e.position_id=p.id
        WHERE e.department_id=$eval_dept_id AND e.id != $eval_id
        ORDER BY e.lastname, e.firstname
    ");
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
if (!$is_dean) $recent_where[] = "e.department_id=$eval_dept_id";
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
            <div class="stat-label">Faculty<?= $is_dean ? ' (All)' : ' (Dept)' ?></div>
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
                <span><i class="fas fa-user-graduate mr-2" style="color:#4361ee;"></i>Faculty Completion</span>
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
                <p class="text-muted text-center py-4 mb-0">No faculty in department</p>
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
