  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="dropdown">
  	
    <a href="./" class="brand-link">
        <?php if(($_SESSION['login_type'] ?? -1) == 2): ?>
        <h3 class="text-center p-0 m-0"><b>ADMIN</b></h3>
        <?php elseif(($_SESSION['login_type'] ?? -1) == 1): ?>
        <h3 class="text-center p-0 m-0"><b>Evaluator</b></h3>
        <?php else: ?>
        <h3 class="text-center p-0 m-0"><b>Employee</b></h3>
        <?php endif; ?>
    </a>
      
    </div>
    <div class="sidebar pb-4 mb-4">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item dropdown">
            <a href="./" class="nav-link nav-home">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <?php
          $lt = $_SESSION['login_type'] ?? -1;
          if ($lt == 0) {
              include 'includes/faculty/sidebar_items.php';
          } elseif ($lt == 1) {
              include 'includes/evaluator/sidebar_items.php';
              include 'includes/shared/sidebar_management_items.php';
          } elseif ($lt == 2) {
              include 'includes/admin/sidebar_items.php';
              include 'includes/shared/sidebar_management_items.php';
          }
          ?>

          <!-- Help — available to all roles -->
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
  		if($('.nav-link.nav-'+page).length > 0){
             $('.nav-link.nav-'+page).addClass('active')
  			if($('.nav-link.nav-'+page).hasClass('tree-item') == true){
           $('.nav-link.nav-'+page).closest('.nav-treeview').siblings('a').addClass('active')
  				$('.nav-link.nav-'+page).closest('.nav-treeview').parent().addClass('menu-open')
  			}
        if($('.nav-link.nav-'+page).hasClass('nav-is-tree') == true){
          $('.nav-link.nav-'+page).parent().addClass('menu-open')
        }

  		}
     
  	})
  </script>
