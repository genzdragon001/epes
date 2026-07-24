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

<!-- Slim period selector: control only — the page title is rendered by the shell header -->
<?php if(!empty($real_periods)): ?>
<div class="d-flex justify-content-end mb-3">
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
</div>
<?php endif; ?>

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
