<?php
// === ADMIN DASHBOARD ===
// All task_progress / ratings queries are scoped to the selected period via $period_filter.
$total_employees   = $conn->query("SELECT COUNT(*) FROM employee_list")->fetch_row()[0];
$verified_tasks    = $conn->query("SELECT COUNT(*) FROM task_progress WHERE progress='Verified' $period_filter")->fetch_row()[0];
$for_verification  = $conn->query("SELECT COUNT(*) FROM task_progress WHERE progress='For Verification' $period_filter")->fetch_row()[0];
$total_submissions = $conn->query("SELECT COUNT(*) FROM task_progress WHERE 1=1 $period_filter")->fetch_row()[0];
$avg_rating        = $conn->query("SELECT AVG((efficiency+timeliness+quality)/3) as a FROM ratings WHERE efficiency>0 $period_filter")->fetch_assoc()['a'] ?? 0;
$intervention_count = $conn->query("SELECT COUNT(*) FROM intervention_flags WHERE acknowledged=0")->fetch_row()[0];

// New system-level stats
$total_targets     = $conn->query("SELECT COUNT(*) FROM task_list WHERE is_active=1")->fetch_row()[0];
$mov_total         = $conn->query("SELECT COUNT(*) FROM mov_uploads")->fetch_row()[0];
$mov_verified      = $conn->query("SELECT COUNT(*) FROM mov_uploads WHERE status='Verified'")->fetch_row()[0];
$total_evaluators  = $conn->query("SELECT COUNT(*) FROM evaluator_list")->fetch_row()[0];
$dept_heads_count  = $conn->query("SELECT COUNT(*) FROM evaluator_list WHERE type=0")->fetch_row()[0];
$deans_count       = $conn->query("SELECT COUNT(*) FROM evaluator_list WHERE type=1")->fetch_row()[0];

// Department data for table
$dept_rows = [];
$dq = $conn->query("
    SELECT d.id, d.department, COUNT(DISTINCT e.id) as faculty,
           COUNT(DISTINCT tp.id) as submissions,
           SUM(CASE WHEN tp.progress='Verified' THEN 1 ELSE 0 END) as verified
    FROM department_list d
    LEFT JOIN employee_list e ON e.department_id = d.id
    LEFT JOIN task_progress tp ON tp.faculty_id = e.id $period_filter
    GROUP BY d.id, d.department
    ORDER BY d.department
");
while($d = $dq->fetch_assoc()) {
    $pct = $d['submissions'] > 0 ? round(($d['verified']/$d['submissions'])*100) : 0;
    $dept_rows[] = [
        'name'      => $d['department'],
        'faculty'   => (int)$d['faculty'],
        'submitted' => (int)$d['submissions'],
        'verified'  => (int)$d['verified'],
        'pct'       => $pct,
    ];
}

// Submission status for donut
$other_submissions = $total_submissions - $verified_tasks - $for_verification;

// Cascading DP data for horizontal bar — use selected period IDs (not just active)
$selected_rp_ids = !empty($selected_period_ids) ? implode(',', $selected_period_ids) : '0';
$cascade_labels = [];
$cascade_scores = [];
$cq = $conn->query("SELECT cr.department_id, d.department, cr.overall_rating FROM cascading_ratings cr LEFT JOIN department_list d ON cr.department_id=d.id WHERE cr.level='DP' AND cr.target_period_id IN ($selected_rp_ids) ORDER BY cr.overall_rating DESC");
while($c = $cq->fetch_assoc()) {
    $cascade_labels[] = $c['department'] ?? 'Dept #'.$c['department_id'];
    $cascade_scores[] = round((float)$c['overall_rating'], 2);
}

// OPCR
$opcr = $conn->query("SELECT overall_rating FROM cascading_ratings WHERE level='OPCR' AND target_period_id IN ($selected_rp_ids) ORDER BY computed_at DESC LIMIT 1")->fetch_assoc();

$completion_pct = $total_submissions > 0 ? round(($verified_tasks/$total_submissions)*100) : 0;
$adj_label = $avg_rating >= 4.75 ? 'Outstanding' : ($avg_rating >= 3.61 ? 'Very Satisfactory' : ($avg_rating >= 2.61 ? 'Satisfactory' : ($avg_rating >= 1.61 ? 'Unsatisfactory' : 'Poor')));

// Recent activity (admin sees all departments)
$recent = $conn->query("
    SELECT e.lastname, e.firstname, tp.progress, tp.date_created
    FROM task_progress tp
    INNER JOIN employee_list e ON tp.faculty_id=e.id
    ORDER BY tp.date_created DESC LIMIT 6
");
?>

<!-- Alert banner -->
<?php if($intervention_count > 0): ?>
<div class="alert-banner mb-4">
    <i class="fas fa-flag mr-2" style="color:#e67e22;"></i>
    <strong><?= $intervention_count ?></strong> faculty flagged for intervention &mdash;
    <a href="index.php?page=faculty_list" class="alert-link" style="font-weight:700;">review now &rarr;</a>
</div>
<?php endif; ?>

<!-- ROW 1: 4 Evaluation Stat Cards -->
<div class="row mb-2">
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $total_employees ?></div>
            <div class="stat-label">Total Faculty</div>
            <div class="stat-sub green"><?= count($dept_rows) ?> departments</div>
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
            <?php if($for_verification > 0): ?><div class="stat-sub amber">needs attention</div><?php endif; ?>
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

<!-- ROW 2: 4 System Stat Cards -->
<div class="row mb-3">
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-teal">
            <div class="stat-icon teal"><i class="fas fa-bullseye"></i></div>
            <div class="stat-value"><?= $total_targets ?></div>
            <div class="stat-label">Active Targets</div>
            <div class="stat-sub">across all depts</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="fas fa-folder-open"></i></div>
            <div class="stat-value"><?= $mov_total ?></div>
            <div class="stat-label">MOV Uploads</div>
            <div class="stat-sub green"><?= $mov_verified ?> verified</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-amber">
            <div class="stat-icon amber"><i class="fas fa-user-tie"></i></div>
            <div class="stat-value"><?= $total_evaluators ?></div>
            <div class="stat-label">Evaluators</div>
            <div class="stat-sub"><?= $dept_heads_count ?> dept heads, <?= $deans_count ?> dean<?= $deans_count != 1 ? 's' : '' ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6 mb-3">
        <div class="stat-card accent-red">
            <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value"><?= $intervention_count ?></div>
            <div class="stat-label">Intervention Flags</div>
            <?php if($intervention_count > 0): ?><div class="stat-sub red">unacknowledged</div><?php endif; ?>
        </div>
    </div>
</div>

<!-- ROW 3: Department Completion Table + Submission Donut -->
<div class="row mb-3">
    <div class="col-lg-8 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-building mr-2" style="color:#4361ee;"></i>Department Completion</span>
                <small class="text-muted"><?= $period_label ?></small>
            </div>
            <div class="chart-card-body">
                <?php if(!empty($dept_rows)): ?>
                <div style="overflow-x:auto;">
                <table class="table table-sm table-flat mb-0" style="font-size:0.83rem;">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th class="text-center">Faculty</th>
                            <th class="text-center">Submitted</th>
                            <th class="text-center">Verified</th>
                            <th class="text-right">Completion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dept_rows as $d):
                            $bar_color = $d['pct'] >= 70 ? '#27ae60' : ($d['pct'] >= 40 ? '#f39c12' : '#e74c3c');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                            <td class="text-center"><?= $d['faculty'] ?></td>
                            <td class="text-center"><?= $d['submitted'] ?></td>
                            <td class="text-center"><?= $d['verified'] ?></td>
                            <td class="text-right">
                                <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end;">
                                    <div style="flex:1;max-width:80px;height:6px;background:#e9ecef;border-radius:3px;overflow:hidden;">
                                        <div style="width:<?= $d['pct'] ?>%;height:100%;background:<?= $bar_color ?>;border-radius:3px;"></div>
                                    </div>
                                    <span style="font-weight:700;font-size:0.78rem;color:<?= $bar_color ?>;min-width:36px;text-align:right;"><?= $d['pct'] ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No departments found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-chart-pie mr-2" style="color:#9b59b6;"></i>Submission Status</span>
            </div>
            <div class="chart-card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:260px;">
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="adminStatusDonut" style="max-width:200px;max-height:200px;"></canvas>
                </div>
                <div class="d-flex justify-content-center mt-2" style="gap:16px; font-size:0.78rem; flex-wrap:wrap;">
                    <span><span class="activity-dot green" style="display:inline-block;"></span> Verified (<?= $verified_tasks ?>)</span>
                    <span><span class="activity-dot amber" style="display:inline-block;"></span> Pending (<?= $for_verification ?>)</span>
                    <span><span class="activity-dot" style="display:inline-block;"></span> Other (<?= max($other_submissions,0) ?>)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 4: DP Cascading + OPCR -->
<div class="row mb-3">
    <div class="col-lg-8 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-sitemap mr-2" style="color:#1abc9c;"></i>DP Cascading Scores</span>
                <small class="text-muted">per department</small>
            </div>
            <div class="chart-card-body">
                <?php if(!empty($cascade_scores)): ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php for($i=0;$i<count($cascade_labels);$i++):
                        $score = $cascade_scores[$i];
                        $bar_color = $score >= 3.61 ? '#1abc9c' : ($score >= 2.61 ? '#f39c12' : '#e74c3c');
                        $pct_width = round(($score/5)*100);
                    ?>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:3px;">
                            <span><?= htmlspecialchars($cascade_labels[$i]) ?></span>
                            <span style="font-weight:700;color:<?= $bar_color ?>;"><?= number_format($score,2) ?></span>
                        </div>
                        <div style="height:8px;background:#e9ecef;border-radius:4px;overflow:hidden;">
                            <div style="width:<?= $pct_width ?>%;height:100%;background:<?= $bar_color ?>;border-radius:4px;"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
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
            <div class="chart-card-body text-center" style="padding:28px 18px;">
                <?php if($opcr):
                    $ocls = $opcr['overall_rating'] >= 3.61 ? 'green' : ($opcr['overall_rating'] >= 2.61 ? 'amber' : 'red');
                    $ocolor = $ocls == 'green' ? '#27ae60' : ($ocls == 'amber' ? '#e67e22' : '#e74c3c');
                ?>
                <div style="font-size:3.2rem;font-weight:800;color:<?= $ocolor ?>;">
                    <?= number_format($opcr['overall_rating'], 2) ?>
                </div>
                <div class="text-muted" style="font-size:0.82rem;">Office Performance Rating</div>
                <div class="mt-2">
                    <span class="badge badge-<?= $ocls == 'green' ? 'success' : ($ocls == 'amber' ? 'warning' : 'danger') ?>" style="font-size:0.78rem;padding:4px 12px;border-radius:12px;">
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

<!-- ROW 5: Recent Activity + Quick Actions -->
<div class="row mb-3">
    <div class="col-lg-8 col-12 mb-3">
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
                        <span class="activity-status"><?= htmlspecialchars($r['progress']) ?></span>
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
    <div class="col-lg-4 col-12 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-bolt mr-2" style="color:#f39c12;"></i>Quick Actions</span>
            </div>
            <div class="chart-card-body">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                    <a href="index.php?page=faculty_list" class="quick-link" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:1px solid #e9ecef;border-radius:8px;text-decoration:none;color:#1a1a2e;font-size:0.78rem;font-weight:600;transition:all 0.15s;">
                        <i class="fas fa-users" style="font-size:1.3rem;color:#4361ee;"></i> Faculty List
                    </a>
                    <a href="index.php?page=target_list" class="quick-link" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:1px solid #e9ecef;border-radius:8px;text-decoration:none;color:#1a1a2e;font-size:0.78rem;font-weight:600;transition:all 0.15s;">
                        <i class="fas fa-bullseye" style="font-size:1.3rem;color:#27ae60;"></i> Targets
                    </a>
                    <a href="index.php?page=rating_period" class="quick-link" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:1px solid #e9ecef;border-radius:8px;text-decoration:none;color:#1a1a2e;font-size:0.78rem;font-weight:600;transition:all 0.15s;">
                        <i class="fas fa-calendar-alt" style="font-size:1.3rem;color:#e67e22;"></i> Rating Periods
                    </a>
                    <a href="index.php?page=percentage_allocation" class="quick-link" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:1px solid #e9ecef;border-radius:8px;text-decoration:none;color:#1a1a2e;font-size:0.78rem;font-weight:600;transition:all 0.15s;">
                        <i class="fas fa-percent" style="font-size:1.3rem;color:#8e44ad;"></i> Allocations
                    </a>
                    <a href="index.php?page=ipcr_view" class="quick-link" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:1px solid #e9ecef;border-radius:8px;text-decoration:none;color:#1a1a2e;font-size:0.78rem;font-weight:600;transition:all 0.15s;">
                        <i class="fas fa-file-alt" style="font-size:1.3rem;color:#16a085;"></i> IPCR Forms
                    </a>
                    <a href="index.php?page=faculty_trends" class="quick-link" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:1px solid #e9ecef;border-radius:8px;text-decoration:none;color:#1a1a2e;font-size:0.78rem;font-weight:600;transition:all 0.15s;">
                        <i class="fas fa-chart-line" style="font-size:1.3rem;color:#e74c3c;"></i> Trends
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    // ── Submission Status Donut ──
    var statusEl = document.getElementById('adminStatusDonut');
    if (statusEl) {
        var statusCtx = statusEl.getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Verified', 'Pending', 'Other'],
                datasets: [{
                    data: [<?= $verified_tasks ?>, <?= $for_verification ?>, <?= max($other_submissions,0) ?>],
                    backgroundColor: ['#27ae60', '#f39c12', '#adb5bd'],
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
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
    }
})();
</script>