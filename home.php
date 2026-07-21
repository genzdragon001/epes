<?php include('db_connect.php') ?>
<?php
$twhere ="";
if(($_SESSION['login_type'] ?? -1) != 1)
  $twhere = "  ";

include 'includes/period_builder.php';

$emp_id   = intval($_SESSION['login_id'] ?? 0);
$emp_type = $_SESSION['login_type'] ?? -1;

// Keep global session rating_period in sync with the active period (used for data entry elsewhere)
if ($active_period) {
    $_SESSION['current_semester'] = $active_period['semester'];
    $_SESSION['current_year']     = $active_period['year'];
    $_SESSION['rating_period']    = epes_short_code($active_period['semester'], $active_period['year']);
}
?>

<!-- ===== SHARED HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0" style="font-weight:700; color:#1a1a2e;"><?= htmlspecialchars($_SESSION['login_name'] ?? 'User') ?></h4>
        <span class="text-muted" style="font-size:0.85rem;">
            <?php
            if ($emp_type == 2) echo 'Administrator';
            elseif ($emp_type == 1) echo 'Evaluator';
            elseif (!empty($_SESSION['is_evaluator'])) {
                $role = $_SESSION['evaluator_role'] ?? 'evaluator';
                echo ucfirst($role) . ' & Faculty';
            } else echo 'Faculty';
            ?>
            <?php if($period_label !== 'No period set'): ?>&middot; <?= htmlspecialchars($period_label) ?><?php endif; ?>
        </span>
    </div>
    <?php if(!empty($real_periods)): ?>
    <select id="period_selector" class="form-control form-control-sm"
            onchange="window.location.href='index.php?page=home&period='+encodeURIComponent(this.value)"
            style="width:auto; font-size:0.85rem; padding:6px 28px 6px 12px; max-width:260px;">
        <?php foreach($real_periods as $rp):
            $key = epes_period_key($rp['semester'], $rp['year']);
            $sel_key = $selected_period ? epes_period_key($selected_period['semester'], $selected_period['year']) : '';
            $opt_label = $rp['semester'] . ' ' . $rp['year'] . ($rp['is_active'] ? ' (current)' : '');
        ?>
        <option value="<?= htmlspecialchars($key) ?>" <?= $key === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
</div>

<?php if($emp_type == 2): ?>
  <?php include 'includes/admin/home_content.php'; ?>
<?php elseif($emp_type == 1): ?>
  <?php include 'includes/evaluator/home_content.php'; ?>
<?php elseif($emp_type == 0 && !empty($_SESSION['is_evaluator'])): ?>
  <!-- Merged Faculty + Evaluator: show BOTH dashboards, clearly separated -->
  <div class="dashboard-section-divider">
      <span class="dashboard-section-title"><i class="fas fa-user-graduate mr-1"></i>My Faculty Dashboard</span>
      <small class="text-muted">Your targets, submissions, and IPCR rating</small>
  </div>
  <?php include 'includes/faculty/home_content.php'; ?>

  <div class="dashboard-section-divider mt-4">
      <span class="dashboard-section-title"><i class="fas fa-user-tie mr-1"></i>
      <?php
      $role = $_SESSION['evaluator_role'] ?? 'evaluator';
      echo ucfirst($role);
      ?>
      Dashboard</span>
      <small class="text-muted">Faculty under your supervision</small>
  </div>
  <?php include 'includes/evaluator/home_content.php'; ?>
<?php else: ?>
  <?php include 'includes/faculty/home_content.php'; ?>
<?php endif; ?>

<!-- ===== MODERN DASHBOARD STYLES ===== -->
<style>
/* ── Section Divider ── */
.dashboard-section-divider {
    border-top: 2px solid #e9ecef;
    padding-top: 14px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.dashboard-section-divider:first-child {
    border-top: none;
    padding-top: 0;
}
.dashboard-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1a1a2e;
}
@media (max-width: 767px) {
    .dashboard-section-title { font-size: 0.95rem; }
}

/* ── Stat Cards ── */
.stat-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px 18px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.stat-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px; height: 100%;
    border-radius: 10px 0 0 10px;
}
.stat-card.accent-blue::after  { background: linear-gradient(180deg, #4361ee, #3a0ca3); }
.stat-card.accent-green::after { background: linear-gradient(180deg, #2ecc71, #27ae60); }
.stat-card.accent-amber::after { background: linear-gradient(180deg, #f39c12, #e67e22); }
.stat-card.accent-purple::after{ background: linear-gradient(180deg, #9b59b6, #8e44ad); }
.stat-card.accent-teal::after  { background: linear-gradient(180deg, #1abc9c, #16a085); }

.stat-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 12px;
}
.stat-icon.blue   { background: #eef2ff; color: #4361ee; }
.stat-icon.green  { background: #e8f8f5; color: #27ae60; }
.stat-icon.amber  { background: #fef5e7; color: #e67e22; }
.stat-icon.purple { background: #f4ecf7; color: #8e44ad; }
.stat-icon.teal   { background: #e8f8f5; color: #16a085; }

.stat-value {
    font-size: 1.7rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.2;
    margin-bottom: 2px;
}
.stat-label {
    font-size: 0.78rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}
.stat-sub {
    font-size: 0.73rem;
    margin-top: 4px;
    font-weight: 600;
}
.stat-sub.green  { color: #27ae60; }
.stat-sub.amber  { color: #e67e22; }
.stat-sub.red    { color: #e74c3c; }

/* ── Chart Cards ── */
.chart-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: none;
    transition: box-shadow 0.2s;
}
.chart-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.chart-card-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: #1a1a2e;
    border-radius: 10px 10px 0 0;
}
.chart-card-header small { font-weight: 400; color: #6c757d; }
.chart-card-body { padding: 18px; }
.chart-wrap {
    position: relative;
    width: 100%;
}
.chart-wrap canvas { width: 100% !important; }

/* ── Activity List ── */
.activity-list { display: flex; flex-direction: column; }
.activity-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.83rem;
    transition: background 0.15s;
}
.activity-item:hover { background: #f8f9fa; }
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #adb5bd;
    flex-shrink: 0;
}
.activity-dot.green  { background: #27ae60; }
.activity-dot.amber  { background: #f39c12; }
.activity-dot.red    { background: #e74c3c; }
.activity-name { flex: 1; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.activity-status { color: #6c757d; font-size: 0.75rem; font-weight: 500; }
.activity-time { color: #adb5bd; font-size: 0.72rem; min-width: 42px; text-align: right; }

/* ── OPCR Badge ── */
.opcr-badge {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 0.85rem;
    font-weight: 600;
}
.opcr-badge strong { font-size: 1.3rem; }
.opcr-badge .green  { color: #27ae60; }
.opcr-badge .amber  { color: #e67e22; }
.opcr-badge .red    { color: #e74c3c; }

/* ── Alert Banner ── */
.alert-banner {
    border-left: 4px solid #f39c12;
    background: #fef9e7;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* ── Responsive ── */
@media (max-width: 991px) {
    /* Stat cards: 2 per row on tablet */
    .stat-card { padding: 16px 14px; }
    .stat-value { font-size: 1.4rem; }
    .stat-label { font-size: 0.72rem; }
    .stat-icon { width: 36px; height: 36px; font-size: 1rem; margin-bottom: 8px; }
}

@media (max-width: 767px) {
    /* Header: stack vertically */
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 .badge {
        align-self: flex-start;
    }

    /* Stat cards: full width, compact, square */
    .stat-card { 
        padding: 12px 14px; 
        margin-bottom: 8px; 
        aspect-ratio: 1 / 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .stat-value { font-size: 1.25rem; }
    .stat-label { font-size: 0.7rem; letter-spacing: 0.3px; }
    .stat-sub { font-size: 0.68rem; }
    .stat-icon { width: 32px; height: 32px; font-size: 0.9rem; margin-bottom: 6px; border-radius: 8px; }

    /* Chart cards: full width, reduced height */
    .chart-card-body { padding: 10px; }
    .chart-card-header { padding: 10px 14px; font-size: 0.82rem; flex-wrap: wrap; gap: 4px; }
    .chart-wrap[style*="height:300px"] { height: 220px !important; }
    .chart-wrap[style*="height:280px"] { height: 200px !important; }

    /* Donut charts: smaller */
    canvas[id$="Donut"] { max-width: 180px !important; max-height: 180px !important; }
    canvas[id$="RadarChart"] { max-width: 260px !important; max-height: 220px !important; }

    /* Tables: horizontal scroll, compact */
    .table-responsive, div[style*="overflow-x:auto"] { -webkit-overflow-scrolling: touch; }
    .table-flat, .table-sm { font-size: 0.75rem !important; }
    .table-flat th, .table-sm th { padding: 6px 8px !important; }
    .table-flat td, .table-sm td { padding: 5px 8px !important; }

    /* Activity list: compact */
    .activity-item { padding: 8px 12px; font-size: 0.78rem; gap: 6px; }
    .activity-name { font-size: 0.76rem; }
    .activity-status { font-size: 0.7rem; }
    .activity-time { font-size: 0.68rem; min-width: 36px; }

    /* OPCR badge: stack */
    .opcr-badge { flex-direction: column; align-items: flex-start; gap: 4px; padding: 10px 14px; }
    .opcr-badge strong { font-size: 1.1rem; }

    /* Alert banner: compact */
    .alert-banner { padding: 8px 12px; font-size: 0.8rem; }

    /* Legend dots: wrap */
    .d-flex.justify-content-center[style*="gap:16px"],
    .d-flex.flex-wrap.justify-content-center[style*="gap:12px"] {
        gap: 8px !important;
        font-size: 0.7rem !important;
    }

    /* Row spacing: tighter */
    .row.mb-4 { margin-bottom: 12px !important; }
    .mb-3 { margin-bottom: 8px !important; }

    /* OPCR score: smaller */
    .chart-card-body.text-center div[style*="font-size:3.5rem"] {
        font-size: 2.5rem !important;
    }
}

@media (max-width: 575px) {
    /* Extra small: even more compact */
    .stat-card { padding: 10px 12px; }
    .stat-value { font-size: 1.15rem; }
    .stat-icon { width: 28px; height: 28px; font-size: 0.8rem; }

    h4.mb-0 { font-size: 1.1rem; }

    .chart-wrap[style*="height:300px"] { height: 180px !important; }
    .chart-wrap[style*="height:280px"] { height: 160px !important; }
    canvas[id$="Donut"] { max-width: 150px !important; max-height: 150px !important; }
    canvas[id$="RadarChart"] { max-width: 220px !important; max-height: 180px !important; }

    .chart-card-header { font-size: 0.78rem; }
    .chart-card-header small { display: none; }

    .activity-item { padding: 6px 10px; }
    .activity-name { max-width: 140px; }

    .brand-link h3 { font-size: 1rem; }
}
</style>
