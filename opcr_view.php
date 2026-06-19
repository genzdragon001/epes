<?php
/**
 * OPCR View Page — Print Preview + PDF Export
 * Accessible by Dean (login_type=1) and Admin (login_type=2)
 */
include 'db_connect.php';
require_once 'opcr_consolidator.php';

if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$login_type = $_SESSION['login_type'] ?? -1;

// Only Dean and Admin can view OPCR
if ($login_type != 1 && $login_type != 2) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Only Deans and Administrators can view OPCR.</div></div>';
    return;
}

// If evaluator, restrict Program Head/Dept Head from OPCR
if ($login_type == 1) {
    require_once 'auth_helper.php';
    if (!is_dean($conn)) {
        echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Only Deans can view OPCR forms.</div></div>';
        return;
    }
}

// Get available OPCR periods
$opcr_periods = [];
$opcr_qry = $conn->query("
    SELECT rp.id, rp.semester, rp.year, rp.start_date, rp.end_date
    FROM rating_period rp
    WHERE rp.period_type = 'OPCR'
    ORDER BY rp.year DESC, FIELD(rp.semester, '1st Semester', 'Summer', '2nd Semester') DESC
");
while ($row = $opcr_qry->fetch_assoc()) {
    $opcr_periods[] = $row;
}

// Default selection
$selected_period_id = isset($_GET['period_id']) ? intval($_GET['period_id']) : ($opcr_periods[0]['id'] ?? 0);

// Generate OPCR
$opcr_html = '';
$has_data = false;
if ($selected_period_id > 0) {
    $generator = new OPCRGenerator();
    $opcr_html = $generator->generateOPCR($selected_period_id);
    $has_data = (strpos($opcr_html, 'PART I: EQUIVALENT WEIGHT OF FUNCTIONS') !== false);
}

// Get selected period label
$period_label = '';
foreach ($opcr_periods as $p) {
    if ($p['id'] == $selected_period_id) {
        $period_label = $p['semester'] . ' ' . $p['year'];
        break;
    }
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-success">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-building"></i> OPCR — Office Performance Commitment and Review
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Controls -->
            <div class="row mb-3 no-print">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Rating Period</label>
                        <select id="period_select" class="form-control" onchange="updateView()">
                            <?php foreach ($opcr_periods as $p): 
                                $sel = ($p['id'] == $selected_period_id) ? 'selected' : '';
                                $range = '';
                                if ($p['start_date'] || $p['end_date']) {
                                    $range = ' (' . ($p['start_date'] ? date('M Y', strtotime($p['start_date'])) : '?') . ' – ' . ($p['end_date'] ? date('M Y', strtotime($p['end_date'])) : '?') . ')';
                                }
                            ?>
                            <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['semester'] . ' ' . $p['year'] . $range) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($opcr_periods)): ?>
                            <option value="">No periods available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="btn-group w-100">
                        <button class="btn btn-success" onclick="printOPCR()">
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
            
            <!-- OPCR Content -->
            <div id="opcr_content" style="background:#fff; padding:10px; border:1px solid #ddd;">
                <?php if ($selected_period_id > 0): ?>
                    <?php if ($has_data): ?>
                        <?= $opcr_html ?>
                    <?php else: ?>
                        <div class="alert alert-warning text-center py-5">
                            <i class="fa fa-exclamation-triangle fa-3x"></i>
                            <h5 class="mt-3">No OPCR data found</h5>
                            <p>There is no cascaded OPCR data for <strong><?= htmlspecialchars($period_label) ?></strong>.</p>
                            <p class="text-muted">Run "Compute Now" on the <a href="index.php?page=rating_period">Rating Period</a> page first.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-building fa-4x"></i>
                        <h5 class="mt-3">Select a rating period to view the OPCR form</h5>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #opcr_content, #opcr_content * { visibility: visible; }
        #opcr_content { 
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
    var period = document.getElementById('period_select').value;
    if (period) {
        window.location.href = 'index.php?page=opcr_view&period_id=' + period;
    }
}

function printOPCR() {
    window.print();
}

function exportPDF() {
    var period = <?= $selected_period_id ?>;
    if (!period) {
        alert_toast('Please select a rating period first.', 'warning');
        return;
    }
    window.open('ajax.php?action=export_opcr_pdf&period_id=' + period, '_blank');
}

function exportExcel() {
    var period = <?= $selected_period_id ?>;
    if (!period) {
        alert_toast('Please select a rating period first.', 'warning');
        return;
    }
    window.open('ajax.php?action=export_opcr_excel&period_id=' + period, '_blank');
}
</script>
