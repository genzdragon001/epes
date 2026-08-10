<?php include 'db_connect.php';
require_once 'includes/rating_functions.php';
require_once 'includes/access_control.php';
require_once 'includes/period_builder.php';

$login_type = $_SESSION['login_type'];

// Fetch full rating_period row for date display
$period_dates = null;
if ($selected_period) {
    $sel_sem_db = $conn->real_escape_string($selected_period['semester']);
    $sel_yr_db  = $conn->real_escape_string($selected_period['year']);
    $pd_qry = $conn->query("SELECT * FROM rating_period WHERE semester = '$sel_sem_db' AND year = '$sel_yr_db' ORDER BY id LIMIT 1");
    if ($pd_qry && $pd_qry->num_rows > 0) {
        $period_dates = $pd_qry->fetch_assoc();
    }
}

// ---------- Build faculty data (same access control as faculty_list.php) ----------
$faculty_data = [];

if ($is_admin) {
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        ORDER BY dep.department, e.lastname
    ");
} elseif ($is_dean) {
    // Dean sees ALL faculty in their college (via department_list.college_office_id)
    $college_q = $conn->query("SELECT college_office_id FROM department_list WHERE id = " . intval($dept_id) . " LIMIT 1");
    $college_id = ($college_q && $college_q->num_rows > 0) ? intval($college_q->fetch_assoc()['college_office_id']) : 0;
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        WHERE dep.college_office_id = $college_id
          AND e.id != $eval_id
        ORDER BY dep.department, e.lastname
    ");
} elseif ($is_dept_head) {
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
    // VP / other evaluators: show faculty assigned via evaluator_id
    // $eval_list_id is already mapped by access_control.php
    $result = $conn->query("
        SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id,
               e.designation_id, dl.designation, dep.department, e.position_id, p.position
        FROM employee_list e
        LEFT JOIN designation_list dl ON e.designation_id = dl.id
        LEFT JOIN department_list dep ON e.department_id = dep.id
        LEFT JOIN position_list p ON e.position_id = p.id
        WHERE e.evaluator_id = $eval_list_id
        ORDER BY e.lastname
    ");
}

$total_faculty = 0;
$total_rated = 0;
$total_verified = 0;
$total_pending = 0;
$total_for_verif = 0;
$dept_stats = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['id'];
        $total_faculty++;

        // Task progress stats (period-filtered — same as faculty_list.php)
        $stats = $conn->query("
            SELECT
                COUNT(DISTINCT task_id) as total_tasks,
                SUM(CASE WHEN progress = 'Verified' THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN progress = 'For Verification' THEN 1 ELSE 0 END) as for_verification,
                SUM(CASE WHEN progress = 'N/A' THEN 1 ELSE 0 END) as na_count
            FROM task_progress WHERE faculty_id = $emp_id $period_filter
        ")->fetch_assoc();
        $row['total_tasks'] = $stats['total_tasks'] ?? 0;
        $row['verified'] = $stats['verified'] ?? 0;
        $row['for_verification'] = $stats['for_verification'] ?? 0;
        $row['na_count'] = $stats['na_count'] ?? 0;
        $row['pending'] = $row['total_tasks'] - $row['verified'] - $row['for_verification'] - $row['na_count'];

        // Weighted rating for current period (same logic as faculty_list.php)
        if (!empty($active_period_code)) {
            $pos_id = $row['position_id'] ?? 0;
            $desig_id = $row['designation_id'] ?? 0;
            $row['avg_rating'] = computeWeightedRating($conn, $emp_id, $pos_id, $desig_id, $active_period_code, $period_filter);
            if ($row['avg_rating'] !== null) $total_rated++;
        } else {
            $row['avg_rating'] = null;
        }

        // Eval status determination
        if ($row['total_tasks'] == 0) {
            $row['eval_status'] = 'No Tasks';
            $row['status_class'] = 'secondary';
        } elseif ($row['verified'] == $row['total_tasks'] - $row['na_count']) {
            $row['eval_status'] = 'Completed';
            $row['status_class'] = 'success';
        } elseif ($row['verified'] > 0 || $row['for_verification'] > 0) {
            $row['eval_status'] = 'In Progress';
            $row['status_class'] = 'info';
        } else {
            $row['eval_status'] = 'Pending';
            $row['status_class'] = 'warning';
        }

        // Accumulate totals
        $total_verified += $row['verified'];
        $total_for_verif += $row['for_verification'];
        $total_pending += $row['pending'];

        // Department stats
        $d_id = $row['department_id'] ?? 0;
        if (!isset($dept_stats[$d_id])) {
            $dept_stats[$d_id] = ['name' => $row['department'] ?? 'Unknown', 'count' => 0, 'rated' => 0, 'completed' => 0];
        }
        $dept_stats[$d_id]['count']++;
        if ($row['avg_rating'] !== null) $dept_stats[$d_id]['rated']++;
        if ($row['eval_status'] === 'Completed') $dept_stats[$d_id]['completed']++;

        $faculty_data[] = $row;
    }
}
?>

<div class="col-lg-12">

    <!-- ===== PERIOD SELECTOR ===== -->
    <?php if (count($real_periods) > 0):
        $start = ($period_dates && $period_dates['start_date']) ? date('M d, Y', strtotime($period_dates['start_date'])) : '—';
        $end = ($period_dates && $period_dates['end_date']) ? date('M d, Y', strtotime($period_dates['end_date'])) : '—';
        $nd_start = ($period_dates && $period_dates['non_desig_start_date']) ? date('M d, Y', strtotime($period_dates['non_desig_start_date'])) : $start;
        $nd_end = ($period_dates && $period_dates['non_desig_end_date']) ? date('M d, Y', strtotime($period_dates['non_desig_end_date'])) : $end;
        $sel_key = epes_period_key($selected_period['semester'], $selected_period['year']);
    ?>
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="info-box bg-gradient-primary">
                <span class="info-box-icon"><i class="fa fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Rating Period</span>
                    <span class="info-box-number"><?= htmlspecialchars($selected_period['semester']) ?> <?= htmlspecialchars($selected_period['year']) ?> <?= !empty($selected_period['is_active']) ? '<span class="badge badge-light ml-1">Current</span>' : '' ?></span>
                    <small>Designated: <?= $start ?> — <?= $end ?> | Non-Desig/COS: <?= $nd_start ?> — <?= $nd_end ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center justify-content-end">
            <label class="mr-2 mb-0 text-muted" style="font-size:0.85rem;"><i class="fa fa-filter"></i> Period:</label>
            <select id="period_selector" class="form-control form-control-sm"
                    onchange="window.location.href='index.php?page=employee_eval_status&period='+encodeURIComponent(this.value)"
                    style="width:auto; font-size:0.85rem; max-width:220px;">
                <?php foreach ($real_periods as $p):
                    $pkey = epes_period_key($p['semester'], $p['year']);
                    $opt_label = $p['semester'] . ' ' . $p['year'] . (!empty($p['is_active']) ? ' (Current)' : '');
                ?>
                <option value="<?= htmlspecialchars($pkey) ?>" <?= $pkey === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== SUMMARY CARDS ===== -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-info">
                <div class="inner"><h3><?= $total_faculty ?></h3><p>Total Faculty</p></div>
                <i class="fa fa-users"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-success">
                <div class="inner"><h3><?= $total_rated ?></h3><p>Rated</p></div>
                <i class="fa fa-star"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3><?= $total_for_verif ?></h3><p>For Verification</p></div>
                <i class="fa fa-clock"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-danger">
                <div class="inner"><h3><?= $total_pending ?></h3><p>Pending Submissions</p></div>
                <i class="fa fa-exclamation-circle"></i>
            </div>
        </div>
    </div>

    <!-- ===== MAIN CARD ===== -->
    <div class="card card-outline card-success">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:6px;">
            <h5 class="card-title mb-0">
                <i class="fa fa-user-friends"></i>
                <?php
                if($is_admin) echo 'Faculty Evaluation Status (All)';
                elseif($is_dean) echo 'Faculty — College Eval Status';
                elseif($is_dept_head) echo 'Faculty Under My Department — Eval Status';
                else echo 'Assigned Faculty — Eval Status';
                ?>
            </h5>
            <span class="badge badge-light"><?= $total_faculty ?> faculty | <?= $total_rated ?> rated (<?= $active_period_code ?: 'N/A' ?>)</span>
        </div>
        <div class="card-body">

            <?php if(count($faculty_data) > 0): ?>
            <!-- Search/Filter Bar -->
            <div class="search-bar">
                <div class="position-relative" style="max-width: 400px;">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" class="form-control" id="evalSearch" placeholder="Search by name, department, or status..." onkeyup="filterEval()">
                </div>
            </div>

            <!-- ===== DESKTOP TABLE ===== -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-striped table-bordered table-sm" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 30px;">#</th>
                            <th>Faculty Name</th>
                            <th><?= $is_admin ? 'Department / Position' : 'Designation' ?></th>
                            <th class="text-center" style="width: 50px;">Tasks</th>
                            <th class="text-center" style="width: 50px;">Verified</th>
                            <th class="text-center" style="width: 50px;">For Verif.</th>
                            <th class="text-center" style="width: 50px;">Pending</th>
                            <th class="text-center" style="width: 100px;">IPCR (<?= $active_period_code ?: 'N/A' ?>)</th>
                            <th class="text-center" style="width: 100px;">Eval Status</th>
                            <th class="text-center" style="width: 100px;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($faculty_data as $row):
                            $avg_r = $row['avg_rating'];
                            if ($avg_r !== null) {
                                if ($avg_r >= 4.75) { $adj = 'Outstanding'; $cls = 'success'; }
                                elseif ($avg_r >= 3.61) { $adj = 'Very Satisfactory'; $cls = 'success'; }
                                elseif ($avg_r >= 2.61) { $adj = 'Satisfactory'; $cls = 'info'; }
                                elseif ($avg_r >= 1.61) { $adj = 'Unsatisfactory'; $cls = 'warning'; }
                                else { $adj = 'Poor'; $cls = 'danger'; }
                            }
                            $progress_pct = ($row['total_tasks'] > 0 && ($row['verified'] + $row['na_count']) > 0)
                                ? round((($row['verified'] + $row['na_count']) / $row['total_tasks']) * 100) : 0;
                        ?>
                        <tr class="eval-row" data-search="<?= htmlspecialchars(strtolower($row['lastname'] . ' ' . $row['firstname'] . ' ' . ($row['department'] ?? '') . ' ' . ($row['designation'] ?? '') . ' ' . $row['eval_status'])) ?>">
                            <td class="text-center font-weight-bold"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']) ?></strong></td>
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
                            <td class="text-center"><span class="badge badge-secondary"><?= $row['total_tasks'] ?></span></td>
                            <td class="text-center">
                                <?php if ($row['verified'] > 0): ?>
                                    <span class="badge badge-success"><?= $row['verified'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['for_verification'] > 0): ?>
                                    <span class="badge badge-warning"><?= $row['for_verification'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['pending'] > 0): ?>
                                    <span class="badge badge-danger"><?= $row['pending'] ?></span>
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
                            <td class="text-center">
                                <span class="badge badge-<?= $row['status_class'] ?>" style="font-size: 0.8rem;">
                                    <?= $row['eval_status'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="progress mb-0" style="height: 15px;">
                                    <div class="progress-bar bg-<?= $row['status_class'] == 'success' ? 'success' : ($row['status_class'] == 'info' ? 'info' : 'warning') ?>" role="progressbar" style="width: <?= $progress_pct ?>%">
                                        <?= $progress_pct ?>%
                                    </div>
                                </div>
                                <small class="text-muted"><?= $row['verified'] + $row['na_count'] ?>/<?= $row['total_tasks'] ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== MOBILE CARDS ===== -->
            <div class="d-md-none" id="mobileList">
                <?php $i = 1; foreach($faculty_data as $row):
                    $avg_r = $row['avg_rating'];
                    if ($avg_r !== null) {
                        if ($avg_r >= 4.75) { $adj = 'Outstanding'; $cls = 'success'; }
                        elseif ($avg_r >= 3.61) { $adj = 'Very Satisfactory'; $cls = 'success'; }
                        elseif ($avg_r >= 2.61) { $adj = 'Satisfactory'; $cls = 'info'; }
                        elseif ($avg_r >= 1.61) { $adj = 'Unsatisfactory'; $cls = 'warning'; }
                        else { $adj = 'Poor'; $cls = 'danger'; }
                    }
                    $progress_pct = ($row['total_tasks'] > 0 && ($row['verified'] + $row['na_count']) > 0)
                        ? round((($row['verified'] + $row['na_count']) / $row['total_tasks']) * 100) : 0;
                ?>
                <div class="eval-card" data-search="<?= htmlspecialchars(strtolower($row['lastname'] . ' ' . $row['firstname'] . ' ' . ($row['department'] ?? '') . ' ' . ($row['designation'] ?? '') . ' ' . $row['eval_status'])) ?>">
                    <div class="eval-card-top">
                        <div class="eval-card-name">
                            <strong><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></strong>
                        </div>
                        <span class="badge badge-<?= $row['status_class'] ?> eval-card-status"><?= $row['eval_status'] ?></span>
                    </div>
                    <div class="eval-card-meta">
                        <?php if ($is_admin): ?>
                            <span class="text-muted"><?= htmlspecialchars($row['department'] ?? 'N/A') ?><?php if (!empty($row['position'])): ?> · <?= htmlspecialchars($row['position']) ?><?php endif; ?></span>
                        <?php else: ?>
                            <span class="text-muted"><?= htmlspecialchars($row['designation'] ?? 'Faculty') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="eval-card-stats">
                        <span class="eval-stat"><i class="fa fa-tasks text-muted"></i> <?= $row['total_tasks'] ?> tasks</span>
                        <span class="eval-stat"><i class="fa fa-check-circle text-success"></i> <?= $row['verified'] ?> verified</span>
                        <span class="eval-stat"><i class="fa fa-clock text-warning"></i> <?= $row['for_verification'] ?> for verif.</span>
                        <?php if ($avg_r !== null): ?>
                            <span class="badge badge-<?= $cls ?> eval-card-rating"><?= number_format($avg_r, 2) ?> <small><?= $adj ?></small></span>
                        <?php else: ?>
                            <span class="text-muted eval-card-rating"><small>Not Rated</small></span>
                        <?php endif; ?>
                    </div>
                    <div class="progress eval-card-progress" style="height: 10px;">
                        <div class="progress-bar bg-<?= $row['status_class'] == 'success' ? 'success' : ($row['status_class'] == 'info' ? 'info' : 'warning') ?>" style="width: <?= $progress_pct ?>%"><?= $progress_pct ?>%</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No faculty records found</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== DEPARTMENT SUMMARY ===== -->
    <?php if (!empty($dept_stats)): ?>
    <div class="card card-outline card-info mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fa fa-chart-bar"></i> Department Eval Summary (<?= $active_period_code ?>)</h5>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Department</th>
                        <th class="text-center">Faculty</th>
                        <th class="text-center">Rated</th>
                        <th class="text-center">Completed</th>
                        <th class="text-center">Coverage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dept_stats as $ds): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ds['name']) ?></strong></td>
                        <td class="text-center"><?= $ds['count'] ?></td>
                        <td class="text-center"><?= $ds['rated'] ?></td>
                        <td class="text-center"><?= $ds['completed'] ?></td>
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
    .eval-row { transition: background 0.15s; }
    .eval-row:hover { background-color: rgba(23,162,184,0.08) !important; }
    .search-bar { margin-bottom: 12px; }
    .search-bar input { border-radius: 20px; padding-left: 36px; }
    .search-bar .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #adb5bd; }
    .small-box { border-radius: .25rem; margin-bottom: 1rem; }
    .small-box .inner { padding: 10px 15px; }
    .small-box h3 { margin: 0; font-size: 2rem; font-weight: 700; }
    .small-box p { margin: 0; font-size: 0.85rem; }
    .small-box > i { position: absolute; right: 15px; bottom: 10px; font-size: 3rem; opacity: 0.3; }

    /* ---- Mobile card layout ---- */
    .eval-card {
        background: #fff; border: 1px solid #e9ecef; border-radius: 8px;
        padding: 10px 12px; margin-bottom: 8px; transition: box-shadow 0.15s, border-color 0.15s;
    }
    .eval-card:hover { border-color: #17a2b8; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .eval-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .eval-card-name { flex: 1; font-size: 0.95rem; line-height: 1.3; }
    .eval-card-status { font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; white-space: nowrap; }
    .eval-card-meta { margin-top: 2px; font-size: 0.78rem; }
    .eval-card-stats { margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px 12px; font-size: 0.75rem; align-items: center; }
    .eval-stat { display: inline-flex; align-items: center; gap: 3px; }
    .eval-card-rating { font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; }
    .eval-card-progress { margin-top: 8px; }

    @media (max-width: 767px) {
        .search-bar input { font-size: 0.85rem; }
        .info-box { min-height: 60px; }
        .info-box-icon { width: 50px; }
        .info-box-number { font-size: 1rem; }
        .small-box h3 { font-size: 1.5rem; }
    }
</style>

<script>
function filterEval() {
    var input = document.getElementById('evalSearch');
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
    }

    // Mobile cards
    var cards = document.querySelectorAll('.eval-card');
    cards.forEach(function(card) {
        var searchData = (card.getAttribute('data-search') || '').toLowerCase();
        card.style.display = searchData.indexOf(filter) !== -1 ? '' : 'none';
    });
}
</script>