<?php 
include 'db_connect.php';

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

function getAllocation($conn, $position_id, $designation_id, $category, $sub_category = null) {
    $sql = "SELECT percentage FROM percentage_allocation 
            WHERE position_id = $position_id";
    
    if ($designation_id) {
        $sql .= " AND designation_id = $designation_id";
    } else {
        $sql .= " AND designation_id IS NULL";
    }
    
    $sql .= " AND category = '$category'";
    
    if ($sub_category) {
        $sql .= " AND sub_category = '$sub_category'";
    } else {
        $sql .= " AND (sub_category IS NULL OR sub_category = '')";
    }
    
    $sql .= " LIMIT 1";
    $qry = $conn->query($sql);
    if($qry && $qry->num_rows > 0) {
        return floatval($qry->fetch_assoc()['percentage']);
    }
    return 0;
}

$selected_position_id = isset($_POST['position_id']) ? intval($_POST['position_id']) : 0;
$selected_designation = isset($_POST['designation']) ? $_POST['designation'] : '';

$isDesignated = ($selected_designation == 'yes');
$position_id = $selected_position_id;
$designation_id = null;

if ($isDesignated) {
    $desig_qry = $conn->query("SELECT id FROM designation_list ORDER BY id ASC LIMIT 1");
    if ($desig_qry && $desig_qry->num_rows > 0) {
        $designation_id = $desig_qry->fetch_assoc()['id'];
    }
}

$position_info = null;
$faculty_targets = [];
$allocations = [
    'strategic' => 0,
    'core_total' => 0,
    'core_instructions' => 0,
    'core_ter' => 0,
    'core_instruction' => 0,
    'core_research' => 0,
    'core_extension' => 0,
    'support' => 0,
];

if ($position_id > 0) {
    $pos_qry = $conn->query("SELECT * FROM position_list WHERE id = $position_id");
    if ($pos_qry && $pos_qry->num_rows > 0) {
        $position_info = $pos_qry->fetch_assoc();
        
        $allocations = [
            'strategic' => getAllocation($conn, $position_id, $designation_id, 'strategic', null),
            'core_total' => getAllocation($conn, $position_id, $designation_id, 'core', 'total'),
            'core_instructions' => getAllocation($conn, $position_id, $designation_id, 'core', 'instructions'),
            'core_ter' => getAllocation($conn, $position_id, $designation_id, 'core', 'ter'),
            'core_instruction' => getAllocation($conn, $position_id, $designation_id, 'core', 'instruction'),
            'core_research' => getAllocation($conn, $position_id, $designation_id, 'core', 'research'),
            'core_extension' => getAllocation($conn, $position_id, $designation_id, 'core', 'extension'),
            'support' => getAllocation($conn, $position_id, $designation_id, 'support', null),
        ];
        
        $task_qry = $conn->query("SELECT * FROM task_list WHERE is_active = 1 ORDER BY id ASC");
        while ($task = $task_qry->fetch_assoc()) {
            $designation_match = empty($task['designation_id']) || $task['designation_id'] == $designation_id;
            $rank_match = empty($task['academic_rank_id']) || $task['academic_rank_id'] == $position_id;
            $task_category = strtolower($task['category'] ?? '');
            
            $exempt_qry = $conn->query("SELECT * FROM target_exemptions WHERE task_id = {$task['id']} 
                AND position_id = $position_id
                LIMIT 1");
            $is_exempted = $exempt_qry && $exempt_qry->num_rows > 0;
            
            if ($designation_match && $rank_match && !$is_exempted) {
                $faculty_targets[] = $task;
            }
        }
    }
}

$position_list = $conn->query("SELECT * FROM position_list ORDER BY id ASC");
$designation_list = $conn->query("SELECT * FROM designation_list ORDER BY designation ASC");

$strategic_targets = array_filter($faculty_targets, function($t) { return strtolower($t['category'] ?? '') == 'strategic'; });
$core_targets = array_filter($faculty_targets, function($t) { return strtolower($t['category'] ?? '') == 'core'; });
$support_targets = array_filter($faculty_targets, function($t) { return strtolower($t['category'] ?? '') == 'support'; });

$total_alloc = ($allocations['strategic'] ?? 0) + ($allocations['core_total'] ?? 0) + ($allocations['support'] ?? 0);
?>

<div class="col-lg-12">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" onclick="printEvaluation()">Print Evaluation</button>
    </div>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h5 class="card-title mb-0"><b>Sample Evaluation</b></h5>
        </div>

        <div class="card-body">
            <form method="POST" action="" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <label><b>Academic Rank:</b></label>
                        <select name="position_id" class="form-control" required>
                            <option value="">-- Select Academic Rank --</option>
                            <?php while($p = $position_list->fetch_assoc()): ?>
                            <option value="<?php echo $p['id'] ?>" <?php echo $selected_position_id == $p['id'] ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($p['position']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label><b>Designation:</b></label>
                        <select name="designation" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option value="no" <?php echo $selected_designation == 'no' ? 'selected' : '' ?>>Without Designation</option>
                            <option value="yes" <?php echo $selected_designation == 'yes' ? 'selected' : '' ?>>With Designation</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">View Evaluation</button>
                    </div>
                </div>
            </form>

            <?php if ($position_info): ?>
            
            <div class="alert alert-info">
                <b>Faculty Configuration:</b><br>
                Academic Rank: <?php echo htmlspecialchars($position_info['position']) ?> (ID: <?php echo $position_id ?>)<br>
                Status: <?php echo $isDesignated ? '<span class="badge badge-success">WITH DESIGNATION</span>' : '<span class="badge badge-secondary">WITHOUT DESIGNATION</span>' ?>
            </div>

            <table class="table table-bordered table-sm" id="list">
                <thead class="bg-dark text-white text-center">
                    <tr>
                        <th width="20%">MAJOR FINAL OUTPUT</th>
                        <th width="35%">SUCCESS INDICATORS (TARGETS + MEASURES)</th>
                        <th class="text-center">E</th>
                        <th class="text-center">T</th>
                        <th class="text-center">Q</th>
                        <th class="text-center">AVE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allocations['strategic'] > 0): ?>
                    <tr><td colspan="6"><b>STRATEGIC FUNCTIONS (<?php echo $allocations['strategic']; ?>%)</b></td></tr>
                    <?php if (count($strategic_targets) > 0): ?>
                    <?php $str_row_count = count($strategic_targets); ?>
                    <?php foreach($strategic_targets as $idx => $task): ?>
                    <tr>
                        <?php if ($idx == 0): ?>
                        <td rowspan="<?php echo $str_row_count; ?>" class="align-middle font-weight-bold text-center">
                            Strategic Functions
                        </td>
                        <?php endif; ?>
                        <td>
                            <div><?php echo htmlspecialchars($task['success_indicators']) ?></div>
                            <small class="text-muted"><i><?php echo htmlspecialchars($task['targets_measures']) ?></i></small>
                        </td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="5" class="text-muted text-center">No strategic targets assigned</td></tr>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($allocations['core_total'] > 0): ?>
                    <tr><td colspan="6"><b>CORE FUNCTIONS (<?php echo $allocations['core_total']; ?>%)</b></td></tr>

                    <?php if ($allocations['core_instructions'] > 0): ?>
                    <tr>
                        <td rowspan="4" class="align-middle font-weight-bold text-center">
                            MFO 1. Higher Education<br>MFO 2. Advanced Education
                        </td>
                        <td colspan="5"><b>A. INSTRUCTION (<?php echo $allocations['core_instructions']; ?>% of Core)</b></td>
                    </tr>
                    
                    <?php if ($allocations['core_ter'] > 0): ?>
                    <tr><td colspan="5"><b>&nbsp;&nbsp;&nbsp;&nbsp;A1. Teaching Effectiveness Rating (<?php echo $allocations['core_ter']; ?>% of Instruction)</b></td></tr>
                    <?php endif; ?>
                    
                    <?php if ($allocations['core_instruction'] > 0): ?>
                    <tr><td colspan="5"><b>&nbsp;&nbsp;&nbsp;&nbsp;A2. Instruction (<?php echo $allocations['core_instruction']; ?>% of Instruction)</b></td></tr>
                    <?php endif; ?>
                    
                    <tr><td colspan="5" class="text-muted"><em>&nbsp;&nbsp;&nbsp;&nbsp;(No targets assigned)</em></td></tr>
                    <?php endif; ?>

                    <?php if ($allocations['core_research'] > 0): ?>
                    <tr>
                        <td rowspan="2" class="align-middle font-weight-bold text-center">
                            MFO 3. Research and Development
                        </td>
                        <td colspan="5"><b>B. RESEARCH (<?php echo $allocations['core_research']; ?>% of Core)</b></td>
                    </tr>
                    <tr><td colspan="5" class="text-muted"><em>(No targets assigned)</em></td></tr>
                    <?php endif; ?>

                    <?php if ($allocations['core_extension'] > 0): ?>
                    <tr>
                        <td rowspan="2" class="align-middle font-weight-bold text-center">
                            MFO 4. Extension Services
                        </td>
                        <td colspan="5"><b>C. EXTENSION (<?php echo $allocations['core_extension']; ?>% of Core)</b></td>
                    </tr>
                    <tr><td colspan="5" class="text-muted"><em>(No targets assigned)</em></td></tr>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($allocations['support'] > 0): ?>
                    <tr><td colspan="6"><b>SUPPORT FUNCTIONS (<?php echo $allocations['support']; ?>%)</b></td></tr>
                    <tr><td colspan="5"><b>Submission of Statutory Requirements</b></td></tr>
                    <?php endif; ?>

                    <?php if (count($faculty_targets) == 0 && $allocations['strategic'] == 0 && $allocations['core_total'] == 0 && $allocations['support'] == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No allocation configured for this selection.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="small text-muted mt-2"><b>E</b> = Efficiency | <b>T</b> = Timeliness | <b>Q</b> = Quality</p>

            <div class="row mt-4">
                <div class="col-md-8">
                    <?php
                    $strategic_portion = $allocations['strategic'] / 100;
                    $core_portion = $allocations['core_total'] / 100;
                    $support_portion = $allocations['support'] / 100;
                    
                    $instr_portion = ($allocations['core_total'] > 0) ? ($allocations['core_instructions'] / 100) * $core_portion : 0;
                    $res_portion = ($allocations['core_total'] > 0) ? ($allocations['core_research'] / 100) * $core_portion : 0;
                    $ext_portion = ($allocations['core_total'] > 0) ? ($allocations['core_extension'] / 100) * $core_portion : 0;
                    ?>
                    <table class="table table-bordered text-center align-middle">
                        <thead>
                            <tr><th colspan="4" class="bg-dark text-white">OVER-ALL RATING</th></tr>
                            <tr>
                                <th>Component</th>
                                <th>Average Rating</th>
                                <th>Portion</th>
                                <th>Adjectival Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($allocations['strategic'] > 0): ?>
                            <tr>
                                <td class="text-left"><b>Strategic Functions</b></td>
                                <td>-</td>
                                <td><?php echo $allocations['strategic']; ?>%</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="text-left"><b>Core Functions</b></td>
                                <td>-</td>
                                <td><?php echo $allocations['core_total']; ?>%</td>
                                <td>-</td>
                            </tr>
                            <?php if ($allocations['core_instructions'] > 0): ?>
                            <tr>
                                <td class="text-left" style="padding-left:30px;">&nbsp;&nbsp;&nbsp;Instruction</td>
                                <td>-</td>
                                <td><?php echo $allocations['core_instructions']; ?>%</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($allocations['core_research'] > 0): ?>
                            <tr>
                                <td class="text-left" style="padding-left:30px;">&nbsp;&nbsp;&nbsp;Research</td>
                                <td>-</td>
                                <td><?php echo $allocations['core_research']; ?>%</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($allocations['core_extension'] > 0): ?>
                            <tr>
                                <td class="text-left" style="padding-left:30px;">&nbsp;&nbsp;&nbsp;Extension</td>
                                <td>-</td>
                                <td><?php echo $allocations['core_extension']; ?>%</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($allocations['support'] > 0): ?>
                            <tr>
                                <td class="text-left"><b>Support Functions</b></td>
                                <td>-</td>
                                <td><?php echo $allocations['support']; ?>%</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            <tr style="font-weight:bold;">
                                <td class="text-right">TOTAL</td>
                                <td>-</td>
                                <td><?php echo $total_alloc; ?>%</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-4">
                    <table class="table table-bordered text-center mb-3">
                        <thead><tr><th colspan="2" class="bg-dark text-white">RATING EQUIVALENT</th></tr></thead>
                        <tbody>
                            <tr><td>4.75 - 5.00</td><td><b>OUTSTANDING</b></td></tr>
                            <tr><td>3.61 - 4.74</td><td><b>VERY SATISFACTORY</b></td></tr>
                            <tr><td>2.61 - 3.30</td><td><b>SATISFACTORY</b></td></tr>
                            <tr><td>1.61 - 2.60</td><td><b>UNSATISFACTORY</b></td></tr>
                            <tr><td>1.60 below</td><td><b>POOR</b></td></tr>
                        </tbody>
                    </table>

                    <table class="table table-bordered text-center">
                        <thead><tr><th>FINAL RATING</th><th>ADJECTIVAL RATING</th></tr></thead>
                        <tbody>
                            <tr style="font-weight:bold;">
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <tr>
                    <td style="border:1px solid #000; height:120px; vertical-align:top; padding:10px;">
                        <b>Rater's comments and recommendations for development purposes or rewards/promotion:</b>
                        <br><br>
                        <i>No comment yet.</i>
                    </td>
                </tr>
            </table>

            <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fa fa-file-alt fa-4x mb-3"></i>
                <p>Please select an academic rank and designation status to view the sample evaluation.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function printEvaluation() {
    var printContent = document.querySelector('.col-lg-12').innerHTML;
    var printWindow = window.open('', '', 'height=900,width=1200');
    printWindow.document.write('<html><head><title>Sample Evaluation</title>');
    printWindow.document.write(`
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <style>
            @page { size: A4 landscape; margin: 1cm; }
            body { padding: 10px; font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 2px; vertical-align: middle; }
            th { background-color: #f8f8f8; }
            .btn, .no-print { display: none !important; }
        </style>
    `);
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>
