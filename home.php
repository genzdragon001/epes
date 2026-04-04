<?php include('db_connect.php') ?>
<?php
$twhere ="";
if($_SESSION['login_type'] != 1)
  $twhere = "  ";

list($start, $end) = explode("-", $_SESSION['current_year']);
$short_year = substr($start, -2) . substr($end, -2);

switch($_SESSION['current_semester']) {
	case '1st Semester':
		$rating_period = "1-".$short_year; break;
	case '2nd Semester': 
		$rating_period = "2-".$short_year; break;
	case 'Summer': 
		$rating_period = "S-".$short_year; break;
}
$_SESSION['rating_period'] = $rating_period;

$emp_id = intval($_SESSION['login_id']);
$emp_type = $_SESSION['login_type'];
?>

<?php if($_SESSION['login_type'] == 2): ?>
<?php
// === ADMIN ANALYTICS DATA ===

// Basic counts
$total_departments = $conn->query("SELECT * FROM department_list")->num_rows;
$total_designations = $conn->query("SELECT * FROM designation_list")->num_rows;
$total_users = $conn->query("SELECT * FROM users")->num_rows;
$total_employees = $conn->query("SELECT * FROM employee_list")->num_rows;
$total_evaluators = $conn->query("SELECT * FROM evaluator_list")->num_rows;
$total_tasks = $conn->query("SELECT * FROM task_list")->num_rows;

// COS Faculty Stats
$cos_employees = $conn->query("SELECT * FROM employee_list WHERE position_id = '18'")->num_rows;
$permanent_employees = $total_employees - $cos_employees;

// Task & Progress Stats
$total_progress = $conn->query("SELECT * FROM task_progress")->num_rows;
$verified_tasks = $conn->query("SELECT * FROM task_progress WHERE progress = 'Verified'")->num_rows;
$pending_tasks = $conn->query("SELECT * FROM task_progress WHERE progress = 'For Verification'")->num_rows;

// Ratings Stats
$total_ratings = $conn->query("SELECT * FROM ratings")->num_rows;
$avg_overall_rating = $conn->query("SELECT AVG((efficiency + timeliness + quality) / 3) as avg FROM ratings")->fetch_assoc()['avg'] ?? 0;

// Recommendations Stats
$total_recommendations = $conn->query("SELECT * FROM renewal_recommendations")->num_rows;
$rec_recommended = $conn->query("SELECT * FROM renewal_recommendations WHERE recommendation_status = 'Recommended'")->num_rows;
$rec_not_recommended = $conn->query("SELECT * FROM renewal_recommendations WHERE recommendation_status = 'Not Recommended'")->num_rows;
$rec_pending = $conn->query("SELECT * FROM renewal_recommendations WHERE recommendation_status = 'Pending'")->num_rows;

// Department Performance Data
$dept_stats = $conn->query("
    SELECT d.department,
        COUNT(DISTINCT e.id) as total_employees,
        COUNT(DISTINCT tp.id) as total_submissions,
        SUM(CASE WHEN tp.progress = 'Verified' THEN 1 ELSE 0 END) as verified
    FROM department_list d
    LEFT JOIN employee_list e ON e.department_id = d.id
    LEFT JOIN task_progress tp ON tp.faculty_id = e.id
    GROUP BY d.id, d.department
");

$dept_labels = [];
$dept_employees = [];
$dept_verified = [];
$dept_pending = [];
while($d = $dept_stats->fetch_assoc()) {
    $dept_labels[] = $d['department'];
    $dept_employees[] = $d['total_employees'];
    $dept_verified[] = $d['verified'];
    $dept_pending[] = $d['total_submissions'] - $d['verified'];
}

// Rating Distribution
$rating_dist = $conn->query("
    SELECT 
        CASE 
            WHEN (efficiency + timeliness + quality) / 3 >= 4.75 THEN 'Outstanding'
            WHEN (efficiency + timeliness + quality) / 3 >= 3.61 THEN 'Very Satisfactory'
            WHEN (efficiency + timeliness + quality) / 3 >= 2.61 THEN 'Satisfactory'
            WHEN (efficiency + timeliness + quality) / 3 >= 1.61 THEN 'Unsatisfactory'
            ELSE 'Poor'
        END as rating_category,
        COUNT(*) as count
    FROM ratings
    GROUP BY rating_category
");
$rating_labels = [];
$rating_counts = [];
while($r = $rating_dist->fetch_assoc()) {
    $rating_labels[] = $r['rating_category'];
    $rating_counts[] = $r['count'];
}

// Monthly Submissions
$monthly_stats = $conn->query("
    SELECT DATE_FORMAT(date_created, '%Y-%m') as month, COUNT(*) as count
    FROM task_progress
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$monthly_labels = [];
$monthly_counts = [];
while($m = $monthly_stats->fetch_assoc()) {
    $monthly_labels[] = $m['month'];
    $monthly_counts[] = $m['count'];
}

// COS Recommendations by Period
$cos_by_period = $conn->query("
    SELECT rating_period,
        SUM(CASE WHEN recommendation_status = 'Recommended' THEN 1 ELSE 0 END) as recommended,
        SUM(CASE WHEN recommendation_status = 'Not Recommended' THEN 1 ELSE 0 END) as not_recommended,
        SUM(CASE WHEN recommendation_status = 'Pending' THEN 1 ELSE 0 END) as pending,
        COUNT(*) as total
    FROM renewal_recommendations
    GROUP BY rating_period
");
$period_labels = [];
$period_recommended = [];
$period_not_recommended = [];
$period_pending = [];
while($p = $cos_by_period->fetch_assoc()) {
    $period_labels[] = $p['rating_period'];
    $period_recommended[] = $p['recommended'];
    $period_not_recommended[] = $p['not_recommended'];
    $period_pending[] = $p['pending'];
}
?>

<div class="row">
  <div class="col-12">
    <h4>Welcome, <?php echo $_SESSION['login_name'] ?>!</h4>
    <p class="text-muted">Administrator Dashboard - <?php echo $_SESSION['current_semester'] ?> <?php echo $_SESSION['current_year'] ?></p>
  </div>
</div>

<!-- KPI Cards Row 1 -->
<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-primary">
      <div class="inner">
        <h3><?php echo $total_employees ?></h3>
        <p>Total Employees</p>
      </div>
      <div class="icon"><i class="fa fa-users"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-info">
      <div class="inner">
        <h3><?php echo $cos_employees ?></h3>
        <p>COS Faculty</p>
      </div>
      <div class="icon"><i class="fa fa-user-tie"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-success">
      <div class="inner">
        <h3><?php echo $total_recommendations ?></h3>
        <p>Recommendations</p>
      </div>
      <div class="icon"><i class="fa fa-clipboard-check"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-warning">
      <div class="inner">
        <h3><?php echo $verified_tasks ?></h3>
        <p>Verified Tasks</p>
      </div>
      <div class="icon"><i class="fa fa-check-circle"></i></div>
    </div>
  </div>
</div>

<!-- KPI Cards Row 2 -->
<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-danger">
      <div class="inner">
        <h3><?php echo $rec_recommended ?></h3>
        <p>Recommended</p>
      </div>
      <div class="icon"><i class="fa fa-thumbs-up"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-secondary">
      <div class="inner">
        <h3><?php echo $rec_pending ?></h3>
        <p>Pending Review</p>
      </div>
      <div class="icon"><i class="fa fa-clock"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-dark">
      <div class="inner">
        <h3><?php echo number_format($avg_overall_rating, 2) ?></h3>
        <p>Avg Rating Score</p>
      </div>
      <div class="icon"><i class="fa fa-star"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-primary">
      <div class="inner">
        <h3><?php echo $total_departments ?></h3>
        <p>Departments</p>
      </div>
      <div class="icon"><i class="fa fa-building"></i></div>
    </div>
  </div>
</div>

<!-- Charts Row 1 -->
<div class="row mt-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-dark">
        <h5 class="card-title"><i class="fa fa-chart-bar"></i> Employee Distribution by Department</h5>
      </div>
      <div class="card-body">
        <canvas id="deptEmpChart" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-success">
        <h5 class="card-title"><i class="fa fa-chart-pie"></i> Task Verification Status</h5>
      </div>
      <div class="card-body">
        <canvas id="taskStatusChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Charts Row 2 -->
<div class="row mt-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-primary">
        <h5 class="card-title"><i class="fa fa-chart-bar"></i> Rating Distribution</h5>
      </div>
      <div class="card-body">
        <canvas id="ratingDistChart" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-info">
        <h5 class="card-title"><i class="fa fa-chart-bar"></i> COS Recommendations by Period</h5>
      </div>
      <div class="card-body">
        <canvas id="cosRecChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Performance Summary Cards -->
<div class="row mt-4">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-gradient-success">
        <h5 class="card-title"><i class="fa fa-tasks"></i> Task Completion Rate</h5>
      </div>
      <div class="card-body text-center">
        <h1 class="text-success"><?php echo $total_progress > 0 ? round(($verified_tasks / $total_progress) * 100) : 0 ?>%</h1>
        <p class="text-muted"><?php echo $verified_tasks ?> of <?php echo $total_progress ?> tasks verified</p>
        <div class="progress mt-3" style="height: 20px;">
          <div class="progress-bar bg-success" style="width: <?php echo $total_progress > 0 ? ($verified_tasks / $total_progress) * 100 : 0 ?>%"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-gradient-primary">
        <h5 class="card-title"><i class="fa fa-user-tie"></i> COS Faculty Ratio</h5>
      </div>
      <div class="card-body text-center">
        <h1 class="text-primary"><?php echo $total_employees > 0 ? round(($cos_employees / $total_employees) * 100) : 0 ?>%</h1>
        <p class="text-muted"><?php echo $cos_employees ?> COS out of <?php echo $total_employees ?> total employees</p>
        <div class="progress mt-3" style="height: 20px;">
          <div class="progress-bar bg-primary" style="width: <?php echo $total_employees > 0 ? ($cos_employees / $total_employees) * 100 : 0 ?>%"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-gradient-warning">
        <h5 class="card-title"><i class="fa fa-clipboard-check"></i> Recommendation Rate</h5>
      </div>
      <div class="card-body text-center">
        <h1 class="text-warning"><?php echo $total_recommendations > 0 ? round(($rec_recommended / $total_recommendations) * 100) : 0 ?>%</h1>
        <p class="text-muted"><?php echo $rec_recommended ?> recommended of <?php echo $total_recommendations ?> total</p>
        <div class="progress mt-3" style="height: 20px;">
          <div class="progress-bar bg-warning" style="width: <?php echo $total_recommendations > 0 ? ($rec_recommended / $total_recommendations) * 100 : 0 ?>%"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Department Employee Chart
    const ctx1 = document.getElementById('deptEmpChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dept_labels); ?>,
            datasets: [{
                label: 'Employees',
                data: <?php echo json_encode($dept_employees); ?>,
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: { y: { beginAtZero: true } }
        }
    });

    // Task Status Doughnut Chart
    const ctx2 = document.getElementById('taskStatusChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Verified', 'Pending', 'For Verification'],
            datasets: [{
                data: [<?php echo $verified_tasks ?>, <?php echo $total_progress - $verified_tasks - $pending_tasks ?>, <?php echo $pending_tasks ?>],
                backgroundColor: ['#28a745', '#ffc107', '#17a2b8']
            }]
        },
        options: { responsive: true }
    });

    // Rating Distribution Chart
    const ctx3 = document.getElementById('ratingDistChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($rating_labels); ?>,
            datasets: [{
                label: 'Count',
                data: <?php echo json_encode($rating_counts); ?>,
                backgroundColor: ['#28a745', '#007bff', '#17a2b8', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

    // COS Recommendations Chart
    const ctx4 = document.getElementById('cosRecChart').getContext('2d');
    new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($period_labels); ?>,
            datasets: [
                {
                    label: 'Recommended',
                    data: <?php echo json_encode($period_recommended); ?>,
                    backgroundColor: '#28a745'
                },
                {
                    label: 'Not Recommended',
                    data: <?php echo json_encode($period_not_recommended); ?>,
                    backgroundColor: '#dc3545'
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode($period_pending); ?>,
                    backgroundColor: '#ffc107'
                }
            ]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<?php elseif($_SESSION['login_type'] == 1): ?>
<?php
$eval_id = intval($_SESSION['login_id']);

$stmt_type = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
$stmt_type->bind_param("i", $eval_id);
$stmt_type->execute();
$stmt_type->bind_result($eval_type);
$stmt_type->fetch();
$stmt_type->close();

$is_dean = ($eval_type == 1);

$total_faculty = $conn->query("SELECT COUNT(*) FROM employee_list WHERE evaluator_id = $eval_id")->fetch_row()[0];
$total_targets = $conn->query("SELECT COUNT(*) FROM task_list")->fetch_row()[0];
$total_submissions = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id = e.id WHERE e.evaluator_id = $eval_id")->fetch_row()[0];
$verified_submissions = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id = e.id WHERE e.evaluator_id = $eval_id AND tp.progress = 'Verified'")->fetch_row()[0];
$for_verification = $conn->query("SELECT COUNT(*) FROM task_progress tp INNER JOIN employee_list e ON tp.faculty_id = e.id WHERE e.evaluator_id = $eval_id AND tp.progress = 'For Verification'")->fetch_row()[0];
$pending_submissions = $total_submissions - $verified_submissions - $for_verification;

if($is_dean) {
    $dept_stats = $conn->query("
        SELECT d.department,
            COUNT(DISTINCT e.id) as total_faculty,
            COUNT(DISTINCT tp.id) as total_submissions,
            SUM(CASE WHEN tp.progress = 'Verified' THEN 1 ELSE 0 END) as verified
        FROM department_list d
        LEFT JOIN employee_list e ON e.department_id = d.id
        LEFT JOIN task_progress tp ON tp.faculty_id = e.id
        GROUP BY d.id, d.department
    ");
    
    $dept_labels = [];
    $dept_verified = [];
    $dept_pending = [];
    while($d = $dept_stats->fetch_assoc()) {
        $dept_labels[] = $d['department'];
        $dept_verified[] = $d['verified'];
        $dept_pending[] = $d['total_submissions'] - $d['verified'];
    }
} else {
    $dept_labels = [];
    $dept_verified = [];
    $dept_pending = [];
}

$recent_evaluations = $conn->query("
    SELECT tp.*, e.firstname, e.lastname, t.success_indicators
    FROM task_progress tp
    INNER JOIN employee_list e ON tp.faculty_id = e.id
    INNER JOIN task_list t ON tp.task_id = t.id
    WHERE e.evaluator_id = $eval_id
    ORDER BY tp.date_created DESC
    LIMIT 10
");
?>
<div class="row">
  <div class="col-12">
    <h4>Welcome, <?php echo $_SESSION['login_name'] ?>!</h4>
    <p class="text-muted"><?php echo $is_dean ? 'Dean' : 'Department Head'; ?> Dashboard - <?php echo $_SESSION['current_semester'] ?> <?php echo $_SESSION['current_year'] ?></p>
  </div>
</div>

<div class="row mt-3">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-primary">
      <div class="inner">
        <h3><?php echo $total_faculty ?></h3>
        <p>Total Faculty</p>
      </div>
      <div class="icon"><i class="fa fa-users"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-info">
      <div class="inner">
        <h3><?php echo $total_submissions ?></h3>
        <p>Total Submissions</p>
      </div>
      <div class="icon"><i class="fa fa-upload"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-warning">
      <div class="inner">
        <h3><?php echo $for_verification ?></h3>
        <p>For Verification</p>
      </div>
      <div class="icon"><i class="fa fa-clock"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-gradient-success">
      <div class="inner">
        <h3><?php echo $verified_submissions ?></h3>
        <p>Verified</p>
      </div>
      <div class="icon"><i class="fa fa-check-circle"></i></div>
    </div>
  </div>
</div>

<div class="row mt-3">
  <div class="col-lg-4 col-6">
    <div class="small-box bg-gradient-success">
      <div class="inner">
        <h3><?php echo $total_targets > 0 ? round(($verified_submissions / $total_targets) * 100) : 0 ?>%</h3>
        <p>Verification Rate</p>
      </div>
      <div class="icon"><i class="fa fa-chart-pie"></i></div>
    </div>
  </div>
  <div class="col-lg-4 col-6">
    <div class="small-box bg-gradient-danger">
      <div class="inner">
        <h3><?php echo $pending_submissions ?></h3>
        <p>Pending</p>
      </div>
      <div class="icon"><i class="fa fa-hourglass-half"></i></div>
    </div>
  </div>
  <div class="col-lg-4 col-6">
    <div class="small-box bg-gradient-secondary">
      <div class="inner">
        <h3><?php echo $total_targets ?></h3>
        <p>Total Targets</p>
      </div>
      <div class="icon"><i class="fa fa-bullseye"></i></div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-dark">
        <h5 class="card-title"><i class="fa fa-chart-bar"></i> Analytics Summary</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-md-6 border-right">
            <h6 class="text-muted">Verification Progress</h6>
            <div class="progress mt-2" style="height: 25px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $total_targets > 0 ? ($verified_submissions / $total_targets) * 100 : 0 ?>%">
                <?php echo $total_targets > 0 ? round(($verified_submissions / $total_targets) * 100) : 0 ?>%
              </div>
            </div>
            <small class="text-muted"><?php echo $verified_submissions ?> of <?php echo $total_targets ?> verified</small>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted">Submission vs Verification</h6>
            <div class="d-flex justify-content-center align-items-center mt-2">
              <span class="badge badge-info mr-2"><?php echo $total_submissions ?> Submitted</span>
              <span class="badge badge-success"><?php echo $verified_submissions ?> Verified</span>
            </div>
            <small class="text-muted"><?php echo $for_verification ?> awaiting verification</small>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <?php if($is_dean): ?>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-primary">
        <h5 class="card-title"><i class="fa fa-chart-pie"></i> Overall Performance by Department</h5>
      </div>
      <div class="card-body">
        <canvas id="deptChart" height="150"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('deptChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dept_labels); ?>,
            datasets: [
                {
                    label: 'Verified',
                    data: <?php echo json_encode($dept_verified); ?>,
                    backgroundColor: '#28a745'
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode($dept_pending); ?>,
                    backgroundColor: '#ffc107'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
  <?php else: ?>
</div>
  <?php endif; ?>
  
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header bg-gradient-dark">
        <h5 class="card-title"><i class="fa fa-history"></i> Recent Evaluations</h5>
      </div>
      <div class="card-body p-0">
        <?php if($recent_evaluations->num_rows > 0): ?>
        <table class="table table-hover table-sm mb-0">
          <thead class="bg-light">
            <tr>
              <th>Faculty</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $recent_evaluations->fetch_assoc()): 
              $prog_class = 'secondary';
              if($row['progress'] == 'Verified') $prog_class = 'success';
              elseif($row['progress'] == 'For Verification') $prog_class = 'info';
              elseif($row['progress'] == 'Completed') $prog_class = 'success';
              elseif($row['progress'] == 'Pending') $prog_class = 'warning';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></td>
              <td><span class="badge badge-<?php echo $prog_class ?>"><?php echo $row['progress'] ?></span></td>
              <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">No recent evaluations</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<?php
$emp_qry = $conn->query("SELECT e.*, p.position as position_name, d.designation as designation_name FROM employee_list e LEFT JOIN position_list p ON e.position_id = p.id LEFT JOIN designation_list d ON e.designation_id = d.id WHERE e.id = $emp_id LIMIT 1");
$emp_data = $emp_qry->fetch_assoc();
$emp_position_id = intval($emp_data['position_id'] ?? 0);
$emp_designation_id = intval($emp_data['designation_id'] ?? 0);
$employee_position_name = $emp_data['position_name'] ?? 'Unknown';
$employee_designation_name = $emp_data['designation_name'] ?? '';
$is_department_head = (stripos($employee_designation_name, 'Head') !== false || stripos($employee_designation_name, 'Director') !== false);
$is_cos = ($emp_position_id == 19);

$allocations = [];
$alloc_qry = $conn->query("SELECT * FROM percentage_allocation 
    WHERE position_id = $emp_position_id 
    AND (designation_id IS NULL OR designation_id = " . intval($emp_designation_id) . ")
    AND is_active = 1");
while ($row = $alloc_qry->fetch_assoc()) {
    $key = $row['category'];
    if ($row['sub_category']) {
        $key .= '_' . $row['sub_category'];
    }
    $allocations[$key] = floatval($row['percentage']);
}

$cat_filters = [];
$has_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0 || $is_department_head;
$has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
$has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && !$is_cos;
$has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && !$is_cos;
$has_support = isset($allocations['support']) && $allocations['support'] > 0;

if ($has_strategic) $cat_filters[] = "t.category = 'strategic'";
if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
if ($has_support) $cat_filters[] = "t.category = 'support'";

$where = "t.is_active = 1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $emp_position_id)";
if (!empty($cat_filters)) {
    $where .= " AND (" . implode(" OR ", $cat_filters) . ")";
}

$total_targets = $conn->query("SELECT COUNT(*) FROM task_list t WHERE $where AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = $emp_position_id)")->fetch_row()[0];

$total_tasks = $conn->query("SELECT COUNT(DISTINCT task_id) FROM task_progress WHERE faculty_id = $emp_id")->fetch_row()[0];
$verified_submissions = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id = $emp_id AND progress = 'Verified'")->fetch_row()[0];
$for_verification = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id = $emp_id AND progress = 'For Verification'")->fetch_row()[0];
$total_submissions = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id = $emp_id")->fetch_row()[0];
$completed_tasks = $conn->query("SELECT COUNT(DISTINCT task_id) FROM task_progress WHERE faculty_id = $emp_id AND progress IN ('Completed', 'Verified')")->fetch_row()[0];
$pending_tasks = $total_tasks - $completed_tasks;
$ongoing_tasks = $conn->query("SELECT COUNT(DISTINCT task_id) FROM task_progress WHERE faculty_id = $emp_id AND progress = 'For Verification'")->fetch_row()[0];

$upcoming_deadlines = $conn->query("SELECT tp.task_id, tp.progress, tp.date_created FROM task_progress tp WHERE tp.faculty_id = $emp_id ORDER BY tp.date_created DESC LIMIT 5");

$recent_submissions = $conn->query("SELECT tp.*, tp.progress, tp.date_created FROM task_progress tp WHERE tp.faculty_id = $emp_id ORDER BY tp.date_created DESC LIMIT 5");
?>
<div class="row">
  <div class="col-12">
    <h4>Welcome, <?php echo $_SESSION['login_name'] ?>!</h4>
    <p class="text-muted"><?php echo $employee_position_name ?><?php echo $employee_designation_name ? ' - ' . $employee_designation_name : '' ?> - <?php echo $_SESSION['current_semester'] ?> <?php echo $_SESSION['current_year'] ?></p>
  </div>
</div>

<div class="row mt-3">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-secondary">
      <div class="inner">
        <h3><?php echo $total_targets ?></h3>
        <p>Total Targets</p>
      </div>
      <div class="icon"><i class="fa fa-bullseye"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3><?php echo $total_tasks ?></h3>
        <p>Submitted</p>
      </div>
      <div class="icon"><i class="fa fa-tasks"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-primary">
      <div class="inner">
        <h3><?php echo $ongoing_tasks ?></h3>
        <p>For Verification</p>
      </div>
      <div class="icon"><i class="fa fa-spinner"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3><?php echo $verified_submissions ?></h3>
        <p>Verified</p>
      </div>
      <div class="icon"><i class="fa fa-check-circle"></i></div>
    </div>
  </div>
</div>

<div class="row mt-3">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3><?php echo $verified_submissions ?></h3>
        <p>Verified</p>
      </div>
      <div class="icon"><i class="fa fa-check-double"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-secondary">
      <div class="inner">
        <h3><?php echo $for_verification ?></h3>
        <p>For Verification</p>
      </div>
      <div class="icon"><i class="fa fa-clock"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-dark">
      <div class="inner">
        <h3><?php echo $total_submissions ?></h3>
        <p>Total Submissions</p>
      </div>
      <div class="icon"><i class="fa fa-upload"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-danger">
      <div class="inner">
        <h3><?php echo $total_targets > 0 ? round(($total_tasks / $total_targets) * 100) : 0 ?>%</h3>
        <p>Submission Rate</p>
      </div>
      <div class="icon"><i class="fa fa-chart-pie"></i></div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title"><i class="fa fa-list"></i> Recent Activity</h5>
      </div>
      <div class="card-body p-0">
        <?php if($upcoming_deadlines->num_rows > 0): ?>
        <table class="table table-hover table-sm mb-0">
          <thead class="bg-light">
            <tr>
              <th>#</th>
              <th>Progress</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php $j = 1; while($row = $upcoming_deadlines->fetch_assoc()) { 
              $prog_class = 'secondary';
              if($row['progress'] == 'Verified') $prog_class = 'success';
              elseif($row['progress'] == 'For Verification') $prog_class = 'info';
              elseif($row['progress'] == 'Completed') $prog_class = 'success';
              elseif($row['progress'] == 'Pending') $prog_class = 'warning';
            ?>
            <tr>
              <td><?php echo $j++ ?></td>
              <td><span class="badge badge-<?php echo $prog_class ?>"><?php echo htmlspecialchars($row['progress']) ?></span></td>
              <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">No upcoming submissions</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title"><i class="fa fa-history"></i> Submission History</h5>
      </div>
      <div class="card-body p-0">
        <?php if($recent_submissions->num_rows > 0): ?>
        <table class="table table-hover table-sm mb-0">
          <thead class="bg-light">
            <tr>
              <th>Task ID</th>
              <th>Progress</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $recent_submissions->fetch_assoc()) { 
              $prog_class = 'secondary';
              if($row['progress'] == 'Verified') $prog_class = 'success';
              elseif($row['progress'] == 'For Verification') $prog_class = 'info';
              elseif($row['progress'] == 'Completed') $prog_class = 'success';
              elseif($row['progress'] == 'Pending') $prog_class = 'warning';
            ?>
            <tr>
              <td><?php echo $row['task_id'] ?></td>
              <td><span class="badge badge-<?php echo $prog_class ?>"><?php echo $row['progress'] ?></span></td>
              <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">No recent submissions</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title"><i class="fa fa-chart-bar"></i> Analytics Summary</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 text-center border-right">
            <h6 class="text-muted">Submission Rate</h6>
            <div class="progress mt-2" style="height: 25px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $total_targets > 0 ? ($total_tasks / $total_targets) * 100 : 0 ?>%">
                <?php echo $total_targets > 0 ? round(($total_tasks / $total_targets) * 100) : 0 ?>%
              </div>
            </div>
            <small class="text-muted"><?php echo $total_tasks ?> of <?php echo $total_targets ?> targets submitted</small>
          </div>
          <div class="col-md-4 text-center border-right">
            <h6 class="text-muted">Verification Rate</h6>
            <div class="progress mt-2" style="height: 25px;">
              <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $total_submissions > 0 ? ($verified_submissions / $total_submissions) * 100 : 0 ?>%">
                <?php echo $total_submissions > 0 ? round(($verified_submissions / $total_submissions) * 100) : 0 ?>%
              </div>
            </div>
            <small class="text-muted"><?php echo $verified_submissions ?> of <?php echo $total_submissions ?> submissions verified</small>
          </div>
          <div class="col-md-4 text-center">
            <h6 class="text-muted">Pending vs Verified</h6>
            <div class="d-flex justify-content-center align-items-center mt-2">
              <span class="badge badge-warning mr-2"><?php echo $total_tasks - $verified_submissions ?> Pending</span>
              <span class="badge badge-success"><?php echo $verified_submissions ?> Verified</span>
            </div>
            <small class="text-muted"><?php echo $for_verification ?> for verification</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
