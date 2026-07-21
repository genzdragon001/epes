<?php
// === FACULTY DASHBOARD ===
$emp_qry = $conn->query("SELECT e.*, p.position as position_name, d.designation as designation_name FROM employee_list e LEFT JOIN position_list p ON e.position_id=p.id LEFT JOIN designation_list d ON e.designation_id=d.id WHERE e.id=$emp_id LIMIT 1");
$emp_data = $emp_qry->fetch_assoc();
$emp_position_id = intval($emp_data['position_id'] ?? 0);
$emp_designation_id = intval($emp_data['designation_id'] ?? 0);
$position_name = $emp_data['position_name'] ?? 'Faculty';
$designation_name = $emp_data['designation_name'] ?? '';

// Build the set of task ids that actually count as this faculty's applicable targets.
$applicable_ids_q = $conn->query("SELECT t.id FROM task_list t WHERE t.is_active=1 AND (t.academic_rank_id IS NULL OR t.academic_rank_id=0 OR t.academic_rank_id=$emp_position_id) AND " . task_designation_match($emp_designation_id) . " AND t.id NOT IN (SELECT task_id FROM target_exemptions WHERE position_id=$emp_position_id)");
$applicable_ids = [];
while ($ar = $applicable_ids_q->fetch_assoc()) $applicable_ids[] = intval($ar['id']);
$applicable_in = $applicable_ids ? implode(',', $applicable_ids) : '0';

$total_targets   = count($applicable_ids);
$submitted       = $conn->query("SELECT COUNT(DISTINCT task_id) FROM task_progress WHERE faculty_id=$emp_id AND task_id IN ($applicable_in) $period_filter")->fetch_row()[0];
$verified        = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id=$emp_id AND progress='Verified' $period_filter")->fetch_row()[0];
$for_verif       = $conn->query("SELECT COUNT(*) FROM task_progress WHERE faculty_id=$emp_id AND progress='For Verification' $period_filter")->fetch_row()[0];
$other_status    = $submitted - $verified - $for_verif;
$not_submitted   = max(0, $total_targets - $submitted);

// IPCR rating — use the same weighted computation as rating.php
require_once 'includes/rating_functions.php';
$ipcr_period_code = $selected_period['code'] ?? '';
$ipcr_score = computeWeightedRating($conn, $emp_id, $emp_position_id, $emp_designation_id, $ipcr_period_code, $period_filter);
$ipcr_adj = $ipcr_score !== null ? getAdjectivalRating($ipcr_score) : 'Not Rated';

$submission_pct = $total_targets > 0 ? round(($submitted/$total_targets)*100) : 0;
$verification_pct = $submitted > 0 ? round(($verified/$submitted)*100) : 0;

// Recent submissions
$recent = $conn->query("SELECT tp.progress, tp.date_created, t.success_indicators FROM task_progress tp INNER JOIN task_list t ON tp.task_id=t.id WHERE tp.faculty_id=$emp_id $period_filter ORDER BY tp.date_created DESC LIMIT 6");
?>

<!-- STAT TILES -->
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="fas fa-tasks"></i></div>
            <div class="stat-value"><?= $submitted ?>/<?= $total_targets ?></div>
            <div class="stat-label">Targets Submitted</div>
            <div class="stat-sub <?= $submission_pct >= 70 ? 'green' : ($submission_pct >= 40 ? 'amber' : 'red') ?>"><?= $submission_pct ?>% complete</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-amber">
            <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $for_verif ?></div>
            <div class="stat-label">Awaiting Review</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $verified ?></div>
            <div class="stat-label">Verified</div>
            <div class="stat-sub <?= $verification_pct >= 70 ? 'green' : 'amber' ?>"><?= $verification_pct ?>% of submitted</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card accent-purple">
            <div class="stat-icon purple"><i class="fas fa-star"></i></div>
            <div class="stat-value"><?= $ipcr_score !== null ? number_format($ipcr_score, 2) : '—' ?></div>
            <div class="stat-label">IPCR Rating</div>
            <div class="stat-sub <?= ($ipcr_score ?? 0) >= 3.61 ? 'green' : (($ipcr_score ?? 0) >= 2.61 ? 'amber' : 'red') ?>"><?= $ipcr_score !== null ? $ipcr_adj : 'Not Rated' ?></div>
        </div>
    </div>
</div>