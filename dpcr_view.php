<?php
/**
 * DPCR View Page — Print Preview + PDF Export
 * Accessible by Dean (login_type=1) and Admin (login_type=2)
 */
include 'db_connect.php';
require_once 'dpcr_generator.php';

if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$login_type = $_SESSION['login_type'] ?? -1;

// Only Dean and Admin can view DPCR
if ($login_type != 1 && $login_type != 2) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Only Deans and Administrators can view DPCR.</div></div>';
    return;
}

// If evaluator, restrict Program Head/Dept Head from DPCR
if ($login_type == 1) {
    require_once 'auth_helper.php';
    if (!is_dean($conn)) {
        echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Only Deans can view DPCR forms.</div></div>';
        return;
    }
}

// Get available DP periods
$dp_periods = [];
$dp_qry = $conn->query("
    SELECT rp.id, rp.semester, rp.year, rp.start_date, rp.end_date
    FROM rating_period rp
    WHERE rp.period_type = 'DP'
    ORDER BY rp.year DESC, FIELD(rp.semester, '1st Semester', 'Summer', '2nd Semester') DESC
");
while ($row = $dp_qry->fetch_assoc()) {
    $dp_periods[] = $row;
}

// Get departments that have DP data
$departments = [];
$dept_qry = $conn->query("
    SELECT DISTINCT d.id, d.department
    FROM department_list d
    INNER JOIN cascading_ratings cr ON d.id = cr.department_id
    WHERE cr.level = 'DP'
    ORDER BY d.department
");
while ($row = $dept_qry->fetch_assoc()) {
    $departments[] = $row;
}

// Default selections
$selected_dept_id = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : ($departments[0]['id'] ?? 0);
$selected_period_id = isset($_GET['period_id']) ? intval($_GET['period_id']) : ($dp_periods[0]['id'] ?? 0);

// Generate DPCR
$dpcr_html = '';
$has_data = false;
if ($selected_dept_id > 0 && $selected_period_id > 0) {
    $generator = new DPCRGenerator();
    $dpcr_html = $generator->generateDPCR($selected_dept_id, $selected_period_id);
    $has_data = (strpos($dpcr_html, 'PART I: EQUIVALENT WEIGHT OF FUNCTIONS') !== false);
}

// Get selected names for display
$dept_name = '';
$period_label = '';
foreach ($departments as $d) { if ($d['id'] == $selected_dept_id) { $dept_name = $d['department']; break; } }
foreach ($dp_periods as $p) { if ($p['id'] == $selected_period_id) { $period_label = $p['semester'] . ' ' . $p['year']; break; } }
?>

<div class="col-lg-12">
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-building"></i> DPCR — Department Performance Commitment and Review
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Controls -->
            <div class="row mb-3 no-print">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department</label>
                        <select id="dept_select" class="form-control" onchange="updateView()">
                            <?php foreach ($departments as $d): 
                                $sel = ($d['id'] == $selected_dept_id) ? 'selected' : '';
                            ?>
                            <option value="<?= $d['id'] ?>" <?= $sel ?>><?= htmlspecialchars($d['department']) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($departments)): ?>
                            <option value="">No DP data available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rating Period</label>
                        <select id="period_select" class="form-control" onchange="updateView()">
                            <?php foreach ($dp_periods as $p): 
                                $sel = ($p['id'] == $selected_period_id) ? 'selected' : '';
                                $range = '';
                                if ($p['start_date'] || $p['end_date']) {
                                    $range = ' (' . ($p['start_date'] ? date('M Y', strtotime($p['start_date'])) : '?') . ' – ' . ($p['end_date'] ? date('M Y', strtotime($p['end_date'])) : '?') . ')';
                                }
                            ?>
                            <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['semester'] . ' ' . $p['year'] . $range) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($dp_periods)): ?>
                            <option value="">No periods available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="btn-group w-100">
                        <button class="btn btn-success" onclick="printDPCR()">
                            <i class="fa fa-print"></i> Print Preview
                        </button>
                        <button class="btn btn-danger" onclick="exportPDF()">
                            <i class="fa fa-file-pdf"></i> Download PDF
                        </button>
                        <button class="btn btn-primary" onclick="exportExcel()">
                            <i class="fa fa-file-excel"></i> Download Excel
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- DPCR Content -->
            <div id="dpcr_content" style="background:#fff; padding:10px; border:1px solid #ddd;">
                <?php if ($selected_dept_id > 0 && $selected_period_id > 0): ?>
                    <?php if ($has_data): ?>
                        <?= $dpcr_html ?>
                    <?php else: ?>
                        <div class="alert alert-warning text-center py-5">
                            <i class="fa fa-exclamation-triangle fa-3x"></i>
                            <h5 class="mt-3">No DPCR data found</h5>
                            <p><?= htmlspecialchars($dept_name) ?> has no cascaded DP data for <strong><?= htmlspecialchars($period_label) ?></strong>.</p>
                            <p class="text-muted">Run "Compute Now" on the <a href="index.php?page=rating_period">Rating Period</a> page first.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-building fa-4x"></i>
                        <h5 class="mt-3">Select a department and rating period to view the DPCR form</h5>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #dpcr_content, #dpcr_content * { visibility: visible; }
        #dpcr_content { 
            position: absolute; left: 0; top: 0; 
            width: 100%; border: none !important; padding: 0 !important; 
        }
        .no-print, .card-header, .main-header, .main-sidebar, .main-footer { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; border: none !important; }
        .content-wrapper { margin-left: 0 !important; }
    }
</style>

<script>
function updateView() {
    var dept = document.getElementById('dept_select').value;
    var period = document.getElementById('period_select').value;
    if (dept && period) {
        window.location.href = 'index.php?page=dpcr_view&dept_id=' + dept + '&period_id=' + period;
    }
}

function printDPCR() {
    window.print();
}

function exportPDF() {
    var dept = <?= $selected_dept_id ?>;
    var period = <?= $selected_period_id ?>;
    if (!dept || !period) {
        alert_toast('Please select a department and period first.', 'warning');
        return;
    }
    window.open('ajax.php?action=export_dpcr_pdf&dept_id=' + dept + '&period_id=' + period, '_blank');
}

function exportExcel() {
    var dept = <?= $selected_dept_id ?>;
    var period = <?= $selected_period_id ?>;
    if (!dept || !period) {
        alert_toast('Please select a department and period first.', 'warning');
        return;
    }
    window.open('ajax.php?action=export_dpcr_excel&dept_id=' + dept + '&period_id=' + period, '_blank');
}
</script>
