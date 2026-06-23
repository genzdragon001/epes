<?php include 'db_connect.php';

// Access: admin (2), dean (1), or faculty with evaluator designation
$login_type = $_SESSION['login_type'] ?? -1;
$is_evaluator_flag = !empty($_SESSION['is_evaluator']);
if ($login_type != 2 && $login_type != 1 && !($login_type == 0 && $is_evaluator_flag)) {
    echo "<script>alert('Access denied'); window.location.href='index.php';</script>";
    exit;
}

$eval_id = intval($_SESSION['login_id']);
$is_admin = ($login_type == 2);
$is_dean = false;
$dept_id = 0;

if ($login_type == 1 || ($login_type == 0 && $is_evaluator_flag)) {
    require_once 'auth_helper.php';
    $is_dean = is_dean($conn);
    if (!$is_dean) {
        $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ?");
        $stmt->bind_param("i", $eval_id);
        $stmt->execute();
        $stmt->bind_result($dept_id);
        $stmt->fetch();
        $stmt->close();
    }
}

// Get all IPCR periods
$periods_qry = $conn->query("
    SELECT id, semester, year, CONCAT(semester, ' ', year) as label,
           CONCAT(CASE semester WHEN '1st Semester' THEN '1' WHEN '2nd Semester' THEN '2' WHEN 'Summer' THEN 'S' ELSE '1' END, '-', SUBSTRING(year,3,2), SUBSTRING(year,8,2)) as period_code
    FROM rating_period
    ORDER BY year ASC, FIELD(semester, '1st Semester', '2nd Semester')
");
$periods = [];
$period_codes = [];
while ($p = $periods_qry->fetch_assoc()) {
    $periods[] = $p;
    $period_codes[] = $p['period_code'];
}
$period_count = count($periods);

// Get all faculty who have ratings (filter by department for deans)
$dept_filter = ($dept_id > 0 && !$is_dean) ? "AND e.department_id = $dept_id" : "";
$fac_qry = $conn->query("
    SELECT DISTINCT e.id, CONCAT(e.lastname, ', ', e.firstname, ' ', COALESCE(e.middlename,'')) as fullname,
           d.department
    FROM employee_list e
    LEFT JOIN department_list d ON e.department_id = d.id
    INNER JOIN ratings r ON r.employee_id = e.id
    WHERE r.efficiency > 0
    $dept_filter
    ORDER BY e.lastname
");
$faculty_list = [];
while ($f = $fac_qry->fetch_assoc()) {
    $faculty_list[] = $f;
}

// Selected faculty (default: first one)
$selected_faculty = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : ($faculty_list[0]['id'] ?? 0);

// Fetch rating history for selected faculty across all periods
$ratings_data = [];
if ($selected_faculty) {
    $stmt = $conn->prepare("
        SELECT rating_period,
               ROUND(AVG(efficiency), 2) as E,
               ROUND(AVG(NULLIF(timeliness,0)), 2) as T,
               ROUND(AVG(NULLIF(quality,0)), 2) as Q,
               ROUND(AVG((efficiency + COALESCE(NULLIF(timeliness,0),0) + COALESCE(NULLIF(quality,0),0)) / 
                    (1 + CASE WHEN timeliness>0 THEN 1 ELSE 0 END + CASE WHEN quality>0 THEN 1 ELSE 0 END)), 2) as Overall
        FROM ratings
        WHERE employee_id = ? AND efficiency > 0
        GROUP BY rating_period
        ORDER BY rating_period
    ");
    $stmt->bind_param('i', $selected_faculty);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        $ratings_data[$r['rating_period']] = $r;
    }
    $stmt->close();
}

// Build chart arrays aligned with period_codes
$chart_e = $chart_t = $chart_q = $chart_ov = [];
$table_rows = [];
foreach ($period_codes as $pc) {
    if (isset($ratings_data[$pc])) {
        $d = $ratings_data[$pc];
        $chart_e[] = $d['E'];
        $chart_t[] = $d['T'];
        $chart_q[] = $d['Q'];
        $chart_ov[] = $d['Overall'];
        $table_rows[] = $d;
    } else {
        $chart_e[] = null;
        $chart_t[] = null;
        $chart_q[] = null;
        $chart_ov[] = null;
        $table_rows[] = ['E' => null, 'T' => null, 'Q' => null, 'Overall' => null];
    }
}

// Compute trend direction
$trend = '—';
$trend_class = 'text-muted';
$trend_icon = '';
$rated_overalls = array_values(array_filter($chart_ov, fn($v) => $v !== null));
if (count($rated_overalls) >= 2) {
    $first = reset($rated_overalls);
    $last = end($rated_overalls);
    $diff = round($last - $first, 2);
    if ($diff > 0.1) { $trend = '↑ +' . $diff; $trend_class = 'green'; $trend_icon = 'fa-arrow-up'; }
    elseif ($diff < -0.1) { $trend = '↓ ' . $diff; $trend_class = 'red'; $trend_icon = 'fa-arrow-down'; }
    else { $trend = '→ 0.00'; $trend_class = 'text-muted'; $trend_icon = 'fa-minus'; }
}

// Adjectival helper
function adj($score) {
    if ($score === null) return '—';
    if ($score >= 4.75) return 'Outstanding';
    if ($score >= 3.61) return 'Very Satisfactory';
    if ($score >= 2.61) return 'Satisfactory';
    if ($score >= 1.61) return 'Unsatisfactory';
    return 'Poor';
}

// Intervention check
$is_flagged = false;
if ($selected_faculty) {
    $int_qry = $conn->query("SELECT * FROM intervention_flags WHERE employee_id = $selected_faculty AND acknowledged = 0");
    $is_flagged = $int_qry && $int_qry->num_rows > 0;
}

// Period labels (short)
$period_labels = array_map(fn($p) => $p['semester'] . "\n" . $p['year'], $periods);

// Latest period comparison data
$latest_period_code = end($period_codes);
$comp_qry = $conn->query("
    SELECT e.id, CONCAT(e.lastname, ', ', LEFT(e.firstname,1), '.') as shortname,
           ROUND(AVG(r.efficiency),2) as E,
           ROUND(AVG(NULLIF(r.timeliness,0)),2) as T,
           ROUND(AVG(NULLIF(r.quality,0)),2) as Q,
           ROUND(AVG((r.efficiency + COALESCE(NULLIF(r.timeliness,0),0) + COALESCE(NULLIF(r.quality,0),0)) / 
                (1 + CASE WHEN r.timeliness>0 THEN 1 ELSE 0 END + CASE WHEN r.quality>0 THEN 1 ELSE 0 END)),2) as Overall
    FROM ratings r
    JOIN employee_list e ON r.employee_id = e.id
    WHERE r.rating_period = '$latest_period_code'
    AND r.efficiency > 0
    $dept_filter
    GROUP BY e.id
    ORDER BY Overall DESC
");
$comp_labels = []; $comp_e = []; $comp_t = []; $comp_q = []; $comp_ov = [];
while ($c = $comp_qry->fetch_assoc()) {
    $comp_labels[] = $c['shortname'];
    $comp_e[] = $c['E'];
    $comp_t[] = $c['T'];
    $comp_q[] = $c['Q'];
    $comp_ov[] = $c['Overall'];
}

$has_data = count($rated_overalls) > 0;
$latest_ov = $has_data ? end($rated_overalls) : null;
$highest_ov = $has_data ? max($rated_overalls) : null;
$lowest_ov = $has_data ? min($rated_overalls) : null;
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:700; color:#1a1a2e;"><i class="fas fa-chart-line mr-2" style="color:#4361ee;"></i>Performance Trends</h4>
        <span class="text-muted" style="font-size:0.85rem;">Track E · T · Q · Overall across rating periods</span>
    </div>
    <form method="GET" class="form-inline">
        <input type="hidden" name="page" value="faculty_trends">
        <select name="faculty_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:280px;">
            <?php if(empty($faculty_list)): ?>
            <option value="">— No faculty with ratings —</option>
            <?php else: ?>
            <?php foreach ($faculty_list as $f): ?>
            <option value="<?= $f['id'] ?>" <?= $selected_faculty == $f['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['fullname']) ?> — <?= htmlspecialchars($f['department'] ?? 'N/A') ?>
            </option>
            <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </form>
</div>

<?php if ($is_flagged): ?>
<div class="alert-banner mb-4">
    <i class="fas fa-exclamation-triangle mr-2" style="color:#e74c3c;"></i>
    <strong>Intervention Flag:</strong> This faculty has 3 consecutive IPCR ratings ≤ 2.60 and is flagged for intervention.
</div>
<?php endif; ?>

<?php if(empty($faculty_list)): ?>
<div class="chart-card">
    <div class="chart-card-body text-center py-5">
        <i class="fas fa-chart-bar" style="font-size:3rem; color:#adb5bd;"></i>
        <p class="text-muted mt-3 mb-0">No faculty have ratings yet. Ratings are created when evaluators score submitted tasks.</p>
    </div>
</div>
<?php else: ?>

<!-- 4 SUMMARY CARDS -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-purple">
            <div class="stat-icon purple"><i class="fas fa-trophy"></i></div>
            <div class="stat-value"><?= $latest_ov !== null ? number_format($latest_ov, 2) : '—' ?></div>
            <div class="stat-label">Latest Overall</div>
            <div class="stat-sub <?= ($latest_ov ?? 0) >= 3.61 ? 'green' : (($latest_ov ?? 0) >= 2.61 ? 'amber' : 'red') ?>"><?= adj($latest_ov) ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-value"><?= $highest_ov !== null ? number_format($highest_ov, 2) : '—' ?></div>
            <div class="stat-label">Highest Overall</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-red" style="border-left-color:#e74c3c;">
            <div class="stat-icon" style="background:#fdedec; color:#e74c3c;"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-value"><?= $lowest_ov !== null ? number_format($lowest_ov, 2) : '—' ?></div>
            <div class="stat-label">Lowest Overall</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-teal">
            <div class="stat-icon teal"><i class="fas <?= $trend_icon ?>"></i></div>
            <div class="stat-value <?= $trend_class ?>"><?= $trend ?></div>
            <div class="stat-label">Trend</div>
            <div class="stat-sub">Across <?= count($rated_overalls) ?> periods</div>
        </div>
    </div>
</div>

<!-- CHARTS ROW: Trend Line + Rating Details -->
<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-chart-line mr-2" style="color:#4361ee;"></i>E · T · Q · Overall Trend</span>
                <small class="text-muted"><?= htmlspecialchars($faculty_list[array_search($selected_faculty, array_column($faculty_list, 'id'))]['fullname'] ?? '') ?></small>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:350px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-table mr-2" style="color:#1abc9c;"></i>Rating Details</span>
            </div>
            <div class="card-body p-0">
                <div style="overflow-x:auto;">
                <table class="table table-sm table-flat mb-0" style="font-size:0.82rem;">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th class="text-center">E</th>
                            <th class="text-center">T</th>
                            <th class="text-center">Q</th>
                            <th class="text-center">Overall</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $period_count; $i++): 
                            $ov = $table_rows[$i]['Overall'];
                            $ov_cls = $ov === null ? 'text-muted' : ($ov >= 3.61 ? 'green' : ($ov >= 2.61 ? 'amber' : 'red'));
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($periods[$i]['label']) ?></strong></td>
                            <td class="text-center"><?= $table_rows[$i]['E'] !== null ? number_format($table_rows[$i]['E'], 2) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center"><?= $table_rows[$i]['T'] !== null ? number_format($table_rows[$i]['T'], 2) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center"><?= $table_rows[$i]['Q'] !== null ? number_format($table_rows[$i]['Q'], 2) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center font-weight-bold <?= $ov_cls ?>">
                                <?= $ov !== null ? number_format($ov, 2) : '<span class="text-muted">—</span>' ?>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COMPARISON CHART: All Faculty Latest Period -->
<div class="row mb-4">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-users mr-2" style="color:#9b59b6;"></i>All Faculty — Latest Period Comparison</span>
                <small class="text-muted"><?= end($periods)['label'] ?? '' ?></small>
            </div>
            <div class="chart-card-body">
                <?php if(!empty($comp_labels)): ?>
                <div class="chart-wrap" style="height:320px;">
                    <canvas id="comparisonChart"></canvas>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No comparison data for the latest period</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if($has_data): ?>
    // --- TREND CHART ---
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($period_labels) ?>,
            datasets: [
                {
                    label: 'Efficiency',
                    data: <?= json_encode($chart_e) ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67,97,238,0.08)',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.25,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#4361ee'
                },
                {
                    label: 'Timeliness',
                    data: <?= json_encode($chart_t) ?>,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243,156,18,0.08)',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.25,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#f39c12'
                },
                {
                    label: 'Quality',
                    data: <?= json_encode($chart_q) ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39,174,96,0.08)',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.25,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#27ae60'
                },
                {
                    label: 'Overall',
                    data: <?= json_encode($chart_ov) ?>,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231,76,60,0.05)',
                    borderWidth: 3,
                    borderDash: [6, 3],
                    fill: false,
                    tension: 0.25,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#e74c3c'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 24, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ' + (ctx.raw !== null ? ctx.raw.toFixed(2) : 'No data');
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 1.0,
                    max: 5.0,
                    ticks: { stepSize: 0.5, callback: function(v) { return v.toFixed(1); }, font: { size: 11 } },
                    grid: { color: '#f1f3f5' }
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false }
                }
            }
        },
        plugins: [{
            id: 'thresholds',
            afterDraw: function(chart) {
                var ctx = chart.ctx, yScale = chart.scales.y, xScale = chart.scales.x;
                // 2.60 intervention line
                var y26 = yScale.getPixelForValue(2.6);
                ctx.save();
                ctx.setLineDash([8, 5]);
                ctx.strokeStyle = 'rgba(231, 76, 60, 0.5)';
                ctx.lineWidth = 1.5;
                ctx.beginPath(); ctx.moveTo(xScale.left, y26); ctx.lineTo(xScale.right, y26); ctx.stroke();
                ctx.fillStyle = '#e74c3c'; ctx.font = 'bold 10px Arial';
                ctx.fillText('Intervention ≤2.60', xScale.left + 8, y26 - 5);
                ctx.restore();
                // 3.61 VSS line
                var y36 = yScale.getPixelForValue(3.61);
                ctx.save();
                ctx.setLineDash([8, 5]);
                ctx.strokeStyle = 'rgba(39, 174, 96, 0.4)';
                ctx.lineWidth = 1.5;
                ctx.beginPath(); ctx.moveTo(xScale.left, y36); ctx.lineTo(xScale.right, y36); ctx.stroke();
                ctx.fillStyle = '#27ae60'; ctx.font = 'bold 10px Arial';
                ctx.fillText('VSS ≥3.61', xScale.left + 8, y36 - 5);
                ctx.restore();
            }
        }]
    });
    <?php endif; ?>

    <?php if(!empty($comp_labels)): ?>
    // --- COMPARISON CHART ---
    var compCtx = document.getElementById('comparisonChart').getContext('2d');
    new Chart(compCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($comp_labels) ?>,
            datasets: [
                {
                    label: 'Efficiency',
                    data: <?= json_encode($comp_e) ?>,
                    backgroundColor: 'rgba(67,97,238,0.75)',
                    borderColor: '#4361ee',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Timeliness',
                    data: <?= json_encode($comp_t) ?>,
                    backgroundColor: 'rgba(243,156,18,0.75)',
                    borderColor: '#f39c12',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Quality',
                    data: <?= json_encode($comp_q) ?>,
                    backgroundColor: 'rgba(39,174,96,0.75)',
                    borderColor: '#27ae60',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return ctx.dataset.label + ': ' + ctx.raw.toFixed(2); }
                    }
                }
            },
            scales: {
                y: {
                    min: 1.0, max: 5.0,
                    ticks: { stepSize: 0.5, callback: function(v) { return v.toFixed(1); }, font: { size: 11 } },
                    grid: { color: '#f1f3f5' }
                },
                x: {
                    ticks: { maxRotation: 45, minRotation: 0, font: { size: 9 } },
                    grid: { display: false }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
