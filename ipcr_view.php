<?php
/**
 * IPCR View Page — Print Preview + PDF Export
 * Access control (same as faculty_list.php):
 *   - Admin (login_type=2): sees all faculty
 *   - Dean (evaluator_role='dean'): sees all faculty
 *   - Dept Head (evaluator_role='dept_head'): sees only faculty in own department
 *   - Faculty (login_type=0, no evaluator): sees only own IPCR
 */
include 'db_connect.php';
require_once 'includes/period_builder.php';
require_once 'ipcr_generator.php';
require_once 'auth_helper.php';

// Auth check
if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$login_type = $_SESSION['login_type'] ?? -1;
$login_id   = $_SESSION['login_id'] ?? 0;
$is_admin   = ($login_type == 2);
$is_dean    = is_dean($conn);
$is_dept_head = is_program_head($conn);
$is_evaluator_flag = !empty($_SESSION['is_evaluator']);

// Determine access scope
// $can_select_faculty: admin, dean, dept_head can select faculty
// $scope_dept_id: if dept_head, restrict to own department; admin/dean = 0 (all)
$can_select_faculty = $is_admin || $is_dean || $is_dept_head || $is_evaluator_flag;
$scope_dept_id = 0;

if (!$is_admin && !$is_dean && ($is_dept_head || $is_evaluator_flag)) {
    // Dept Head / evaluator: restrict to own department
    $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $login_id);
    $stmt->execute();
    $eval_dept = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $scope_dept_id = intval($eval_dept['department_id'] ?? 0);
}

// Determine faculty_id to view
$faculty_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$can_select_faculty) {
    // Plain faculty: can only view own IPCR
    $faculty_id = $login_id;
} elseif ($scope_dept_id > 0 && $faculty_id > 0) {
    // Dept Head: verify the selected faculty is in own department
    $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $emp_dept = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (intval($emp_dept['department_id'] ?? -1) !== $scope_dept_id) {
        echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. You can only view IPCR forms of faculty in your department.</div></div>';
        return;
    }
}

// Build dropdown label=>key map from real_periods
$periods = [];
foreach ($real_periods as $rp) {
    $label = $rp['semester'] . ' ' . $rp['year'] . (!empty($rp['is_active']) ? ' (Current)' : '');
    $key = epes_period_key($rp['semester'], $rp['year']);
    $periods[$label] = $key;
}

// Determine the selected period code (canonical code for the selected period)
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
$selected_label = $selected_period ? ($selected_period['semester'] . ' ' . $selected_period['year']) : '';
$selected_key = $selected_period ? epes_period_key($selected_period['semester'], $selected_period['year']) : '';

// Get faculty info for display
$faculty_name = '';
if ($faculty_id > 0) {
    $fq = $conn->query("SELECT CONCAT(lastname, ', ', firstname, ' ', middlename) as name FROM employee_list WHERE id = $faculty_id LIMIT 1");
    if ($fq && $fq->num_rows > 0) {
        $faculty_name = $fq->fetch_assoc()['name'];
    }
}

// Generate IPCR HTML if faculty and period selected
$ipcr_html = '';
$has_data = false;
if ($faculty_id > 0 && !empty($selected_period_code)) {
    $generator = new IPCRGenerator();
    $ipcr_html = $generator->generateIPCR($faculty_id, $selected_period_code, $period_codes);
    // Check if there's actual rating data (not just the "no ratings" fallback)
    $has_data = (strpos($ipcr_html, 'OVERALL RATING') !== false);
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-file-alt"></i> IPCR Form — Print Preview & Export
            </h5>
        </div>
        <div class="card-body">

            <!-- Controls -->
            <div class="row mb-3 no-print">
                <div class="col-md-4">
                    <?php if ($can_select_faculty): ?>
                    <div class="form-group">
                        <label>Faculty Member</label>
                        <select id="faculty_select" class="form-control" onchange="updateView()">
                            <option value="">— Select Faculty —</option>
                            <?php
                            // Build faculty list based on access scope
                            if ($scope_dept_id > 0) {
                                $fac_qry = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname, ' ', middlename) as name FROM employee_list WHERE department_id = $scope_dept_id ORDER BY lastname, firstname");
                            } else {
                                $fac_qry = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname, ' ', middlename) as name FROM employee_list ORDER BY lastname, firstname");
                            }
                            while ($f = $fac_qry->fetch_assoc()):
                                $sel = ($f['id'] == $faculty_id) ? 'selected' : '';
                            ?>
                            <option value="<?= $f['id'] ?>" <?= $sel ?>><?= htmlspecialchars($f['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rating Period</label>
                        <select id="period_select" class="form-control" onchange="updateView()">
                            <?php foreach ($periods as $label => $key):
                                $sel = ($key == $selected_key) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $sel ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($periods)): ?>
                            <option value="">No periods available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="btn-group w-100">
                        <button class="btn btn-success" onclick="printIPCR()">
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

            <!-- IPCR Content -->
            <div id="ipcr_content" style="background:#fff; padding:10px; border:1px solid #ddd;">
                <?php if ($faculty_id > 0 && !empty($selected_period_code)): ?>
                    <?php if ($has_data): ?>
                        <?= $ipcr_html ?>
                    <?php else: ?>
                        <div class="alert alert-warning text-center py-5">
                            <i class="fa fa-exclamation-triangle fa-3x"></i>
                            <h5 class="mt-3">No IPCR ratings found</h5>
                            <p><?= htmlspecialchars($faculty_name) ?> has no ratings for period <strong><?= htmlspecialchars($selected_label) ?></strong>.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-file-alt fa-4x"></i>
                        <h5 class="mt-3">Select a faculty member and rating period to view the IPCR form</h5>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #ipcr_content, #ipcr_content * { visibility: visible; }
        #ipcr_content {
            position: absolute;
            left: 0; top: 0;
            width: 100%;
            border: none !important;
            padding: 0 !important;
        }
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { display: none !important; }
        .card-body { padding: 0 !important; border: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        .main-header, .main-sidebar, .main-footer { display: none !important; }
    }
</style>

<script>
function updateView() {
    var fid = document.getElementById('faculty_select') ?
              document.getElementById('faculty_select').value :
              <?= $can_select_faculty ? 0 : $login_id ?>;
    var period = document.getElementById('period_select').value;
    if (fid && period) {
        window.location.href = 'index.php?page=ipcr_view&id=' + fid + '&period=' + encodeURIComponent(period);
    }
}

function printIPCR() {
    window.print();
}

function exportPDF() {
    var fid = <?= $faculty_id ?>;
    var period = '<?= addslashes($selected_period_code) ?>';
    if (!fid || !period) {
        alert_toast('Please select a faculty member and period first.', 'warning');
        return;
    }
    window.open('ajax.php?action=export_ipcr_pdf&faculty_id=' + fid + '&period=' + encodeURIComponent(period), '_blank');
}

function exportExcel() {
    var fid = <?= $faculty_id ?>;
    var period = '<?= addslashes($selected_period_code) ?>';
    if (!fid || !period) {
        alert_toast('Please select a faculty member and period first.', 'warning');
        return;
    }
    window.open('ajax.php?action=export_ipcr_excel&faculty_id=' + fid + '&period=' + encodeURIComponent(period), '_blank');
}
</script>