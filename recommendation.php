<?php include 'db_connect.php' ?>
<?php
$eval_id = intval($_SESSION['login_id']);
$rating_period = isset($_SESSION['rating_period']) ? $_SESSION['rating_period'] : date('Y');

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

$conn->query("ALTER TABLE renewal_recommendations ADD COLUMN IF NOT EXISTS instruction_ave decimal(5,2) DEFAULT NULL AFTER overall_score");
$conn->query("ALTER TABLE renewal_recommendations ADD COLUMN IF NOT EXISTS support_ave decimal(5,2) DEFAULT NULL AFTER instruction_ave");

$stmt_type = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ?");
$stmt_type->bind_param("i", $eval_id);
$stmt_type->execute();
$stmt_type->bind_result($eval_type);
$stmt_type->fetch();
$stmt_type->close();

$is_dean = ($eval_type == 1);

function getAdjectivalRating($score) {
    if (!is_numeric($score) || $score <= 0) return "NO RATING";
    $score = round($score, 2);
    if ($score >= 4.75) return "OUTSTANDING";
    if ($score >= 3.61) return "VERY SATISFACTORY";
    if ($score >= 2.61) return "SATISFACTORY";
    if ($score >= 1.61) return "UNSATISFACTORY";
    if ($score <= 1.60) return "POOR";
    return "NO RATING";
}

function generateStatementOfReason($overall_score, $total_ratings, $instruction_ave, $support_ave, $faculty_type) {
    $parts = [];
    
    if ($total_ratings == 0) {
        return "No ratings found for this evaluation period. Faculty has no performance data available for renewal assessment.";
    }
    
    $adjectival = getAdjectivalRating($overall_score);
    $parts[] = "The faculty has demonstrated {$adjectival} performance with an overall weighted score of " . number_format($overall_score, 2) . " out of 5.0.";
    
    if ($faculty_type == 'COS') {
        $parts[] = "For Contract of Service (COS) Faculty:";
        if (is_numeric($instruction_ave) && $instruction_ave > 0) {
            $parts[] = "Instruction Average: " . number_format($instruction_ave, 2) . " (" . getAdjectivalRating($instruction_ave) . ") - Weighted at 90%.";
        }
        if (is_numeric($support_ave) && $support_ave > 0) {
            $parts[] = "Support Function Average: " . number_format($support_ave, 2) . " (" . getAdjectivalRating($support_ave) . ") - Weighted at 10%.";
        }
    }
    
    $parts[] = "A total of {$total_ratings} rating(s) were submitted for evaluation.";
    
    if ($overall_score >= 4.75) {
        $parts[] = "Based on the exceptional performance indicators, this faculty member is STRONGLY RECOMMENDED for contract renewal.";
    } elseif ($overall_score >= 3.61) {
        $parts[] = "Based on the satisfactory performance indicators, this faculty member is RECOMMENDED for contract renewal.";
    } elseif ($overall_score >= 2.61) {
        $parts[] = "Based on the marginal performance indicators, this faculty member is recommended for contract renewal with conditions for improvement.";
    } elseif ($overall_score >= 1.61) {
        $parts[] = "Based on the unsatisfactory performance indicators, this faculty member requires significant improvement before renewal consideration.";
    } else {
        $parts[] = "Based on the poor performance indicators, this faculty member is NOT RECOMMENDED for contract renewal at this time.";
    }
    
    return implode(" ", $parts);
}

$periods_result = $conn->query("SELECT DISTINCT rating_period FROM renewal_recommendations UNION SELECT '" . date('Y') . "' ORDER BY rating_period DESC");
?>
<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-clipboard-check"></i> COS Faculty Renewal Recommendation</h5>
            <div class="card-tools">
                <select id="rating_period_filter" class="form-control form-control-sm" style="width: 150px;">
                    <option value="">All Periods</option>
                    <?php while($p = $periods_result->fetch_assoc()): ?>
                    <option value="<?php echo $p['rating_period'] ?>" <?php echo ($rating_period == $p['rating_period']) ? 'selected' : '' ?>><?php echo $p['rating_period'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="card-body">
            <?php
            $faculty_list = $conn->query("SELECT id, firstname, middlename, lastname, department_id, position_id FROM employee_list WHERE position_id = '19' ORDER BY lastname ASC");
            $faculty_data = [];
            
            while ($emp = $faculty_list->fetch_assoc()) {
                $emp_id = $emp['id'];
                $emp_position_id = $emp['position_id'];
                
                $mfo = "1,0";
                
                $qry = $conn->query("
                    SELECT 
                        t.id AS task_id,
                        t.category,
                        t.sub_category,
                        t.success_indicators,
                        t.targets_measures,
                        t.efficiency AS task_efficiency,
                        t.timeliness AS task_timeliness,
                        t.quality AS task_quality,
                        t.mfo,
                        r.efficiency AS rating_efficiency,
                        r.timeliness AS rating_timeliness,
                        r.quality AS rating_quality,
                        tp.progress
                    FROM task_progress tp
                    INNER JOIN task_list t ON tp.task_id = t.id
                    LEFT JOIN ratings r ON r.task_id = tp.task_id AND r.employee_id = tp.faculty_id
                    WHERE tp.faculty_id = $emp_id 
                      AND t.mfo IN ($mfo)
                      AND t.is_active = 1
                    ORDER BY t.category, t.sub_category, t.id
                ");
                
                $ter_sum = 0; $ter_count = 0;
                $instruction_sum = 0; $instruction_count = 0;
                $support_sum = 0; $support_count = 0;
                $total_ratings = 0;
                
                while ($row = $qry->fetch_assoc()) {
                    $rating_eff = (isset($row['rating_efficiency']) && is_numeric($row['rating_efficiency'])) ? (float)$row['rating_efficiency'] : null;
                    $rating_time = (isset($row['rating_timeliness']) && is_numeric($row['rating_timeliness'])) ? (float)$row['rating_timeliness'] : null;
                    $rating_qual = (isset($row['rating_quality']) && is_numeric($row['rating_quality'])) ? (float)$row['rating_quality'] : null;
                    
                    $criteria = [];
                    if ($row['task_quality'] == 'Applicable' && $rating_qual !== null) $criteria['quality'] = $rating_qual;
                    if ($row['task_efficiency'] == 'Applicable' && $rating_eff !== null) $criteria['efficiency'] = $rating_eff;
                    if ($row['task_timeliness'] == 'Applicable' && $rating_time !== null) $criteria['timeliness'] = $rating_time;
                    
                    $average = (count($criteria) > 0) ? array_sum($criteria) / count($criteria) : null;
                    
                    $sub = strtolower($row['sub_category'] ?? '');
                    
                    if ($row['progress'] == 'Verified' && is_numeric($average)) {
                        $total_ratings++;
                        
                        if ($sub == 'ter') {
                            $ter_sum += $average;
                            $ter_count++;
                        } elseif ($sub == 'instruction' || $sub == 'instructions') {
                            $instruction_sum += $average;
                            $instruction_count++;
                        } elseif ($row['category'] == 'support') {
                            $support_sum += $average;
                            $support_count++;
                        }
                    }
                }
                
                $ter_ave = $ter_count > 0 ? $ter_sum / $ter_count : 0;
                
                $instr_task_qry = $conn->query("SELECT COUNT(*) as task_count FROM task_list WHERE category = 'core' AND (sub_category = 'instruction' OR sub_category = 'instructions') AND is_active = 1 AND (academic_rank_id IS NULL OR academic_rank_id = 0 OR academic_rank_id = $emp_position_id)");
                $total_instr_count = $instr_task_qry ? (int)$instr_task_qry->fetch_assoc()['task_count'] : 0;
                
                $exempt_qry = $conn->query("SELECT COUNT(*) as exempt_count FROM target_exemptions te INNER JOIN task_list tl ON te.task_id = tl.id WHERE te.position_id = $emp_position_id AND (tl.sub_category = 'instruction' OR tl.sub_category = 'instructions')");
                $exempt_count = $exempt_qry ? (int)$exempt_qry->fetch_assoc()['exempt_count'] : 0;
                $expected_instr_count = $total_instr_count - $exempt_count;
                
                $divisor = $expected_instr_count > 0 ? $expected_instr_count : ($instruction_count > 0 ? $instruction_count : 1);
                $instruction_div = $instruction_count > 0 ? $instruction_sum / $divisor : 0;
                
                $instruction_rating = ($ter_ave * 0.50) + ($instruction_div * 0.50);
                
                $support_average = $support_count > 0 ? $support_sum / $support_count : 0;
                
                $core_sum = 0;
                $core_total_count = 0;
                
                if ($instruction_count > 0 || $ter_count > 0) {
                    $core_sum += $instruction_rating;
                    $core_total_count += 1;
                }
                if ($support_count > 0) {
                    $core_sum += $support_average;
                    $core_total_count += 1;
                }
                
                $core_function = $core_total_count > 0 ? $core_sum / $core_total_count : 0;
                $core_weighted = $core_function * 0.90;
                $support_weighted = $support_average * 0.10;
                $total_score = $core_weighted + $support_weighted;
                
                $eff_sum = 0; $eff_count = 0;
                $time_sum = 0; $time_count = 0;
                $qual_sum = 0; $qual_count = 0;
                
                $qry2 = $conn->query("
                    SELECT 
                        r.efficiency AS rating_efficiency,
                        r.timeliness AS rating_timeliness,
                        r.quality AS rating_quality
                    FROM task_progress tp
                    INNER JOIN task_list t ON tp.task_id = t.id
                    LEFT JOIN ratings r ON r.task_id = tp.task_id AND r.employee_id = tp.faculty_id
                    WHERE tp.faculty_id = $emp_id 
                      AND t.mfo IN ($mfo)
                      AND tp.progress = 'Verified'
                ");
                
                while ($r = $qry2->fetch_assoc()) {
                    if (isset($r['rating_efficiency']) && is_numeric($r['rating_efficiency'])) { $eff_sum += $r['rating_efficiency']; $eff_count++; }
                    if (isset($r['rating_timeliness']) && is_numeric($r['rating_timeliness'])) { $time_sum += $r['rating_timeliness']; $time_count++; }
                    if (isset($r['rating_quality']) && is_numeric($r['rating_quality'])) { $qual_sum += $r['rating_quality']; $qual_count++; }
                }
                
                $avg_eff = $eff_count > 0 ? round($eff_sum / $eff_count, 2) : null;
                $avg_time = $time_count > 0 ? round($time_sum / $time_count, 2) : null;
                $avg_qual = $qual_count > 0 ? round($qual_sum / $qual_count, 2) : null;
                
                $dept_qry = $conn->query("SELECT department FROM department_list WHERE id = " . $emp['department_id']);
                $department = ($dept_qry && $dept_qry->num_rows > 0) ? $dept_qry->fetch_assoc()['department'] : 'N/A';
                
                $faculty_data[] = [
                    'id' => $emp_id,
                    'name' => $emp['lastname'] . ', ' . $emp['firstname'] . ' ' . $emp['middlename'],
                    'department' => $department,
                    'total_ratings' => $total_ratings,
                    'instruction_ave' => is_numeric($instruction_rating) ? number_format($instruction_rating, 2) : '-',
                    'support_ave' => is_numeric($support_average) ? number_format($support_average, 2) : '-',
                    'total_score' => is_numeric($total_score) ? number_format($total_score, 2) : '0.00',
                    'adjectival' => getAdjectivalRating($total_score),
                    'avg_efficiency' => $avg_eff,
                    'avg_timeliness' => $avg_time,
                    'avg_quality' => $avg_qual,
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
                            <th class="text-center">Total Ratings</th>
                            <th class="text-center">Instruction (90%)</th>
                            <th class="text-center">Support (10%)</th>
                            <th class="text-center">Total Score</th>
                            <th class="text-center">Adjectival Rating</th>
                            <th class="text-center">Status</th>
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
                            
                            $row_class = $row['adjectival'] == 'OUTSTANDING' ? 'table-success' : ($row['adjectival'] == 'VERY SATISFACTORY' ? 'table-primary' : ($row['adjectival'] == 'SATISFACTORY' ? 'table-info' : ($row['adjectival'] == 'UNSATISFACTORY' ? 'table-warning' : '')));
                            
                            $total_score_val = is_numeric($row['total_score']) ? floatval($row['total_score']) : 0;
                            $instruction_ave_val = is_numeric($row['instruction_ave']) ? floatval($row['instruction_ave']) : 0;
                            $support_ave_val = is_numeric($row['support_ave']) ? floatval($row['support_ave']) : 0;
                            $avg_eff_val = $row['avg_efficiency'] !== null && $row['avg_efficiency'] !== '' ? floatval($row['avg_efficiency']) : 'null';
                            $avg_time_val = $row['avg_timeliness'] !== null && $row['avg_timeliness'] !== '' ? floatval($row['avg_timeliness']) : 'null';
                            $avg_qual_val = $row['avg_quality'] !== null && $row['avg_quality'] !== '' ? floatval($row['avg_quality']) : 'null';
                        ?>
                        <tr class="<?php echo $row_class ?>" data-faculty-id="<?php echo $row['id'] ?>" data-overall="<?php echo $total_score_val ?>" data-tasks="<?php echo intval($row['total_ratings']) ?>" data-inst="<?php echo $instruction_ave_val ?>" data-supp="<?php echo $support_ave_val ?>" data-eff="<?php echo $avg_eff_val ?>" data-time="<?php echo $avg_time_val ?>" data-qual="<?php echo $avg_qual_val ?>">
                            <td class="text-center font-weight-bold"><?php echo $i++ ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']) ?></strong></td>
                            <td><?php echo htmlspecialchars($row['department']) ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?php echo $row['total_ratings'] ?></span></td>
                            <td class="text-center"><?php echo $row['instruction_ave'] ?></td>
                            <td class="text-center"><?php echo $row['support_ave'] ?></td>
                            <td class="text-center"><strong><?php echo $row['total_score'] ?></strong></td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-<?php 
                                    echo $row['adjectival'] == 'OUTSTANDING' ? 'success' : 
                                        ($row['adjectival'] == 'VERY SATISFACTORY' ? 'primary' : 
                                        ($row['adjectival'] == 'SATISFACTORY' ? 'info' : 
                                        ($row['adjectival'] == 'UNSATISFACTORY' ? 'warning' : 'danger')));
                                ?> px-3 py-2"><?php echo $row['adjectival'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if($rec_data): ?>
                                <span class="badge badge-pill <?php echo $rec_data['recommendation_status'] == 'Recommended' ? 'badge-success' : ($rec_data['recommendation_status'] == 'Not Recommended' ? 'badge-danger' : 'badge-secondary') ?>">
                                    <?php echo $rec_data['recommendation_status'] ?>
                                </span>
                                <?php else: ?>
                                <span class="badge badge-pill badge-light">Not Generated</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary view-details" data-id="<?php echo $row['id'] ?>">
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
                <h5 class="text-muted">No faculty data available</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailsModalLabel"><i class="fa fa-user-graduate"></i> Faculty Renewal Details</h5>
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
        var overall = parseFloat(tr.data('overall')) || 0;
        var totalRatings = parseInt(tr.data('tasks')) || 0;
        var instructionAve = parseFloat(tr.data('inst')) || 0;
        var supportAve = parseFloat(tr.data('supp')) || 0;
        var facultyType = tr.data('type');
        var eff = tr.data('eff');
        var time = tr.data('time');
        var qual = tr.data('qual');
        
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
        
        var content = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Faculty Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Name:</strong></td><td>${facultyName}</td></tr>
                        <tr><td><strong>Department:</strong></td><td>${department}</td></tr>
                        <tr><td><strong>Faculty Type:</strong></td><td><span class="badge badge-warning">COS</span></td></tr>
                        <tr><td><strong>Rating Period:</strong></td><td><?php echo $rating_period ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Performance Summary</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Total Ratings:</strong></td><td>${totalRatings}</td></tr>
                        <tr><td><strong>Instruction (90%):</strong></td><td>${instructionAve}</td></tr>
                        <tr><td><strong>Support (10%):</strong></td><td>${supportAve}</td></tr>
                        <tr><td><strong>Overall Score:</strong></td><td><span class="badge badge-${getScoreClass(overall)}">${overall.toFixed(2)} / 5.0</span></td></tr>
                        <tr><td><strong>Adjectival Rating:</strong></td><td><span class="badge badge-pill badge-${getScoreClass(overall)}">${adjectival}</span></td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6 class="text-primary"><i class="fa fa-file-alt"></i> System Generated Statement of Reason</h6>
                    <div class="p-3 bg-light rounded" style="white-space: pre-wrap;">${systemStatement}</div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button class="btn btn-success btn-block btn-generate-rec" data-faculty-id="${facultyId}" data-overall="${overall}" data-tasks="${totalRatings}" data-inst="${instructionAve}" data-supp="${supportAve}" data-eff="${eff !== null ? eff : ''}" data-time="${time !== null ? time : ''}" data-qual="${qual !== null ? qual : ''}">
                        <i class="fa fa-save"></i> Generate & Save Recommendation
                    </button>
                </div>
            </div>
        `;
        
        $('#modal-content').html(content);
        $('#detailsModal').modal('show');
    });

    $(document).on('click', '.btn-generate-rec', function(e) {
        e.preventDefault();
        console.log('Generate button clicked');
        
        var $btn = $(this);
        var facultyId = $btn.attr('data-faculty-id');
        var overall = parseFloat($btn.attr('data-overall')) || 0;
        var totalRatings = parseInt($btn.attr('data-tasks')) || 0;
        var instructionAve = parseFloat($btn.attr('data-inst')) || 0;
        var supportAve = parseFloat($btn.attr('data-supp')) || 0;
        var eff = $btn.attr('data-eff');
        var time = $btn.attr('data-time');
        var qual = $btn.attr('data-qual');
        
        console.log('Faculty ID:', facultyId);
        console.log('Overall:', overall);
        console.log('Instruction:', instructionAve);
        console.log('Support:', supportAve);
        
        if (eff === null || eff === undefined || eff === '' || eff === 'null' || eff === 'undefined' || isNaN(eff)) eff = '';
        else eff = parseFloat(eff);
        if (time === null || time === undefined || time === '' || time === 'null' || time === 'undefined' || isNaN(time)) time = '';
        else time = parseFloat(time);
        if (qual === null || qual === undefined || qual === '' || qual === 'null' || qual === 'undefined' || isNaN(qual)) qual = '';
        else qual = parseFloat(qual);
        
        var systemStatement = generateStatement(overall, totalRatings, instructionAve, supportAve);
        var recStatus = overall >= 4.75 ? 'Recommended' : (overall >= 3.61 ? 'Recommended' : (overall >= 2.61 ? 'For Review' : 'Not Recommended'));
        
        var btnHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $btn.prop('disabled', true);
        
        var postData = {
            faculty_id: facultyId,
            evaluator_id: <?php echo $eval_id ?>,
            rating_period: '<?php echo $rating_period ?>',
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
        };
        
        console.log('Post Data:', postData);
        
        $.ajax({
            url: 'ajax.php?action=save_renewal_recommendation',
            method: 'POST',
            data: postData,
            success: function(resp) {
                console.log('Full Response:', resp);
                console.log('Response length:', resp.length);
                console.log('Response trimmed:', resp.trim());
                if(resp.trim() == '1') {
                    alert('Recommendation generated and saved successfully!');
                    location.reload();
                } else {
                    alert('Failed to save recommendation.\n\nResponse: ' + resp);
                    $btn.prop('disabled', false);
                    $btn.html(btnHtml);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                alert('Error saving recommendation: ' + error);
                $btn.prop('disabled', false);
                $btn.html(btnHtml);
            }
        });
    });

    function generateStatement(overall, totalRatings, instructionAve, supportAve) {
        var parts = [];
        
        if (totalRatings == 0) {
            return "No ratings found for this evaluation period. Faculty has no performance data available for renewal assessment.";
        }
        
        var adjectival = getAdjectivalLabel(overall);
        parts.push("The faculty has demonstrated " + adjectival + " performance with an overall weighted score of " + overall.toFixed(2) + " out of 5.0.");
        
        parts.push("For Contract of Service (COS) Faculty:");
        if (instructionAve && instructionAve !== '-') {
            var instAdj = getAdjectivalLabel(parseFloat(instructionAve));
            parts.push("Instruction Average: " + instructionAve + " (" + instAdj + ") - Weighted at 90%.");
        }
        if (supportAve && supportAve !== '-') {
            var suppAdj = getAdjectivalLabel(parseFloat(supportAve));
            parts.push("Support Function Average: " + supportAve + " (" + suppAdj + ") - Weighted at 10%.");
        }
        
        parts.push("A total of " + totalRatings + " rating(s) were submitted for evaluation.");
        
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
</script>

<style>
.card-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
.card-title { margin: 0; font-weight: 600; }
.badge-pill { font-size: 0.85rem; font-weight: 500; }
.modal-header.bg-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.btn-group-toggle .btn { padding: 10px 20px; }
#rating_period_filter { border-radius: 20px; }
.table-success { background-color: rgba(40, 167, 69, 0.15) !important; }
.table-primary { background-color: rgba(0, 123, 255, 0.15) !important; }
.table-info { background-color: rgba(23, 162, 184, 0.15) !important; }
.table-warning { background-color: rgba(255, 193, 7, 0.15) !important; }
</style>
