<?php include 'db_connect.php';
$eval_id = intval($_SESSION['login_id']);

$faculty_data = [];
$result = $conn->query("
    SELECT e.id, e.firstname, e.middlename, e.lastname, d.department
    FROM employee_list e
    LEFT JOIN department_list d ON e.department_id = d.id
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['id'];
        $stats = $conn->query("
            SELECT 
                COUNT(DISTINCT task_id) as total_tasks,
                SUM(CASE WHEN progress = 'Verified' THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN progress = 'For Verification' THEN 1 ELSE 0 END) as for_verification
            FROM task_progress WHERE faculty_id = $emp_id
        ")->fetch_assoc();
        $row['total_tasks'] = $stats['total_tasks'] ?? 0;
        $row['verified'] = $stats['verified'] ?? 0;
        $row['for_verification'] = $stats['for_verification'] ?? 0;
        $row['pending'] = $row['total_tasks'] - $row['verified'] - $row['for_verification'];
        $faculty_data[] = $row;
    }
}

usort($faculty_data, function($a, $b) {
    return strcmp($a['department'], $b['department']);
});
?>
<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fa fa-user-friends"></i> Faculty Evaluation Status
            </h5>
        </div>
        <div class="card-body">
            <?php if(count($faculty_data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th class="text-center">Total Tasks</th>
                            <th class="text-center">For Verification</th>
                            <th class="text-center">Verified</th>
                         
                            <th class="text-center" style="width: 120px;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($faculty_data as $row): 
                            $total_f = $row['total_tasks'] > 0 ? $row['total_tasks'] : 1;
                            $progress_pct = $row['total_tasks'] > 0 ? round(($row['verified'] / $row['total_tasks']) * 100) : 0;
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']) ?></strong></td>
                            <td><?= htmlspecialchars($row['department'] ?? 'N/A') ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $row['total_tasks'] ?></span></td>
                            <td class="text-center"><span class="badge badge-warning"><?= $row['for_verification'] ?></span></td>
                            <td class="text-center"><span class="badge badge-success"><?= $row['verified'] ?></span></td>
                            
                            <td class="text-center">
                                <div class="progress mb-0" style="height: 15px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress_pct ?>%">
                                        <?= $progress_pct ?>%
                                    </div>
                                </div>
                                <small class="text-muted"><?= $row['verified'] ?>/<?= $row['total_tasks'] ?> verified</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No faculty records found</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .card-title { margin: 0; font-weight: 600; }
</style>
