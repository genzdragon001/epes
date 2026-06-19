<?php
// Shared sidebar items visible to Dean, Program Head, and Admin
$eval_id = intval($_SESSION['login_id'] ?? 0);
$eval_type = null;
if (($_SESSION['login_type'] ?? -1) == 1) {
    $stmt = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
    $stmt->bind_param("i", $eval_id);
    $stmt->execute();
    $stmt->bind_result($eval_type);
    $stmt->fetch();
    $stmt->close();
}
$is_dean = ($eval_type === 1);
$is_admin = (($_SESSION['login_type'] ?? -1) == 2);
?>
<li class="nav-item dropdown">
  <a href="./index.php?page=faculty_trends" class="nav-link nav-faculty_trends">
    <i class="nav-icon fas fa-chart-line"></i>
    <p>Performance Trends</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=ipcr_view" class="nav-link nav-ipcr_view">
    <i class="nav-icon fas fa-file-alt"></i>
    <p>IPCR Forms</p>
  </a>
</li>
<?php
// DPCR and OPCR are only visible to Dean and Admin
if ($is_dean || $is_admin) { ?>
<li class="nav-item dropdown">
  <a href="./index.php?page=dpcr_view" class="nav-link nav-dpcr_view">
    <i class="nav-icon fas fa-building"></i>
    <p>DPCR Forms</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=opcr_view" class="nav-link nav-opcr_view">
    <i class="nav-icon fas fa-chart-pie"></i>
    <p>OPCR Forms</p>
  </a>
</li>
<?php } ?>
<li class="nav-item dropdown">
  <a href="./index.php?page=document_archive" class="nav-link nav-document_archive">
    <i class="nav-icon fas fa-archive"></i>
    <p>Doc Archive</p>
  </a>
</li>
