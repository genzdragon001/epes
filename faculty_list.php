<?php include 'db_connect.php';
$login_type = $_SESSION['login_type'];
$eval_id = intval($_SESSION['login_id']);
$is_admin = ($login_type == 2);
$is_dean = false;
$is_dept_head = false;
$dept_id = 0;

if (!$is_admin) {
    $stmt_type = $conn->prepare("SELECT type, department_id FROM evaluator_list WHERE id = ?");
    $stmt_type->bind_param("i", $eval_id);
    $stmt_type->execute();
    $stmt_type->bind_result($eval_type, $dept_id);
    $stmt_type->fetch();
    $stmt_type->close();
    
    $is_dean = ($eval_type == 1);
    $is_dept_head = ($eval_type == 0);
}
?>
<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fa fa-users"></i> 
                <?php 
                if($is_admin) echo 'All Faculty';
                elseif($is_dean) echo 'Department Heads';
                elseif($is_dept_head) echo 'Faculty Under My Department';
                else echo 'Faculty';
                ?>
            </h5>
        </div>
        <div class="card-body">
            <?php
            if($is_admin) {
                $faculty_data = [];
                $result = $conn->query("
                    SELECT e.id, e.firstname, e.middlename, e.lastname, dl.designation, dep.department
                    FROM employee_list e
                    LEFT JOIN designation_list dl ON e.designation_id = dl.id
                    LEFT JOIN department_list dep ON e.department_id = dep.id
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
            } elseif($is_dean) {
                $faculty_data = [];
                $result = $conn->query("
                    SELECT e.id, e.firstname, e.middlename, e.lastname, dl.designation, dep.department
                    FROM employee_list e
                    LEFT JOIN designation_list dl ON e.designation_id = dl.id
                    LEFT JOIN department_list dep ON e.department_id = dep.id
                    WHERE e.designation_id = 2
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
            } elseif($is_dept_head) {
                $faculty_data = [];
                $result = $conn->query("
                    SELECT e.id, e.firstname, e.middlename, e.lastname, dl.designation
                    FROM employee_list e
                    LEFT JOIN designation_list dl ON e.designation_id = dl.id
                    LEFT JOIN evaluator_list ev ON e.firstname = ev.firstname AND e.lastname = ev.lastname AND ev.type = '0' AND ev.department_id = $dept_id
                    WHERE e.department_id = $dept_id AND ev.id IS NULL
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
            } else {
                $faculty_data = [];
                $result = $conn->query("
                    SELECT e.id, e.firstname, e.middlename, e.lastname, d.designation, dep.department
                    FROM employee_list e
                    LEFT JOIN designation_list d ON e.designation_id = d.id
                    LEFT JOIN department_list dep ON e.department_id = dep.id
                    WHERE e.department_id = $dept_id
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
            }
            ?>
            
            <?php if(count($faculty_data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th><?php echo $is_admin ? 'Faculty Name' : ($is_dean ? 'Department Head' : 'Faculty Name'); ?></th>
                            <th><?php echo $is_admin ? 'Department' : 'Designation'; ?></th>
                            <?php if($is_admin || $is_dean || $is_dept_head): ?>
                            <th class="text-center" style="width: 100px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($faculty_data as $row): ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']) ?></strong></td>
                            <td><?= htmlspecialchars($is_admin ? ($row['department'] ?? 'N/A') : ($row['designation'] ?? 'Faculty')) ?></td>
                            <?php if($is_admin || $is_dean || $is_dept_head): ?>
                            <td class="text-center">
                                <a href="index.php?page=evaluation&id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fa fa-search"></i> Check Evaluation
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No records found</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .card-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
    .card-title { margin: 0; font-weight: 600; }
</style>
