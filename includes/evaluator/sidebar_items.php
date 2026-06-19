<!-- Evaluator (Dean / Program Head) sidebar menu items -->
<?php
$eval_id = intval($_SESSION['login_id'] ?? 0);
$stmt_check = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
$stmt_check->bind_param("i", $eval_id);
$stmt_check->execute();
$stmt_check->bind_result($eval_type);
$stmt_check->fetch();
$stmt_check->close();
$is_dean_sidebar = ($eval_type == 1);
?>

<?php if ($is_dean_sidebar): ?>
<li class="nav-item dropdown">
  <a href="./index.php?page=faculty_list" class="nav-link nav-faculty_list">
    <i class="nav-icon fas fa-building"></i>
    <p>Department Heads</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=employee_eval_status" class="nav-link nav-employee_eval_status">
    <i class="nav-icon fas fa-user-friends"></i>
    <p>Faculty Evaluation</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=recommendation" class="nav-link nav-recommendation">
    <i class="nav-icon fas fa-clipboard-check"></i>
    <p>Recommendation</p>
  </a>
</li>
<?php else: ?>
<li class="nav-item dropdown">
  <a href="./index.php?page=evaluation" class="nav-link nav-evaluation">
    <i class="nav-icon far fa-edit"></i>
    <p>Evaluation</p>
  </a>
</li>
<?php endif; ?>
