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
        category, mfo, success_indicators, targets_measures, timeliness, quality, efficiency,
        (SELECT GROUP_CONCAT(deadline ORDER BY deadline SEPARATOR '|') FROM target_deadlines WHERE target_id = $target_id) as deadlines
        FROM task_list WHERE id = $target_id")->fetch_assoc();
}

// Determine semester months and date range
$semester_months = [];
$start_month = 1;
$end_month = 12;

if (strpos($period, '1st') !== false) {
    $semester_months = ['August', 'September', 'October', 'November', 'December'];
    $semester_label = '1st Semester (August - December)';
    $start_month = 8; // August
    $end_month = 12;  // December
} elseif (strpos($period, '2nd') !== false) {
    $semester_months = ['January', 'February', 'March', 'April', 'May'];
    $semester_label = '2nd Semester (January - May)';
    $start_month = 1; // January
    $end_month = 5;   // May
} else {
    $semester_months = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
    $semester_label = 'Full Year';
    $start_month = 1;
    $end_month = 12;
}

// Extract year from period (e.g., "1st Semester 2025-2026" -> 2025)
$year_match = [];
preg_match('/(\d{4})/', $period, $year_match);
$year = $year_match[1] ?? date('Y');

// For 2nd semester, use second year
if (strpos($period, '2nd') !== false) {
    $year = $year + 1;
}

// Build semester date filter
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
// Add semester date range filter
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

// Determine semester months and date range
$semester_months = [];
$start_month = 1;
$end_month = 12;

if (strpos($period, '1st') !== false || strpos($period, '1st') !== false) {
    $semester_months = ['August', 'September', 'October', 'November', 'December'];
    $semester_label = '1st Semester (August - December)';
    $start_month = 8; // August
    $end_month = 12;  // December
} elseif (strpos($period, '2nd') !== false || strpos($period, '2nd') !== false) {
    $semester_months = ['January', 'February', 'March', 'April', 'May'];
    $semester_label = '2nd Semester (January - May)';
    $start_month = 1; // January
    $end_month = 5;   // May
} else {
    $semester_months = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
    $semester_label = 'Full Year';
    $start_month = 1;
    $end_month = 12;
}

// Extract year from period (e.g., "1st Semester 2025-2026" -> 2025)
$year_match = [];
preg_match('/(\d{4})/', $period, $year_match);
$year = $year_match[1] ?? date('Y');

// Filter MOVs by semester date range
$semester_filter = " AND MONTH(m.date_submitted) BETWEEN $start_month AND $end_month";
$semester_filter .= " AND YEAR(m.date_submitted) = $year";

// Rating calculation based on timeliness
function calculateRating($date_submitted, $deadline) {
    if (empty($date_submitted)) return 1; // No submission
    
    $submitted = strtotime($date_submitted);
    $due = strtotime($deadline);
    
    $days_diff = ($due - $submitted) / (60 * 60 * 24);
    
    if ($days_diff > 0) {
        return 5; // Before deadline
    } elseif ($days_diff == 0) {
        return 3; // On deadline
    } else {
        return 2; // Beyond deadline
    }
}

$rating_calc = ($summary['verified'] * 5) + ($summary['pending'] * 3);
$efficiency_rating = $summary['total'] > 0 ? round(($rating_calc / ($summary['total'] * 5)) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>MOV Summary Report</title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0.5in; }
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            font-size: 11px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 { margin: 5px 0; font-size: 16px; font-weight: bold; }
        .header h3 { margin: 5px 0; font-size: 13px; font-weight: normal; }
        .faculty-info { 
            margin-bottom: 15px; 
            border: 1px solid #000;
            padding: 8px;
            background: #f5f5f5;
        }
        .target-info {
            margin-bottom: 15px;
            border: 2px solid #000;
            padding: 10px;
            background: #fff;
        }
        .target-info h3 { margin: 0 0 10px 0; font-size: 14px; font-weight: bold; }
        .summary-box { 
            display: inline-block; 
            padding: 8px 15px; 
            margin: 3px; 
            border: 1px solid #000; 
            background: #fff;
            text-align: center;
            min-width: 80px;
        }
        .summary-box h3 { margin: 0; font-size: 18px; color: #007bff; }
        .summary-box p { margin: 3px 0 0 0; font-size: 10px; font-weight: bold; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
            font-size: 10px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: left; 
        }
        th { 
            background: #007bff; 
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) { background: #f8f9fa; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row { 
            background: #e9ecef; 
            font-weight: bold;
        }
        .rating-5 { background: #28a745; color: white; }
        .rating-4 { background: #5cb85c; color: white; }
        .rating-3 { background: #ffc107; color: black; }
        .rating-2 { background: #fd7e14; color: white; }
        .rating-1 { background: #dc3545; color: white; }
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 200px;
        }
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .rating-legend {
            margin-top: 15px;
            font-size: 9px;
            border: 1px solid #000;
            padding: 8px;
            background: #f8f9fa;
        }
        .rating-legend h4 { margin: 0 0 5px 0; font-size: 11px; }
        .rating-legend p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>MEANS OF VERIFICATION (MOV) SUMMARY REPORT</h2>
        <h3>Employee Performance Evaluation System</h3>
        <?php if (!empty($period)): ?>
        <h3><?php echo htmlspecialchars($period); ?> 
        <?php endif; ?>
    </div>
    
    <div class="faculty-info">
        <strong>Faculty Member:</strong> <?php echo htmlspecialchars($faculty['name']); ?><br>
        <strong>Position:</strong> <?php echo htmlspecialchars($faculty['position'] ?? 'N/A'); ?><br>
        <strong>Designation:</strong> <?php echo htmlspecialchars($faculty['designation'] ?? 'N/A'); ?><br>
        <strong>Report Generated:</strong> <?php echo date('F d, Y h:i A'); ?>
    </div>
    
    <?php if ($target): ?>
    <div class="target-info">
        <h3>
            <?php if ($target['category']): ?>
           
            <?php endif; ?>
            <?php echo htmlspecialchars($target['name']); ?>
        </h3>
        <?php if (!empty($target['success_indicators'])): ?>
        <small><strong>Success Indicator:</strong> <?php echo htmlspecialchars($target['targets_measures']); ?></small>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    
    
    
    <?php 
    // Check if target has applicable efficiency, quality, timeliness
    $has_timeliness = ($target && isset($target['timeliness']) && strtolower($target['timeliness']) === 'applicable');
    $has_quality = ($target && isset($target['quality']) && strtolower($target['quality']) === 'applicable');
    $has_efficiency = ($target && isset($target['efficiency']) && strtolower($target['efficiency']) === 'applicable');
    
     ?>
    
    
    <?php if ($has_efficiency): ?>
    <div class="efficiency-section mb-4">
       
       
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 10px;">#</th>
                    <th>Activity Title</th>
                    <th>Conducted Activity</th>
                    <th>Percentage of Attendance</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $efficiency_query = $conn->query("SELECT * FROM efficiency_attendance WHERE target_id = $target_id AND faculty_id = $faculty_id AND rating_period = '$period' ORDER BY id");
                $total_rating = 0;
                $total_percentage = 0;
                $count = 0;
                while ($eff = $efficiency_query->fetch_assoc()): 
                    $count++;
                    $total_rating += $eff['rating'];
                    $total_percentage += floatval($eff['percentage']);
                ?>
                <tr>
                    <td class="text-center"><?php echo $count; ?></td>
                    <td><?php echo htmlspecialchars($eff['activity_title']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($eff['date_conducted'])); ?></td>
                    <td class="text-center"><?php echo $eff['percentage']; ?>%</td>
                    <td class="text-center">
                        <span class="rating-<?php echo $eff['rating']; ?>" style="padding: 3px 8px; border-radius: 3px; font-weight: bold;">
                            <?php echo $eff['rating']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if ($count > 0): ?>
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>Total Rating</strong></td>
                    <td class="text-center"><strong><?php echo round($total_percentage / $count); ?>%</strong></td>
                    <td class="text-center"><strong><?php echo round($total_rating / $count, 2); ?></strong></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No efficiency attendance records</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="rating-legend mt-2">
            <strong>Efficiency Rating Scale:</strong>
            <p style="margin: 5px 0;">5 – 100% attendance</p>
            <p style="margin: 5px 0;">4 – 75-99%</p>
            <p style="margin: 5px 0;">3 – 63-74%</p>
            <p style="margin: 5px 0;">2 – 51-79%</p>
            <p style="margin: 5px 0;">1 – 60% and below</p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($has_timeliness): ?>
    <div class="timeliness-section mb-4">
        <h4>Timeliness Report</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center" style="width: 10px;">#</th>
                    <th style="width: 20%;">MOV Title</th>
                    <th class="text-center" style="width: 25%;">Date Submitted</th>
                    <th class="text-center" style="width: 25%;">Deadline</th>
                    <th class="text-center" style="width: 10%;">Rating</th>
                    <th class="text-center" style="width: 10%;">In Deadline</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $total_rating = 0;
                $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
                
                $year_match = [];
                preg_match('/(\d{4})/', $period, $year_match);
                $report_year = $year_match[1] ?? date('Y');
                
                if (strpos($period, '2nd') !== false) {
                    $report_year = $report_year + 1;
                }
                
                $movs_copy = $conn->query("SELECT m.*, 
                    COALESCE(t.major_output, t.success_indicators) as target_name,
                    t.category, t.mfo
                    FROM mov_uploads m
                    LEFT JOIN task_list t ON m.target_id = t.id
                    WHERE m.faculty_id = $faculty_id AND m.rating_period = '$period' AND m.target_id = $target_id
                    ORDER BY m.date_submitted DESC");
                
                while ($row = $movs_copy->fetch_assoc()): 
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
                    
                    $deadline_display = 'No deadline set';
                    $deadline_timestamp = null;
                    if ($target && isset($target['deadlines']) && !empty($target['deadlines'])) {
                        $deadlines = explode('|', $target['deadlines']);
                        if (isset($deadlines[$i - 1]) && !empty($deadlines[$i - 1])) {
                            $deadline_display = date('M d, Y', strtotime($deadlines[$i - 1]));
                            $deadline_timestamp = strtotime($deadlines[$i - 1]);
                        }
                    }
                    
                    $valid_date = strtotime($row['date_submitted']);
                    if ($valid_date && $valid_date > 0) {
                        $date_display = date('M d, Y', $valid_date);
                        $submitted_timestamp = $valid_date;
                    } else {
                        $submitted_timestamp = mktime(0, 0, 0, $month_num, 15, $report_year);
                        $date_display = $month . ' 15, ' . $report_year;
                    }
                    
                    $days_diff = $deadline_timestamp ? ($deadline_timestamp - $submitted_timestamp) / (60 * 60 * 24) : 0;
                    
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
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo $date_display; ?></td>
                    <td class="text-center"><?php echo $deadline_display; ?></td>
                    <td class="text-center">
                        <span class="rating-<?php echo $rating; ?>" style="padding: 3px 8px; border-radius: 3px; font-weight: bold;">
                            <?php echo $rating; ?>
                        </span>
                    </td>
                    <td class="text-center" style="font-size: 16px;"><?php echo $in_deadline; ?></td>
                </tr>
                <?php endwhile; ?>
                
                <?php if ($i > 1): ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right">Average Rating:</td>
                    <td class="text-center"><strong><?php echo round($total_rating / ($i - 1), 2); ?></strong></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No MOVs uploaded for this period</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="rating-legend mt-2">
            <strong>Timeliness Rating Scale:</strong>
            <p style="margin: 5px 0;"><span class="rating-5" style="padding: 2px 6px;">5</span> - Before the deadline</p>
            <p style="margin: 5px 0;"><span class="rating-3" style="padding: 2px 6px;">3</span> - On the deadline</p>
            <p style="margin: 5px 0;"><span class="rating-2" style="padding: 2px 6px;">2</span> - Beyond the deadline</p>
            <p style="margin-top: 5px;"><strong>Note:</strong> Deadline is every 15th of the ensuing month</p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="signature-section">
        <div class="signature-box" style="display: table-cell; width: 50%;">
            <strong>Prepared by:</strong>
            <br />
            <div class="signature-line">
                <?php echo htmlspecialchars($faculty['name']); ?><br>
                <small>Faculty Member</small>
            </div>
        </div>
        <div class="signature-box" style="display: table-cell; width: 50%;">
            <strong>Verified by:</strong>
             <br />
            <div class="signature-line">
                <?php echo htmlspecialchars($evaluator_name); ?><br>
                <small>Department Head / Evaluator</small>
            </div>
        </div>
    </div>
    
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 25px; font-size: 14px; cursor: pointer; margin: 5px;">
            🖨️ Print Report
        </button>
        <button onclick="window.close()" style="padding: 8px 25px; font-size: 14px; cursor: pointer; margin: 5px;">
            ✕ Close
        </button>
    </div>
</body>
</html>
