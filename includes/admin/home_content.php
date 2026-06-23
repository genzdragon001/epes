<?php
// === ADMIN DASHBOARD ===
$total_employees   = $conn->query("SELECT COUNT(*) FROM employee_list")->fetch_row()[0];
$verified_tasks    = $conn->query("SELECT COUNT(*) FROM task_progress WHERE progress='Verified'")->fetch_row()[0];
$for_verification  = $conn->query("SELECT COUNT(*) FROM task_progress WHERE progress='For Verification'")->fetch_row()[0];
$total_submissions = $conn->query("SELECT COUNT(*) FROM task_progress")->fetch_row()[0];
$avg_rating        = $conn->query("SELECT AVG((efficiency+timeliness+quality)/3) as a FROM ratings WHERE efficiency>0")->fetch_assoc()['a'] ?? 0;
$intervention_count = $conn->query("SELECT COUNT(*) FROM intervention_flags WHERE acknowledged=0")->fetch_row()[0];

// Department data for bar chart
$dept_labels = [];
$dept_completion = [];
$dept_faculty = [];
$dq = $conn->query("
    SELECT d.department, COUNT(DISTINCT e.id) as faculty,
           COUNT(DISTINCT tp.id) as submissions,
           SUM(CASE WHEN tp.progress='Verified' THEN 1 ELSE 0 END) as verified
    FROM department_list d
    LEFT JOIN employee_list e ON e.department_id = d.id
    LEFT JOIN task_progress tp ON tp.faculty_id = e.id
    GROUP BY d.id, d.department
    ORDER BY d.department
");
while($d = $dq->fetch_assoc()) {
    $dept_labels[] = $d['department'];
    $dept_completion[] = $d['submissions'] > 0 ? round(($d['verified']/$d['submissions'])*100, 1) : 0;
    $dept_faculty[] = (int)$d['faculty'];
}

// Submission status for donut
$other_submissions = $total_submissions - $verified_tasks - $for_verification;

// Cascading DP data for horizontal bar
$active_rp = $conn->query("SELECT id FROM rating_period WHERE is_active = 1 LIMIT 1")->fetch_assoc();
$active_rp_id = $active_rp ? $active_rp['id'] : 0;
$cascade_labels = [];
$cascade_scores = [];
$cq = $conn->query("SELECT cr.department_id, d.department, cr.overall_rating FROM cascading_ratings cr LEFT JOIN department_list d ON cr.department_id=d.id WHERE cr.level='DP' AND cr.target_period_id=$active_rp_id ORDER BY cr.overall_rating DESC");
while($c = $cq->fetch_assoc()) {
    $cascade_labels[] = $c['department'] ?? 'Dept #'.$c['department_id'];
    $cascade_scores[] = round((float)$c['overall_rating'], 2);
}

// OPCR
$opcr = $conn->query("SELECT overall_rating FROM cascading_ratings WHERE level='OPCR' AND target_period_id=$active_rp_id ORDER BY computed_at DESC LIMIT 1")->fetch_assoc();

$completion_pct = $total_submissions > 0 ? round(($verified_tasks/$total_submissions)*100) : 0;
$adj_label = $avg_rating >= 4.75 ? 'Outstanding' : ($avg_rating >= 3.61 ? 'Very Satisfactory' : ($avg_rating >= 2.61 ? 'Satisfactory' : ($avg_rating >= 1.61 ? 'Unsatisfactory' : 'Poor')));
?>

<!-- 4 STAT CARDS -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $total_employees ?></div>
            <div class="stat-label">Total Faculty</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $verified_tasks ?></div>
            <div class="stat-label">Verified Tasks</div>
            <div class="stat-sub <?= $completion_pct >= 70 ? 'green' : 'amber' ?>"><?= $completion_pct ?>% completion</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-amber">
            <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $for_verification ?></div>
            <div class="stat-label">Pending Review</div>
            <?php if($for_verification > 0): ?>
            <div class="stat-sub amber">needs attention</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-purple">
            <div class="stat-icon purple"><i class="fas fa-star"></i></div>
            <div class="stat-value"><?= number_format($avg_rating, 2) ?></div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-sub <?= $avg_rating >= 3.61 ? 'green' : ($avg_rating >= 2.61 ? 'amber' : 'red') ?>"><?= $adj_label ?></div>
        </div>
    </div>
</div>

<?php if($intervention_count > 0): ?>
<div class="alert-banner mb-4">
    <i class="fas fa-flag mr-2" style="color:#e67e22;"></i>
    <strong><?= $intervention_count ?></strong> faculty flagged for intervention &mdash;
    <a href="index.php?page=faculty_list" class="alert-link" style="font-weight:700;">review now &rarr;</a>
</div>
<?php endif; ?>

<!-- CHARTS ROW 1: Department Completion + Submission Status -->
<div class="row mb-4">
    <div class="col-lg-8 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-building mr-2" style="color:#4361ee;"></i>Department Completion</span>
                <small class="text-muted"><?= $period_label ?></small>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:300px;">
                    <canvas id="adminDeptChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-chart-pie mr-2" style="color:#9b59b6;"></i>Submission Status</span>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:300px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="adminStatusDonut" style="max-width:240px; max-height:240px;"></canvas>
                </div>
                <div class="d-flex justify-content-center mt-2" style="gap:16px; font-size:0.78rem;">
                    <span><span class="activity-dot green" style="display:inline-block;"></span> Verified (<?= $verified_tasks ?>)</span>
                    <span><span class="activity-dot amber" style="display:inline-block;"></span> Pending (<?= $for_verification ?>)</span>
                    <span><span class="activity-dot" style="display:inline-block;"></span> Other (<?= $other_submissions ?>)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW 2: DP Cascading + OPCR -->
<div class="row mb-4">
    <div class="col-lg-8 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-sitemap mr-2" style="color:#1abc9c;"></i>DP Cascading Scores</span>
                <small class="text-muted">per department</small>
            </div>
            <div class="chart-card-body">
                <?php if(!empty($cascade_scores)): ?>
                <div class="chart-wrap" style="height:<?= max(200, count($cascade_scores)*36) ?>px;">
                    <canvas id="adminCascadeChart"></canvas>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No cascading data yet. Run cascade compute first.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-trophy mr-2" style="color:#f39c12;"></i>OPCR Score</span>
            </div>
            <div class="chart-card-body text-center">
                <?php if($opcr): 
                    $ocls = $opcr['overall_rating'] >= 3.61 ? 'green' : ($opcr['overall_rating'] >= 2.61 ? 'amber' : 'red');
                ?>
                <div style="font-size:3.5rem; font-weight:800; color:<?= $ocls == 'green' ? '#27ae60' : ($ocls == 'amber' ? '#e67e22' : '#e74c3c') ?>;">
                    <?= number_format($opcr['overall_rating'], 2) ?>
                </div>
                <div class="text-muted" style="font-size:0.85rem;">Office Performance Rating</div>
                <div class="mt-2">
                    <span class="badge badge-<?= $ocls == 'green' ? 'success' : ($ocls == 'amber' ? 'warning' : 'danger') ?>" style="font-size:0.8rem; padding:6px 14px;">
                        <?= $opcr['overall_rating'] >= 4.75 ? 'Outstanding' : ($opcr['overall_rating'] >= 3.61 ? 'Very Satisfactory' : ($opcr['overall_rating'] >= 2.61 ? 'Satisfactory' : ($opcr['overall_rating'] >= 1.61 ? 'Unsatisfactory' : 'Poor'))) ?>
                    </span>
                </div>
                <?php else: ?>
                <p class="text-muted py-4 mb-0">No OPCR data yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    // ── Department Completion Bar Chart ──
    var deptCtx = document.getElementById('adminDeptChart').getContext('2d');
    new Chart(deptCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dept_labels) ?>,
            datasets: [{
                label: 'Completion %',
                data: <?= json_encode($dept_completion) ?>,
                backgroundColor: <?= json_encode($dept_completion) ?>.map(function(v){
                    return v >= 70 ? 'rgba(39,174,96,0.75)' : v >= 40 ? 'rgba(243,156,18,0.75)' : 'rgba(231,76,60,0.75)';
                }),
                borderColor: <?= json_encode($dept_completion) ?>.map(function(v){
                    return v >= 70 ? '#27ae60' : v >= 40 ? '#f39c12' : '#e74c3c';
                }),
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 50
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx){ return ctx.raw + '% completed'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: function(v){ return v + '%'; }, font: { size: 11 } },
                    grid: { color: '#f1f3f5' }
                },
                x: {
                    ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 },
                    grid: { display: false }
                }
            }
        }
    });

    // ── Submission Status Donut ──
    var statusCtx = document.getElementById('adminStatusDonut').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Verified', 'Pending', 'Other'],
            datasets: [{
                data: [<?= $verified_tasks ?>, <?= $for_verification ?>, <?= $other_submissions ?>],
                backgroundColor: ['#27ae60', '#f39c12', '#adb5bd'],
                borderColor: '#fff',
                borderWidth: 2,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx){
                            var total = ctx.dataset.data.reduce(function(a,b){return a+b;},0);
                            var pct = total > 0 ? Math.round((ctx.raw/total)*100) : 0;
                            return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });

    <?php if(!empty($cascade_scores)): ?>
    // ── DP Cascading Horizontal Bar ──
    var cascadeCtx = document.getElementById('adminCascadeChart').getContext('2d');
    new Chart(cascadeCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($cascade_labels) ?>,
            datasets: [{
                label: 'DP Rating',
                data: <?= json_encode($cascade_scores) ?>,
                backgroundColor: <?= json_encode($cascade_scores) ?>.map(function(v){
                    return v >= 3.61 ? 'rgba(26,188,156,0.75)' : v >= 2.61 ? 'rgba(243,156,18,0.75)' : 'rgba(231,76,60,0.75)';
                }),
                borderColor: <?= json_encode($cascade_scores) ?>.map(function(v){
                    return v >= 3.61 ? '#1abc9c' : v >= 2.61 ? '#f39c12' : '#e74c3c';
                }),
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 28
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx){ return 'DP Rating: ' + ctx.raw; }
                    }
                }
            },
            scales: {
                x: {
                    min: 1,
                    max: 5,
                    ticks: { stepSize: 1, font: { size: 11 } },
                    grid: { color: '#f1f3f5' }
                },
                y: {
                    ticks: { font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
    <?php endif; ?>
})();
</script>
