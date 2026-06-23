<?php include'db_connect.php';

// Allow: admin (2), legacy evaluator (1), or faculty with evaluator designation (0 + is_evaluator)
$login_type = $_SESSION['login_type'] ?? -1;
$is_evaluator_flag = !empty($_SESSION['is_evaluator']);
if ($login_type == 0 && !$is_evaluator_flag) {
    echo "<script>alert('Invalid Credential');
    window.location.href = 'index.php';
</script>";
    exit;
}

$nameId = isset($_GET['id']) ? $_GET['id'] : '9999';

// Admin (login_type 2) is view-only — no rating, status changes, or comments
$is_admin_view = ($login_type == 2);

// Fetch evaluator designation and faculty designation for Strategic Plan restriction
$eval_desig_id = 0;
$fac_desig_id = 0;
$is_vp = false;
$fac_is_director = false;
if ($login_type == 1) {
    $stmt = $conn->prepare("SELECT designation_id FROM evaluator_list WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['login_id']);
    $stmt->execute();
    $stmt->bind_result($eval_desig_id);
    $stmt->fetch();
    $stmt->close();
    $is_vp = ($eval_desig_id == 4);
}
// Fetch faculty designation and position
$stmt = $conn->prepare("SELECT designation_id, position_id FROM employee_list WHERE id = ?");
$stmt->bind_param("i", $nameId);
$stmt->execute();
$stmt->bind_result($fac_desig_id, $fac_position_id);
$stmt->fetch();
$stmt->close();
$fac_is_director = ($fac_desig_id == 6);
// Strategic Plan tasks are locked for non-VP evaluators when faculty is Director
$strat_locked = ($fac_is_director && !$is_vp && !$is_admin_view);

$qry = $conn->query("SELECT CONCAT(firstname, ' ', lastname) AS faculty_name FROM employee_list WHERE id = '$nameId' LIMIT 1");

if ($qry && $qry->num_rows > 0) {
    $row = $qry->fetch_assoc();
    $faculty_name = $row['faculty_name'];
} else {
    echo "<script>window.location.href = 'index.php?page=faculty_list'</script>";
}
?>
<div class="col-lg-12">
	<div class="card card-outline card-success">
		<div class="card-header">
			<div class="card-tools">
				<!-- <a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="./index.php?page=new_evaluation"><i class="fa fa-plus"></i> Add New Evaluation</a> -->
			</div>
		</div>
		<div class="card-body">

        
            <h5 class="mb-3">Name of Faculty: <b><?= htmlspecialchars($faculty_name); ?></b></h5>

         
			<table class="table table-hover table-bordered table-striped" id="list">
				<thead class="thead-dark">
					<tr>
						<th class="text-center" style="width: 50px;">#</th>
						<th style="width: 30%;">Success Indicator</th>
						<th class="text-center" style="width: 100px;">MOV</th>
                        <th class="text-center" style="width: 130px;">Action</th>
						<th class="text-center" style="width: 110px;">Efficiency</th>
						<th class="text-center" style="width: 110px;">Quality</th>
						<th class="text-center" style="width: 120px;">Timeliness</th>
					</tr>
				</thead>
				<tbody>
				<?php

$where = "";
$faculty_id = $_SESSION['login_id'] ?? null;
$faculty_type = null;

if ($faculty_id) {
    $stmt = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $stmt->bind_result($faculty_type);
    $stmt->fetch();
    $stmt->close();
}

$qry = $conn->query("
SELECT 
    tp.id AS progress_id,
    tp.task_id AS task_id,
    tp.faculty_id AS faculty,
    tp.file_path,
    tp.file_type,
    tp.progress AS task_progress,
    tp.date_created,
    CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) AS faculty_name,
    
    t.id AS real_task_id,
    t.success_indicators AS si,
    t.efficiency AS task_efficiency,
    t.timeliness AS task_timeliness,
    t.quality AS task_quality,
    t.category AS task_category,
    t.is_active,
    CONCAT(tp.file_path, '.', tp.file_type) AS file_name,
    r.id AS rating_id,
    r.efficiency AS rating_efficiency,
    r.timeliness AS rating_timeliness,
    r.quality AS rating_quality,
    ((((r.efficiency + r.timeliness + r.quality) / 4) / 5) * 100) AS pa
    FROM task_list t
    LEFT JOIN task_progress tp ON tp.task_id = t.id AND tp.faculty_id = " . intval($nameId) . "
    LEFT JOIN employee_list e ON tp.faculty_id = e.id
    LEFT JOIN ratings r ON r.employee_id = " . intval($nameId) . " AND r.task_id = t.id
    WHERE t.is_active = 1
        AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = " . intval($fac_position_id) . ")
        AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = " . intval($fac_desig_id) . ")
        AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = " . intval($fac_position_id) . ")
    ORDER BY 
        CASE WHEN t.category = 'strategic' THEN 0
             WHEN t.category = 'core' THEN 1
             WHEN t.category = 'support' THEN 2
             ELSE 3 END,
        t.sub_category,
        t.id");
   
    $num=1;
    $current_category = '';
    $current_sub = '';
    $category_labels = ['strategic' => 'STRATEGIC FUNCTIONS', 'core' => 'CORE FUNCTIONS', 'support' => 'SUPPORT FUNCTIONS'];
    $category_colors = ['strategic' => 'bg-dark text-white', 'core' => 'bg-secondary text-white', 'support' => 'bg-info text-white'];
    $sub_labels = ['ter' => 'A.1 Teaching Effectiveness (TER)', 'instructions' => 'A.2 Instructions', 'research' => 'B. Research', 'extension' => 'C. Extension'];
    $sub_colors = ['ter' => 'table-light', 'instructions' => 'table-light', 'research' => 'table-light', 'extension' => 'table-light'];
            if($qry->num_rows == 0):
                echo '<tr><td colspan="7" class="text-center text-muted py-4">No targets assigned to this faculty.</td></tr>';
            else:
            while ($row = $qry->fetch_assoc()):
                $task_category = $row['task_category'] ?? '';
                $task_sub = $row['sub_category'] ?? '';
                // Insert category header when category changes
                if ($task_category !== $current_category):
                    $current_category = $task_category;
                    $current_sub = '';
                    $label = $category_labels[$task_category] ?? strtoupper($task_category);
                    $color = $category_colors[$task_category] ?? 'bg-light';
?>
    <tr class="<?= $color ?>">
        <td colspan="7" class="font-weight-bold py-2">
            <i class="fa fa-tasks mr-2"></i> <?= $label ?>
        </td>
    </tr>
<?php
                endif;
                // Insert sub-category header within core when sub changes
                if ($task_category === 'core' && $task_sub !== $current_sub):
                    $current_sub = $task_sub;
                    $sub_label = $sub_labels[$task_sub] ?? ucwords($task_sub);
                    $sub_color = $sub_colors[$task_sub] ?? 'table-light';
?>
    <tr class="<?= $sub_color ?>">
        <td colspan="7" class="font-weight-bold py-1 pl-4">
            <i class="fa fa-angle-right mr-2"></i> <?= $sub_label ?>
        </td>
    </tr>
<?php
                endif;
                $task_is_strategic = (($row['task_category'] ?? '') === 'strategic');
                $row_locked = ($strat_locked && $task_is_strategic);
                $has_submission = !empty($row['progress_id']);
                $currentStatus = $row['task_progress'] ?? null;
                $is_na = ($currentStatus === 'N/A');
?>
    <tr>
        <th class="text-center align-middle"><?= $num++ ?></th>
        <td class="align-middle"><?= ucwords(htmlspecialchars($row['si'])) ?></td>
        <td class="text-center align-middle">
        <?php if ($has_submission && !empty($row['file_path']) && !empty($row['file_type'])): ?>
        <button type="button" 
           class="btn btn-sm btn-primary view-file-btn"
           data-file="<?= htmlspecialchars($row['file_path'] . '.' . $row['file_type']) ?>"
           data-filetype="<?= htmlspecialchars($row['file_type']) ?>">
           View
        </button>
        <?php elseif ($is_na): ?>
            <span class="badge badge-secondary">N/A</span>
        <?php else: ?>
            <span class="text-muted">-</span>
        <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?php if ($is_admin_view): ?>
                <?php 
                $statusClass = ($currentStatus == 'Verified') ? 'badge-success' : (($currentStatus == 'For Verification') ? 'badge-warning' : ($is_na ? 'badge-secondary' : 'badge-secondary'));
                ?>
                <span class="badge <?= $statusClass ?>" <?= $row_locked ? 'title="Strategic Plan — VP only"' : '' ?>><?= $currentStatus ?? 'Pending' ?></span>
            <?php elseif ($row_locked): ?>
                <?php 
                $statusClass = ($currentStatus == 'Verified') ? 'badge-success' : (($currentStatus == 'For Verification') ? 'badge-warning' : ($is_na ? 'badge-secondary' : 'badge-secondary'));
                ?>
                <span class="badge <?= $statusClass ?>" title="Strategic Plan tasks can only be rated by the Vice President"><?= $currentStatus ?? 'Pending' ?> <i class="fas fa-lock ml-1" style="font-size:0.65rem;"></i></span>
            <?php else: ?>
            <div class="dropdown">
                <?php 
                $statusClass = ($currentStatus == 'Verified') ? 'btn-success' : (($currentStatus == 'For Verification') ? 'btn-warning' : ($is_na ? 'btn-info' : 'btn-secondary'));
                ?>
                <button class="btn btn-sm <?= $statusClass ?> dropdown-toggle" 
                        type="button" 
                        id="statusDropdown<?= $row['real_task_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        data-faculty="<?= $nameId ?>">
                    <?= $currentStatus ?? 'Pending' ?>
                </button>
                <div class="dropdown-menu" aria-labelledby="statusDropdown<?= $row['real_task_id'] ?>">
                    <a class="dropdown-item set_status" href="javascript:void(0)" 
                       data-id="<?= $row['real_task_id'] ?>" 
                       data-faculty="<?= $nameId ?>" 
                       data-value="For Verification">For Verification</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item set_status" href="javascript:void(0)" 
                       data-id="<?= $row['real_task_id'] ?>" 
                       data-faculty="<?= $nameId ?>" 
                       data-value="Verified">Verified</a>
                    <?php if ($is_na): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item set_status text-info" href="javascript:void(0)" 
                       data-id="<?= $row['real_task_id'] ?>" 
                       data-faculty="<?= $nameId ?>" 
                       data-value="N/A Verified">Verify N/A</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?php 
                $effApplicable = (isset($row['task_efficiency']) && $row['task_efficiency'] === 'Applicable');
                $currentEff = isset($row['rating_efficiency']) ? $row['rating_efficiency'] : '-';
                $ratingDisabled = $is_na || !$has_submission;
            ?>
            <?php if ($is_admin_view || $row_locked || $ratingDisabled): ?>
                <span class="badge <?= isset($row['rating_efficiency']) ? 'badge-success' : 'badge-secondary' ?>" <?= $row_locked ? 'title="Strategic Plan — VP only"' : '' ?>><?= ($effApplicable && !$ratingDisabled) ? $currentEff : 'N/A' ?><?= $row_locked ? ' <i class="fas fa-lock" style="font-size:0.6rem;"></i>' : '' ?></span>
            <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm <?= isset($row['rating_efficiency']) ? 'btn-success' : 'btn-secondary' ?> dropdown-toggle" 
                        type="button" 
                        id="effDropdown<?= $row['progress_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        <?= !$effApplicable ? 'disabled' : '' ?>>
                    <?= $effApplicable ? (isset($row['rating_efficiency']) ? $row['rating_efficiency'] : 'Set') : 'N/A' ?>
                </button>
                <?php if ($effApplicable): ?>
                    <div class="dropdown-menu p-3" aria-labelledby="effDropdown<?= $row['progress_id'] ?>" style="min-width: 200px;">
                        <small class="text-muted mb-2 d-block">Select or enter rating:</small>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <a class="dropdown-item py-1 set_rating" 
                            href="javascript:void(0)" 
                            data-id="<?= $row['real_task_id'] ?>" 
                            data-faculty="<?= $nameId ?>"
                            data-field="efficiency" 
                            data-value="<?= $i ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <div class="dropdown-divider"></div>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control form-control-sm custom_rating_input" 
                                   data-id="<?= $row['real_task_id'] ?>" 
                                   data-faculty="<?= $nameId ?>"
                                   data-field="efficiency"
                                   min="0" max="5" step="0.01"
                                   placeholder="Other (0-5)">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm submit-custom-rating" type="button">
                                    <i class="fa fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?php 
                $qualApplicable = (isset($row['task_quality']) && $row['task_quality'] === 'Applicable');
                $currentQual = isset($row['rating_quality']) ? $row['rating_quality'] : '-';
                $ratingDisabled = $is_na || !$has_submission;
            ?>
            <?php if ($is_admin_view || $row_locked || $ratingDisabled): ?>
                <span class="badge <?= isset($row['rating_quality']) ? 'badge-success' : 'badge-secondary' ?>" <?= $row_locked ? 'title="Strategic Plan — VP only"' : '' ?>><?= ($qualApplicable && !$ratingDisabled) ? $currentQual : 'N/A' ?><?= $row_locked ? ' <i class="fas fa-lock" style="font-size:0.6rem;"></i>' : '' ?></span>
            <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm <?= isset($row['rating_quality']) ? 'btn-success' : 'btn-secondary' ?> dropdown-toggle" 
                        type="button" 
                        id="qualDropdown<?= $row['progress_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        <?= !$qualApplicable ? 'disabled' : '' ?>>
                    <?= $qualApplicable ? (isset($row['rating_quality']) ? $row['rating_quality'] : 'Set') : 'N/A' ?>
                </button>
                <?php if ($qualApplicable): ?>
                    <div class="dropdown-menu p-3" aria-labelledby="qualDropdown<?= $row['progress_id'] ?>" style="min-width: 200px;">
                        <small class="text-muted mb-2 d-block">Select or enter rating:</small>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <a class="dropdown-item py-1 set_rating" 
                               href="javascript:void(0)" 
                               data-id="<?= $row['real_task_id'] ?>" 
                               data-faculty="<?= $nameId ?>"
                               data-field="quality" 
                               data-value="<?= $i ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <div class="dropdown-divider"></div>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control form-control-sm custom_rating_input" 
                                   data-id="<?= $row['real_task_id'] ?>" 
                                   data-faculty="<?= $nameId ?>"
                                   data-field="quality"
                                   min="0" max="5" step="0.01"
                                   placeholder="Other (0-5)">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm submit-custom-rating" type="button">
                                    <i class="fa fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?php 
                $timeApplicable = (isset($row['task_timeliness']) && $row['task_timeliness'] === 'Applicable');
                $currentTime = isset($row['rating_timeliness']) ? $row['rating_timeliness'] : '-';
                $ratingDisabled = $is_na || !$has_submission;
            ?>
            <?php if ($is_admin_view || $row_locked || $ratingDisabled): ?>
                <span class="badge <?= isset($row['rating_timeliness']) ? 'badge-success' : 'badge-secondary' ?>" <?= $row_locked ? 'title="Strategic Plan — VP only"' : '' ?>><?= ($timeApplicable && !$ratingDisabled) ? $currentTime : 'N/A' ?><?= $row_locked ? ' <i class="fas fa-lock" style="font-size:0.6rem;"></i>' : '' ?></span>
            <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm <?= isset($row['rating_timeliness']) ? 'btn-success' : 'btn-secondary' ?> dropdown-toggle" 
                        type="button" 
                        id="timeDropdown<?= $row['progress_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        <?= !$timeApplicable ? 'disabled' : '' ?>>
                    <?= $timeApplicable ? (isset($row['rating_timeliness']) ? $row['rating_timeliness'] : 'Set') : 'N/A' ?>
                </button>
                <?php if ($timeApplicable): ?>
                    <div class="dropdown-menu p-3" aria-labelledby="timeDropdown<?= $row['progress_id'] ?>" style="min-width: 200px;">
                        <small class="text-muted mb-2 d-block">Select or enter rating:</small>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <a class="dropdown-item py-1 set_rating" 
                               href="javascript:void(0)" 
                               data-id="<?= $row['real_task_id'] ?>" 
                               data-faculty="<?= $nameId ?>"
                               data-field="timeliness" 
                               data-value="<?= $i ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <div class="dropdown-divider"></div>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control form-control-sm custom_rating_input" 
                                   data-id="<?= $row['real_task_id'] ?>" 
                                   data-faculty="<?= $nameId ?>"
                                   data-field="timeliness"
                                   min="0" max="5" step="0.01"
                                   placeholder="Other (0-5)">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm submit-custom-rating" type="button">
                                    <i class="fa fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>
    </tr>
<?php endwhile; endif; ?>

				</tbody>
			</table>

    <!-- Add this comment form section after the faculty name display -->
<?php
   // Fetch existing comment for this faculty-evaluator combination
$existing_comment = "";
$comment_check = $conn->query("SELECT comment_text FROM comments WHERE employee_id = '$nameId' AND rater_id = '{$_SESSION['login_id']}'");
if($comment_check && $comment_check->num_rows > 0){
    $comment_row = $comment_check->fetch_assoc();
    $existing_comment = htmlspecialchars($comment_row['comment_text']);
}
?>
<?php if (!$is_admin_view): ?>
<div class="card mt-4 border-secondary">
    <div class="card-header bg-secondary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-comments"></i> Evaluator Comment</h5>
    </div>
    <div class="card-body">
        <form id="commentForm">
            <input type="hidden" name="faculty_id" value="<?= $nameId ?>">
            <input type="hidden" name="evaluator_id" value="<?= $_SESSION['login_id'] ?>">
            
            <div class="form-group">
                <textarea class="form-control" id="commentText" name="comment" rows="3" 
                          placeholder="Enter your comment about this faculty's performance..." 
                          required><?= $existing_comment ?></textarea>
            </div>
            
            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Comment
                </button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
    <?php if (!empty($existing_comment)): ?>
    <div class="card mt-4 border-secondary">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-comments"></i> Evaluator Comment</h5>
        </div>
        <div class="card-body">
            <p class="text-muted"><?= $existing_comment ?></p>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>
		</div>
	</div>
</div>

<div class="modal fade" id="fileViewModal" tabindex="-1" role="dialog" aria-labelledby="fileViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileViewModalLabel">View File</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" id="fileViewContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" id="downloadFileBtn" class="btn btn-primary" download>Download</a>
            </div>
        </div>
    </div>
</div>

<script>



$(document).ready(function(){
    $(document).on("click", ".set_rating", function(){
        var taskId = $(this).data("id");
        var facultyId = $(this).data("faculty");
        var field = $(this).data("field");
        var value = $(this).data("value");

        $.ajax({
            url: "ajax.php?action=save_rating",
            method: "POST",
            data: { task_id: taskId, faculty_id: facultyId, field: field, value: value },
            success: function(resp){
                if(resp == 1){
                    alert_toast("Rating saved successfully", "success");
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast("Failed to save rating", "danger");
                }
            },
            error: function(xhr, status, error){
                alert_toast("Error occurred during AJAX request", "danger");
            }
        });
    });
});


// Make sure this runs after the DOM is ready
$(document).ready(function() {

console.log("✅ Document ready — jQuery initialized");

$(".set_status").click(function() {
    console.log("🔘 Dropdown item clicked");

    // Get data from clicked dropdown item
    var id = $(this).data('id');
    var faculty = $(this).data('faculty');
    var status = $(this).data('value');

    console.log("📦 Retrieved data:");
    console.log("➡️ Task ID:", id);
    console.log("➡️ Faculty:", faculty);
    console.log("➡️ Status:", status);

    // Validate before sending
    if (!id || !status) {
        console.error("❌ Missing ID or Status value. AJAX aborted.");
        alert_toast("Invalid data — please check console.", 'danger');
        return;
    }

    // Optional: show loading animation if you have one
    console.log("⏳ Starting loading animation...");
    if (typeof start_load === "function") start_load();

    $.ajax({
        url: 'ajax.php?action=save_status',
        method: 'POST',
        data: {
            id: id,
            faculty: faculty,
            status: status
        },
    
        success: function(resp) {
            try {
                var result = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (result.status === 'success') {
                    alert_toast(result.message || "Status updated successfully!", 'success');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast(result.message || "Failed to update status.", 'danger');
                }
            } catch (e) {
                if (resp == 1) {
                    alert_toast("Status updated successfully!", 'success');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast("Failed to update status.", 'danger');
                }
            }
            if (typeof end_load === "function") end_load();
         },
        error: function(xhr, status, error) {
            console.error("❌ AJAX error:", status, error);
            console.error("🪵 Response text:", xhr.responseText);
            alert_toast("Error connecting to server.", 'danger');
            if (typeof end_load === "function") end_load();
        }
    });
});

});



$(document).ready(function(){

       // Manual input with onchange
    $(document).on("change", ".custom_rating_input", function(){
        var taskId = $(this).data("id");
        var facultyId = $(this).data("faculty");
        var field = $(this).data("field");
        var value = $(this).val();

        if(value === "" || isNaN(value)){
            alert_toast("Please enter a valid number", "warning");
            return;
        }

        $.ajax({
            url: "ajax.php?action=save_rating",
            method: "POST",
            data: { task_id: taskId, faculty_id: facultyId, field: field, value: value },
            success: function(resp){
                if(resp == 1){
                    alert_toast("Custom rating saved successfully", "success");
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast("Failed to save custom rating", "danger");
                }
            }
        });
    });

    $(document).on("click", ".submit-custom-rating", function(){
        var input = $(this).closest('.input-group').find('.custom_rating_input');
        var taskId = input.data("id");
        var facultyId = input.data("faculty");
        var field = input.data("field");
        var value = input.val();

        if(value === "" || isNaN(value)){
            alert_toast("Please enter a valid number", "warning");
            return;
        }

        if(value < 0 || value > 5){
            alert_toast("Value must be between 0 and 5", "warning");
            return;
        }

        $.ajax({
            url: "ajax.php?action=save_rating",
            method: "POST",
            data: { task_id: taskId, faculty_id: facultyId, field: field, value: value },
            success: function(resp){
                if(resp == 1){
                    alert_toast("Rating saved successfully", "success");
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast("Failed to save rating", "danger");
                }
            }
        });
    });
});


$(document).ready(function(){
    console.log("✅ Comment form handler initialized");
    
    // Handle comment form submission
    $('#commentForm').submit(function(e){
        e.preventDefault();
        console.log("📝 Comment form submission triggered");
        
        var formData = $(this).serialize();
        console.log("📦 Form data serialized:", formData);
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        console.log("⏳ Submit button disabled and loading state activated");
        
        console.log("🚀 Sending AJAX request to: ajax.php?action=save_comment");
        console.log("📤 Request method: POST");
        console.log("📋 Request data:", formData);
        
        $.ajax({
            url: 'ajax.php?action=save_comment',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                console.log("🔄 AJAX request initiated - beforeSend");
            },
            success: function(resp){
                console.log("✅ AJAX request successful");
                console.log("📥 Server response:", resp);
                console.log("📥 Response type:", typeof resp);
                console.log("📥 Response length:", resp.length);
                
                if(resp == 1){
                    console.log("💾 Comment saved successfully in database");
                    alert_toast("Comment saved successfully!", "success");
                    
                    console.log("🕒 Scheduling page reload in 1500ms");
                    // Reload page to show updated comment
                    setTimeout(function(){
                        console.log("🔄 Reloading page...");
                        location.reload();
                    }, 1500);
                } else {
                    console.log("❌ Failed to save comment - server returned:", resp);
                    console.log("❌ Possible issues: Database error, validation failed, or server error");
                    alert_toast("Failed to save comment. Please try again.", "danger");
                }
            },
            error: function(xhr, status, error){
                console.error("❌ AJAX request failed");
                console.error("📊 Error details:");
                console.error("➡️ Status:", status);
                console.error("➡️ Error:", error);
                console.error("➡️ XHR readyState:", xhr.readyState);
                console.error("➡️ XHR status:", xhr.status);
                console.error("➡️ XHR statusText:", xhr.statusText);
                console.error("➡️ Response text:", xhr.responseText);
                
                alert_toast("Error saving comment. Please try again.", "danger");
            },
            complete: function(xhr, status){
                console.log("🏁 AJAX request completed");
                console.log("📊 Completion status:", status);
                console.log("🔄 Restoring submit button to original state");
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Additional debug: Log when form is found/not found
    if ($('#commentForm').length) {
        console.log("✅ Comment form found in DOM");
        console.log("📝 Form elements:", $('#commentForm').find('input, textarea, button').length);
    } else {
        console.error("❌ Comment form not found in DOM - check HTML structure");
    }
});

$(document).ready(function(){
    $(document).on('click', '.view-file-btn', function(){
        var filePath = $(this).data('file');
        var fileType = $(this).data('filetype').toLowerCase();
        var modal = $('#fileViewModal');
        var content = $('#fileViewContent');
        var downloadBtn = $('#downloadFileBtn');
        
        content.empty();
        downloadBtn.attr('href', filePath);
        
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        var pdfExt = 'pdf';
        
        if (imageExts.includes(fileType)) {
            content.html('<img src="' + filePath + '" class="img-fluid" style="max-height: 70vh;">');
        } else if (fileType === pdfExt) {
            content.html('<iframe src="' + filePath + '" style="width: 100%; height: 70vh; border: none;"></iframe>');
        } else {
            content.html('<p>Cannot preview this file type. Please download to view.</p>');
        }
        
        modal.modal('show');
    });
});

</script>