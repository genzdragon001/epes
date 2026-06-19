<?php 
session_start();
include 'db_connect.php';

$faculty_id = $_SESSION['login_id'];
$period = $_GET['period'] ?? '';
$target_id = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0;

// Get faculty info
$faculty = $conn->query("SELECT CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) as name, 
    e.position_id, p.position, e.department_id, d.designation, e.evaluator_id
    FROM employee_list e
    LEFT JOIN position_list p ON e.position_id = p.id
    LEFT JOIN designation_list d ON e.designation_id = d.id
    WHERE e.id = $faculty_id")->fetch_assoc();

// Get evaluator info
$evaluator_name = 'N/A';
if (!empty($faculty['evaluator_id'])) {
    $evaluator = $conn->query("SELECT CONCAT(lastname, ', ', firstname, ' ', middlename) as name 
        FROM evaluator_list WHERE id = {$faculty['evaluator_id']}")->fetch_assoc();
    if ($evaluator) {
        $evaluator_name = $evaluator['name'];
    }
}

// Get target info
$target = null;
if ($target_id > 0) {
    $target = $conn->query("SELECT COALESCE(major_output, success_indicators) as name, 
        category, mfo, success_indicators
        FROM task_list WHERE id = $target_id")->fetch_assoc();
}

// Determine semester months and date range
$semester_months = [];
$start_month = 1;
$end_month = 12;

if (strpos($period, '1st') !== false) {
    $semester_months = ['August', 'September', 'October', 'November', 'December'];
    $semester_label = '1st Semester (August - December)';
    $start_month = 8;
    $end_month = 12;
} elseif (strpos($period, '2nd') !== false) {
    $semester_months = ['January', 'February', 'March', 'April', 'May'];
    $semester_label = '2nd Semester (January - May)';
    $start_month = 1;
    $end_month = 5;
} else {
    $semester_months = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
    $semester_label = 'Full Year';
    $start_month = 1;
    $end_month = 12;
}

// Extract year from period
$year_match = [];
preg_match('/(\d{4})/', $period, $year_match);
$year = $year_match[1] ?? date('Y');

if (strpos($period, '2nd') !== false) {
    $year = $year + 1;
}

$semester_filter = " AND MONTH(m.date_submitted) BETWEEN $start_month AND $end_month";
$semester_filter .= " AND YEAR(m.date_submitted) = $year";

// Get MOV data
$where = "WHERE m.faculty_id = $faculty_id";
if (!empty($period)) {
    $where .= " AND m.rating_period = '$period'";
}
if ($target_id > 0) {
    $where .= " AND m.target_id = $target_id";
}
$where .= $semester_filter;

$movs = $conn->query("SELECT m.*, 
    COALESCE(t.major_output, t.success_indicators) as target_name,
    t.category, t.mfo
    FROM mov_uploads m
    LEFT JOIN task_list t ON m.target_id = t.id
    $where
    ORDER BY m.date_submitted DESC");

// Calculate summary
$summary_where = "WHERE faculty_id = $faculty_id";
if (!empty($period)) {
    $summary_where .= " AND rating_period = '$period'";
}
if ($target_id > 0) {
    $summary_where .= " AND target_id = $target_id";
}

$summary = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(file_size) as total_size
    FROM mov_uploads
    $summary_where")->fetch_assoc();

$rating_calc = ($summary['verified'] * 5) + ($summary['pending'] * 3);
$efficiency_rating = $summary['total'] > 0 ? round(($rating_calc / ($summary['total'] * 5)) * 100, 2) : 0;
?>

<div class="container-fluid" style="max-height: 80vh; overflow-y: auto;">
    <div class="header text-center mb-3" style="border-bottom: 2px solid #000; padding-bottom: 10px;">
        <h4 style="margin: 5px 0; font-size: 16px; font-weight: bold;">MEANS OF VERIFICATION (MOV) SUMMARY REPORT</h4>
        <h5 style="margin: 5px 0; font-size: 13px; font-weight: normal;">Employee Performance Evaluation System</h5>
        <?php if (!empty($period)): ?>
        <h5 style="margin: 5px 0; font-size: 12px;"><?php echo htmlspecialchars($period); ?> | <?php echo $semester_label; ?></h5>
        <?php endif; ?>
    </div>
    
    <div class="faculty-info mb-3" style="border: 1px solid #000; padding: 8px; background: #f5f5f5; font-size: 11px;">
        <strong>Faculty Member:</strong> <?php echo htmlspecialchars($faculty['name']); ?><br>
        <strong>Position:</strong> <?php echo htmlspecialchars($faculty['position'] ?? 'N/A'); ?><br>
        <strong>Designation:</strong> <?php echo htmlspecialchars($faculty['designation'] ?? 'N/A'); ?><br>
        <strong>Report Generated:</strong> <?php echo date('F d, Y h:i A'); ?>
    </div>
    
    <?php if ($target): ?>
    <div class="target-info mb-3" style="border: 2px solid #000; padding: 10px; background: #fff;">
        <h6 style="margin: 0 0 10px 0; font-size: 13px; font-weight: bold;">
            <?php if ($target['category']): ?>
            [<?php echo strtoupper($target['category']); ?> - MFO-<?php echo $target['mfo']; ?>]
            <?php endif; ?>
            <?php echo htmlspecialchars($target['name']); ?>
        </h6>
        <?php if (!empty($target['success_indicators'])): ?>
        <small style="font-size: 10px;"><strong>Success Indicator:</strong> <?php echo htmlspecialchars($target['success_indicators']); ?></small>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="text-center mb-3">
        <div class="summary-box d-inline-block" style="display: inline-block; padding: 8px 15px; margin: 3px; border: 1px solid #000; background: #fff; text-align: center; min-width: 70px;">
            <h6 style="margin: 0; font-size: 16px; color: #007bff;"><?php echo $summary['total']; ?></h6>
            <p style="margin: 3px 0 0 0; font-size: 9px; font-weight: bold;">Total</p>
        </div>
        <div class="summary-box d-inline-block" style="display: inline-block; padding: 8px 15px; margin: 3px; border: 1px solid #000; background: #fff; text-align: center; min-width: 70px;">
            <h6 style="margin: 0; font-size: 16px; color: #28a745;"><?php echo $summary['verified']; ?></h6>
            <p style="margin: 3px 0 0 0; font-size: 9px; font-weight: bold;">Verified</p>
        </div>
        <div class="summary-box d-inline-block" style="display: inline-block; padding: 8px 15px; margin: 3px; border: 1px solid #000; background: #fff; text-align: center; min-width: 70px;">
            <h6 style="margin: 0; font-size: 16px; color: #ffc107;"><?php echo $summary['pending']; ?></h6>
            <p style="margin: 3px 0 0 0; font-size: 9px; font-weight: bold;">Pending</p>
        </div>
        <div class="summary-box d-inline-block" style="display: inline-block; padding: 8px 15px; margin: 3px; border: 1px solid #000; background: #fff; text-align: center; min-width: 70px;">
            <h6 style="margin: 0; font-size: 16px; color: #dc3545;"><?php echo $summary['rejected']; ?></h6>
            <p style="margin: 3px 0 0 0; font-size: 9px; font-weight: bold;">Rejected</p>
        </div>
        <div class="summary-box d-inline-block" style="display: inline-block; padding: 8px 15px; margin: 3px; border: 1px solid #000; background: #fff; text-align: center; min-width: 70px;">
            <h6 style="margin: 0; font-size: 16px; color: #17a2b8;"><?php echo $efficiency_rating; ?>%</h6>
            <p style="margin: 3px 0 0 0; font-size: 9px; font-weight: bold;">Efficiency</p>
        </div>
    </div>
    
    <h6 style="margin: 15px 0 10px 0; font-size: 13px; font-weight: bold;">MOV Submission Details</h6>
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-bordered table-striped" style="font-size: 9px; width: 100%;">
            <thead style="background: #007bff; color: white;">
                <tr>
                    <th class="text-center" style="width: 25px; padding: 4px;">#</th>
                    <th style="width: 15%; padding: 4px;">Month</th>
                    <th class="text-center" style="width: 18%; padding: 4px;">Date Submitted</th>
                    <th class="text-center" style="width: 18%; padding: 4px;">Deadline</th>
                    <th class="text-center" style="width: 10%; padding: 4px;">Rating</th>
                    <th class="text-center" style="width: 10%; padding: 4px;">✓</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $total_rating = 0;
                $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
                $report_year = $year;
                
                while ($row = $movs->fetch_assoc()): 
                    $month_from_title = null;
                    foreach ($semester_months as $m) {
                        if (stripos($row['title'], $m) !== false) {
                            $month_from_title = $m;
                            break;
                        }
                    }
                    
                    if ($month_from_title) {
                        $month = $month_from_title;
                        $month_num = array_search($month, $month_names);
                    } else {
                        $valid_date = strtotime($row['date_submitted']);
                        if ($valid_date && $valid_date > 0) {
                            $month_num = date('n', $valid_date);
                            $month = $month_names[$month_num];
                        } else {
                            $month_num = $start_month;
                            $month = $month_names[$month_num];
                        }
                    }
                    
                    $deadline_month_num = $month_num + 1;
                    $deadline_year = $report_year;
                    if ($deadline_month_num > 12) {
                        $deadline_month_num = 1;
                        $deadline_year = $report_year + 1;
                    }
                    $deadline = $month_names[$deadline_month_num] . ' 15, ' . $deadline_year;
                    
                    $valid_date = strtotime($row['date_submitted']);
                    if ($valid_date && $valid_date > 0) {
                        $date_display = date('M d, Y', $valid_date);
                        $submitted_timestamp = $valid_date;
                    } else {
                        $submitted_timestamp = mktime(0, 0, 0, $month_num, 15, $report_year);
                        $date_display = $month . ' 15, ' . $report_year;
                    }
                    
                    $deadline_timestamp = strtotime($deadline);
                    $days_diff = ($deadline_timestamp - $submitted_timestamp) / (60 * 60 * 24);
                    
                    if ($days_diff > 0) {
                        $rating = 5;
                    } elseif ($days_diff == 0) {
                        $rating = 3;
                    } else {
                        $rating = 2;
                    }
                    
                    $total_rating += $rating;
                    $in_deadline = ($days_diff >= 0) ? '✓' : '✗';
                ?>
                <tr>
                    <td class="text-center" style="padding: 4px;"><?php echo $i++; ?></td>
                    <td style="padding: 4px;"><?php echo $month; ?></td>
                    <td class="text-center" style="padding: 4px;"><?php echo $date_display; ?></td>
                    <td class="text-center" style="padding: 4px;"><?php echo $deadline; ?></td>
                    <td class="text-center" style="padding: 4px;">
                        <span style="padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; background: <?php echo $rating == 5 ? '#28a745' : ($rating == 3 ? '#ffc107' : '#fd7e14'); ?>; color: <?php echo $rating == 3 ? '#000' : '#fff'; ?>;">
                            <?php echo $rating; ?>
                        </span>
                    </td>
                    <td class="text-center" style="padding: 4px; font-size: 12px;"><?php echo $in_deadline; ?></td>
                </tr>
                <?php endwhile; ?>
                
                <?php if ($movs->num_rows > 0): ?>
                <tr style="background: #e9ecef; font-weight: bold;">
                    <td colspan="3" class="text-right" style="padding: 4px;">Average Rating:</td>
                    <td class="text-center" style="padding: 4px;"><strong><?php echo $i > 1 ? round($total_rating / ($i - 1), 2) : '0.00'; ?></strong></td>
                    <td colspan="2" style="padding: 4px;"></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 8px;">No MOVs uploaded for this period</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="signature-section mt-4" style="display: table; width: 100%;">
        <div class="signature-box" style="display: table-cell; width: 50%; text-align: center; padding: 0 10px;">
            <strong style="font-size: 10px;">Prepared by:</strong>
            <div style="margin-top: 40px; border-top: 1px solid #000; padding-top: 5px; display: inline-block; min-width: 150px;">
                <?php echo htmlspecialchars($faculty['name']); ?><br>
                <small style="font-size: 8px;">Faculty Member</small>
            </div>
        </div>
        <div class="signature-box" style="display: table-cell; width: 50%; text-align: center; padding: 0 10px;">
            <strong style="font-size: 10px;">Verified by:</strong>
            <div style="margin-top: 40px; border-top: 1px solid #000; padding-top: 5px; display: inline-block; min-width: 150px;">
                <?php echo htmlspecialchars($evaluator_name); ?><br>
                <small style="font-size: 8px;">Department Head / Evaluator</small>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-3">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
</div>

<style>
.table th, .table td {
    border: 1px solid #000 !important;
}
.summary-box:hover {
    background: #f0f0f0;
}
</style>
