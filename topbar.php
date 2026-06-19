<!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-primary navbar-dark ">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <?php if(isset($_SESSION['login_id'])): ?>
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="" role="button"><i class="fas fa-bars"></i></a>
      </li>
    <?php endif; ?>
      <li>
        <a class="nav-link text-white"  href="./" role="button"> <large><b><?php echo $_SESSION['system']['name'] ?></b></large></a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto">
   
     <p class="text-white" >  <?php echo ($_SESSION['current_semester'] ?? 'N/A') . ' ' . ($_SESSION['current_year'] ?? ''); ?>
     </p>
     
      <!-- Notifications Bell -->
      <?php
      require_once 'notification_helper.php';
      $notif_count = get_unread_count($conn, $_SESSION['login_id'], $_SESSION['login_type']);
      ?>
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" title="Notifications">
          <i class="fas fa-bell"></i>
          <?php if ($notif_count > 0): ?>
          <span class="badge badge-danger navbar-badge" id="notif_badge"><?= $notif_count > 99 ? '99+' : $notif_count ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-height: 400px; overflow-y: auto;">
          <span class="dropdown-header">Notifications</span>
          <div class="dropdown-divider"></div>
          <?php
          $notifications = get_recent_notifications($conn, $_SESSION['login_id'], $_SESSION['login_type'], 8);
          if (count($notifications) > 0):
            foreach ($notifications as $n):
              $icon = $n['type'] === 'Success' ? 'fa-check-circle text-success' : 
                     ($n['type'] === 'Warning' ? 'fa-exclamation-triangle text-warning' : 
                     ($n['type'] === 'Danger' ? 'fa-times-circle text-danger' : 'fa-info-circle text-info'));
              $time = date('M d, h:i A', strtotime($n['created_at']));
          ?>
          <a href="<?= $n['link'] ? $n['link'] : 'index.php?page=notifications' ?>" class="dropdown-item">
            <i class="fas <?= $icon ?> mr-2"></i> <?= htmlspecialchars($n['title']) ?>
            <span class="float-right text-muted text-sm"><?= $time ?></span>
            <br><small class="text-muted"><?= htmlspecialchars(substr($n['message'], 0, 80)) ?><?= strlen($n['message']) > 80 ? '...' : '' ?></small>
          </a>
          <div class="dropdown-divider"></div>
          <?php endforeach; ?>
          <a href="index.php?page=notifications" class="dropdown-item dropdown-footer">See All Notifications</a>
          <?php else: ?>
          <a href="#" class="dropdown-item text-muted text-center">No new notifications</a>
          <?php endif; ?>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>

      
     <li class="nav-item dropdown">
            <a class="nav-link"  data-toggle="dropdown" aria-expanded="true" href="javascript:void(0)">
              <span>
                <div class="d-felx badge-pill">
                  <span class=""><img src="assets/uploads/<?php echo $_SESSION['login_avatar'] ?>" alt="" class="user-img border "></span>
                  <span><b><?php echo ucwords($_SESSION['login_firstname']) ?></b></span>
                  <span class="fa fa-angle-down ml-2"></span>
                </div>
              </span>
            </a>
            <div class="dropdown-menu" aria-labelledby="account_settings" style="left: -2.5em;">
              <a class="dropdown-item" href="javascript:void(0)" id="manage_account"><i class="fa fa-cog"></i> Manage Account</a>
              <a class="dropdown-item" href="ajax.php?action=logout"><i class="fa fa-power-off"></i> Logout</a>
            </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->
  <script>
     $('#manage_account').click(function(){
        uni_modal('Manage Account','manage_user.php?id=<?php echo $_SESSION['login_id'] ?>')
      })
  </script>
  <style>
    .user-img {
        border-radius: 50%;
        height: 25px;
        width: 25px;
        object-fit: cover;
    }
  </style>
