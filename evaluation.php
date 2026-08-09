<?php include'db_connect.php';
require_once 'includes/period_builder.php';

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
$is_vprei = false;
$fac_is_director = false;
if ($login_type == 1) {
    $stmt = $conn->prepare("SELECT designation_id FROM evaluator_list WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['login_id']);
    $stmt->execute();
    $stmt->bind_result($eval_desig_id);
    $stmt->fetch();
    $stmt->close();
    // Any VP designation can evaluate a Director's Strategic Plan targets
    $is_vp = in_array(intval($eval_desig_id ?? 0), [4, 9, 10, 18, 19]); // VPAF, VPAA, VPREI (both ID schemes)
    // VPREI only evaluates Strategic targets — core/support are locked
    $is_vprei = in_array(intval($eval_desig_id ?? 0), [10, 19]);
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
// For VPREI: lock everything EXCEPT strategic (opposite of Dean)
$non_strat_locked = ($is_vprei && !$is_admin_view);

// VPREI must NOT evaluate Department Heads — the Dean evaluates Dept Heads.
// VPREI may only evaluate the Director (strategic targets) and the Dean.
if ($is_vprei && $fac_desig_id == 2 && !$is_admin_view) {
    echo "<script>alert('You are not authorized to evaluate this faculty. Department Heads are evaluated by the Dean.'); window.location.href='index.php?page=faculty_list';</script>";
    exit;
}

// Evaluators can only rate in the active/current rating period.
// Inactive periods are read-only (view past ratings only).
$period_is_active = ($selected_period && !empty($selected_period['is_active']));
$period_locked = !$is_admin_view && !$period_is_active;

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
		<div class="card-header d-flex justify-content-between align-items-center flex-wrap">
			<div class="d-flex align-items-center flex-wrap">
				<h3 class="card-title mr-3 mb-0"><i class="fa fa-tasks mr-1"></i> Evaluation</h3>
				<?php if (count($real_periods) > 0):
					$sel_key = epes_period_key($selected_period['semester'], $selected_period['year']);
				?>
				<span class="badge badge-primary p-2" style="font-size:0.85rem;">
					<i class="fa fa-calendar-alt mr-1"></i>
					<?= htmlspecialchars($selected_period['semester']) ?> <?= htmlspecialchars($selected_period['year']) ?>
					<?= !empty($selected_period['is_active']) ? '<span class="badge badge-light ml-1">Current</span>' : '' ?>
					<?php if ($period_locked): ?>
					<span class="badge badge-warning ml-1" title="This rating period is no longer active"><i class="fa fa-lock"></i> Read-only</span>
					<?php endif; ?>
				</span>
				<?php endif; ?>
			</div>
			<?php if (count($real_periods) > 0): ?>
			<div class="card-tools ml-auto">
				<select id="period_selector" class="form-control form-control-sm"
						onchange="window.location.href='index.php?page=evaluation&id=<?= htmlspecialchars($nameId) ?>&period='+encodeURIComponent(this.value)"
						style="width:auto; font-size:0.85rem; max-width:240px;">
					<?php foreach ($real_periods as $p):
						$pkey = epes_period_key($p['semester'], $p['year']);
						$opt_label = $p['semester'] . ' ' . $p['year'] . (!empty($p['is_active']) ? ' (Current)' : '');
					?>
					<option value="<?= htmlspecialchars($pkey) ?>" <?= $pkey === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>
		</div>
		<div class="card-body">

        
            <h5 class="mb-3">Name of Faculty: <b><?= htmlspecialchars($faculty_name); ?></b></h5>

			<style>
			/* Responsive: transform evaluation table into stacked cards on small screens */
			@media (max-width: 768px) {
			    #list thead { display: none; }
			    #list, #list tbody, #list tr, #list td { display: block; width: 100%; }
			    #list tr.category-header,
			    #list tr.sub-header { display: table-row; }
			    #list tr:not(.category-header):not(.sub-header) {
			        border: 1px solid #dee2e6;
			        border-radius: 8px;
			        margin-bottom: 12px;
			        padding: 8px 10px;
			        background: #fff;
			        box-shadow: 0 1px 3px rgba(0,0,0,.08);
			    }
			    #list tr:not(.category-header):not(.sub-header) td {
			        display: flex;
			        justify-content: space-between;
			        align-items: center;
			        text-align: right !important;
			        border: none;
			        border-bottom: 1px solid #f0f0f0;
			        padding: 6px 4px;
			    }
			    #list tr:not(.category-header):not(.sub-header) td:last-child { border-bottom: none; }
			    #list tr:not(.category-header):not(.sub-header) td::before {
			        content: attr(data-label);
			        font-weight: 600;
			        color: #6c757d;
			        text-align: left;
			        margin-right: 12px;
			        flex: 0 0 auto;
			    }
			    /* E/Q/T as inline chips on mobile */
			    #list td[data-label="E"], #list td[data-label="Q"], #list td[data-label="T"] {
			        display: inline-flex;
			        width: 32%;
			        border: 1px solid #f0f0f0;
			        border-radius: 6px;
			        margin: 2px 1%;
			        justify-content: center;
			    }
			    #list td[data-label="E"]::before, #list td[data-label="Q"]::before, #list td[data-label="T"]::before {
			        margin-right: 0;
			    }
			    #list td[data-label="E"] { margin-left: 0; }
			    #list td[data-label="T"] { margin-right: 0; }
			}
			</style>

			<div class="table-responsive">
			<table class="table table-hover table-bordered table-striped" id="list">
				<thead class="thead-dark">
					<tr>
						<th class="text-center" style="width: 50px;">#</th>
						<th style="width: 30%;">Success Indicator</th>
						<th class="text-center" style="width: 100px;">MOV</th>
						<th class="text-center" style="width: 120px;">Status</th>
						<th class="text-center" style="width: 90px;">Action</th>
						<th class="text-center" style="width: 45px;" title="Efficiency">E</th>
						<th class="text-center" style="width: 45px;" title="Quality">Q</th>
						<th class="text-center" style="width: 45px;" title="Timeliness">T</th>
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
        tp.actual_accomplishment,
    CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) AS faculty_name,
    
    t.id AS real_task_id,
    t.success_indicators AS si,
    t.efficiency AS task_efficiency,
    t.timeliness AS task_timeliness,
    t.quality AS task_quality,
    t.category AS task_category,
    t.sub_category,
    t.is_active,
    CONCAT(tp.file_path, '.', tp.file_type) AS file_name,
    r.id AS rating_id,
    r.efficiency AS rating_efficiency,
    r.timeliness AS rating_timeliness,
    r.quality AS rating_quality,
    ((((r.efficiency + r.timeliness + r.quality) / 4) / 5) * 100) AS pa
    FROM task_list t
    LEFT JOIN task_progress tp ON tp.id = (
        SELECT MAX(tp2.id) FROM task_progress tp2
        WHERE tp2.task_id = t.id AND tp2.faculty_id = " . intval($nameId) . " $period_filter
    )
    LEFT JOIN employee_list e ON tp.faculty_id = e.id
    LEFT JOIN ratings r ON r.id = (
        SELECT MAX(r2.id) FROM ratings r2
        WHERE r2.employee_id = " . intval($nameId) . " AND r2.task_id = t.id
          AND r2.rating_period IN (" . implode(",", array_map(function($c) use ($conn) { return "'" . $conn->real_escape_string($c) . "'"; }, $period_codes)) . ")
    )
    WHERE t.is_active = 1
        AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = " . intval($fac_position_id) . ")
        AND " . task_designation_match($fac_desig_id, intval($nameId)) . "
        AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id = " . intval($fac_position_id) . ")
    ORDER BY 
        CASE WHEN t.category = 'strategic' THEN 0
             WHEN t.category = 'core' THEN 1
             WHEN t.category = 'support' THEN 2
             ELSE 3 END,
        t.sort_order,
        t.id");
   
    $num=1;
    $current_category = '';
    $current_sub = '';
    $category_labels = ['strategic' => 'STRATEGIC FUNCTIONS', 'core' => 'CORE FUNCTIONS', 'support' => 'SUPPORT FUNCTIONS'];
    $category_colors = ['strategic' => 'bg-dark text-white', 'core' => 'bg-secondary text-white', 'support' => 'bg-info text-white'];
    $sub_labels = ['ter' => 'A.1 Teaching Effectiveness (TER)', 'instructions' => 'A.2 Instructions', 'research' => 'B. Research', 'extension' => 'C. Extension'];
    $sub_colors = ['ter' => 'table-light', 'instructions' => 'table-light', 'research' => 'table-light', 'extension' => 'table-light'];
            if($qry->num_rows == 0):
                echo '<tr><td colspan="8" class="text-center text-muted py-4">No targets assigned to this faculty.</td></tr>';
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
    <tr class="<?= $color ?> category-header">
        <td colspan="8" class="font-weight-bold py-2">
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
    <tr class="<?= $sub_color ?> sub-header">
        <td colspan="8" class="font-weight-bold py-1 pl-4">
            <i class="fa fa-angle-right mr-2"></i> <?= $sub_label ?>
        </td>
    </tr>
<?php
                endif;
                $task_is_strategic = (($row['task_category'] ?? '') === 'strategic');
                $row_locked = ($strat_locked && $task_is_strategic) || ($non_strat_locked && !$task_is_strategic);
                $has_submission = !empty($row['progress_id']);
                $currentStatus = $row['task_progress'] ?? null;
                $is_na = ($currentStatus === 'N/A');
                // Resolve the real on-disk file (file_path may or may not include the extension)
                $real_file = ($has_submission && !empty($row['file_path']))
                    ? epes_real_file_path($row['file_path'], $row['file_type'])
                    : null;
?>
    <tr>
        <th class="text-center align-middle"><?= $num++ ?></th>
        <td class="align-middle" data-label="Target"><?= ucwords(htmlspecialchars($row['si'])) ?></td>
        <td class="text-center align-middle" data-label="MOV">
        <?php if ($real_file): ?>
            <span class="badge badge-success" title="File submitted">Submitted</span>
        <?php elseif ($has_submission && !empty($row['file_path'])): ?>
            <span class="badge badge-warning" title="File record exists but the file is missing on the server">Missing</span>
        <?php elseif ($is_na): ?>
            <span class="badge badge-secondary">N/A</span>
        <?php else: ?>
            <span class="text-muted">-</span>
        <?php endif; ?>
        <?php if (!$is_admin_view && $has_submission): ?>
        <button type="button" class="btn btn-sm btn-outline-info ml-1 view-movs-btn"
                data-target-id="<?= $row['real_task_id'] ?>" title="View uploaded MOVs">
            <i class="fa fa-folder-open"></i> MOVs
        </button>
        <?php endif; ?>
        </td>
        <td class="text-center align-middle" data-label="Status">
            <?php
            $badgeClass = ($currentStatus == 'Verified') ? 'badge-success'
                : (($currentStatus == 'For Verification') ? 'badge-warning'
                : ($is_na ? 'badge-info' : 'badge-secondary'));
            ?>
            <span class="badge <?= $badgeClass ?>" <?= $row_locked ? 'title="' . ($task_is_strategic ? 'Strategic Plan — VP only' : 'VPREI — Strategic targets only') . '"' : '' ?>>
                <?= $currentStatus ?? 'Pending' ?>
                <?= $row_locked ? ' <i class="fas fa-lock ml-1" style="font-size:0.6rem;"></i>' : '' ?>
            </span>
        </td>
        <td class="text-center align-middle" data-label="Actions">
            <div class="btn-group" role="group">
            <?php if (!$is_admin_view && !$row_locked && !$period_locked): ?>
            <div class="dropdown d-inline-block">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                        type="button"
                        id="statusDropdown<?= $row['real_task_id'] ?>"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                        data-faculty="<?= $nameId ?>" title="Change status">
                    <i class="fa fa-cog"></i>
                </button>
                <div class="dropdown-menu" aria-labelledby="statusDropdown<?= $row['real_task_id'] ?>">
                    <a class="dropdown-item set_status" href="javascript:void(0)"
                       data-id="<?= $row['real_task_id'] ?>"
                       data-faculty="<?= $nameId ?>"
                       data-value="For Verification"><i class="fa fa-clock mr-2"></i>For Verification</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item set_status" href="javascript:void(0)"
                       data-id="<?= $row['real_task_id'] ?>"
                       data-faculty="<?= $nameId ?>"
                       data-value="Verified"><i class="fa fa-check-double mr-2"></i>Verified</a>
                    <?php if ($is_na): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item set_status text-info" href="javascript:void(0)"
                       data-id="<?= $row['real_task_id'] ?>"
                       data-faculty="<?= $nameId ?>"
                       data-value="N/A Verified"><i class="fa fa-ban mr-2"></i>Verify N/A</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php
            // Count existing comments for this task/faculty
            $commentCount = 0;
            $existingComment = '';
            if ($has_submission) {
                $cmt_q = $conn->query("SELECT id, comment_text FROM target_comments WHERE task_id = " . intval($row['real_task_id']) . " AND faculty_id = " . intval($nameId) . " ORDER BY created_at DESC LIMIT 1");
                if ($cmt_q && $cmt_q->num_rows > 0) {
                    $cmt_row = $cmt_q->fetch_assoc();
                    $existingComment = $cmt_row['comment_text'];
                    $cnt_q = $conn->query("SELECT COUNT(*) AS cnt FROM target_comments WHERE task_id = " . intval($row['real_task_id']) . " AND faculty_id = " . intval($nameId));
                    if ($cnt_q) $commentCount = $cnt_q->fetch_assoc()['cnt'];
                }
            }
            $acc_text = $row['actual_accomplishment'] ?? '';
            ?>
            <?php if ($has_submission && !$is_na): ?>
                <button type="button" class="btn btn-sm btn-outline-info comment-btn ml-1"
                        data-task-id="<?= $row['real_task_id'] ?>"
                        data-faculty="<?= $nameId ?>"
                        data-comment="<?= htmlspecialchars($existingComment) ?>"
                        data-task-name="<?= htmlspecialchars($row['si']) ?>"
                        title="Comment on MOV">
                    <i class="fa fa-comment"></i>
                    <?php if ($commentCount > 0): ?>
                        <span class="badge badge-light ml-1"><?= $commentCount ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success acc-btn ml-1"
                        data-task-id="<?= $row['real_task_id'] ?>"
                        data-task-name="<?= htmlspecialchars($row['si']) ?>"
                        data-acc="<?= htmlspecialchars($acc_text) ?>"
                        title="View Actual Accomplishment">
                    <i class="fa fa-check-circle"></i>
                </button>
            <?php elseif ($has_submission && $is_na): ?>
                <button type="button" class="btn btn-sm btn-outline-success acc-btn ml-1"
                        data-task-id="<?= $row['real_task_id'] ?>"
                        data-task-name="<?= htmlspecialchars($row['si']) ?>"
                        data-acc="<?= htmlspecialchars($acc_text) ?>"
                        title="View Actual Accomplishment">
                    <i class="fa fa-check-circle"></i>
                </button>
            <?php endif; ?>
            </div>
        </td>
        <td class="text-center align-middle" data-label="E">
            <?php 
                $effApplicable = (isset($row['task_efficiency']) && $row['task_efficiency'] === 'Applicable');
                $currentEff = isset($row['rating_efficiency']) ? $row['rating_efficiency'] : '-';
                $ratingDisabled = $is_na || !$has_submission || $period_locked;
                $hardLocked = $is_admin_view || $row_locked || $is_na || !$has_submission;
            ?>
            <?php if ($hardLocked): ?>
                <span class="badge <?= isset($row['rating_efficiency']) ? 'badge-success' : 'badge-secondary' ?>" <?= $row_locked ? 'title="' . ($task_is_strategic ? 'Strategic Plan — VP only' : 'VPREI — Strategic targets only') . '"' : '' ?>><?= ($effApplicable && !$ratingDisabled) ? $currentEff : 'N/A' ?><?= $row_locked ? ' <i class="fas fa-lock" style="font-size:0.6rem;"></i>' : '' ?></span>
            <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm <?= isset($row['rating_efficiency']) ? 'btn-success' : 'btn-secondary' ?> dropdown-toggle" 
                        type="button" 
                        id="effDropdown<?= $row['progress_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        <?= !$effApplicable || $period_locked ? 'disabled' : '' ?>>
                    <?= $effApplicable ? (isset($row['rating_efficiency']) ? $row['rating_efficiency'] : 'Set') : 'N/A' ?>
                    <?php if ($period_locked): ?><i class="fas fa-lock ml-1" style="font-size:0.6rem;"></i><?php endif; ?>
                </button>
                <?php if ($effApplicable && !$period_locked): ?>
                    <div class="dropdown-menu p-3" aria-labelledby="effDropdown<?= $row['progress_id'] ?>" style="min-width: 200px;">
                        <small class="text-muted mb-2 d-block">Select rating:</small>
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
                        <?php if ($task_sub === 'ter'): ?>
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
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle" data-label="Q">
            <?php 
                $qualApplicable = (isset($row['task_quality']) && $row['task_quality'] === 'Applicable');
                $currentQual = isset($row['rating_quality']) ? $row['rating_quality'] : '-';
                $ratingDisabled = $is_na || !$has_submission || $period_locked;
                $hardLocked = $is_admin_view || $row_locked || $is_na || !$has_submission;
            ?>
            <?php if ($hardLocked): ?>
                <span class="badge <?= isset($row['rating_quality']) ? 'badge-success' : 'badge-secondary' ?>" <?= $row_locked ? 'title="' . ($task_is_strategic ? 'Strategic Plan — VP only' : 'VPREI — Strategic targets only') . '"' : '' ?>><?= ($qualApplicable && !$ratingDisabled) ? $currentQual : 'N/A' ?><?= $row_locked ? ' <i class="fas fa-lock" style="font-size:0.6rem;"></i>' : '' ?></span>
            <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm <?= isset($row['rating_quality']) ? 'btn-success' : 'btn-secondary' ?> dropdown-toggle" 
                        type="button" 
                        id="qualDropdown<?= $row['progress_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        <?= !$qualApplicable || $period_locked ? 'disabled' : '' ?>>
                    <?= $qualApplicable ? (isset($row['rating_quality']) ? $row['rating_quality'] : 'Set') : 'N/A' ?>
                    <?php if ($period_locked): ?><i class="fas fa-lock ml-1" style="font-size:0.6rem;"></i><?php endif; ?>
                </button>
                <?php if ($qualApplicable && !$period_locked): ?>
                    <div class="dropdown-menu p-3" aria-labelledby="qualDropdown<?= $row['progress_id'] ?>" style="min-width: 200px;">
                        <small class="text-muted mb-2 d-block">Select rating:</small>
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
                        <?php if ($task_sub === 'ter'): ?>
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
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle" data-label="T">
            <?php 
                $timeApplicable = (isset($row['task_timeliness']) && $row['task_timeliness'] === 'Applicable');
                $currentTime = isset($row['rating_timeliness']) ? $row['rating_timeliness'] : '-';
                $ratingDisabled = $is_na || !$has_submission || $period_locked;
                $hardLocked = $is_admin_view || $row_locked || $is_na || !$has_submission;
            ?>
            <?php if ($hardLocked): ?>
                <span class="badge <?= isset($row['rating_timeliness']) ? 'badge-success' : 'badge-secondary' ?>" <?= $row_locked ? 'title="' . ($task_is_strategic ? 'Strategic Plan — VP only' : 'VPREI — Strategic targets only') . '"' : '' ?>><?= ($timeApplicable && !$ratingDisabled) ? $currentTime : 'N/A' ?><?= $row_locked ? ' <i class="fas fa-lock" style="font-size:0.6rem;"></i>' : '' ?></span>
            <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm <?= isset($row['rating_timeliness']) ? 'btn-success' : 'btn-secondary' ?> dropdown-toggle" 
                        type="button" 
                        id="timeDropdown<?= $row['progress_id'] ?>" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false"
                        <?= !$timeApplicable || $period_locked ? 'disabled' : '' ?>>
                    <?= $timeApplicable ? (isset($row['rating_timeliness']) ? $row['rating_timeliness'] : 'Set') : 'N/A' ?>
                    <?php if ($period_locked): ?><i class="fas fa-lock ml-1" style="font-size:0.6rem;"></i><?php endif; ?>
                </button>
                <?php if ($timeApplicable && !$period_locked): ?>
                    <div class="dropdown-menu p-3" aria-labelledby="timeDropdown<?= $row['progress_id'] ?>" style="min-width: 200px;">
                        <small class="text-muted mb-2 d-block">Select rating:</small>
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
                        <?php if ($task_sub === 'ter'): ?>
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
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>
    </tr>
<?php endwhile; endif; ?>

				</tbody>
						</table>
						</div><!-- /table-responsive -->

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
<?php if (!$is_admin_view && !$period_locked): ?>
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


<div class="modal fade" id="targetCommentModal" tabindex="-1" role="dialog" aria-labelledby="targetCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="targetCommentModalLabel"><i class="fa fa-comment mr-2"></i>Comment on Target/MOV</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="targetCommentForm">
                    <input type="hidden" name="task_id" id="commentTaskId">
                    <input type="hidden" name="faculty_id" id="commentFacultyId">
                    <div class="form-group">
                        <label class="font-weight-bold">Target</label>
                        <p class="text-muted mb-2" id="commentTaskName"></p>
                    </div>
                    <div class="form-group">
                        <label for="commentTextInput" class="font-weight-bold">Your Comment</label>
                        <textarea class="form-control" id="commentTextInput" name="comment_text" rows="5" placeholder="Write your feedback on the submitted MOV/target..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveTargetCommentBtn"><i class="fa fa-save mr-1"></i> Save Comment</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="targetAccomplishmentModal" tabindex="-1" role="dialog" aria-labelledby="targetAccomplishmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="targetAccomplishmentModalLabel"><i class="fa fa-check-circle mr-2"></i>Actual Accomplishment</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">Target</label>
                    <p class="text-muted mb-2" id="accTaskName"></p>
                </div>
                <div class="form-group">
                    <div class="p-3" style="background:#f8f9fa; border-left:4px solid #28a745; border-radius:4px;" id="accTextDisplay"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>

// Pass the selected period code to JS for rating AJAX calls
<?php
// Build the canonical code for the SELECTED period (not necessarily the active one)
$selected_period_code = '';
if ($selected_period) {
    $sel_key = epes_period_key($selected_period['semester'], $selected_period['year']);
    foreach ($raw_periods as $p) {
        if (epes_period_key($p['semester'], $p['year']) === $sel_key) {
            $selected_period_code = $p['code'];
            break;
        }
    }
}
// Fall back to active if no selected period code found
if (empty($selected_period_code)) $selected_period_code = $active_period_code;
?>
var EPES_RATING_PERIOD = '<?= htmlspecialchars($selected_period_code) ?>';

$(document).ready(function(){
    // Target/MOV comment modal open
    $(document).on('click', '.comment-btn', function(){
        var taskId = $(this).data('task-id');
        var facultyId = $(this).data('faculty');
        var comment = $(this).data('comment') || '';
        var taskName = $(this).data('task-name') || '';
        
        $('#commentTaskId').val(taskId);
        $('#commentFacultyId').val(facultyId);
        $('#commentTaskName').text(taskName);
        $('#commentTextInput').val(comment);
        $('#targetCommentModal').modal('show');
    });

    // Actual Accomplishment modal open
    $(document).on('click', '.acc-btn', function(){
        var taskName = $(this).data('task-name') || '';
        var acc = $(this).data('acc') || '';
        $('#accTaskName').text(taskName);
        if (acc.trim() === '') {
            $('#accTextDisplay').html('<span class="text-muted">No actual accomplishment recorded yet.</span>');
        } else {
            $('#accTextDisplay').text(acc);
        }
        $('#targetAccomplishmentModal').modal('show');
    });

    // Save target comment
    $('#saveTargetCommentBtn').click(function(){
        var btn = $(this);
        var taskId = $('#commentTaskId').val();
        var facultyId = $('#commentFacultyId').val();
        var comment = $('#commentTextInput').val().trim();
        
        if (!comment) {
            alert_toast('Please enter a comment.', 'warning');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');
        
        $.ajax({
            url: 'ajax.php?action=save_target_comment',
            method: 'POST',
            data: {
                task_id: taskId,
                faculty_id: facultyId,
                comment_text: comment
            },
            success: function(resp){
                try {
                    var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (r.status === 'success') {
                        alert_toast(r.message || 'Comment saved.', 'success');
                        $('#targetCommentModal').modal('hide');
                        setTimeout(function(){ location.reload(); }, 1000);
                    } else {
                        alert_toast(r.message || 'Failed to save comment.', 'danger');
                    }
                } catch(e) {
                    alert_toast('Failed to save comment.', 'danger');
                }
                btn.prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save Comment');
            },
            error: function(){
                alert_toast('Connection error.', 'danger');
                btn.prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save Comment');
            }
        });
    });
});

$(document).ready(function(){
    $(document).on("click", ".set_rating", function(){
        var taskId = $(this).data("id");
        var facultyId = $(this).data("faculty");
        var field = $(this).data("field");
        var value = $(this).data("value");

        $.ajax({
            url: "ajax.php?action=save_rating",
            method: "POST",
            data: { task_id: taskId, faculty_id: facultyId, field: field, value: value, rating_period: EPES_RATING_PERIOD },
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

    // Pre-check before marking a target "Verified": every Applicable rating
    // criterion (E/Q/T) for this row must already have a value, otherwise the
    // server's ratings-gate will silently reject the verify and the status
    // appears "stuck" (rating saves, status doesn't change). Surface the exact
    // missing criteria here so the evaluator knows what to set first.
    if (status === 'Verified') {
        var $row = $(this).closest('tr');
        var missing = [];
        $row.find('td[data-label="E"], td[data-label="Q"], td[data-label="T"]').each(function () {
            var label = $(this).data('label');
            // A criterion is required if its dropdown button is present and
            // still shows "Set" (applicable but unrated) OR a stored 0/empty
            // value (the server also treats 0 as "not rated"). "N/A" cells have
            // no dropdown button, so they are correctly skipped.
            var $btn = $(this).find('button.dropdown-toggle');
            if ($btn.length) {
                var txt = $.trim($btn.text());
                // "N/A" criteria are not applicable — skip them entirely so a
                // target can be verified when only the Applicable criteria are
                // rated. (The rating cell renders an N/A button, not a span, so
                // we must exclude it by its exact text.)
                if (txt === 'N/A') return;
                var num = parseFloat(txt);
                if (txt === 'Set' || txt === '' || isNaN(num) || num === 0) {
                    var name = (label === 'E') ? 'Efficiency'
                             : (label === 'Q') ? 'Quality'
                             : (label === 'T') ? 'Timeliness' : label;
                    missing.push(name);
                }
            }
        });
        if (missing.length > 0) {
            alert_toast(
                'Cannot verify yet — please set the ' + missing.join(', ') +
                ' rating' + (missing.length > 1 ? 's' : '') + ' first.',
                'warning'
            );
            return;
        }
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

        if(value < 0 || value > 5){
            alert_toast("Rating must be between 0 and 5", "warning");
            $(this).val("");
            return;
        }

        $.ajax({
            url: "ajax.php?action=save_rating",
            method: "POST",
            data: { task_id: taskId, faculty_id: facultyId, field: field, value: value, rating_period: EPES_RATING_PERIOD },
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
            data: { task_id: taskId, faculty_id: facultyId, field: field, value: value, rating_period: EPES_RATING_PERIOD },
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
    $(document).on('click', '.view-movs-btn', function(){
    var targetId = $(this).data('target-id');
    uni_modal('<i class="fa fa-folder-open"></i> Uploaded MOVs', 'view_target_movs.php?target_id=' + targetId + '&faculty_id=<?= htmlspecialchars($nameId) ?>', 'large');
    });
});

</script>