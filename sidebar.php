  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand -->
    <a href="./" class="brand-link">
        <?php
        $lt = $_SESSION['login_type'] ?? -1;
        if ($lt == 2): ?>
        <h3 class="text-center p-0 m-0"><b>ADMIN</b></h3>
        <?php elseif ($lt == 1):
            $er = $_SESSION['evaluator_role'] ?? '';
            $role_label = ($er === 'dean') ? 'DEAN' : (($er === 'vp') ? 'VP' : (($er === 'director') ? 'DIRECTOR' : 'EVALUATOR'));
        ?>
        <h3 class="text-center p-0 m-0"><b><?= $role_label ?></b></h3>
        <?php elseif (!empty($_SESSION['is_evaluator'])):
            $er = $_SESSION['evaluator_role'] ?? '';
            $role_label = ($er === 'dean') ? 'DEAN' : (($er === 'vp') ? 'VP' : (($er === 'director') ? 'DIRECTOR' : 'DEPT HEAD'));
        ?>
        <h3 class="text-center p-0 m-0"><b><?= $role_label ?></b></h3>
        <?php else: ?>
        <h3 class="text-center p-0 m-0"><b>FACULTY</b></h3>
        <?php endif; ?>
    </a>

    <div class="sidebar pb-4 mb-4">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">

          <?php
          $lt   = $_SESSION['login_type'] ?? -1;
          $is_eval = !empty($_SESSION['is_evaluator']);
          $er   = $_SESSION['evaluator_role'] ?? '';

          // Resolve dean/supervisor visibility (dean / vp / director => supervisor panels + DPCR/OPCR)
          $is_supervisor = false;
          $eval_type = null;
          if ($lt == 1) {
              // legacy evaluator: look up type from DB
              $eval_id = intval($_SESSION['login_id'] ?? 0);
              $stmt = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
              $stmt->bind_param("i", $eval_id);
              $stmt->execute();
              $stmt->bind_result($eval_type);
              $stmt->fetch(); $stmt->close();
              $is_supervisor = ($eval_type == 1);
          } elseif ($is_eval) {
              $is_supervisor = in_array($er, ['dean','vp','director']);
          }
          $is_admin = ($lt == 2);
          ?>

          <!-- ============ MY WORK ============ -->
          <li class="nav-sec">My Work</li>

          <li class="nav-item">
            <a href="./" class="nav-link nav-home">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <?php if ($lt == 0): /* FACULTY */ ?>
          <li class="nav-item">
            <a href="./index.php?page=target_list" class="nav-link nav-target_list">
              <i class="nav-icon fas fa-bullseye"></i>
              <p>Targets</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=mov_management" class="nav-link nav-mov_management">
              <i class="nav-icon fas fa-folder-open"></i>
              <p>MOV Management</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=status" class="nav-link nav-status">
              <i class="nav-icon fas fa-list"></i>
              <p>Status Log</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=rating" class="nav-link nav-rating">
              <i class="nav-icon fas fa-check"></i>
              <p>Rating</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=archives" class="nav-link nav-archives">
              <i class="nav-icon fas fa-archive"></i>
              <p>Archives</p>
            </a>
          </li>

          <?php elseif ($lt == 1): /* EVALUATOR (legacy) */ ?>
            <?php if ($is_supervisor): ?>
          <li class="nav-item">
            <a href="./index.php?page=faculty_list" class="nav-link nav-faculty_list">
              <i class="nav-icon fas fa-building"></i>
              <p>Department Heads</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=employee_eval_status" class="nav-link nav-employee_eval_status">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>Faculty Evaluation</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=recommendation" class="nav-link nav-recommendation">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>Recommendation</p>
            </a>
          </li>
            <?php else: ?>
          <li class="nav-item">
            <a href="./index.php?page=evaluation" class="nav-link nav-evaluation">
              <i class="nav-icon far fa-edit"></i>
              <p>Evaluation</p>
            </a>
          </li>
            <?php endif; ?>

          <?php elseif ($lt == 2): /* ADMIN */ ?>
          <li class="nav-item">
            <a href="./index.php?page=faculty_list" class="nav-link nav-faculty_list">
              <i class="nav-icon fas fa-users"></i>
              <p>Faculty List</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=evaluation" class="nav-link nav-evaluation">
              <i class="nav-icon fas fa-search"></i>
              <p>Check Evaluation</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=rec_admin" class="nav-link nav-rec_admin">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>COS Recommendations</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=department" class="nav-link nav-department">
              <i class="nav-icon fas fa-th-list"></i>
              <p>Departments</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=college_office" class="nav-link nav-college_office">
              <i class="nav-icon fas fa-university"></i>
              <p>Colleges / Offices</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=designation" class="nav-link nav-designation">
              <i class="nav-icon fas fa-list-alt"></i>
              <p>Designations</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=academic_rank_list" class="nav-link nav-academic_rank_list">
              <i class="nav-icon fas fa-graduation-cap"></i>
              <p>Academic Ranks</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=target_list" class="nav-link nav-target_list">
              <i class="nav-icon fas fa-bullseye"></i>
              <p>Targets Management</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=function_categories" class="nav-link nav-function_categories">
              <i class="nav-icon fas fa-tasks"></i>
              <p>Function Categories</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=percentage_allocation" class="nav-link nav-percentage_allocation">
              <i class="nav-icon fas fa-percent"></i>
              <p>Faculty Allocation</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=rating_period" class="nav-link nav-rating_period">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Rating Period</p>
            </a>
          </li>
          <?php endif; ?>

          <?php if ($lt == 0 && $is_eval): /* FACULTY-who-is-evaluator gets a slim supervisor section */ ?>
          <li class="nav-divider"></li>
          <li class="nav-sec"><?= $is_supervisor ? 'Supervisor Panel' : 'Dept Head Panel' ?></li>
            <?php if ($is_supervisor): ?>
          <li class="nav-item">
            <a href="./index.php?page=faculty_list" class="nav-link nav-faculty_list">
              <i class="nav-icon fas fa-building"></i>
              <p>Department Heads</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=employee_eval_status" class="nav-link nav-employee_eval_status">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>Faculty Evaluation</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=recommendation" class="nav-link nav-recommendation">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>Recommendation</p>
            </a>
          </li>
            <?php else: ?>
          <li class="nav-item">
            <a href="./index.php?page=evaluation" class="nav-link nav-evaluation">
              <i class="nav-icon far fa-edit"></i>
              <p>Evaluation</p>
            </a>
          </li>
            <?php endif; ?>
          <?php endif; ?>

          <!-- ============ REPORTS (hidden for pure faculty) ============ -->
          <?php if (!($lt == 0 && !$is_eval)): ?>
          <li class="nav-divider"></li>
          <li class="nav-sec">Reports</li>
          <li class="nav-item">
            <a href="./index.php?page=faculty_trends" class="nav-link nav-faculty_trends">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Performance Trends</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=ipcr_view" class="nav-link nav-ipcr_view">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>IPCR Forms</p>
            </a>
          </li>
          <?php if ($is_supervisor || $is_admin): ?>
          <li class="nav-item">
            <a href="./index.php?page=dpcr_view" class="nav-link nav-dpcr_view">
              <i class="nav-icon fas fa-building"></i>
              <p>DPCR Forms</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=opcr_view" class="nav-link nav-opcr_view">
              <i class="nav-icon fas fa-chart-pie"></i>
              <p>OPCR Forms</p>
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a href="./index.php?page=document_archive" class="nav-link nav-document_archive">
              <i class="nav-icon fas fa-archive"></i>
              <p>Doc Archive</p>
            </a>
          </li>
          <?php endif; ?>

          <!-- ============ ADMINISTRATION (admin only) ============ -->
          <?php if ($is_admin): ?>
          <li class="nav-divider"></li>
          <li class="nav-sec">Administration</li>
          <li class="nav-item">
            <a href="./index.php?page=new_employee" class="nav-link nav-new_employee tree-item">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>Manage Faculty</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=new_evaluator" class="nav-link nav-new_evaluator tree-item">
              <i class="nav-icon fas fa-user-secret"></i>
              <p>Manage Evaluators</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=assign_evaluator" class="nav-link nav-assign_evaluator tree-item">
              <i class="nav-icon fas fa-user-check"></i>
              <p>Assign Evaluator</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="./index.php?page=new_admin" class="nav-link nav-new_admin tree-item">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>Manage Administrators</p>
            </a>
          </li>
          <?php endif; ?>

          <!-- ============ HELP (all) ============ -->
          <li class="nav-divider"></li>
          <li class="nav-sec">Help &amp; Training</li>
          <li class="nav-item">
            <a href="./index.php?page=help" class="nav-link nav-help">
              <i class="nav-icon fas fa-question-circle"></i>
              <p>Help &amp; Training</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>
  <script>
  	$(document).ready(function(){
      var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
  		var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';
      if(s!='')
        page = page+'_'+s;
  		var $link = $('.nav-link.nav-'+page);
  		if($link.length > 0){
        $link.addClass('active');
        // open parent treeview if this is a tree-item (Administration links)
        if($link.hasClass('tree-item')){
          $link.closest('.nav-treeview').siblings('a').addClass('active');
          $link.closest('.nav-treeview').parent().addClass('menu-open');
        }
      }
  	});
  </script>
