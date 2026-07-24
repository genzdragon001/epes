<?php 
include 'db_connect.php'; 

// Session is already started by db_connect.php — do NOT call session_start() again.
// Accept an optional faculty_id (from evaluator drill-down); default to the viewer.
$target_id = intval($_GET['target_id']);
$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : intval($_SESSION['login_id'] ?? 0);

// Get target info (include deadlines for the Additional MOVs table)
$target = $conn->query("SELECT COALESCE(major_output, success_indicators) as name, 
    category, mfo, success_indicators 
    FROM task_list WHERE id = $target_id")->fetch_assoc();

// Fetch all deadlines for this target (matched per-MOV by submission month/year)
$target_deadlines = [];
$dl_res = $conn->query("SELECT deadline FROM target_deadlines WHERE target_id = $target_id ORDER BY deadline");
if ($dl_res) {
    while ($dl_row = $dl_res->fetch_assoc()) {
        $target_deadlines[] = $dl_row['deadline'];
    }
}

// Access: use shared helper — covers login_type 1 (legacy evaluator)
// and login_type 0 with is_evaluator flag (Dean/Dept Head/VP/Director).
require_once __DIR__ . '/auth_helper.php';
$can_verify = is_evaluator();
// Delete is a faculty-only (owner) action. Owner = the viewer is the faculty
// whose MOVs are shown AND they are not acting as an evaluator.
$viewer_id = intval($_SESSION['login_id'] ?? 0);
$is_owner = ($viewer_id === $faculty_id) && !$can_verify;

// ---------------------------------------------------------------------------
// PRIMARY / MAIN MOV: submission uploaded from the Target List (task_progress).
// This is the authoritative submission for the target.
// ---------------------------------------------------------------------------
$progress = null;
$pq = $conn->query("SELECT * FROM task_progress 
    WHERE task_id = $target_id AND faculty_id = $faculty_id 
    ORDER BY date_created DESC LIMIT 1");
if ($pq && $pq->num_rows > 0) {
    $progress = $pq->fetch_assoc();
    $progress['real_path'] = epes_real_file_path($progress['file_path'], $progress['file_type']) ?: '';
    $progress['progress']  = $progress['progress'] ?? '';
}

$prog_status = $progress['progress'] ?? '';
$prog_badge = [
    'For Verification' => 'badge-warning',
    'Verified'         => 'badge-success',
    'N/A'              => 'badge-secondary',
    ''                 => 'badge-secondary'
][$prog_status] ?? 'badge-secondary';

$main_type  = strtolower($progress['file_type'] ?? '');
$main_size  = '';
if (!empty($progress['real_path']) && file_exists($progress['real_path'])) {
    $bytes = filesize($progress['real_path']);
    $su = ['B','KB','MB','GB'];
    $si = 0;
    while ($bytes >= 1024 && $si < count($su) - 1) { $bytes /= 1024; $si++; }
    $main_size = round($bytes, 2) . ' ' . $su[$si];
}
$main_date = !empty($progress['date_created']) ? date('M d, Y h:i A', strtotime($progress['date_created'])) : '';

$image_types = ['jpg','jpeg','png','gif','bmp','webp'];

// ---------------------------------------------------------------------------
// EXTRA MOVs: additional evidence uploaded via MOV Management (mov_uploads).
// ---------------------------------------------------------------------------
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

    <!-- ============ MAIN SUBMISSION (from Target List) ============ -->
    <div class="card mb-3 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fa fa-upload mr-1"></i> Submitted MOV <small class="font-weight-normal">(from Target List)</small></h6>
            <span class="badge <?= $prog_badge ?>"><?= htmlspecialchars($prog_status ?: 'None') ?></span>
        </div>
        <div class="card-body">
            <?php if ($progress && !empty($progress['real_path'])): ?>
                <?php if (in_array($main_type, $image_types)): ?>
                    <div class="text-center mb-2">
                        <img src="<?= htmlspecialchars($progress['real_path']) ?>" class="img-fluid" style="max-height: 360px;" alt="Submitted MOV">
                    </div>
                <?php elseif ($main_type === 'pdf'): ?>
                    <iframe src="<?= htmlspecialchars($progress['real_path']) ?>" style="width:100%; height:360px; border:1px solid #ddd;"></iframe>
                <?php else: ?>
                    <p class="text-muted mb-2"><i class="fa fa-file-o"></i> Preview not available for this file type. Use the download button below.</p>
                <?php endif; ?>
                <div class="small text-muted mb-2">
                    <?= strtoupper($main_type) ?><?= $main_size ? ' &middot; ' . htmlspecialchars($main_size) : '' ?>
                    <?= $main_date ? ' &middot; Submitted ' . htmlspecialchars($main_date) : '' ?>
                </div>
                <?php if (!empty($progress['actual_accomplishment'])): ?>
                    <div class="p-2 mb-2" style="background:#f8f9fa; border-left:4px solid #007bff; border-radius:4px;">
                        <small class="font-weight-bold text-muted">Actual Accomplishment:</small><br>
                        <?= nl2br(htmlspecialchars($progress['actual_accomplishment'])) ?>
                    </div>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($progress['real_path']) ?>" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-external-link"></i> Open
                </a>
                <a href="<?= htmlspecialchars($progress['real_path']) ?>" download class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-download"></i> Download
                </a>
            <?php elseif ($progress): ?>
                <p class="text-muted mb-0">
                    <?php if ($prog_status === 'N/A'): ?>
                        <i class="fa fa-minus-circle"></i> Marked as N/A — no file submitted for this target.
                    <?php else: ?>
                        <i class="fa fa-clock"></i> Submission recorded (<?= htmlspecialchars($prog_status) ?>) but no file is attached.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-muted mb-0"><i class="fa fa-info-circle"></i> No submission from Target List for this target yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ EXTRA MOVs (from MOV Management) ============ -->
    <h6 class="text-muted mb-2"><i class="fa fa-folder-open mr-1"></i> Additional MOVs <small class="font-weight-normal">(from MOV Management)</small></h6>
    <?php if ($movs && $movs->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th class="text-center" style="width: 40px;">#</th>
                    <th style="width: 25%;">File</th>
                    <th style="width: 25%;">Date Submitted</th>
                    <th style="width: 20%;">Deadline</th>
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
                    <td><small><?php
                        // Use the per-MOV deadline stored in mov_uploads.deadline.
                        // Fall back to matching target_deadlines by month/year for older MOVs.
                        $mov_deadline = '—';
                        if (!empty($mov['deadline'])) {
                            $mov_deadline = date('M d, Y', strtotime($mov['deadline']));
                        } else {
                            $sub_month = date('n', strtotime($mov['date_submitted']));
                            $sub_year  = date('Y', strtotime($mov['date_submitted']));
                            foreach ($target_deadlines as $dl) {
                                if (date('n', strtotime($dl)) == $sub_month && date('Y', strtotime($dl)) == $sub_year) {
                                    $mov_deadline = date('M d, Y', strtotime($dl));
                                    break;
                                }
                            }
                        }
                        echo htmlspecialchars($mov_deadline);
                    ?></small></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-default btn-flat border-info" 
                            onclick="viewMOV(<?php echo $mov['id']; ?>)" title="View">
                            <i class="fa fa-eye text-info"></i>
                        </button>
                        <?php if ($can_verify): ?>
                        <?php if ($status != 'Verified'): ?>
                        <button type="button" class="btn btn-sm btn-default btn-flat border-success" 
                            onclick="verifyMOV(<?php echo $mov['id']; ?>, 'Verified')" title="Approve">
                            <i class="fa fa-check text-success"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($status != 'Rejected'): ?>
                        <button type="button" class="btn btn-sm btn-default btn-flat border-danger" 
                            onclick="verifyMOV(<?php echo $mov['id']; ?>, 'Rejected')" title="Reject">
                            <i class="fa fa-times text-danger"></i>
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($is_owner): ?>
                        <button type="button" class="btn btn-sm btn-default btn-flat border-danger" 
                            onclick="deleteMOV(<?php echo $mov['id']; ?>)" title="Delete">
                            <i class="fa fa-trash text-danger"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot class="thead-dark">
                <tr>
                    <td colspan="2" class="text-right"><strong>Total Additional:</strong></td>
                    <td colspan="3"><strong><?php echo $i - 1; ?> MOV<?php echo ($i - 1) !== 1 ? 's' : ''; ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-light border text-center py-3 mb-0">
        <i class="fa fa-folder-open fa-2x text-muted mb-2"></i>
        <p class="mb-0 text-muted">No additional MOVs uploaded via MOV Management for this target.</p>
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

function verifyMOV(id, newStatus) {
    var remarks = '';
    if (newStatus === 'Rejected') {
        remarks = prompt('Reason for rejection (required):');
        if (remarks === null) return; // cancelled
        if (remarks.trim() === '') {
            alert_toast('Please enter a rejection reason.', 'warning');
            return;
        }
    }
    var confMsg = newStatus === 'Verified'
        ? 'Approve (verify) this MOV?'
        : 'Reject this MOV?';
    if (!confirm(confMsg)) return;
    start_load();
    $.ajax({
        url: 'ajax.php?action=verify_mov',
        method: 'POST',
        data: { id: id, status: newStatus, remarks: remarks },
        success: function(resp) {
            if (resp == 1) {
                alert_toast(newStatus === 'Verified' ? 'MOV approved.' : 'MOV rejected.', 'success');
                setTimeout(function(){ location.reload(); }, 800);
            } else {
                end_load();
                alert_toast('Failed to update MOV.', 'danger');
            }
        },
        error: function() {
            end_load();
            alert_toast('Error occurred.', 'danger');
        }
    });
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
