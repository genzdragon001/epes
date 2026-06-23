<?php include 'db_connect.php' ?>
<?php
$id = intval($_SESSION['login_id']);
$rating_period = isset($_SESSION['rating_period']) ? $_SESSION['rating_period'] : '';

$total_logs = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id = $id")->fetch_row()[0];
$verified_count = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id = $id AND progress = 'Verified'")->fetch_row()[0];
$for_verification = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id = $id AND progress = 'For Verification'")->fetch_row()[0];
$pending_count = $total_logs - $verified_count - $for_verification;

$qry = $conn->query("
    SELECT tp.*, t.success_indicators, r.date_created as rated_on
    FROM task_progress tp
    INNER JOIN task_list t ON tp.task_id = t.id
    LEFT JOIN ratings r ON r.task_id = tp.task_id AND r.employee_id = tp.faculty_id
    WHERE tp.faculty_id = $id
    ORDER BY tp.date_created DESC
");
?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-secondary">
            <div class="inner">
                <h3><?php echo $total_logs ?></h3>
                <p>Total Logs</p>
            </div>
            <div class="icon"><i class="fa fa-file-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-success">
            <div class="inner">
                <h3><?php echo $verified_count ?></h3>
                <p>Verified</p>
            </div>
            <div class="icon"><i class="fa fa-check-double"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-info">
            <div class="inner">
                <h3><?php echo $for_verification ?></h3>
                <p>For Verification</p>
            </div>
            <div class="icon"><i class="fa fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-gradient-warning">
            <div class="inner">
                <h3><?php echo $pending_count ?></h3>
                <p>Pending</p>
            </div>
            <div class="icon"><i class="fa fa-hourglass-half"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h5 class="card-title"><i class="fa fa-list-alt"></i> Submission Logs</h5>
            </div>
            <div class="card-body">
                <?php if($qry->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered" id="list">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th>Success Indicator</th>
                                <th class="text-center" style="width: 150px;">Status</th>
                                <th class="text-center" style="width: 140px;">Submitted Date</th>
                                <th class="text-center" style="width: 140px;">Verified Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while($row = $qry->fetch_assoc()):
                                $progress_class = 'bg-secondary';
                                $progress_icon = 'fa fa-circle';
                                if($row['progress'] == 'Ongoing') {
                                    $progress_class = 'bg-primary';
                                    $progress_icon = 'fa fa-spinner';
                                }
                                elseif($row['progress'] == 'Completed' || $row['progress'] == 'Verified') {
                                    $progress_class = 'bg-success';
                                    $progress_icon = 'fa fa-check-circle';
                                }
                                elseif($row['progress'] == 'Pending') {
                                    $progress_class = 'bg-warning';
                                    $progress_icon = 'fa fa-clock';
                                }
                                elseif($row['progress'] == 'Overdue') {
                                    $progress_class = 'bg-danger';
                                    $progress_icon = 'fa fa-exclamation-circle';
                                }
                                elseif($row['progress'] == 'For Verification') {
                                    $progress_class = 'bg-info';
                                    $progress_icon = 'fa fa-search';
                                }
                                
                                $verified_date = !empty($row['rated_on']) ? date("M d, Y", strtotime($row['rated_on'])) : '';
                            ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?php echo $i++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="mr-2"><i class="fa fa-tasks text-muted"></i></span>
                                        <span><?php echo htmlspecialchars($row['success_indicators']); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-pill <?php echo $progress_class ?> px-3 py-2">
                                        <i class="<?php echo $progress_icon ?> mr-1"></i>
                                        <?php echo htmlspecialchars($row['progress']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted"><i class="fa fa-calendar-alt mr-1"></i><?php echo date("M d, Y", strtotime($row['date_created'])); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($verified_date): ?>
                                        <span class="text-success"><i class="fa fa-check-circle mr-1"></i><?php echo $verified_date ?></span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fa fa-minus"></i> -</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa fa-folder-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No submission logs found</h5>
                    <p class="text-muted">Start by submitting your first task progress</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .small-box { border-radius: 10px; }
    .small-box h3 { font-size: 2.5rem; }
    .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .card-header h5 { margin: 0; font-weight: 600; }
    .table thead th { border-bottom: 2px solid #dee2e6; }
    .badge-pill { font-size: 0.85rem; font-weight: 500; }
    .table td { vertical-align: middle; }
    .bg-gradient-secondary { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; }
    .bg-gradient-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
    .bg-gradient-info { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; }
    .bg-gradient-warning { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529; }
</style>
<script>
    $(document).ready(function(){
        $('#list').dataTable({
            "ordering": true,
            "order": [[0, "asc"]],
            "pageLength": 10,
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
        });
    });
</script>
