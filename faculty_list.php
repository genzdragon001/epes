<?php include 'db_connect.php';
$login_type = $_SESSION['login_type'];
$eval_id = intval($_SESSION['login_id']);
$is_admin = ($login_type == 2);
$is_dean = false;
$is_dept_head = false;
$dept_id = 0;

if (!$is_admin) {
    $stmt_type = $conn->prepare("SELECT type, department_id FROM evaluator_list WHERE id = ?");
    $stmt_type->bind_param("i", $eval_id);
    $stmt_type->execute();
    $stmt_type->bind_result($eval_type, $dept_id);
    $stmt_type->fetch();
    $stmt_type->close();
    
    $is_dean = ($eval_type == 1);
    $is_dept_head = ($eval_type == 0);
}

// Fetch current rating periods
$periods = [];
$rp_qry = $conn->query("
    SELECT * FROM rating_period 
    WHERE (period_type, id) IN (SELECT period_type, MAX(id) FROM rating_period GROUP BY period_type)
    ORDER BY FIELD(period_type, 'IPCR', 'DP', 'OPCR')
");
while ($rp = $rp_qry->fetch_assoc()) {
    $periods[$rp['period_type']] = $rp;
}

// Current IPCR period code for faculty rating lookup
$active_period_code = '';
if (!empty($periods['IPCR'])) {
    $active_period_code = $periods['IPCR']['semester'] . '-' . $periods['IPCR']['year'];
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
        
        // Average rating for current period
        if (!empty($active_period_code)) {
            $rating_qry = $conn->query("
                SELECT AVG((efficiency + timeliness + quality) / 3) as overall,
                       COUNT(*) as rated_count
                FROM ratings
                WHERE employee_id = $emp_id
                AND rating_period = '$active_period_code'
                AND efficiency > 0 AND timeliness > 0 AND quality > 0
            ")->fetch_assoc();
            $row['avg_rating'] = $rating_qry['rated_count'] > 0 ? round($rating_qry['overall'], 2) : null;
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

    <!-- ===== RATING PERIOD OVERVIEW BAR ===== -->
    <?php if (!empty($periods)): ?>
    <div class="row mb-3">
        <?php foreach (['IPCR', 'DP', 'OPCR'] as $pt): ?>
        <?php if (isset($periods[$pt])): 
            $p = $periods[$pt];
            $colors = ['IPCR' => 'primary', 'DP' => 'warning', 'OPCR' => 'danger'];
            $icons = ['IPCR' => 'fa-user', 'DP' => 'fa-building', 'OPCR' => 'fa-sitemap'];
            $labels = ['IPCR' => 'Individual', 'DP' => 'Department', 'OPCR' => 'Office'];
            $start = $p['start_date'] ? date('M d, Y', strtotime($p['start_date'])) : '—';
            $end = $p['end_date'] ? date('M d, Y', strtotime($p['end_date'])) : '—';
        ?>
        <div class="col-md-4">
            <div class="info-box bg-gradient-<?= $colors[$pt] ?>">
                <span class="info-box-icon"><i class="fa <?= $icons[$pt] ?>"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><?= $pt ?> <small>(<?= $labels[$pt] ?>)</small></span>
                    <span class="info-box-number"><?= htmlspecialchars($p['semester']) ?> <?= htmlspecialchars($p['year']) ?></span>
                    <small><?= $start ?> — <?= $end ?></small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
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
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered table-sm" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 30px;">#</th>
                            <th>Faculty Name</th>
                            <th><?= $is_admin ? 'Department / Position' : 'Designation' ?></th>
                            <th class="text-center" style="width: 70px;">Tasks</th>
                            <th class="text-center" style="width: 70px;">Verified</th>
                            <th class="text-center" style="width: 90px;">Rating (<?= $active_period_code ?: 'N/A' ?>)</th>
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
                        <tr class="<?= $flagged ? 'table-warning' : '' ?>">
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
                            <td class="text-center">
                                <?php if ($avg_r !== null): ?>
                                    <span class="badge badge-<?= $cls ?> font-weight-bold" style="font-size: 0.9rem;">
                                        <?= number_format($avg_r, 2) ?>
                                    </span>
                                    <br><small class="text-muted"><?= $adj ?></small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($flagged): ?>
                                    <span class="badge badge-warning"><i class="fa fa-exclamation"></i> Needs Review</span>
                                <?php elseif ($avg_r !== null && $avg_r >= 3.61): ?>
                                    <span class="badge badge-success"><i class="fa fa-check"></i> Good</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">—</span>
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
    .card-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
    .card-title { margin: 0; font-weight: 600; }
    .info-box { min-height: 80px; }
    .info-box-icon { display: flex; align-items: center; justify-content: center; width: 70px; }
    .table td { vertical-align: middle; }
</style>
