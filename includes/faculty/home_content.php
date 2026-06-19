<?php
// === FACULTY DASHBOARD ===
$emp_qry = $conn->query("SELECT e.*, p.position as position_name, d.designation as designation_name FROM employee_list e LEFT JOIN position_list p ON e.position_id=p.id LEFT JOIN designation_list d ON e.designation_id=d.id WHERE e.id=$emp_id LIMIT 1");
$emp_data = $emp_qry->fetch_assoc();
$emp_position_id = intval($emp_data['position_id'] ?? 0);
$emp_designation_id = intval($emp_data['designation_id'] ?? 0);
$position_name = $emp_data['position_name'] ?? 'Faculty';
$designation_name = $emp_data['designation_name'] ?? '';

// Task counts
$total_targets   = $conn->query("SELECT COUNT(*) FROM task_list t WHERE t.is_active=1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id=0 OR t.academic_rank_id=$emp_position_id) AND (t.designation_id IS NULL OR t.designation_id=0 OR t.designation_id=$emp_designation_id) AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id=$emp_position_id)")->fetch_row()[0];
$submitted       = $conn->query("SELECT COUNT(DISTINCT task_id) FROM task_progress WHERE faculty_id=$emp_id")->fetch_row()[0];
$verified        = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id=$emp_id AND progress='Verified'")->fetch_row()[0];
$for_verif       = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id=$emp_id AND progress='For Verification'")->fetch_row()[0];
$other_status    = $submitted - $verified - $for_verif;
$not_submitted   = $total_targets - $submitted;

// IPCR rating
$ipcr = $conn->query("SELECT AVG((efficiency+timeliness+quality)/3) as overall, COUNT(*) as cnt FROM ratings WHERE employee_id=$emp_id AND efficiency>0 AND timeliness>0 AND quality>0")->fetch_assoc();
$ipcr_score = $ipcr['cnt'] > 0 ? round($ipcr['overall'], 2) : null;
$ipcr_adj   = $ipcr_score ? ($ipcr_score >= 4.75 ? 'Outstanding' : ($ipcr_score >= 3.61 ? 'Very Satisfactory' : ($ipcr_score >= 2.61 ? 'Satisfactory' : ($ipcr_score >= 1.61 ? 'Unsatisfactory' : 'Poor')))) : 'Not Rated';

// Rating dimensions for radar chart
$dim_eff = $conn->query("SELECT AVG(efficiency) as a FROM ratings WHERE employee_id=$emp_id AND efficiency>0")->fetch_assoc()['a'] ?? 0;
$dim_time = $conn->query("SELECT AVG(timeliness) as a FROM ratings WHERE employee_id=$emp_id AND timeliness>0")->fetch_assoc()['a'] ?? 0;
$dim_qual = $conn->query("SELECT AVG(quality) as a FROM ratings WHERE employee_id=$emp_id AND quality>0")->fetch_assoc()['a'] ?? 0;

// Recent submissions
$recent = $conn->query("SELECT tp.progress, tp.date_created, t.success_indicators FROM task_progress tp INNER JOIN task_list t ON tp.task_id=t.id WHERE tp.faculty_id=$emp_id ORDER BY tp.date_created DESC LIMIT 6");

$submission_pct = $total_targets > 0 ? round(($submitted/$total_targets)*100) : 0;
$verification_pct = $submitted > 0 ? round(($verified/$submitted)*100) : 0;
?>

<!-- 4 STAT CARDS -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="fas fa-tasks"></i></div>
            <div class="stat-value"><?= $submitted ?>/<?= $total_targets ?></div>
            <div class="stat-label">Targets Submitted</div>
            <div class="stat-sub <?= $submission_pct >= 70 ? 'green' : ($submission_pct >= 40 ? 'amber' : 'red') ?>">
                <?= $submission_pct ?>% complete
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-amber">
            <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $for_verif ?></div>
            <div class="stat-label">Awaiting Review</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $verified ?></div>
            <div class="stat-label">Verified</div>
            <div class="stat-sub <?= $verification_pct >= 70 ? 'green' : 'amber' ?>">
                <?= $verification_pct ?>% of submitted
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card accent-purple">
            <div class="stat-icon purple"><i class="fas fa-star"></i></div>
            <div class="stat-value"><?= $ipcr_score ? number_format($ipcr_score, 2) : '—' ?></div>
            <div class="stat-label">IPCR Rating</div>
            <div class="stat-sub <?= $ipcr_score >= 3.61 ? 'green' : ($ipcr_score >= 2.61 ? 'amber' : 'red') ?>">
                <?= $ipcr_adj ?>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW: Submission Status + Rating Radar -->
<div class="row mb-4">
    <div class="col-lg-5 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-chart-pie mr-2" style="color:#9b59b6;"></i>Submission Status</span>
                <small class="text-muted"><?= $period_label ?></small>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:280px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="facStatusDonut" style="max-width:220px; max-height:220px;"></canvas>
                </div>
                <div class="d-flex flex-wrap justify-content-center mt-2" style="gap:12px; font-size:0.76rem;">
                    <span><span class="activity-dot green" style="display:inline-block;"></span> Verified (<?= $verified ?>)</span>
                    <span><span class="activity-dot amber" style="display:inline-block;"></span> Pending (<?= $for_verif ?>)</span>
                    <span><span class="activity-dot" style="display:inline-block;"></span> Other (<?= $other_status ?>)</span>
                    <span><span class="activity-dot red" style="display:inline-block;"></span> Not submitted (<?= $not_submitted ?>)</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-3">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-compass mr-2" style="color:#4361ee;"></i>Rating Dimensions</span>
                <small class="text-muted">Efficiency &middot; Timeliness &middot; Quality</small>
            </div>
            <div class="chart-card-body">
                <?php if($ipcr['cnt'] > 0): ?>
                <div class="chart-wrap" style="height:280px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="facRadarChart" style="max-width:320px; max-height:280px;"></canvas>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-5 mb-0">No ratings yet. Submit tasks for evaluation.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ACTIVITY -->
<div class="row mb-4">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-card-header">
                <span><i class="fas fa-history mr-2" style="color:#1abc9c;"></i>Recent Submissions</span>
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
                        <span class="activity-name"><?= htmlspecialchars(mb_strimwidth($r['success_indicators'], 0, 50, '...')) ?></span>
                        <span class="activity-status"><?= $r['progress'] ?></span>
                        <span class="activity-time"><?= $time ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No submissions yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    // ── Submission Status Donut ──
    var statusCtx = document.getElementById('facStatusDonut').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Verified', 'Pending', 'Other', 'Not Submitted'],
            datasets: [{
                data: [<?= $verified ?>, <?= $for_verif ?>, <?= $other_status ?>, <?= $not_submitted ?>],
                backgroundColor: ['#27ae60', '#f39c12', '#adb5bd', '#e9ecef'],
                borderColor: '#fff',
                borderWidth: 2,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '60%',
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

    <?php if($ipcr['cnt'] > 0): ?>
    // ── Rating Dimensions Radar Chart ──
    var radarCtx = document.getElementById('facRadarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Efficiency', 'Timeliness', 'Quality'],
            datasets: [{
                label: 'Your Rating',
                data: [<?= round($dim_eff, 2) ?>, <?= round($dim_time, 2) ?>, <?= round($dim_qual, 2) ?>],
                backgroundColor: 'rgba(67,97,238,0.15)',
                borderColor: '#4361ee',
                borderWidth: 2,
                pointBackgroundColor: '#4361ee',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }, {
                label: 'Target (4.0)',
                data: [4.0, 4.0, 4.0],
                backgroundColor: 'rgba(39,174,96,0.05)',
                borderColor: '#27ae60',
                borderWidth: 1.5,
                borderDash: [6, 4],
                pointBackgroundColor: '#27ae60',
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                r: {
                    beginAtZero: true,
                    min: 0,
                    max: 5,
                    ticks: { stepSize: 1, backdropColor: 'transparent', font: { size: 10 } },
                    pointLabels: { font: { size: 12, weight: '600' } },
                    grid: { color: '#e9ecef' }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx){ return ctx.dataset.label + ': ' + ctx.raw; }
                    }
                }
            }
        }
    });
    <?php endif; ?>
})();
</script>
