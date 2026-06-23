<!-- Faculty sidebar menu items (with conditional evaluator items) -->
<li class="nav-item dropdown">
  <a href="./index.php?page=target_list" class="nav-link nav-target_list">
    <i class="nav-icon fas fa-tasks"></i>
    <p>Targets</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=mov_management" class="nav-link nav-mov_management">
    <i class="nav-icon fas fa-folder-open"></i>
    <p>MOV Management</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=status" class="nav-link nav-status">
    <i class="nav-icon fas fa-list"></i>
    <p>Status Log</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=rating" class="nav-link nav-rating">
    <i class="nav-icon fas fa-check"></i>
    <p>Rating</p>
  </a>
</li>
<li class="nav-item dropdown">
  <a href="./index.php?page=archives" class="nav-link nav-archives">
    <i class="nav-icon fas fa-archive"></i>
    <p>Archives</p>
  </a>
</li>

<?php if (isset($_SESSION['is_evaluator']) && $_SESSION['is_evaluator']): ?>
<?php
$eval_role = $_SESSION['evaluator_role'] ?? '';
$is_dean_sidebar = ($eval_role === 'dean');
?>
<!-- ===== EVALUATOR SECTION (visually separated) ===== -->
<li class="nav-item" style="border-top: 1px solid rgba(255,255,255,0.15); margin-top: 6px; padding-top: 4px;">
  <div class="nav-link disabled" style="color: rgba(255,255,255,0.5); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; cursor: default;">
    <i class="nav-icon fas fa-user-shield" style="font-size: 0.8rem;"></i>
    <p style="font-size: 0.7rem;"><?= $is_dean_sidebar ? 'Dean Panel' : 'Dept Head Panel' ?></p>
  </div>
</li>
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
<?php endif; ?>
