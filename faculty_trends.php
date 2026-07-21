<?php
/**
 * Faculty Trend Analysis — IPCR / DP / OPCR over time
 * Accessible by admin, dean, department head, and faculty (own record only)
 */
include 'db_connect.php';
require_once 'includes/period_builder.php';

session_start();
if (!isset($_SESSION['login_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'login_check.php';

$login_type = $_SESSION['login_type'] ?? -1;
$login_id   = intval($_SESSION['login_id'] ?? 0);
$is_evaluator_flag = !empty($_SESSION['is_evaluator']);

// Determine context faculty
$context_faculty_id   = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
$context_dept_head_id = isset($_GET['dept_head_id']) ? intval($_GET['dept_head_id']) : 0;

if ($login_type == 0 && !$is_evaluator_flag) {
    // Plain faculty — only own record
    $context_faculty_id   = $login_id;
    $context_dept_head_id = 0;
} elseif ($login_type == 2) {
    // Admin — can view any faculty
} elseif ($login_type == 1 || ($login_type == 0 && $is_evaluator_flag)) {
    require_once 'auth_helper.php';
    if (is_dean($conn)) {
        // Dean sees all — no restriction
    } else {
        // Dept head — restrict to their department
        $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $login_id);
        $stmt->execute();
        $eval_dept = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $eval_department_id = intval($eval_dept['department_id'] ?? 0);
        if ($eval_department_id > 0 && $context_faculty_id > 0) {
            $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $context_faculty_id);
            $stmt->execute();
            $emp_dept = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (intval($emp_dept['department_id'] ?? -1) !== $eval_department_id) {
                $context_faculty_id = 0;
            }
        }
    }
}

// Load all periods from canonical table
$all_periods = [];
$p_qry = $conn->query("
    SELECT id, semester, year, code
    FROM rating_period
    ORDER BY year ASC, FIELD(semester, '1st Semester', '2nd Semester', 'Summer')
");
while ($p = $p_qry->fetch_assoc()) {
    $all_periods[$p['id']] = [
        'label' => $p['semester'] . ' ' . $p['year'],
        'code'  => $p['code'],
        'id'    => $p['id'],
    ];
}

// Build period codes for IN() queries
$all_codes = array_column($all_periods, 'code');
if (!empty($all_codes)) {
    $codes_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $all_codes)) . "'";
} else {
    $codes_str = "''";
}

// IPCR trend: compute per faculty per period using same weighted logic
$ipcr_trend = [];
foreach ($all_periods as $period) {
    $period_id = $period['id'];
    $code      = $period['code'];

    if ($context_faculty_id > 0) {
        $stmt = $conn->prepare("
            SELECT 
                r.task_id,
                SUM(r.efficiency) as efficiency,
                SUM(r.timeliness) as timeliness,
                SUM(r.quality) as quality,
                t.category,
                t.sub_category
            FROM ratings r
            INNER JOIN task_list t ON r.task_id = t.id
            WHERE r.employee_id = ?
              AND r.rating_period = ?
              AND (r.efficiency > 0 OR r.timeliness > 0 OR r.quality > 0)
            GROUP BY r.task_id
        ");
        $stmt->bind_param('is', $context_faculty_id, $code);
        $stmt->execute();
        $ratings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($ratings)) {
            // Compute weighted rating per category
            $strat_pct = $core_pct = $supp_pct = 0;
            $stmt = $conn->prepare("SELECT * FROM percentage_allocation WHERE position_id = (SELECT position_id FROM employee_list WHERE id = ? LIMIT 1) AND category = 'strategic' AND is_active = 1 LIMIT 1");
            $stmt->bind_param('i', $context_faculty_id);
            $stmt->execute();
            $strat_pct = $stmt->get_result()->fetch_assoc()['percentage'] ?? 0;
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM percentage_allocation WHERE position_id = (SELECT position_id FROM employee_list WHERE id = ? LIMIT 1) AND category = 'core' AND is_active = 1 LIMIT 1");
            $stmt->bind_param('i', $context_faculty_id);
            $stmt->execute();
            $core_pct = $stmt->get_result()->fetch_assoc()['percentage'] ?? 0;
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM percentage_allocation WHERE position_id = (SELECT position_id FROM employee_list WHERE id = ? LIMIT 1) AND category = 'support' AND is_active = 1 LIMIT 1");
            $stmt->bind_param('i', $context_faculty_id);
            $stmt->execute();
            $supp_pct = $stmt->get_result()->fetch_assoc()['percentage'] ?? 0;
            $stmt->close();

            $strat_sum = $strat_cnt = 0;
            $core_sum = $core_cnt = 0;
            $supp_sum = $supp_cnt = 0;
            foreach ($ratings as $r) {
                $e = (float)$r['efficiency'];
                $t = (float)$r['timeliness'];
                $q = (float)$r['quality'];
                $avg = ($e + $t + $q) / 3;
                if ($r['category'] == 'strategic') { $strat_sum += $avg; $strat_cnt++; }
                elseif ($r['category'] == 'core') { $core_sum += $avg; $core_cnt++; }
                elseif ($r['category'] == 'support') { $supp_sum += $avg; $supp_cnt++; }
            }
            $strat_score = $strat_cnt > 0 ? $strat_sum / $strat_cnt : 0;
            $core_score  = $core_cnt  > 0 ? $core_sum  / $core_cnt  : 0;
            $supp_score  = $supp_cnt  > 0 ? $supp_sum  / $supp_cnt  : 0;
            $score = round($strat_score * $strat_pct / 100 + $core_score * $core_pct / 100 + $supp_score * $supp_pct / 100, 2);
            $ipcr_trend[] = ['period_id' => $period_id, 'label' => $period['label'], 'score' => $score];
        }
    }
}

// DP trend: department average per period
$dp_trend = [];
if ($context_faculty_id > 0) {
    $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $context_faculty_id);
    $stmt->execute();
    $f_dept = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $dept_id = intval($f_dept['department_id'] ?? 0);
    if ($dept_id > 0) {
        $sr = $conn->query("
            SELECT cr.source_period_id, rp.semester, rp.year, cr.overall_rating
            FROM cascading_ratings cr
            INNER JOIN rating_period rp ON rp.id = cr.source_period_id
            WHERE cr.level = 'DP' AND cr.department_id = $dept_id
            ORDER BY rp.year ASC, rp.semester ASC
        ");
        while ($row = $sr->fetch_assoc()) {
            $dp_trend[] = ['period_id' => $row['source_period_id'], 'label' => $row['semester'] . ' ' . $row['year'], 'score' => (float)$row['overall_rating']];
        }
    }
}

// OPCR trend: office average per period
$opcr_trend = [];
if ($context_faculty_id > 0) {
    $sr = $conn->query("
        SELECT cr.source_period_id, rp.semester, rp.year, cr.overall_rating
        FROM cascading_ratings cr
        INNER JOIN rating_period rp ON rp.id = cr.source_period_id
        WHERE cr.level = 'OPCR' AND cr.department_id = 0
        ORDER BY rp.year ASC, rp.semester ASC
    ");
    while ($row = $sr->fetch_assoc()) {
        $opcr_trend[] = ['period_id' => $row['source_period_id'], 'label' => $row['semester'] . ' ' . $row['year'], 'score' => (float)$row['overall_rating']];
    }
}

// Merge all series for chart
$all_labels = [];
foreach (array_merge($ipcr_trend, $dp_trend, $opcr_trend) as $pt) {
    $all_labels[$pt['period_id']] = $pt['label'];
}
ksort($all_labels);
$chart_labels = array_values($all_labels);

function seriesForChart($trend, $all_labels) {
    $map = [];
    foreach ($trend as $t) { $map[$t['period_id']] = $t['score']; }
    $out = [];
    foreach ($all_labels as $pid => $lbl) { $out[] = $map[$pid] ?? null; }
    return $out;
}
$ipcr_data  = seriesForChart($ipcr_trend, $all_labels);
$dp_data    = seriesForChart($dp_trend, $all_labels);
$opcr_data  = seriesForChart($opcr_trend, $all_labels);

// Faculty info
$faculty_info = [];
if ($context_faculty_id > 0) {
    $fi = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname, ' ', middlename) as name, department_id FROM employee_list WHERE id = $context_faculty_id LIMIT 1")->fetch_assoc();
    if ($fi) $faculty_info = $fi;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Trend Analysis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f9; }
        .panel { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .chart-container { position: relative; height: 350px; }
        table.data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.data-table th { background: #1a1a2e; color: #fff; padding: 10px; text-align: center; }
        table.data-table td { padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        table.data-table tr:nth-child(even) { background: #f9f9f9; }
        .score-cell { font-weight: bold; }
        .score-excellent { color: #1e8449; }
        .score-very-good { color: #229954; }
        .score-good { color: #f39c12; }
        .score-low { color: #c0392b; }
        .no-data { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="panel">
                    <h3 style="margin-top:0; color:#1a1a2e;">
                        <?php if (!empty($faculty_info)): ?>
                            Trend Analysis: <?= htmlspecialchars($faculty_info['name']) ?>
                        <?php else: ?>
                            Trend Analysis
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted">Compare IPCR, DP, and OPCR ratings across rating periods</p>
                    <a href="index.php" class="btn btn-default btn-sm">&larr; Back to Dashboard</a>
                </div>

                <?php if (empty($all_labels)): ?>
                    <div class="panel">
                        <div class="alert alert-warning">No cascaded ratings available yet. Run cascade first from the Rating Periods page.</div>
                    </div>
                <?php else: ?>
                    <div class="panel">
                        <h4>Trend Chart</h4>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                    <div class="panel">
                        <h4>Detailed Scores</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>IPCR Score</th>
                                    <th>DP Score</th>
                                    <th>OPCR Score</th>
                                    <th>IPCR Rating</th>
                                    <th>DP Rating</th>
                                    <th>OPCR Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($all_labels as $pid => $label):
                                    $ip = $ipcr_data[array_search($label, $chart_labels)] ?? null;
                                    $dp = $dp_data[array_search($label, $chart_labels)] ?? null;
                                    $op = $opcr_data[array_search($label, $chart_labels)] ?? null;
                                    $ip_str = $ip !== null ? number_format($ip, 2) : '<span class="no-data">N/A</span>';
                                    $dp_str = $dp !== null ? number_format($dp, 2) : '<span class="no-data">N/A</span>';
                                    $op_str = $op !== null ? number_format($op, 2) : '<span class="no-data">N/A</span>';
                                    $ip_rating = $ip !== null ? getAdjectivalRating($ip) : '';
                                    $dp_rating = $dp !== null ? getAdjectivalRating($dp) : '';
                                    $op_rating = $op !== null ? getAdjectivalRating($op) : '';
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($label) ?></strong></td>
                                    <td class="score-cell <?= $ip !== null ? ($ip >= 3.61 ? 'score-very-good' : ($ip >= 2.61 ? 'score-good' : 'score-low')) : '' ?>"><?= $ip_str ?></td>
                                    <td class="score-cell <?= $dp !== null ? ($dp >= 3.61 ? 'score-very-good' : ($dp >= 2.61 ? 'score-good' : 'score-low')) : '' ?>"><?= $dp_str ?></td>
                                    <td class="score-cell <?= $op !== null ? ($op >= 3.61 ? 'score-very-good' : ($op >= 2.61 ? 'score-good' : 'score-low')) : '' ?>"><?= $op_str ?></td>
                                    <td><?= $ip_rating ?></td>
                                    <td><?= $dp_rating ?></td>
                                    <td><?= $op_rating ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="panel">
                        <h4>Legend</h4>
                        <p>
                            <strong>IPCR</strong>: Individual Performance Commitment and Review — weighted average of the faculty's own ratings across Strategic, Core, and Support functions.<br>
                            <strong>DP</strong>: Department Performance — average IPCR score of all faculty in the department.<br>
                            <strong>OPCR</strong>: Office Performance — average IPCR score of all Department Heads (Dean's rating).
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (!empty($chart_labels)): ?>
    <script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'IPCR',
                    data: <?= json_encode($ipcr_data) ?>,
                    borderColor: '#1a5276',
                    backgroundColor: 'rgba(26,82,118,0.1)',
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: false
                },
                {
                    label: 'DP',
                    data: <?= json_encode($dp_data) ?>,
                    borderColor: '#117a65',
                    backgroundColor: 'rgba(17,122,101,0.1)',
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: false
                },
                {
                    label: 'OPCR',
                    data: <?= json_encode($opcr_data) ?>,
                    borderColor: '#a04000',
                    backgroundColor: 'rgba(160,64,0,0.1)',
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    title: { display: true, text: 'Score' }
                },
                x: {
                    title: { display: true, text: 'Rating Period' }
                }
            }
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>
