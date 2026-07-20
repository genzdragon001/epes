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

// Get all rating periods (deduplicated by semester+year)
$all_periods_raw = [];
$pq = $conn->query("SELECT * FROM rating_period ORDER BY year ASC, id ASC");
while ($p = $pq->fetch_assoc()) {
    $key = $p['semester'] . '|' . $p['year'];
    if (!isset($all_periods_raw[$key])) $all_periods_raw[$key] = $p;
}
$periods = array_values($all_periods_raw);

// Build period_code (short format like "1-2526") for each period
$period_codes = [];
foreach ($periods as $p) {
    $sem = $p['semester'];
    $yr = $p['year'];
    $sem_num = '1';
    if (strpos($sem, '2nd') !== false) $sem_num = '2';
    elseif (stripos($sem, 'Summer') !== false) $sem_num = 'S';
    $short = '';
    if (strpos($yr, '-') !== false) {
        $parts = explode('-', $yr);
        $short = substr($parts[0], -2) . substr($parts[1], -2);
    }
    $period_codes[] = $sem_num . '-' . $short;
}

// Also build the full code from the DB (e.g. "1stSemester-2025-2026")
$period_full_codes = [];
foreach ($periods as $p) {
    $period_full_codes[] = $p['code'] ?? ($p['semester'] . '-' . $p['year']);
}

// For each period, gather ALL rating_period variants from the DB that match
// (e.g. "1-2526", "1stSemester-2025-2026", "IPCR-1stSemester-2025-2026", "")
$period_variants = [];
foreach ($periods as $idx => $p) {
    $sel_sem = $conn->real_escape_string($p['semester']);
    $sel_yr  = $conn->real_escape_string($p['year']);
    $short_code = $period_codes[$idx];
    $like_yr  = '%' . $sel_yr . '%';
    $like_sht = '%' . $short_code . '%';
    $variants = [];
    $vq = $conn->query("SELECT DISTINCT rating_period FROM ratings WHERE rating_period <> '' AND (rating_period LIKE '$like_yr' OR rating_period LIKE '$like_sht')");
    while ($vr = $vq->fetch_assoc()) $variants[] = $vr['rating_period'];
    // If this is the only/active period, also include empty (legacy untagged)
    $period_variants[$idx] = $variants;
}

// Is this the active period? Include legacy untagged rows for it.
$active_period_key = null;
foreach ($periods as $p) {
    if (!empty($p['is_active'])) { $active_period_key = $p['semester'] . '|' . $p['year']; break; }
}

$period_count = count($periods);

// Get all faculty who have ratings (filter by department for non-dean evaluators)
$dept_filter = ($dept_id > 0 && !$is_dean && !$is_admin) ? "AND e.department_id = $dept_id" : "";
$fac_qry = $conn->query("
    SELECT DISTINCT e.id, CONCAT(e.lastname, ', ', e.firstname, ' ', COALESCE(e.middlename,'')) as fullname,
           e.department_id, d.department
    FROM employee_list e
    LEFT JOIN department_list d ON e.department_id = d.id
    INNER JOIN ratings r ON r.employee_id = e.id
    WHERE (r.efficiency > 0 OR r.timeliness > 0 OR r.quality > 0)
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
// Use the same per-task average logic as rating.php: only count Applicable criteria > 0
$ratings_data = [];
if ($selected_faculty) {
    foreach ($periods as $idx => $p) {
        $variants = $period_variants[$idx];
        $is_active = ($p['semester'] . '|' . $p['year']) === $active_period_key;

        // Build IN clause for matching rating_period values
        $in_parts = [];
        foreach ($variants as $v) {
            $in_parts[] = "'" . $conn->real_escape_string($v) . "'";
        }
        $period_in = !empty($in_parts) ? implode(',', $in_parts) : "''";

        // For active period, also include legacy untagged (empty) rows
        $legacy_or = $is_active ? " OR r.rating_period = '' OR r.rating_period IS NULL" : "";

        $sql = "
            SELECT ROUND(AVG(NULLIF(r.efficiency, 0)), 2) as E,
                   ROUND(AVG(NULLIF(r.timeliness, 0)), 2) as T,
                   ROUND(AVG(NULLIF(r.quality, 0)), 2) as Q,
                   COUNT(*) as cnt
            FROM ratings r
            WHERE r.employee_id = $selected_faculty
              AND (r.efficiency > 0 OR r.timeliness > 0 OR r.quality > 0)
              AND (r.rating_period IN ($period_in)$legacy_or)
        ";
        $rq = $conn->query($sql);
        if ($rq) {
            $row = $rq->fetch_assoc();
            if ($row['cnt'] > 0) {
                // Compute overall as simple average of available non-zero dimensions
                $dims = [];
                if ($row['E'] !== null) $dims[] = $row['E'];
                if ($row['T'] !== null && $row['T'] > 0) $dims[] = $row['T'];
                if ($row['Q'] !== null && $row['Q'] > 0) $dims[] = $row['Q'];
                $overall = count($dims) > 0 ? round(array_sum($dims) / count($dims), 2) : null;
                $ratings_data[$idx] = [
                    'E' => $row['E'],
                    'T' => $row['T'],
                    'Q' => $row['Q'],
                    'Overall' => $overall,
                ];
            }
        }
    }
}

// Build chart arrays aligned with periods
$chart_e = $chart_t = $chart_q = $chart_ov = [];
$table_rows = [];
foreach ($periods as $idx => $p) {
    if (isset($ratings_data[$idx])) {
        $d = $ratings_data[$idx];
        $chart_e[] = $d['E'] ?? null;
        $chart_t[] = ($d['T'] !== null && $d['T'] > 0) ? $d['T'] : null;
        $chart_q[] = ($d['Q'] !== null && $d['Q'] > 0) ? $d['Q'] : null;
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
$trend_icon = 'fa-minus';
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

// Period labels
$period_labels = array_map(fn($p) => $p['semester'] . " " . $p['year'], $periods);

// Latest period comparison data
$latest_idx = $period_count - 1;
$latest_variants = $period_variants[$latest_idx] ?? [];
$latest_is_active = ($periods[$latest_idx]['semester'] . '|' . $periods[$latest_idx]['year']) === $active_period_key;
$latest_in_parts = [];
foreach ($latest_variants as $v) {
    $latest_in_parts[] = "'" . $conn->real_escape_string($v) . "'";
}
$latest_in = !empty($latest_in_parts) ? implode(',', $latest_in_parts) : "''";
$latest_legacy = $latest_is_active ? " OR r.rating_period = '' OR r.rating_period IS NULL" : "";

$comp_qry = $conn->query("
    SELECT e.id, CONCAT(e.lastname, ', ', LEFT(e.firstname,1), '.') as shortname,
           ROUND(AVG(NULLIF(r.efficiency, 0)), 2) as E,
           ROUND(AVG(NULLIF(r.timeliness, 0)), 2) as T,
           ROUND(AVG(NULLIF(r.quality, 0)), 2) as Q
    FROM ratings r
    JOIN employee_list e ON r.employee_id = e.id
    WHERE (r.rating_period IN ($latest_in)$latest_legacy)
    AND (r.efficiency > 0 OR r.timeliness > 0 OR r.quality > 0)
    $dept_filter
    GROUP BY e.id
    ORDER BY E DESC
");
$comp_labels = []; $comp_e = []; $comp_t = []; $comp_q = [];
while ($c = $comp_qry->fetch_assoc()) {
    $comp_labels[] = $c['shortname'];
    $comp_e[] = $c['E'];
    $comp_t[] = ($c['T'] !== null && $c['T'] > 0) ? $c['T'] : 0;
    $comp_q[] = ($c['Q'] !== null && $c['Q'] > 0) ? $c['Q'] : 0;
}

$has_data = count($rated_overalls) > 0;
$latest_ov = $has_data ? end($rated_overalls) : null;
$highest_ov = $has_data ? max($rated_overalls) : null;
$lowest_ov = $has_data ? min($rated_overalls) : null;

// Selected faculty name
$selected_name = '';
foreach ($faculty_list as $f) {
    if ($f['id'] == $selected_faculty) { $selected_name = $f['fullname']; break; }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:8px;">
    <div>
        <h4 style="font-weight:700; color:#1a1a2e;"><i class="fas fa-chart-line mr-2" style="color:#4361ee;"></i>Performance Trends</h4>
        <span class="text-muted" style="font-size:0.85rem;">Track E · T · Q · Overall across rating periods</span>
    </div>
    <form method="GET" class="form-inline">
        <input type="hidden" name="page" value="faculty_trends">
        <select name="faculty_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:240px;">
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
<div class="alert-banner mb-3">
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

<!-- STAT TILES -->
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-purple">
            <div class="stat-icon purple"><i class="fas fa-trophy"></i></div>
            <div class="stat-value"><?= $latest_ov !== null ? number_format($latest_ov, 2) : '—' ?></div>
            <div class="stat-label">Latest Overall</div>
            <div class="stat-sub <?= ($latest_ov ?? 0) >= 3.61 ? 'green' : (($latest_ov ?? 0) >= 2.61 ? 'amber' : 'red') ?>"><?= adj($latest_ov) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-value"><?= $highest_ov !== null ? number_format($highest_ov, 2) : '—' ?></div>
            <div class="stat-label">Highest Overall</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-red" style="border-left-color:#e74c3c;">
            <div class="stat-icon" style="background:#fdedec; color:#e74c3c;"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-value"><?= $lowest_ov !== null ? number_format($lowest_ov, 2) : '—' ?></div>
            <div class="stat-label">Lowest Overall</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-teal">
            <div class="stat-icon teal"><i class="fas <?= $trend_icon ?>"></i></div>
            <div class="stat-value <?= $trend_class ?>"><?= $trend ?></div>
            <div class="stat-label">Trend</div>
            <div class="stat-sub">Across <?= count($rated_overalls) ?> periods</div>
        </div>
    </div>
</div>

<!-- TREND CHART + RATING DETAILS TABLE -->
<div class="row mb-3">
    <div class="col-lg-8 col-12 mb-2">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-chart-line mr-2" style="color:#4361ee;"></i>E · T · Q · Overall Trend</span>
                <small class="text-muted"><?= htmlspecialchars($selected_name) ?></small>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:320px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12 mb-2">
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
                            <td><strong><?= htmlspecialchars($periods[$i]['semester'] . ' ' . $periods[$i]['year']) ?></strong></td>
                            <td class="text-center"><?= $table_rows[$i]['E'] !== null ? number_format($table_rows[$i]['E'], 2) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center"><?= ($table_rows[$i]['T'] !== null && $table_rows[$i]['T'] > 0) ? number_format($table_rows[$i]['T'], 2) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center"><?= ($table_rows[$i]['Q'] !== null && $table_rows[$i]['Q'] > 0) ? number_format($table_rows[$i]['Q'], 2) : '<span class="text-muted">—</span>' ?></td>
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

<!-- COMPARISON CHART -->
<div class="row mb-3">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-users mr-2" style="color:#9b59b6;"></i>All Faculty — Latest Period Comparison</span>
                <small class="text-muted"><?= $periods[$latest_idx]['semester'] . ' ' . $periods[$latest_idx]['year'] ?? '' ?></small>
            </div>
            <div class="chart-card-body">
                <?php if(!empty($comp_labels)): ?>
                <div class="chart-wrap" style="height:280px;">
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
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($period_labels) ?>,
            datasets: [
                { label: 'Efficiency', data: <?= json_encode($chart_e) ?>, borderColor: '#4361ee', backgroundColor: 'rgba(67,97,238,0.08)', borderWidth: 2.5, fill: false, tension: 0.25, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#4361ee' },
                { label: 'Timeliness', data: <?= json_encode($chart_t) ?>, borderColor: '#f39c12', backgroundColor: 'rgba(243,156,18,0.08)', borderWidth: 2.5, fill: false, tension: 0.25, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#f39c12' },
                { label: 'Quality', data: <?= json_encode($chart_q) ?>, borderColor: '#27ae60', backgroundColor: 'rgba(39,174,96,0.08)', borderWidth: 2.5, fill: false, tension: 0.25, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#27ae60' },
                { label: 'Overall', data: <?= json_encode($chart_ov) ?>, borderColor: '#e74c3c', backgroundColor: 'rgba(231,76,60,0.05)', borderWidth: 3, borderDash: [6, 3], fill: false, tension: 0.25, pointRadius: 6, pointHoverRadius: 8, pointBackgroundColor: '#e74c3c' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11 } } },
                tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + (ctx.raw !== null ? ctx.raw.toFixed(2) : 'No data'); } } }
            },
            scales: {
                y: { min: 1.0, max: 5.0, ticks: { stepSize: 0.5, callback: function(v) { return v.toFixed(1); }, font: { size: 11 } }, grid: { color: '#f1f3f5' } },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } }
            }
        },
        plugins: [{
            id: 'thresholds',
            afterDraw: function(chart) {
                var ctx = chart.ctx, yScale = chart.scales.y, xScale = chart.scales.x;
                var y26 = yScale.getPixelForValue(2.6);
                ctx.save(); ctx.setLineDash([8, 5]); ctx.strokeStyle = 'rgba(231,76,60,0.5)'; ctx.lineWidth = 1.5;
                ctx.beginPath(); ctx.moveTo(xScale.left, y26); ctx.lineTo(xScale.right, y26); ctx.stroke();
                ctx.fillStyle = '#e74c3c'; ctx.font = 'bold 10px Arial'; ctx.fillText('Intervention ≤2.60', xScale.left + 8, y26 - 5); ctx.restore();
                var y36 = yScale.getPixelForValue(3.61);
                ctx.save(); ctx.setLineDash([8, 5]); ctx.strokeStyle = 'rgba(39,174,96,0.4)'; ctx.lineWidth = 1.5;
                ctx.beginPath(); ctx.moveTo(xScale.left, y36); ctx.lineTo(xScale.right, y36); ctx.stroke();
                ctx.fillStyle = '#27ae60'; ctx.font = 'bold 10px Arial'; ctx.fillText('VSS ≥3.61', xScale.left + 8, y36 - 5); ctx.restore();
            }
        }]
    });
    <?php endif; ?>

    <?php if(!empty($comp_labels)): ?>
    var compCtx = document.getElementById('comparisonChart').getContext('2d');
    new Chart(compCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($comp_labels) ?>,
            datasets: [
                { label: 'Efficiency', data: <?= json_encode($comp_e) ?>, backgroundColor: 'rgba(67,97,238,0.75)', borderColor: '#4361ee', borderWidth: 1, borderRadius: 4 },
                { label: 'Timeliness', data: <?= json_encode($comp_t) ?>, backgroundColor: 'rgba(243,156,18,0.75)', borderColor: '#f39c12', borderWidth: 1, borderRadius: 4 },
                { label: 'Quality', data: <?= json_encode($comp_q) ?>, backgroundColor: 'rgba(39,174,96,0.75)', borderColor: '#27ae60', borderWidth: 1, borderRadius: 4 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11 } } },
                tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + ctx.raw.toFixed(2); } } }
            },
            scales: {
                y: { min: 1.0, max: 5.0, ticks: { stepSize: 0.5, callback: function(v) { return v.toFixed(1); }, font: { size: 11 } }, grid: { color: '#f1f3f5' } },
                x: { ticks: { maxRotation: 45, minRotation: 0, font: { size: 9 } }, grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
});
</script>