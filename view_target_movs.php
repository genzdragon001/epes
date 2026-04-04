<?php 
include 'db_connect.php'; 
session_start();

$target_id = intval($_GET['target_id']);
$faculty_id = $_SESSION['login_id'];

// Get target info
$target = $conn->query("SELECT COALESCE(major_output, success_indicators) as name, 
    category, mfo, success_indicators 
    FROM task_list WHERE id = $target_id")->fetch_assoc();

// Get MOVs for this target from mov_uploads
$movs = $conn->query("SELECT m.*, 
    DATE_FORMAT(m.date_submitted, '%Y-%m-%d %H:%i') as date_submitted
    FROM mov_uploads m
    WHERE m.faculty_id = $faculty_id AND m.target_id = $target_id
    ORDER BY m.date_submitted DESC");
?>

<div class="container-fluid">
    <div class="alert alert-info mb-3">
        <p class="mb-0"><?php echo htmlspecialchars($target['name']); ?></p>
        <?php if (!empty($target['success_indicators'])): ?>
        <small><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($target['success_indicators']); ?></small>
        <?php endif; ?>
    </div>
    
    <?php if ($movs && $movs->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th class="text-center" style="width: 40px;">#</th>
                    <th style="width: 25%;">File</th>
                    <th style="width: 25%;">Date Submitted</th>
                    <th style="width: 20%;">Rating Period</th>
                    <th class="text-center" style="width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                while ($mov = $movs->fetch_assoc()): 
                    $file_size = $mov['file_size'] ?? 0;
                    $size_units = ['B', 'KB', 'MB', 'GB'];
                    $size_index = 0;
                    while ($file_size >= 1024 && $size_index < count($size_units) - 1) {
                        $file_size /= 1024;
                        $size_index++;
                    }
                    $formatted_size = round($file_size, 2) . ' ' . $size_units[$size_index];
                    $badge_class = [
                        'Pending' => 'badge-warning',
                        'Verified' => 'badge-success',
                        'Rejected' => 'badge-danger'
                    ];
                    $status = $mov['status'] ?? 'Pending';
                ?>
                <tr>
                    <td class="text-center font-weight-bold"><?php echo $i++; ?></td>
                    <td>
                        <a href="<?php echo $mov['file_path'] . '.' . $mov['file_type']; ?>" target="_blank" class="btn btn-sm btn-info">
                            <i class="fa fa-download"></i> Download
                        </a>
                        <br><small><?php echo $formatted_size; ?> - <?php echo strtoupper($mov['file_type']); ?></small>
                    </td>
                    <td><?php echo date('M d, Y h:i A', strtotime($mov['date_submitted'])); ?></td>
                    <td><small><?php echo htmlspecialchars($mov['rating_period']); ?></small></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-default btn-flat border-info" 
                            onclick="viewMOV(<?php echo $mov['id']; ?>)" title="View">
                            <i class="fa fa-eye text-info"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-default btn-flat border-danger" 
                            onclick="deleteMOV(<?php echo $mov['id']; ?>)" title="Delete">
                            <i class="fa fa-trash text-danger"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot class="thead-dark">
                <tr>
                    <td colspan="2" class="text-right"><strong>Total:</strong></td>
                    <td colspan="5"><strong><?php echo $i - 1; ?> MOV<?php echo ($i - 1) !== 1 ? 's' : ''; ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
        <h5>No MOVs uploaded for this target yet</h5>
        <p>Click "Add MOV" to upload your first MOV for this target</p>
    </div>
    <?php endif; ?>
    
    <div class="text-right mt-3">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
</div>

<script>
function viewMOV(id) {
    uni_modal('<i class="fa fa-eye"></i> MOV Details', 'view_mov.php?id=' + id, 'mid-large');
}

function deleteMOV(id) {
    _conf('Are you sure you want to delete this MOV?', 'deleteMOV', [id]);
}

function deleteMOV(id) {
    start_load();
    $.ajax({
        url: 'ajax.php?action=delete_mov',
        method: 'POST',
        data: { id: id },
        success: function(resp) {
            if (resp == 1) {
                alert_toast('MOV successfully deleted', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                end_load();
                alert_toast('Failed to delete MOV', 'danger');
            }
        },
        error: function() {
            end_load();
            alert_toast('Error occurred', 'danger');
        }
    });
}
</script>

<style>
.alert-info {
    border-left: 4px solid #17a2b8;
    background-color: #17a2b8 !important;
    color: white !important;
}
.alert-info h5, .alert-info p, .alert-info small, .alert-info i {
    color: white !important;
}
</style>
