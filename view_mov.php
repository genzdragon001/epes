<?php 
include 'db_connect.php'; 
require_once __DIR__ . '/auth_helper.php';
// Session already started by db_connect.php; do NOT call session_start() again.
$id = intval($_GET['id']);
$mov = $conn->query("SELECT m.*, 
    COALESCE(t.major_output, t.success_indicators) as target_name,
    t.success_indicators,
    t.category,
    t.mfo,
    CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) as faculty_name
    FROM mov_uploads m
    LEFT JOIN task_list t ON m.target_id = t.id
    LEFT JOIN employee_list e ON m.faculty_id = e.id
    WHERE m.id = $id")->fetch_assoc();

if (!$mov) {
    echo "MOV not found";
    exit;
}

// Access: evaluators (login_type 1) or faculty with evaluator designation (login_type 0 + is_evaluator).
$can_verify = is_evaluator();
$mov_status = $mov['status'] ?? 'Pending';
$status_badge = [
    'Pending'  => 'badge-warning',
    'Verified' => 'badge-success',
    'Rejected' => 'badge-danger',
][$mov_status] ?? 'badge-secondary';

$file_size = $mov['file_size'];
$size_units = ['B', 'KB', 'MB', 'GB'];
$size_index = 0;
while ($file_size >= 1024 && $size_index < count($size_units) - 1) {
    $file_size /= 1024;
    $size_index++;
}
$formatted_size = round($file_size, 2) . ' ' . $size_units[$size_index];

$file_path = $mov['file_path'] . '.' . $mov['file_type'];
$file_type = strtolower($mov['file_type']);
?>

<div class="container-fluid">
    <!-- MOV Metadata -->
    <dl class="row mb-2">
        <dt class="col-sm-3">Target</dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars($mov['target_name']); ?></dd>
        <dt class="col-sm-3">Faculty</dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars($mov['faculty_name']); ?></dd>
        <dt class="col-sm-3">Title</dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars($mov['title']); ?></dd>
        <dt class="col-sm-3">Type</dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars(ucfirst($mov['mov_type'] ?? 'N/A')); ?></dd>
        <dt class="col-sm-3">Period</dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars($mov['rating_period']); ?></dd>
        <dt class="col-sm-3">Submitted</dt>
        <dd class="col-sm-9"><?php echo date('M d, Y h:i A', strtotime($mov['date_submitted'])); ?></dd>
        <dt class="col-sm-3">Status</dt>
        <dd class="col-sm-9"><span class="badge <?= $status_badge ?>" id="movStatusBadge"><?= htmlspecialchars($mov_status) ?></span></dd>
        <?php if ($mov_status == 'Rejected' && !empty($mov['remarks'])): ?>
        <dt class="col-sm-3">Remarks</dt>
        <dd class="col-sm-9 text-danger"><?php echo nl2br(htmlspecialchars($mov['remarks'])); ?></dd>
        <?php endif; ?>
    </dl>

    <!-- File Preview Only -->
    <div class="card card-outline card-primary mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fa fa-file"></i> <?php echo htmlspecialchars($mov['file_name']); ?>
            </h5>
        </div>
        <div class="card-body text-center" style="min-height: 400px; background: #f5f5f5;">
            <?php 
            $image_types = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            if (in_array($file_type, $image_types)): 
            ?>
                <!-- Image Preview -->
                <img src="<?php echo $file_path; ?>" alt="MOV File" style="max-width: 100%; max-height: 500px;">
            <?php elseif ($file_type == 'pdf'): ?>
                <!-- PDF Preview -->
                <iframe src="<?php echo $file_path; ?>" style="width: 100%; height: 500px; border: 1px solid #ddd;"></iframe>
            <?php else: ?>
                <!-- File Preview Not Available -->
                <div style="padding: 100px 20px;">
                    <i class="fa fa-file-o" style="font-size: 100px; color: #ccc;"></i>
                    <h4 class="mt-3">Preview not available for this file type</h4>
                    <p class="text-muted">File: <?php echo htmlspecialchars($mov['file_name']); ?></p>
                    <p class="text-muted">Type: <?php echo strtoupper($file_type); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center mb-3">
        <a href="<?php echo $file_path; ?>" download class="btn btn-primary btn-lg">
            <i class="fa fa-download"></i> Download File
        </a>
        <?php if ($can_verify && $mov_status != 'Verified'): ?>
        <button type="button" class="btn btn-success btn-lg ml-2" id="verifyMovBtn" data-id="<?= $id ?>">
            <i class="fa fa-check-double"></i> Verify
        </button>
        <?php endif; ?>
        <?php if ($can_verify && $mov_status != 'Rejected'): ?>
        <button type="button" class="btn btn-danger btn-lg ml-2" id="rejectMovBtn" data-id="<?= $id ?>">
            <i class="fa fa-times"></i> Reject
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary btn-lg ml-2" data-dismiss="modal">
            <i class="fa fa-times"></i> Close
        </button>
    </div>

    <?php if ($can_verify): ?>
    <!-- Rejection remarks (hidden until Reject clicked) -->
    <div id="rejectBox" style="display:none;" class="mb-3">
        <div class="form-group">
            <label for="rejectRemarks" class="font-weight-bold">Rejection Remarks</label>
            <textarea class="form-control" id="rejectRemarks" rows="3" placeholder="Explain why this MOV is rejected..."></textarea>
        </div>
        <button type="button" class="btn btn-danger btn-sm" id="confirmRejectBtn" data-id="<?= $id ?>">
            <i class="fa fa-check"></i> Confirm Rejection
        </button>
        <button type="button" class="btn btn-default btn-sm" id="cancelRejectBtn">Cancel</button>
    </div>
    <?php endif; ?>
</div>

<style>
dl {
    margin-bottom: 10px;
}
dt {
    font-weight: 600;
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
}
dd {
    margin-left: 0;
    margin-bottom: 15px;
    font-size: 14px;
}
.card-body {
    padding: 1.25rem;
}
</style>

<script>
$(document).ready(function(){
    // Verify MOV
    $('#verifyMovBtn').click(function(){
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verifying...');
        $.ajax({
            url: 'ajax.php?action=verify_mov',
            method: 'POST',
            data: { id: id, status: 'Verified' },
            success: function(resp){
                if (resp == 1) {
                    alert_toast('MOV verified successfully.', 'success');
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    alert_toast('Failed to verify MOV.', 'danger');
                    btn.prop('disabled', false).html('<i class="fa fa-check-double"></i> Verify');
                }
            },
            error: function(){
                alert_toast('Error occurred.', 'danger');
                btn.prop('disabled', false).html('<i class="fa fa-check-double"></i> Verify');
            }
        });
    });

    // Reject MOV
    $('#rejectMovBtn').click(function(){
        $('#rejectBox').slideDown();
        $(this).hide();
    });
    $('#cancelRejectBtn').click(function(){
        $('#rejectBox').slideUp();
        $('#rejectMovBtn').show();
    });
    $('#confirmRejectBtn').click(function(){
        var btn = $(this);
        var id = btn.data('id');
        var remarks = $('#rejectRemarks').val().trim();
        if (!remarks) {
            alert_toast('Please enter rejection remarks.', 'warning');
            return;
        }
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Rejecting...');
        $.ajax({
            url: 'ajax.php?action=verify_mov',
            method: 'POST',
            data: { id: id, status: 'Rejected', remarks: remarks },
            success: function(resp){
                if (resp == 1) {
                    alert_toast('MOV rejected.', 'success');
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    alert_toast('Failed to reject MOV.', 'danger');
                    btn.prop('disabled', false).text('Confirm Rejection');
                }
            },
            error: function(){
                alert_toast('Error occurred.', 'danger');
                btn.prop('disabled', false).text('Confirm Rejection');
            }
        });
    });
});
</script>
