  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="dropdown">
   	<a href="./" class="brand-link">
        <?php if($_SESSION['login_type'] == 2): ?>
        <h3 class="text-center p-0 m-0"><b>ADMIN</b></h3>
        <?php elseif($_SESSION['login_type'] == 1): ?>
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
              <p>
                Dashboard
              </p>
            </a>
          </li> 
          <?php if($_SESSION['login_type'] != 1): ?>
           <li class="nav-item dropdown">
            <a href="./index.php?page=target_list" class="nav-link nav-target_list">
              <i class="nav-icon fas fa-tasks"></i>
              <p>
                Targets
              </p>
            </a>
          </li>
          <?php endif; ?> 
        
          
          <!-- show only if user -->
          <?php if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == '0'): ?>


            <li class="nav-item dropdown">
                  <a href="./index.php?page=mov_management" class="nav-link nav-mov_management">
                      <i class="nav-icon fas fa-folder-open"></i>
                      <p>
                          MOV Management
                      </p>
                  </a>
              </li>     
                        
              <li class="nav-item dropdown">
                  <a href="./index.php?page=status" class="nav-link nav-status">
                      <i class="nav-icon fas fa-list"></i>
                      <p>
                          Status Log
                      </p>
                  </a>
              </li>     
                        
              <li class="nav-item dropdown">
                  <a href="./index.php?page=rating" class="nav-link nav-rating">
                      <i class="nav-icon fas fa-check"></i>
                      <p>
                          Rating
                      </p>
                  </a>
              </li>     
                                            
           <?php endif; ?>
           <!-- end show -->

            <?php if($_SESSION['login_type'] == 1): ?>
              <?php
              $eval_id = intval($_SESSION['login_id']);
              $stmt_check = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
              $stmt_check->bind_param("i", $eval_id);
              $stmt_check->execute();
              $stmt_check->bind_result($eval_type);
              $stmt_check->fetch();
              $stmt_check->close();
              $is_dean_sidebar = ($eval_type == 1);
              ?>
              <?php if($is_dean_sidebar): ?>
              <li class="nav-item dropdown">
              <a href="./index.php?page=faculty_list" class="nav-link nav-faculty_list">
                <i class="nav-icon fas fa-building"></i>
                <p>
                  Department Heads
                </p>
              </a>
            </li>
              <li class="nav-item dropdown">
              <a href="./index.php?page=employee_eval_status" class="nav-link nav-employee_eval_status">
                <i class="nav-icon fas fa-user-friends"></i>
                <p>
                  Faculty Evaluation
                </p>
              </a>
            </li>
              <?php else: ?>
              <li class="nav-item dropdown">
              <a href="./index.php?page=evaluation" class="nav-link nav-evaluation">
                <i class="nav-icon far fa-edit"></i>
                <p>
                  Evaluation
                </p>
              </a>
            </li>
              <?php endif; ?>
              <?php if($is_dean_sidebar): ?>
              <li class="nav-item dropdown">
              <a href="./index.php?page=recommendation" class="nav-link nav-recommendation">
                <i class="nav-icon fas fa-clipboard-check"></i>
                <p>
                  Recommendation
                </p>
              </a>
            </li>
              <?php endif; ?>
             <?php elseif($_SESSION['login_type'] == 2): ?>
            <li class="nav-item dropdown">
            <a href="./index.php?page=faculty_list" class="nav-link nav-faculty_list">
              <i class="nav-icon far fa-edit"></i>
              <p>
                Faculty List
              </p>
            </a>
          </li>
            <li class="nav-item dropdown">
            <a href="./index.php?page=evaluation" class="nav-link nav-evaluation">
              <i class="nav-icon far fa-edit"></i>
              <p>
                Evaluation
              </p>
            </a>
          </li>
            <li class="nav-item dropdown">
            <a href="./index.php?page=rec_admin" class="nav-link nav-rec_admin">
              <i class="nav-icon fas fa-clipboard-check"></i>
              <p>
                COS Recommendations
              </p>
            </a>
          </li>
          <?php endif; ?>
          <?php if($_SESSION['login_type'] == 2): ?>
          <li class="nav-item dropdown">
            <a href="./index.php?page=department" class="nav-link nav-department">
              <i class="nav-icon fas fa-th-list"></i>
              <p>
                Departments
              </p>
            </a>
          </li> 
          <li class="nav-item dropdown">
            <a href="./index.php?page=designation" class="nav-link nav-designation">
              <i class="nav-icon fas fa-list-alt"></i>
              <p>
                Designations
              </p>
            </a>
          </li> 
          <li class="nav-item dropdown">
            <a href="./index.php?page=academic_rank_list" class="nav-link nav-academic_rank_list">
              <i class="nav-icon fas fa-graduation-cap"></i>
              <p>
                Academic Ranks
              </p>
            </a>
          </li> 
          <li class="nav-item dropdown">
            <a href="./index.php?page=function_categories" class="nav-link nav-function_categories">
              <i class="nav-icon fas fa-tasks"></i>
              <p>
                Function Categories
              </p>
            </a>
          </li> 
          <li class="nav-item dropdown">
            <a href="./index.php?page=percentage_allocation" class="nav-link nav-percentage_allocation">
              <i class="nav-icon fas fa-percent"></i>
              <p>
                Faculty Allocation
              </p>
            </a>
          </li> 
          <li class="nav-item dropdown">
            <a href="./index.php?page=sample_evaluation" class="nav-link nav-sample_evaluation">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>
                Sample Evaluation
              </p>
            </a>
          </li> 
          <li class="nav-item dropdown">
            <a href="./index.php?page=rating_period" class="nav-link nav-rating_period">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>
                Rating Period
              </p>
            </a>
          </li> 
          <li class="nav-item">
            <a href="#" class="nav-link nav-edit_employee">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>
                Employees
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./index.php?page=new_employee" class="nav-link nav-new_employee tree-item">
                  <i class="fas fa-angle-right nav-icon"></i>
                  <p>Add New</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./index.php?page=employee_list" class="nav-link nav-employee_list tree-item">
                  <i class="fas fa-angle-right nav-icon"></i>
                  <p>List</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link nav-edit_evaluator">
              <i class="nav-icon fas fa-user-secret"></i>
              <p>
                Evaluator
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./index.php?page=new_evaluator" class="nav-link nav-new_evaluator tree-item">
                  <i class="fas fa-angle-right nav-icon"></i>
                  <p>Add New</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./index.php?page=evaluator_list" class="nav-link nav-evaluator_list tree-item">
                  <i class="fas fa-angle-right nav-icon"></i>
                  <p>List</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link nav-edit_user">
              <i class="nav-icon fas fa-users"></i>
              <p>
                Administrator
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./index.php?page=new_admin" class="nav-link nav-new_admin tree-item">
                  <i class="fas fa-angle-right nav-icon"></i>
                  <p>Add New</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./index.php?page=admin_list" class="nav-link nav-admin_list tree-item">
                  <i class="fas fa-angle-right nav-icon"></i>
                  <p>List</p>
                </a>
              </li>
            </ul>
          </li>
          
        <?php endif; ?>
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