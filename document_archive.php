<?php
/**
 * Document Archive — Browse and download archived IPCR/DPCR/OPCR documents
 * Accessible by Dean (1) and Admin (2)
 */
include 'db_connect.php';
require_once 'document_archive_helper.php';

if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$login_type = $_SESSION['login_type'] ?? -1;
$is_evaluator_flag = !empty($_SESSION['is_evaluator']);
if ($login_type != 1 && $login_type != 2 && !($login_type == 0 && $is_evaluator_flag)) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied.</div></div>';
    return;
}

// Determine evaluator restrictions
$eval_dept_id = 0;
$is_dean = false;
if ($login_type == 1 || ($login_type == 0 && $is_evaluator_flag)) {
    require_once 'auth_helper.php';
    $is_dean = is_dean($conn);
    if (!$is_dean) {
        $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $_SESSION['login_id']);
        $stmt->execute();
        $eval_dept = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $eval_dept_id = intval($eval_dept['department_id'] ?? 0);
    }
}

// Filters
$filter_type     = $_GET['type'] ?? '';
$filter_dept     = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;
$filter_period   = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
$filter_faculty  = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;

// If program head/dept head, force department filter to own department
if ($login_type == 1 && !$is_dean) {
    $filter_dept = $eval_dept_id;
}

// Get documents (filtered by dept for program head/dept head)
$docs = get_archived_documents($conn, $filter_type ?: null, $filter_faculty ?: null, $filter_dept ?: null, $filter_period ?: null, 100);

// Get filter options
$doc_types = ['IPCR', 'DPCR', 'OPCR'];
$departments = [];
$dq = $conn->query("SELECT id, department FROM department_list ORDER BY department");
while ($d = $dq->fetch_assoc()) $departments[] = $d;

$periods = [];
$pq = $conn->query("SELECT id, semester, year FROM rating_period ORDER BY year DESC, FIELD(semester, '1st Semester', '2nd Semester') DESC");
while ($p = $pq->fetch_assoc()) $periods[] = $p;

// Stats
$total_docs = $conn->query("SELECT COUNT(*) as c FROM performance_documents")->fetch_assoc()['c'];
$by_type = [];
$tq = $conn->query("SELECT document_type, COUNT(*) as cnt FROM performance_documents GROUP BY document_type");
while ($t = $tq->fetch_assoc()) $by_type[$t['document_type']] = $t['cnt'];
?>

<div class="col-lg-12">
    <div class="card card-outline card-navy">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-archive"></i> Document Archive — IPCR / DPCR / OPCR
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Stats -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="info-box bg-gradient-navy">
                        <span class="info-box-icon"><i class="fa fa-file-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Documents</span>
                            <span class="info-box-number"><?= $total_docs ?></span>
                        </div>
                    </div>
                </div>
                <?php foreach (['IPCR', 'DPCR', 'OPCR'] as $dt): ?>
                <div class="col-md-3">
                    <div class="info-box bg-gradient-<?= $dt == 'IPCR' ? 'primary' : ($dt == 'DPCR' ? 'warning' : 'danger') ?>">
                        <span class="info-box-icon"><i class="fa fa-<?= $dt == 'IPCR' ? 'user' : ($dt == 'DPCR' ? 'building' : 'sitemap') ?>"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= $dt ?></span>
                            <span class="info-box-number"><?= $by_type[$dt] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Document Type</label>
                        <select class="form-control" onchange="applyFilter()" id="filter_type">
                            <option value="">All Types</option>
                            <?php foreach ($doc_types as $dt): $sel = ($filter_type == $dt) ? 'selected' : ''; ?>
                            <option value="<?= $dt ?>" <?= $sel ?>><?= $dt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Department</label>
                        <select class="form-control" onchange="applyFilter()" id="filter_dept">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): $sel = ($filter_dept == $d['id']) ? 'selected' : ''; ?>
                            <option value="<?= $d['id'] ?>" <?= $sel ?>><?= htmlspecialchars($d['department']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Period</label>
                        <select class="form-control" onchange="applyFilter()" id="filter_period">
                            <option value="">All Periods</option>
                            <?php foreach ($periods as $p): $sel = ($filter_period == $p['id']) ? 'selected' : ''; ?>
                            <option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['semester'] . ' ' . $p['year']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="fa fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>
            
            <!-- Documents Table -->
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th width="8%">Type</th>
                            <th>Faculty / Department</th>
                            <th>Period</th>
                            <th class="text-center" width="8%">Size</th>
                            <th class="text-center" width="12%">Generated</th>
                            <th class="text-center" width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($docs) > 0): ?>
                        <?php $num = 1; foreach ($docs as $doc): 
                            $badge = $doc['document_type'] == 'IPCR' ? 'badge-primary' : 
                                    ($doc['document_type'] == 'DPCR' ? 'badge-warning' : 'badge-danger');
                            $label = $doc['faculty_name'] ?: ($doc['dept_name'] ?: 'Office-Wide');
                            $size_kb = round($doc['file_size'] / 1024);
                            $time = date('M d, Y h:i A', strtotime($doc['generated_at']));
                            $download_url = str_replace('\\', '/', str_replace(__DIR__, '', $doc['file_path']));
                        ?>
                        <tr>
                            <td class="text-center"><?= $num++ ?></td>
                            <td><span class="badge <?= $badge ?>"><?= $doc['document_type'] ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($label) ?></strong>
                                <?php if ($doc['document_type'] == 'IPCR' && $doc['dept_name']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($doc['dept_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($doc['rating_period_label']) ?></td>
                            <td class="text-center"><?= $size_kb ?> KB</td>
                            <td class="text-center"><small><?= $time ?></small></td>
                            <td class="text-center">
                                <a href="<?= $download_url ?>" class="btn btn-sm btn-outline-danger" download>
                                    <i class="fa fa-download"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fa fa-archive fa-2x d-block mb-2"></i>
                                No archived documents found. Generate IPCR or DPCR forms to populate this archive.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<style>
    .card-navy .card-header {
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        color: white;
    }
</style>

<script>
function applyFilter() {
    var params = [];
    var type = document.getElementById('filter_type').value;
    var dept = document.getElementById('filter_dept').value;
    var period = document.getElementById('filter_period').value;
    if (type) params.push('type=' + encodeURIComponent(type));
    if (dept) params.push('dept_id=' + dept);
    if (period) params.push('period_id=' + period);
    window.location.href = 'index.php?page=document_archive' + (params.length ? '&' + params.join('&') : '');
}

function clearFilters() {
    window.location.href = 'index.php?page=document_archive';
}
</script>
