<?php include 'db_connect.php' ?>
<?php
require_once 'includes/rating_functions.php';
require_once 'includes/period_builder.php';

// Access control: Dean only
$eval_id = intval($_SESSION['login_id']);
$login_type = $_SESSION['login_type'] ?? -1;
$is_dean = false;

if (!empty($_SESSION['is_evaluator'])) {
    $is_dean = (($_SESSION['evaluator_role'] ?? '') === 'dean');
} elseif ($login_type == 1) {
    $stmt = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
    $stmt->bind_param("i", $eval_id);
    $stmt->execute();
    $stmt->bind_result($eval_type);
    $stmt->fetch();
    $stmt->close();
    $is_dean = ($eval_type == 1);
}

if (!$is_dean) {
    echo "<script>alert('Access denied. Dean only.'); window.location.href='index.php?page=home';</script>";
    exit;
}

// Use the active period code from period_builder
$rating_period = $active_period_code ?? '';
$period_label_str = $period_label ?? 'No period';

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS `renewal_recommendations` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `rating_period` varchar(100) NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `instruction_ave` decimal(5,2) DEFAULT NULL,
  `support_ave` decimal(5,2) DEFAULT NULL,
  `total_tasks` int(11) NOT NULL DEFAULT 0,
  `verified_tasks` int(11) NOT NULL DEFAULT 0,
  `avg_efficiency` decimal(3,2) DEFAULT NULL,
  `avg_timeliness` decimal(3,2) DEFAULT NULL,
  `avg_quality` decimal(3,2) DEFAULT NULL,
  `recommendation_status` enum('Pending','Recommended','Not Recommended','For Review') DEFAULT 'Pending',
  `system_generated_reason` text NOT NULL,
  `dean_reason` text DEFAULT NULL,
  `dean_decision` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `dean_decision_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `evaluator_id` (`evaluator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
?>
<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0"><i class="fa fa-clipboard-check mr-1"></i> COS Faculty Renewal Recommendation</h5>
                <button class="btn btn-success btn-sm ml-2" onclick="printRecommendation()">
                    <i class="fas fa-print mr-1"></i> Print Recommendation
                </button>
            </div>
            <?php if (count($real_periods) > 0):
                $sel_key = $selected_period ? epes_period_key($selected_period['semester'], $selected_period['year']) : '';
            ?>
            <select id="period_selector" class="form-control form-control-sm"
                    onchange="window.location.href='index.php?page=recommendation&period='+encodeURIComponent(this.value)"
                    style="width:auto; font-size:0.85rem; padding:6px 28px 6px 12px; max-width:260px;">
                <?php foreach($real_periods as $rp):
                    $key = epes_period_key($rp['semester'], $rp['year']);
                    $opt_label = $rp['semester'] . ' ' . $rp['year'] . ($rp['is_active'] ? ' (current)' : '');
                ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3" style="font-size:0.82rem;">
                <i class="fas fa-info-circle mr-1"></i> Showing COS faculty performance for <strong><?= htmlspecialchars($period_label_str) ?></strong>.
                Scores are computed using the same weighted formula as the IPCR rating page.
            </p>
            <?php
            // Find COS position IDs by name (not hardcoded)
            $cos_pos_ids = [];
            $pos_qry = $conn->query("SELECT id FROM position_list WHERE position LIKE '%Contract of Service%' OR position LIKE '%COS%'");
            while ($pr = $pos_qry->fetch_assoc()) $cos_pos_ids[] = (int)$pr['id'];
            $pos_in = count($cos_pos_ids) > 0 ? implode(',', $cos_pos_ids) : '19';

            // Get all COS faculty
            $faculty_list = $conn->query("
                SELECT e.id, e.firstname, e.middlename, e.lastname, e.department_id, e.position_id, e.designation_id,
                       dep.department AS dept_name
                FROM employee_list e
                LEFT JOIN department_list dep ON e.department_id = dep.id
                WHERE e.position_id IN ($pos_in)
                ORDER BY dep.department, e.lastname, e.firstname
            ");
            $faculty_data = [];

            while ($emp = $faculty_list->fetch_assoc()) {
                $emp_id = (int)$emp['id'];
                $emp_pos = (int)$emp['position_id'];
                $emp_des = (int)$emp['designation_id'];

                // Use shared computeWeightedRating — same as dashboard/rating.php
                $overall_score = computeWeightedRating($conn, $emp_id, $emp_pos, $emp_des, $rating_period, $period_filter);

                // Get instruction and support sub-averages for display
                $instruction_ave = null;
                $support_ave = null;

                // Instruction: average of verified instruction+TER tasks
                $instr_q = $conn->query("
                    SELECT AVG(r.efficiency) as eff, AVG(r.timeliness) as tim, AVG(r.quality) as qual
                    FROM ratings r
                    JOIN task_list t ON r.task_id = t.id
                    WHERE r.employee_id = $emp_id AND t.category = 'core'
                    AND (t.sub_category = 'ter' OR t.sub_category = 'instruction' OR t.sub_category = 'instructions')
                    $period_filter
                ");
                if ($instr_q) {
                    $ir = $instr_q->fetch_assoc();
                    $vals = array_filter([floatval($ir['eff']), floatval($ir['tim']), floatval($ir['qual'])], fn($v) => $v > 0);
                    if (count($vals) > 0) $instruction_ave = round(array_sum($vals) / count($vals), 2);
                }

                // Support: average of verified support tasks
                $supp_q = $conn->query("
                    SELECT AVG(r.efficiency) as eff, AVG(r.timeliness) as tim, AVG(r.quality) as qual
                    FROM ratings r
                    JOIN task_list t ON r.task_id = t.id
                    WHERE r.employee_id = $emp_id AND t.category = 'support'
                    $period_filter
                ");
                if ($supp_q) {
                    $sr = $supp_q->fetch_assoc();
                    $vals = array_filter([floatval($sr['eff']), floatval($sr['tim']), floatval($sr['qual'])], fn($v) => $v > 0);
                    if (count($vals) > 0) $support_ave = round(array_sum($vals) / count($vals), 2);
                }

                // Count verified tasks for this period
                $vt_q = $conn->query("SELECT COUNT(DISTINCT tp.task_id) as cnt FROM task_progress tp WHERE tp.faculty_id = $emp_id AND tp.progress = 'Verified' $period_filter");
                $verified_count = $vt_q ? (int)$vt_q->fetch_assoc()['cnt'] : 0;

                // E/T/Q averages
                $avg_eff = null; $avg_time = null; $avg_qual = null;
                $etq_q = $conn->query("
                    SELECT AVG(CASE WHEN r.efficiency > 0 THEN r.efficiency END) as eff,
                           AVG(CASE WHEN r.timeliness > 0 THEN r.timeliness END) as tim,
                           AVG(CASE WHEN r.quality > 0 THEN r.quality END) as qual
                    FROM ratings r WHERE r.employee_id = $emp_id $period_filter
                ");
                if ($etq_q) {
                    $etq = $etq_q->fetch_assoc();
                    $avg_eff = $etq['eff'] !== null ? round(floatval($etq['eff']), 2) : null;
                    $avg_time = $etq['tim'] !== null ? round(floatval($etq['tim']), 2) : null;
                    $avg_qual = $etq['qual'] !== null ? round(floatval($etq['qual']), 2) : null;
                }

                $score_val = $overall_score !== null ? round(floatval($overall_score), 2) : 0;

                $faculty_data[] = [
                    'id' => $emp_id,
                    'name' => $emp['lastname'] . ', ' . $emp['firstname'] . ' ' . $emp['middlename'],
                    'department' => $emp['dept_name'] ?? 'N/A',
                    'verified_tasks' => $verified_count,
                    'instruction_ave' => $instruction_ave,
                    'support_ave' => $support_ave,
                    'total_score' => $score_val,
                    'adjectival' => getAdjectivalRating($score_val),
                    'avg_efficiency' => $avg_eff,
                    'avg_timeliness' => $avg_time,
                    'avg_quality' => $avg_qual,
                    'position_id' => $emp_pos,
                    'designation_id' => $emp_des,
                ];
            }
            ?>

            <?php if(count($faculty_data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="recommendation-list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th class="text-center">Verified Tasks</th>
                            <th class="text-center">Instruction (90%)</th>
                            <th class="text-center">Support (10%)</th>
                            <th class="text-center">Overall Score</th>
                            <th class="text-center">Adjectival Rating</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Dean Decision</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach($faculty_data as $row):
                            $stmt_check = $conn->prepare("SELECT * FROM renewal_recommendations WHERE faculty_id = ? AND rating_period = ?");
                            $stmt_check->bind_param("is", $row['id'], $rating_period);
                            $stmt_check->execute();
                            $check_rec = $stmt_check->get_result();
                            $rec_data = $check_rec->fetch_assoc();
                            $stmt_check->close();

                            $row_class = $row['adjectival'] == 'OUTSTANDING' ? 'table-success' : ($row['adjectival'] == 'VERY SATISFACTORY' ? 'table-primary' : ($row['adjectival'] == 'SATISFACTORY' ? 'table-info' : ($row['adjectival'] == 'UNSATISFACTORY' ? 'table-warning' : '')));

                            $total_score_val = $row['total_score'];
                            $instruction_ave_val = $row['instruction_ave'] ?? 0;
                            $support_ave_val = $row['support_ave'] ?? 0;
                            $avg_eff_val = $row['avg_efficiency'] !== null ? $row['avg_efficiency'] : 'null';
                            $avg_time_val = $row['avg_timeliness'] !== null ? $row['avg_timeliness'] : 'null';
                            $avg_qual_val = $row['avg_quality'] !== null ? $row['avg_quality'] : 'null';
                            $rec_id = $rec_data['id'] ?? 0;
                            $dean_decision = $rec_data['dean_decision'] ?? 'Pending';
                            $dean_reason_val = $rec_data['dean_reason'] ?? '';
                        ?>
                        <tr class="<?= $row_class ?>" data-faculty-id="<?= $row['id'] ?>" data-overall="<?= $total_score_val ?>" data-tasks="<?= intval($row['verified_tasks']) ?>" data-inst="<?= $instruction_ave_val ?>" data-supp="<?= $support_ave_val ?>" data-eff="<?= $avg_eff_val ?>" data-time="<?= $avg_time_val ?>" data-qual="<?= $avg_qual_val ?>" data-rec-id="<?= $rec_id ?>" data-dean-decision="<?= $dean_decision ?>" data-dean-reason="<?= htmlspecialchars($dean_reason_val, ENT_QUOTES) ?>">
                            <td class="text-center font-weight-bold"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $row['verified_tasks'] ?></span></td>
                            <td class="text-center"><?= $row['instruction_ave'] !== null ? number_format($row['instruction_ave'], 2) : '-' ?></td>
                            <td class="text-center"><?= $row['support_ave'] !== null ? number_format($row['support_ave'], 2) : '-' ?></td>
                            <td class="text-center"><strong><?= number_format($row['total_score'], 2) ?></strong></td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-<?= $row['adjectival'] == 'OUTSTANDING' ? 'success' : ($row['adjectival'] == 'VERY SATISFACTORY' ? 'primary' : ($row['adjectival'] == 'SATISFACTORY' ? 'info' : ($row['adjectival'] == 'UNSATISFACTORY' ? 'warning' : 'danger'))) ?> px-3 py-2"><?= $row['adjectival'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if($rec_data): ?>
                                <span class="badge badge-pill <?= $rec_data['recommendation_status'] == 'Recommended' ? 'badge-success' : ($rec_data['recommendation_status'] == 'Not Recommended' ? 'badge-danger' : 'badge-secondary') ?>">
                                    <?= $rec_data['recommendation_status'] ?>
                                </span>
                                <?php else: ?>
                                <span class="badge badge-pill badge-light">Not Generated</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($dean_decision == 'Approved'): ?>
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>
                                <?php elseif($dean_decision == 'Rejected'): ?>
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>
                                <?php else: ?>
                                <span class="badge badge-light">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary view-details" data-id="<?= $row['id'] ?>">
                                    <i class="fa fa-eye"></i> Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <h6><i class="fa fa-info-circle"></i> Rating Equivalent:</h6>
                <div class="row">
                    <div class="col-md-2"><span class="badge badge-success">4.75 - 5.00: OUTSTANDING</span></div>
                    <div class="col-md-2"><span class="badge badge-primary">3.61 - 4.74: VERY SATISFACTORY</span></div>
                    <div class="col-md-2"><span class="badge badge-info">2.61 - 3.60: SATISFACTORY</span></div>
                    <div class="col-md-2"><span class="badge badge-warning">1.61 - 2.60: UNSATISFACTORY</span></div>
                    <div class="col-md-2"><span class="badge badge-danger">1.60 below: POOR</span></div>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No COS faculty found</h5>
                <p class="text-muted">No faculty with Contract of Service position found in the system.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailsModalLabel"><i class="fa fa-user-graduate mr-1"></i> Faculty Renewal Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal-content">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#recommendation-list').DataTable({
        "dom": 'Bfrtip',
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
        "ordering": true,
        "order": [[1, 'asc']]
    });

    $(document).on('click', '.view-details', function() {
        var tr = $(this).closest('tr');
        var facultyId = tr.data('faculty-id');
        var recId = tr.data('rec-id');
        var overall = parseFloat(tr.data('overall')) || 0;
        var totalRatings = parseInt(tr.data('tasks')) || 0;
        var instructionAve = parseFloat(tr.data('inst')) || 0;
        var supportAve = parseFloat(tr.data('supp')) || 0;
        var eff = tr.data('eff');
        var time = tr.data('time');
        var qual = tr.data('qual');
        var deanDecision = tr.data('dean-decision') || 'Pending';
        var deanReason = tr.data('dean-reason') || '';

        if (eff === null || eff === undefined || eff === '' || eff === 'null' || isNaN(eff)) eff = null;
        else eff = parseFloat(eff);
        if (time === null || time === undefined || time === '' || time === 'null' || isNaN(time)) time = null;
        else time = parseFloat(time);
        if (qual === null || qual === undefined || qual === '' || qual === 'null' || isNaN(qual)) qual = null;
        else qual = parseFloat(qual);

        var facultyName = tr.find('td:nth-child(2)').text().trim();
        var department = tr.find('td:nth-child(3)').text().trim();
        var adjectival = tr.find('td:nth-child(8) span').text().trim();

        var systemStatement = generateStatement(overall, totalRatings, instructionAve, supportAve);

        var deanDecisionBadge = '';
        if (deanDecision === 'Approved') deanDecisionBadge = '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>';
        else if (deanDecision === 'Rejected') deanDecisionBadge = '<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>';
        else deanDecisionBadge = '<span class="badge badge-light">Pending</span>';

        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Faculty Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Name:</strong></td><td>${facultyName}</td></tr>
                        <tr><td><strong>Department:</strong></td><td>${department}</td></tr>
                        <tr><td><strong>Faculty Type:</strong></td><td><span class="badge badge-warning">COS</span></td></tr>
                        <tr><td><strong>Rating Period:</strong></td><td><?= htmlspecialchars($period_label_str) ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Performance Summary</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Verified Tasks:</strong></td><td>${totalRatings}</td></tr>
                        <tr><td><strong>Instruction (90%):</strong></td><td>${instructionAve > 0 ? instructionAve.toFixed(2) : '-'}</td></tr>
                        <tr><td><strong>Support (10%):</strong></td><td>${supportAve > 0 ? supportAve.toFixed(2) : '-'}</td></tr>
                        <tr><td><strong>Overall Score:</strong></td><td><span class="badge badge-${getScoreClass(overall)}">${overall.toFixed(2)} / 5.0</span></td></tr>
                        <tr><td><strong>Adjectival:</strong></td><td><span class="badge badge-pill badge-${getScoreClass(overall)}">${adjectival}</span></td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6 class="text-primary"><i class="fa fa-file-alt mr-1"></i> System Generated Statement of Reason</h6>
                    <div class="p-3 bg-light rounded" style="white-space: pre-wrap; font-size:0.85rem;">${systemStatement}</div>
                </div>
            </div>
            ${recId > 0 ? `
            <div class="row mt-3">
                <div class="col-md-12">
                    <hr>
                    <h6 class="text-success"><i class="fas fa-gavel mr-1"></i> Dean Decision</h6>
                    <p class="mb-2">Current decision: ${deanDecisionBadge}</p>
                    <div class="form-group">
                        <label><strong>Dean's Reason / Comments:</strong></label>
                        <textarea id="dean_reason" class="form-control" rows="3" placeholder="Enter your reasoning for the decision...">${deanReason}</textarea>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-success btn-sm btn-dean-decision" data-rec-id="${recId}" data-decision="Approved">
                            <i class="fas fa-check mr-1"></i> Approve
                        </button>
                        <button class="btn btn-danger btn-sm btn-dean-decision" data-rec-id="${recId}" data-decision="Rejected">
                            <i class="fas fa-times mr-1"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
            ` : `
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info" style="font-size:0.85rem;">
                        <i class="fas fa-info-circle mr-1"></i> Generate a recommendation first to enable Dean decision.
                    </div>
                    <button class="btn btn-success btn-block btn-generate-rec" data-faculty-id="${facultyId}" data-overall="${overall}" data-tasks="${totalRatings}" data-inst="${instructionAve}" data-supp="${supportAve}" data-eff="${eff !== null ? eff : ''}" data-time="${time !== null ? time : ''}" data-qual="${qual !== null ? qual : ''}">
                        <i class="fa fa-save mr-1"></i> Generate & Save Recommendation
                    </button>
                </div>
            </div>
            `}
        `;

        $('#modal-content').html(content);
        $('#detailsModal').modal('show');
    });

    $(document).on('click', '.btn-generate-rec', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var facultyId = $btn.attr('data-faculty-id');
        var overall = parseFloat($btn.attr('data-overall')) || 0;
        var totalRatings = parseInt($btn.attr('data-tasks')) || 0;
        var instructionAve = parseFloat($btn.attr('data-inst')) || 0;
        var supportAve = parseFloat($btn.attr('data-supp')) || 0;
        var eff = $btn.attr('data-eff');
        var time = $btn.attr('data-time');
        var qual = $btn.attr('data-qual');

        if (eff === '' || eff === 'null' || eff === 'undefined' || isNaN(eff)) eff = '';
        else eff = parseFloat(eff);
        if (time === '' || time === 'null' || time === 'undefined' || isNaN(time)) time = '';
        else time = parseFloat(time);
        if (qual === '' || qual === 'null' || qual === 'undefined' || isNaN(qual)) qual = '';
        else qual = parseFloat(qual);

        var systemStatement = generateStatement(overall, totalRatings, instructionAve, supportAve);
        var recStatus = overall >= 3.61 ? 'Recommended' : (overall >= 2.61 ? 'For Review' : 'Not Recommended');

        var btnHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $btn.prop('disabled', true);

        $.ajax({
            url: 'ajax.php?action=save_renewal_recommendation',
            method: 'POST',
            data: {
                faculty_id: facultyId,
                evaluator_id: <?= $eval_id ?>,
                rating_period: '<?= $rating_period ?>',
                overall_score: overall,
                instruction_ave: instructionAve,
                support_ave: supportAve,
                total_tasks: totalRatings,
                verified_tasks: 0,
                avg_efficiency: eff,
                avg_timeliness: time,
                avg_quality: qual,
                recommendation_status: recStatus,
                system_reason: systemStatement
            },
            success: function(resp) {
                if(resp.trim() == '1') {
                    alert_toast('Recommendation generated and saved successfully!', 'success');
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    alert_toast('Failed to save recommendation: ' + resp, 'danger');
                    $btn.prop('disabled', false);
                    $btn.html(btnHtml);
                }
            },
            error: function(xhr, status, error) {
                alert_toast('Error saving recommendation: ' + error, 'danger');
                $btn.prop('disabled', false);
                $btn.html(btnHtml);
            }
        });
    });

    $(document).on('click', '.btn-dean-decision', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var recId = $btn.attr('data-rec-id');
        var decision = $btn.attr('data-decision');
        var reason = $('#dean_reason').val();

        var btnHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $btn.prop('disabled', true);

        $.ajax({
            url: 'ajax.php?action=submit_dean_decision',
            method: 'POST',
            data: {
                id: recId,
                dean_decision: decision,
                dean_reason: reason
            },
            success: function(resp) {
                if(resp.trim() == '1') {
                    alert_toast('Dean decision saved: ' + decision, 'success');
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    alert_toast('Failed to save decision: ' + resp, 'danger');
                    $btn.prop('disabled', false);
                    $btn.html(btnHtml);
                }
            },
            error: function(xhr, status, error) {
                alert_toast('Error: ' + error, 'danger');
                $btn.prop('disabled', false);
                $btn.html(btnHtml);
            }
        });
    });

    function generateStatement(overall, totalRatings, instructionAve, supportAve) {
        var parts = [];
        if (totalRatings == 0) {
            return "No verified ratings found for this evaluation period. Faculty has no performance data available for renewal assessment.";
        }
        var adjectival = getAdjectivalLabel(overall);
        parts.push("The faculty has demonstrated " + adjectival + " performance with an overall weighted score of " + overall.toFixed(2) + " out of 5.0.");
        parts.push("For Contract of Service (COS) Faculty:");
        if (instructionAve > 0) {
            parts.push("Instruction Average: " + instructionAve.toFixed(2) + " (" + getAdjectivalLabel(instructionAve) + ") - Weighted at 90%.");
        }
        if (supportAve > 0) {
            parts.push("Support Function Average: " + supportAve.toFixed(2) + " (" + getAdjectivalLabel(supportAve) + ") - Weighted at 10%.");
        }
        parts.push("A total of " + totalRatings + " verified task(s) were evaluated for this period.");
        if (overall >= 4.75) {
            parts.push("Based on the exceptional performance indicators, this faculty member is STRONGLY RECOMMENDED for contract renewal.");
        } else if (overall >= 3.61) {
            parts.push("Based on the satisfactory performance indicators, this faculty member is RECOMMENDED for contract renewal.");
        } else if (overall >= 2.61) {
            parts.push("Based on the marginal performance indicators, this faculty member is recommended for contract renewal with conditions for improvement.");
        } else if (overall >= 1.61) {
            parts.push("Based on the unsatisfactory performance indicators, this faculty member requires significant improvement before renewal consideration.");
        } else {
            parts.push("Based on the poor performance indicators, this faculty member is NOT RECOMMENDED for contract renewal at this time.");
        }
        return parts.join(" ");
    }

    function getScoreClass(score) {
        if (score >= 4.75) return 'success';
        if (score >= 3.61) return 'primary';
        if (score >= 2.61) return 'info';
        if (score >= 1.61) return 'warning';
        return 'danger';
    }

    function getAdjectivalLabel(score) {
        if (score >= 4.75) return 'OUTSTANDING';
        if (score >= 3.61) return 'VERY SATISFACTORY';
        if (score >= 2.61) return 'SATISFACTORY';
        if (score >= 1.61) return 'UNSATISFACTORY';
        return 'POOR';
    }
});

// Print Recommendation — only Dean-Approved COS faculty
function printRecommendation() {
    var approvedRows = [];
    $('#recommendation-list tbody tr').each(function() {
        var deanDecision = $(this).data('dean-decision');
        if (deanDecision === 'Approved') {
            approvedRows.push({
                name: $(this).find('td:nth-child(2)').text().trim(),
                department: $(this).find('td:nth-child(3)').text().trim(),
                verified: $(this).find('td:nth-child(4)').text().trim(),
                instruction: $(this).find('td:nth-child(5)').text().trim(),
                support: $(this).find('td:nth-child(6)').text().trim(),
                overall: $(this).find('td:nth-child(7)').text().trim(),
                adjectival: $(this).find('td:nth-child(8) span').text().trim(),
            });
        }
    });

    if (approvedRows.length === 0) {
        alert_toast('No Dean-Approved COS faculty to print. Approve recommendations first.', 'warning');
        return;
    }

    var periodLabel = '<?= htmlspecialchars($period_label_str) ?>';
    var deanName = '<?php echo htmlspecialchars($_SESSION["login_name"] ?? "Dean"); ?>';
    var today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

    var rowsHtml = '';
    approvedRows.forEach(function(r, i) {
        rowsHtml += '<tr>'
            + '<td style="text-align:center;">' + (i + 1) + '</td>'
            + '<td style="font-weight:bold;">' + r.name + '</td>'
            + '<td>' + r.department + '</td>'
            + '<td style="text-align:center;">' + r.instruction + '</td>'
            + '<td style="text-align:center;">' + r.support + '</td>'
            + '<td style="text-align:center;font-weight:bold;">' + r.overall + '</td>'
            + '<td style="text-align:center;">' + r.adjectival + '</td>'
            + '</tr>';
    });

    var html = '<html><head><title>COS Faculty Renewal Recommendation - ' + periodLabel + '</title>'
        + '<style>'
        + '@page { size: A4; margin: 1.5cm; }'
        + 'body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }'
        + '.header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }'
        + '.header .school { font-size: 12px; font-weight: bold; text-transform: uppercase; }'
        + '.header .office { font-size: 10px; color: #444; margin-top: 2px; }'
        + '.header h2 { font-size: 14px; margin: 8px 0 2px; }'
        + '.header .period { font-size: 10px; color: #555; }'
        + 'table { width: 100%; border-collapse: collapse; margin-top: 10px; }'
        + 'th, td { border: 1px solid #000; padding: 5px 8px; font-size: 10px; }'
        + 'th { background-color: #e8e8e8; font-weight: bold; text-align: center; }'
        + '.summary { margin: 15px 0; font-size: 10px; }'
        + '.summary p { margin: 3px 0; }'
        + '.signatures { margin-top: 40px; display: flex; justify-content: space-between; }'
        + '.sig-block { text-align: center; width: 45%; }'
        + '.sig-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 5px; font-weight: bold; }'
        + '.sig-title { font-size: 9px; color: #555; }'
        + '.no-print { display: none; }'
        + '</style></head><body>'

        + '<div class="header">'
        +   '<div class="school">DR. EMILIO B. ESPINOSA SR. MEMORIAL STATE COLLEGE OF AGRICULTURE AND TECHNOLOGY</div>'
        +   '<div class="office">Office of the Vice President for Academic Affairs</div>'
        +   '<h2>RECOMMENDATION FOR CONTRACT OF SERVICE (COS) FACULTY RENEWAL</h2>'
        +   '<div class="period">Rating Period: ' + periodLabel + '</div>'
        + '</div>'

        + '<div class="summary">'
        +   '<p>This is to recommend the following Contract of Service (COS) faculty for contract renewal based on their performance evaluation results for the above-mentioned rating period.</p>'
        +   '<p>Total recommended faculty: <strong>' + approvedRows.length + '</strong></p>'
        + '</div>'

        + '<table>'
        +   '<thead><tr>'
        +     '<th style="width:30px;">#</th>'
        +     '<th>Name of Faculty</th>'
        +     '<th>Department</th>'
        +     '<th>Instruction (90%)</th>'
        +     '<th>Support (10%)</th>'
        +     '<th>Overall Score</th>'
        +     '<th>Adjectival Rating</th>'
        +   '</tr></thead>'
        +   '<tbody>' + rowsHtml + '</tbody>'
        + '</table>'

        + '<div style="margin-top:10px;font-size:9px;color:#555;">'
        +   'Rating Scale: 4.75-5.00 Outstanding | 3.61-4.74 Very Satisfactory | 2.61-3.60 Satisfactory | 1.61-2.60 Unsatisfactory | 1.60 below Poor'
        + '</div>'

        + '<div class="signatures">'
        +   '<div class="sig-block">'
        +     '<div class="sig-line">' + deanName + '</div>'
        +     '<div class="sig-title">Recommending Approval</div>'
        +     '<div class="sig-title">College Dean</div>'
        +   '</div>'
        +   '<div class="sig-block">'
        +     '<div class="sig-line">&nbsp;</div>'
        +     '<div class="sig-title">Approved By</div>'
        +     '<div class="sig-title">Vice President for Academic Affairs</div>'
        +   '</div>'
        + '</div>'

        + '<div style="margin-top:20px;font-size:9px;color:#999;text-align:right;">Generated: ' + today + '</div>'

        + '</body></html>';

    var printWindow = window.open('', '', 'height=800,width=1000');
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = function() {
        printWindow.print();
    };
}
</script>

<style>
.card-title { margin: 0; font-weight: 600; }
.badge-pill { font-size: 0.85rem; font-weight: 500; }
.modal-header.bg-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.btn-group-toggle .btn { padding: 10px 20px; }
#period_selector { border-radius: 20px; }
.table-success { background-color: rgba(40, 167, 69, 0.15) !important; }
.table-primary { background-color: rgba(0, 123, 255, 0.15) !important; }
.table-info { background-color: rgba(23, 162, 184, 0.15) !important; }
.table-warning { background-color: rgba(255, 193, 7, 0.15) !important; }
</style>