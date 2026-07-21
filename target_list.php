<?php include 'db_connect.php' ?>
<?php
if (!function_exists('csrf_field')) { require_once __DIR__ . '/csrf_helper.php'; }
$login_type = $_SESSION['login_type'];
$faculty_id = $_SESSION['login_id'] ?? 0;

include 'includes/period_builder.php';

// Current rating period code (for new submissions — uses the active period)
$rating_period = $active_period_code;

$emp_qry = $conn->query("SELECT e.*, p.position as position_name, d.designation as designation_name 
    FROM employee_list e 
    LEFT JOIN position_list p ON e.position_id = p.id 
    LEFT JOIN designation_list d ON e.designation_id = d.id 
    WHERE e.id = $faculty_id LIMIT 1");
$emp_data = $emp_qry->fetch_assoc();
$emp_position_id = intval($emp_data['position_id'] ?? 0);
$emp_designation_id = $emp_data['designation_id'] ?? null;
$position_name = $emp_data['position_name'] ?? 'Unknown';
$is_cos = ($emp_position_id == 19);

$designations = $conn->query("SELECT * FROM designation_list ORDER BY designation ASC");
$academic_ranks = $conn->query("SELECT * FROM position_list ORDER BY position ASC");

$total_targets = $conn->query("SELECT COUNT(*) as cnt FROM task_list WHERE is_active = 1")->fetch_assoc()['cnt'];

$allocations = [];
$alloc_qry = $conn->query("SELECT * FROM percentage_allocation 
    WHERE position_id = $emp_position_id 
    AND (designation_id IS NULL OR designation_id = " . intval($emp_designation_id) . ")
    AND is_active = 1");
while ($row = $alloc_qry->fetch_assoc()) {
    $key = $row['category'];
    if ($row['sub_category']) {
        $key .= '_' . $row['sub_category'];
    }
    $allocations[$key] = floatval($row['percentage']);
}
?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-bullseye"></i> Target Management Module</h5>
            <?php if(!empty($real_periods)): ?>
            <div class="card-tools d-flex align-items-center" style="gap:8px;">
                <select id="period_selector" class="form-control form-control-sm"
                        onchange="window.location.href='index.php?page=target_list&period='+encodeURIComponent(this.value)"
                        style="width:auto; font-size:0.85rem; padding:6px 28px 6px 12px; max-width:260px;">
                    <?php foreach($real_periods as $rp):
                        $key = epes_period_key($rp['semester'], $rp['year']);
                        $sel_key = $selected_period ? epes_period_key($selected_period['semester'], $selected_period['year']) : '';
                        $opt_label = $rp['semester'] . ' ' . $rp['year'] . ($rp['is_active'] ? ' (current)' : '');
                    ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $key === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if($login_type == 2): ?>
                <a href="download_target_template.php" class="btn btn-sm btn-default btn-flat border-secondary">
                    <i class="fa fa-download"></i> CSV Template
                </a>
                <button class="btn btn-sm btn-default btn-flat border-success" id="bulk_upload_btn">
                    <i class="fa fa-file-upload"></i> Bulk Upload
                </button>
                <button class="btn btn-sm btn-default btn-flat border-primary" id="new_task">
                    <i class="fa fa-plus"></i> Add New Target
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if($login_type == 2): ?>
            <div class="row mb-2">
                <div class="col-md-8">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" id="card_search" placeholder="Search targets (success indicators, measures, category...)">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-center justify-content-end">
                    <span class="text-muted small"><span id="card_count">0</span> of <span id="card_total">0</span> targets</span>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label><small><b>Filter by Designation:</b></small></label>
                    <select class="form-control form-control-sm filter-select" id="filter_designation">
                        <option value="">All Designations</option>
                        <option value="0">Faculty</option>
                        <?php
                        $designations2 = $conn->query("SELECT * FROM designation_list WHERE id > 0 ORDER BY designation ASC");
                        while($d = $designations2->fetch_assoc()):
                        ?>
                        <option value="<?php echo $d['id'] ?>"><?php echo htmlspecialchars($d['designation']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><small><b>Filter by Academic Rank:</b></small></label>
                    <select class="form-control form-control-sm filter-select" id="filter_rank">
                        <option value="">All Academic Ranks</option>
                        <?php
                        $academic_ranks2 = $conn->query("SELECT * FROM position_list ORDER BY position ASC");
                        while($r = $academic_ranks2->fetch_assoc()):
                        ?>
                        <option value="<?php echo $r['id'] ?>"><?php echo $r['position'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><small><b>Filter by Category:</b></small></label>
                    <select class="form-control form-control-sm filter-select" id="filter_category">
                        <option value="">All Categories</option>
                        <option value="strategic">Strategic</option>
                        <option value="core">Core</option>
                        <option value="support">Support</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><small><b>Filter by Status:</b></small></label>
                    <select class="form-control form-control-sm filter-select" id="filter_status">
                        <option value="">All Status</option>
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <?php if($login_type == 0): ?>
            <div class="alert alert-secondary mb-3">
                <strong>Academic Rank:</strong> <?php echo htmlspecialchars($position_name); ?> (ID: <?php echo $emp_position_id; ?>) |
                <?php if($is_cos): ?>
                    <span class="badge badge-warning">COS Faculty</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Permanent Faculty</span>
                <?php endif; ?>
                <?php if(!empty($allocations)): ?>
                    | <small>Allocations: <?php 
                        $alloc_labels = [];
                        if (isset($allocations['strategic'])) $alloc_labels[] = 'Strategic: ' . $allocations['strategic'] . '%';
                        if (isset($allocations['core_instructions'])) $alloc_labels[] = 'Instruction: ' . $allocations['core_instructions'] . '%';
                        if (isset($allocations['core_research'])) $alloc_labels[] = 'Research: ' . $allocations['core_research'] . '%';
                        if (isset($allocations['core_extension'])) $alloc_labels[] = 'Extension: ' . $allocations['core_extension'] . '%';
                        if (isset($allocations['support'])) $alloc_labels[] = 'Support: ' . $allocations['support'] . '%';
                        echo implode(' | ', $alloc_labels);
                    ?></small>
                <?php else: ?>
                    | <span class="badge badge-danger">No allocations set</span>
                <?php endif; ?>
            </div>
            </div>
            <?php endif; ?>

            <?php
            // Shared data fetch for both faculty (table) and admin (cards)
            $i = 1;
            $where = "t.is_active = 1";
            if ($login_type == 0) {
                $where .= " AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $emp_position_id)";
                $where .= " AND " . task_designation_match($emp_designation_id);
            }
            $qry = $conn->query("SELECT t.*, 
                    COALESCE(GROUP_CONCAT(DISTINCT d.designation ORDER BY d.designation SEPARATOR ', '), '') as junction_designations,
                    r.position as rank_name 
                FROM task_list t 
                LEFT JOIN task_designations td ON td.task_id = t.id
                LEFT JOIN designation_list d ON d.id = td.designation_id
                LEFT JOIN position_list r ON t.academic_rank_id = r.id 
                WHERE $where 
                GROUP BY t.id
                ORDER BY t.category, t.sub_category, t.id");
            $tasks = $qry ? $qry->fetch_all(MYSQLI_ASSOC) : [];
            $matched_count = count($tasks);
            ?>

            <?php if($login_type == 0): ?>
            <div class="row" id="list">
                <?php foreach($tasks as $row):
                    $exempt_qry = $conn->query("SELECT COUNT(*) as cnt FROM target_exemptions WHERE task_id = {$row['id']} AND position_id = $emp_position_id");
                    $is_exempted = $exempt_qry->fetch_assoc()['cnt'] > 0;
                    if ($is_exempted) continue;

                    $progress_qry = $conn->query("SELECT * FROM task_progress
                        WHERE faculty_id = $faculty_id AND task_id = {$row['id']} $period_filter
                        ORDER BY unix_timestamp(date_created) DESC LIMIT 1");
                    $hasSubmission = $progress_qry->num_rows > 0;
                    $isVerified = false; $isNA = false; $filePath = ''; $fileType = '';
                    if ($hasSubmission) {
                    $progress_row = $progress_qry->fetch_assoc();
                    $isVerified = (isset($progress_row['progress']) && $progress_row['progress'] === 'Verified');
                    $isNA = (isset($progress_row['progress']) && $progress_row['progress'] === 'N/A');
                    $filePath = epes_real_file_path($progress_row['file_path'], $progress_row['file_type']) ?: '';
                    $fileType = $progress_row['file_type'];
                    }
                    $cat = $row['category'];
                    $cat_class = $cat == 'strategic' ? 'badge-primary' : ($cat == 'core' ? 'badge-success' : 'badge-warning');
                    $sub_cat = $row['sub_category'] ?? '';
                    $rating = [];
                    if ($row['quality'] == 'Applicable') $rating[] = 'Q';
                    if ($row['timeliness'] == 'Applicable') $rating[] = 'T';
                    if ($row['efficiency'] == 'Applicable') $rating[] = 'E';
                ?>
                <div class="col-12 mb-3 fc-card-wrap">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body fc-body">
                            <div class="fc-main">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="font-weight-bold mb-0 fc-title">
                                        <i class="fa fa-bullseye text-info mr-1"></i>
                                        <?php echo nl2br(htmlspecialchars($row['success_indicators'])) ?>
                                    </h6>
                                    <span class="badge badge-pill <?php echo $row['is_active'] == 1 ? 'badge-success' : 'badge-danger' ?> ml-2 flex-shrink-0"><?php echo $row['is_active'] == 1 ? 'Active' : 'Inactive' ?></span>
                                </div>
                                <div class="text-muted mb-2 fc-measures" style="font-size:.85rem;">
                                    <?php echo htmlspecialchars($row['targets_measures']) ?>
                                </div>
                                <div class="fc-badges">
                                    <span class="badge <?php echo $cat_class ?>"><?php echo ucfirst($cat) ?></span>
                                    <?php if(!empty($sub_cat)): ?><span class="badge badge-info"><?php echo ucfirst($sub_cat) ?></span><?php else: ?><span class="badge badge-secondary">Main</span><?php endif; ?>
                                    <?php if(!empty($rating)): ?><span class="badge badge-light border" title="Applicable rating dimensions"><i class="fa fa-star text-warning mr-1"></i><?php echo implode(' &middot; ', $rating) ?></span><?php else: ?><span class="badge badge-secondary">No Rating</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="fc-submit">
                                <?php if($hasSubmission): ?>
                                    <?php if($isNA): ?>
                                        <span class="badge badge-secondary mb-1 d-block"><i class="fa fa-minus-circle mr-1"></i> N/A</span>
                                        <button class="btn btn-outline-danger btn-sm" onclick="delete_file(<?= $row['id'] ?>, <?= $faculty_id ?>)"><i class="fa fa-trash mr-1"></i> Remove N/A</button>
                                    <?php else: ?>
                                        <span class="badge badge-<?= $isVerified ? 'info' : 'success' ?> mb-1 d-block"><i class="fa fa-<?= $isVerified ? 'check-double' : 'check' ?> mr-1"></i><?= $isVerified ? 'Verified' : 'Submitted' ?></span>
                                        <button type="button" class="btn btn-outline-primary btn-sm view-submit-file" data-file="<?= htmlspecialchars($filePath) ?>" data-filetype="<?= htmlspecialchars($fileType) ?>"><i class="fa fa-eye mr-1"></i> View</button>
                                        <?php if(!$isVerified): ?>
                                        <div class="dropdown d-inline-block mt-1">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="fcAction<?php echo $row['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-cog"></i></button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="fcAction<?php echo $row['id']; ?>">
                                                <form action="reupload_file.php" method="POST" enctype="multipart/form-data" class="px-3 py-2">
                                                    <label class="small text-muted">Re-upload:</label>
                                                    <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                                    <input type="file" name="document" class="form-control form-control-sm mb-2" required>
                                                    <button type="submit" class="btn btn-sm btn-primary btn-block">Update</button>
                                                </form>
                                                <button class="dropdown-item text-danger" onclick="delete_file(<?= $row['id'] ?>, <?= $faculty_id ?>)"><i class="fa fa-trash mr-2"></i>Delete File</button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary mb-1 d-block"><i class="fa fa-clock mr-1"></i> Not Submitted</span>
                                    <button class="btn btn-primary btn-sm submit-btn mb-1" data-task-id="<?php echo $row['id']; ?>"><i class="fa fa-upload mr-1"></i> Submit</button>
                                    <button class="btn btn-outline-secondary btn-sm na-btn" data-task-id="<?php echo $row['id']; ?>">N/A</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; // faculty card layout (login_type == 0) ?>

            <?php if($login_type == 2): // admin card layout (Option A+B) ?>
            <div class="row" id="list">
                <?php foreach($tasks as $row): ?>
                <div class="col-12 mb-3 target-card-wrap"
                     data-designation="<?php echo $row['designation_id'] ?>"
                     data-rank="<?php echo $row['academic_rank_id'] ?>"
                     data-category="<?php echo $row['category'] ?>"
                     data-subcategory="<?php echo $row['sub_category'] ?? '' ?>"
                     data-status="<?php echo $row['is_active'] ?>"
                     data-search="<?php echo htmlspecialchars(strtolower(($row['success_indicators'] ?? '') . ' ' . ($row['targets_measures'] ?? '') . ' ' . ($row['category'] ?? '') . ' ' . ($row['sub_category'] ?? '') . ' ' . ($row['junction_designations'] ?? '') . ' ' . ($row['rank_name'] ?? ''))) ?>">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="tc-main">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="font-weight-bold mb-0">
                                        <i class="fa fa-bullseye text-info mr-1"></i>
                                        <?php echo nl2br(htmlspecialchars($row['success_indicators'])) ?>
                                    </h6>
                                    <span class="badge badge-pill <?php echo $row['is_active'] == 1 ? 'badge-success' : 'badge-danger' ?> ml-2">
                                        <?php echo $row['is_active'] == 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                                <div class="text-muted mb-2" style="font-size:.85rem">
                                    <?php echo htmlspecialchars($row['targets_measures']) ?>
                                </div>
                                <div class="d-flex justify-content-start">
                                    <button type="button" class="btn btn-sm btn-info view_task mr-1" data-id="<?php echo $row['id'] ?>"><i class="fa fa-eye"></i></button>
                                    <button type="button" class="btn btn-sm btn-warning manage_exemption mr-1" data-id="<?php echo $row['id'] ?>" title="Manage Exemptions"><i class="fa fa-ban"></i></button>
                                    <button type="button" class="btn btn-sm btn-primary manage_task mr-1" data-id="<?php echo $row['id'] ?>"><i class="fa fa-edit"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger delete_task" data-id="<?php echo $row['id'] ?>"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="tc-meta">
                                <div class="tc-grid">
                                    <div><span class="k">Category</span>
                                        <span class="badge <?php echo $row['category'] == 'strategic' ? 'badge-primary' : ($row['category'] == 'core' ? 'badge-success' : 'badge-warning') ?>">
                                            <?php echo ucfirst($row['category']) ?>
                                        </span>
                                    </div>
                                    <div><span class="k">Sub-Category</span>
                                        <?php if(!empty($row['sub_category'])): ?>
                                            <span class="badge badge-info"><?php echo ucfirst($row['sub_category']) ?></span>
                                        <?php else: ?><span class="text-muted">Main</span><?php endif; ?>
                                    </div>
                                    <div><span class="k">Designation</span>
                                        <?php
                                        $desig_labels = [];
                                        $jn_q = $conn->query("SELECT DISTINCT d.designation FROM task_designations td JOIN designation_list d ON d.id = td.designation_id WHERE td.task_id = " . intval($row['id']));
                                        if ($jn_q) {
                                            while ($jn = $jn_q->fetch_assoc()) $desig_labels[trim($jn['designation'])] = true;
                                        }
                                        if (empty($desig_labels) && !empty($row['designation_id'])) {
                                            $ld = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($row['designation_id']));
                                            if ($ld && $lr = $ld->fetch_assoc()) $desig_labels[trim($lr['designation'])] = true;
                                        }
                                        echo !empty($desig_labels) ? htmlspecialchars(implode(', ', array_keys($desig_labels))) : '<span class="text-muted">All</span>';
                                        ?>
                                    </div>
                                    <div><span class="k">Academic Rank</span>
                                        <?php echo htmlspecialchars($row['rank_name']) ?: '<span class="text-muted">All</span>' ?>
                                    </div>
                                    <div><span class="k">Exemption</span>
                                        <?php
                                        $ex_q = $conn->query("SELECT COUNT(*) as cnt FROM target_exemptions WHERE task_id = {$row['id']}");
                                        $ex_n = $ex_q ? $ex_q->fetch_assoc()['cnt'] : 0;
                                        echo $ex_n > 0 ? '<span class="badge badge-warning">' . $ex_n . ' Exempted</span>' : '<span class="badge badge-secondary">None</span>';
                                        ?>
                                    </div>
                                    <div><span class="k">Rating</span>
                                        <span class="d-inline-block mr-2"><i class="fa fa-check text-success"></i> Q</span>
                                        <?php if($row['timeliness'] == 'Applicable'): ?><span class="d-inline-block mr-2"><i class="fa fa-check text-success"></i> T</span><?php endif; ?>
                                        <?php if($row['efficiency'] == 'Applicable'): ?><span class="d-inline-block"><i class="fa fa-check text-success"></i> E</span><?php endif; ?>
                                    </div>
                                    <div><span class="k">Date Created</span>
                                        <?php echo date("M d, Y", strtotime($row['date_created'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; // admin card layout ?>
        </div>
    </div>
</div>

<div class="modal fade" id="submitFileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-file mr-2"></i>View File</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" id="submitFileContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" id="submitDownloadBtn" class="btn btn-primary" download><i class="fa fa-download mr-1"></i>Download</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadSubmitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-upload mr-2"></i>Submit File</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="uploadSubmitForm" enctype="multipart/form-data">
                <input type="hidden" name="task_id" id="submitTaskId">
                <input type="hidden" name="rating_period" id="submitRatingPeriod" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="submitDocument">Select file to upload:</label>
                        <input type="file" name="document" id="submitDocument" class="form-control" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx,.ppt,.pptx">
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX, JPG, PNG, GIF, XLS, XLSX, PPT, PPTX</small>
                    </div>
                    <div class="form-group">
                        <label><b>Rating Period:</b></label>
                        <p class="form-control-plaintext" id="displayRatingPeriod">Loading...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-upload mr-1"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($login_type == 2): ?>
<div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-file-upload mr-2"></i>Bulk Upload Targets (CSV)</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="bulkUploadForm" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="fa fa-info-circle mr-1"></i>
                        Download the <a href="download_target_template.php" class="alert-link">CSV template</a>,
                        fill in one target per row, then upload it here. Rows starting with <code>#</code> are ignored.
                    </div>
                    <div class="form-group">
                        <label for="csvFileInput"><b>Select CSV file:</b></label>
                        <input type="file" name="csv_file" id="csvFileInput" class="form-control" accept=".csv" required>
                        <small class="text-muted">Required columns: category, success_indicators, targets_measures.</small>
                    </div>
                    <div id="bulkResult" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="bulkSubmitBtn"><i class="fa fa-upload mr-1"></i> Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
table p { margin: unset !important; }
table td { vertical-align: middle !important; }
.table-hover tbody tr:hover { background-color: rgba(0,123,255,.05); }
.dropdown-item.text-danger:hover { background-color: #f8d7da; color: #721c24; }
/* Admin card layout (Option A+B) — wide landscape / crosswise rectangles */
.tc-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.5rem .75rem; font-size:.82rem; }
.tc-grid .k { color:#868e96; display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; margin-bottom:.1rem; }
.target-card-wrap { width:100% !important; max-width:100%; flex:0 0 100%; }
.target-card-wrap .card { transition: box-shadow .15s; height:auto; border-radius:.5rem; }
.target-card-wrap .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12) !important; }
/* crosswise: card body lays out horizontally (content left, actions right) */
.target-card-wrap .card-body { display:flex; flex-wrap:wrap; align-items:flex-start; gap:1rem; }
.target-card-wrap .tc-main { flex:1 1 320px; min-width:260px; }
.target-card-wrap .tc-meta { flex:2 1 420px; min-width:260px; }
.target-card-wrap .card-footer { border-top:0; padding-top:0; }

/* Faculty card layout — crosswise rectangles, no horizontal scroll */
.fc-card-wrap { width:100% !important; max-width:100%; flex:0 0 100%; }
.fc-card-wrap .card { border-radius:.5rem; transition: box-shadow .15s; }
.fc-card-wrap .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12) !important; }
.fc-body { display:flex; flex-wrap:wrap; align-items:center; gap:.75rem 1rem; }
.fc-main { flex:1 1 280px; min-width:0; }
.fc-title { line-height:1.2; }
.fc-measures { overflow-wrap:anywhere; word-break:break-word; }
.fc-badges .badge { margin-right:.25rem; }
.fc-submit { flex:0 0 auto; min-width:0; display:flex; flex-direction:column; align-items:center; }
</style>

<script>
$(document).ready(function(){
    var isAdmin = <?php echo $login_type == 2 ? 'true' : 'false'; ?>;

    // Faculty: keep DataTables. Admin: card layout, filter via show/hide.
    var table = null;
    if (!isAdmin && $('#list').is('table')) {
        table = $('#list').DataTable({
            "dom": 'Bfrtip',
            "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
            "ordering": true,
            "order": [[0, 'asc']],
            "pageLength": 25
        });
    }

    // Admin: combined filter (dropdowns + text search)
    function applyAdminFilters(){
        var designation = $('#filter_designation').val();
        var rank        = $('#filter_rank').val();
        var category    = $('#filter_category').val();
        var status      = $('#filter_status').val();
        var term        = ($('#card_search').val() || '').toLowerCase().trim();
        var visible = 0;
        var total = 0;

        $('#list .target-card-wrap').each(function(){
            total++;
            var $c = $(this);
            var rd = $c.data('designation');
            var rk = $c.data('rank');
            var rc = $c.data('category');
            var rs = $c.data('status');
            var hay = ($c.data('search') || '').toString();
            var show = true;

            if (designation !== '' && designation !== null) {
                if (designation === '0') { if (rd !== null && rd !== 0 && rd !== '0') show = false; }
                else { if (String(rd) !== String(designation)) show = false; }
            }
            if (show && rank !== '' && rank !== null) { if (String(rk) !== String(rank)) show = false; }
            if (show && category !== '' && category !== null) { if (rc !== category) show = false; }
            if (show && status !== '' && status !== null) { if (String(rs) !== String(status)) show = false; }
            if (show && term !== '') { if (hay.indexOf(term) === -1) show = false; }

            $c.toggle(show);
            if (show) visible++;
        });

        $('#card_count').text(visible);
        $('#card_total').text(total);
    }

    $('.filter-select').change(function(){
        if (!isAdmin && table) {
            var designation = $('#filter_designation').val();
            var rank = $('#filter_rank').val();
            var category = $('#filter_category').val();
            var status = $('#filter_status').val();

            // Faculty table path (DataTables search plugin)
            $.fn.dataTable.ext.search.pop();
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var row = $('#list tbody tr:eq(' + dataIndex + ')');
                var rowDesignation = row.data('designation');
                var rowRank = row.data('rank');
                var rowCategory = row.data('category');
                var rowStatus = row.data('status');

                if (designation !== '' && designation !== null) {
                    if (designation === '0') {
                        if (rowDesignation !== null && rowDesignation !== 0) return false;
                    } else {
                        if (rowDesignation != designation) return false;
                    }
                }
                if (rank !== '' && rank !== null) {
                    if (rowRank != rank) return false;
                }
                if (category !== '' && category !== null) {
                    if (rowCategory !== category) return false;
                }
                if (status !== '' && status !== null) {
                    if (rowStatus != status) return false;
                }
                return true;
            });
            table.draw();
        } else {
            applyAdminFilters();
        }
    });

    // Admin: live text search (debounced)
    if (isAdmin) {
        var searchTimer = null;
        $('#card_search').on('input', function(){
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyAdminFilters, 200);
        });
        // initial count
        applyAdminFilters();
    }

    $('#new_task').click(function(){
        uni_modal("<i class='fa fa-plus'></i> New Target","manage_task.php",'mid-large')
    })
    $('.view_task').click(function(){
        uni_modal("View Target","view_task.php?id="+$(this).attr('data-id'),'mid-large')
    })
    $('.manage_task').click(function(){
        uni_modal("<i class='fa fa-edit'></i> Edit Target","manage_task.php?id="+$(this).attr('data-id'),'mid-large')
    })
});

$(document).on('click', '.delete_task', function(){
    var id = $(this).data('id'); 
    if(confirm("Are you sure you want to delete this target?")){
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_task',
            method: 'POST',
            data: {id: id},
            success: function(resp){
                if(resp == 1){
                    alert_toast("Data successfully deleted", "success");
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    alert_toast("Failed to delete target", "danger");
                    end_load();
                }
            }
        });
    }
});

// ===== Bulk CSV upload =====
$('#bulk_upload_btn').click(function(){
    $('#bulkResult').hide().empty();
    $('#csvFileInput').val('');
    $('#bulkUploadModal').modal('show');
});

$('#bulkUploadForm').submit(function(e){
    e.preventDefault();
    var fileInput = $('#csvFileInput')[0];
    if (!fileInput.files.length) {
        alert_toast('Please choose a CSV file first.', 'warning');
        return;
    }
    var formData = new FormData(this);
    var $btn = $('#bulkSubmitBtn');
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Importing...');
    $('#bulkResult').hide().empty();

    $.ajax({
        url: 'bulk_upload_targets.php',
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(resp){
            var r;
            try { r = (typeof resp === 'string') ? JSON.parse(resp) : resp; }
            catch (err) { r = null; }

            if (!r) {
                $('#bulkResult').html('<div class="alert alert-danger">Unexpected server response.</div>').show();
            } else {
                var cls = r.status === 'success' ? 'alert-success' : 'alert-danger';
                var html = '<div class="alert ' + cls + ' mb-2">' + r.message + '</div>';
                if (r.errors && r.errors.length) {
                    html += '<div class="alert alert-warning" style="max-height:220px;overflow:auto;">';
                    html += '<b>Skipped rows:</b><ul class="mb-0 pl-3">';
                    r.errors.forEach(function(er){ html += '<li><small>' + $('<div>').text(er).html() + '</small></li>'; });
                    html += '</ul></div>';
                }
                $('#bulkResult').html(html).show();
                if (r.status === 'success' && r.inserted > 0) {
                    alert_toast(r.message, 'success');
                    setTimeout(function(){ location.reload(); }, 2200);
                }
            }
            $btn.prop('disabled', false).html('<i class="fa fa-upload mr-1"></i> Upload &amp; Import');
        },
        error: function(){
            $('#bulkResult').html('<div class="alert alert-danger">Upload failed. Please try again.</div>').show();
            $btn.prop('disabled', false).html('<i class="fa fa-upload mr-1"></i> Upload &amp; Import');
        }
    });
});

function delete_file(taskId, facultyId) {
    if (!confirm("Are you sure you want to delete this file?")) return;
    start_load();
    $.ajax({
        url: 'ajax.php?action=delete_file',
        method: 'POST',
        data: { task_id: taskId, faculty_id: facultyId },
        success: function(resp) {
            if (resp == 1) {
                alert_toast("File successfully deleted", "success");
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                alert_toast("Failed to delete file", "danger");
                end_load();
            }
        }
    });
}

$(document).on('click', '.view-submit-file', function(){
    var filePath = $(this).data('file');
    var fileType = $(this).data('filetype').toLowerCase();
    var modal = $('#submitFileModal');
    var content = $('#submitFileContent');
    var downloadBtn = $('#submitDownloadBtn');
    
    content.empty();
    downloadBtn.attr('href', filePath);
    
    var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    
    if (imageExts.includes(fileType)) {
        content.html('<img src="' + filePath + '" class="img-fluid" style="max-height: 70vh;">');
    } else if (fileType === 'pdf') {
        content.html('<iframe src="' + filePath + '" style="width: 100%; height: 70vh; border: none;"></iframe>');
    } else {
        content.html('<p class="text-muted">Cannot preview this file type. Please download to view.</p>');
    }
    
    modal.modal('show');
});

$(document).on('click', '.submit-btn', function(){
    var taskId = $(this).data('task-id');
    $('#submitTaskId').val(taskId);
    $('#submitRatingPeriod').val('<?= $rating_period ?>');
    $('#displayRatingPeriod').text('<?= $rating_period ?>');
    $('#submitDocument').val('');
    $('#uploadSubmitModal').modal('show');
});

$(document).on('click', '.na-btn', function(){
    var taskId = $(this).data('task-id');
    if (!confirm('Mark this target as N/A (not applicable)?')) return;
    start_load();
    $.ajax({
        url: 'ajax.php?action=submit_na',
        method: 'POST',
        data: {
            task_id: taskId,
            rating_period: '<?= $rating_period ?>'
        },
        success: function(resp){
            try {
                var result = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (result.status === 'success') {
                    alert_toast(result.message || "Target marked as N/A.", 'success');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast(result.message || "Failed to mark as N/A.", 'danger');
                }
            } catch (e) {
                alert_toast("Failed to mark as N/A.", 'danger');
            }
            end_load();
        }
    });
});

$('#uploadSubmitForm').submit(function(e){
    e.preventDefault();
    var formData = new FormData(this);
    
    start_load();
    $.ajax({
        url: 'ajax.php?action=submit_file',
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(resp){
            try {
                var result = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (result.status === 'success') {
                    alert_toast(result.message || "File submitted successfully!", 'success');
                    $('#uploadSubmitModal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast(result.message || "Failed to submit file.", 'danger');
                }
            } catch (e) {
                alert_toast("Failed to submit file.", 'danger');
            }
            end_load();
        }
    });
});
</script>

<?php if($login_type == 2): ?>
<div class="modal fade" id="exemptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fa fa-ban mr-2"></i>Manage Exemptions</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="exempt_task_id">
                <p><b>Target:</b> <span id="exempt_task_name"></span></p>
                <hr>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label><b>Academic Rank to Exempt:</b></label>
                            <select id="exempt_position" class="form-control">
                                <option value="">-- Select Academic Rank --</option>
                                <?php 
                                $pos_qry = $conn->query("SELECT * FROM position_list ORDER BY id ASC");
                                while($p = $pos_qry->fetch_assoc()): ?>
                                <option value="<?php echo $p['id'] ?>"><?php echo htmlspecialchars($p['position']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="add_exemption">
                                <i class="fa fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
                <hr>
                <h6>Current Exemptions:</h6>
                <div id="exemption_list" class="mt-2">
                    <p class="text-muted">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('click', '.manage_exemption', function(){
    var task_id = $(this).data('id');
    $('#exempt_task_id').val(task_id);
    
    var task_row = $(this).closest('tr');
    var success_indicators = task_row.find('td:nth-child(2)').text().trim();
    $('#exempt_task_name').text(success_indicators.substring(0, 100) + (success_indicators.length > 100 ? '...' : ''));
    
    loadExemptions(task_id);
    $('#exemptionModal').modal('show');
});

function loadExemptions(task_id) {
    $.ajax({
        url: 'ajax.php?action=get_exemptions',
        method: 'POST',
        data: { task_id: task_id },
        success: function(resp) {
            try {
                var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (data.status === 'success') {
                    var html = '';
                    if (data.exemptions.length > 0) {
                        data.exemptions.forEach(function(ex) {
                            html += '<div class="alert alert-warning d-flex justify-content-between align-items-center py-2">';
                            html += '<div><strong>' + (ex.position_name || 'Unknown') + '</strong></div>';
                            html += '<button class="btn btn-sm btn-danger remove-exemption" data-exid="' + ex.id + '"><i class="fa fa-trash"></i></button>';
                            html += '</div>';
                        });
                    } else {
                        html = '<p class="text-muted">No exemptions set for this target.</p>';
                    }
                    $('#exemption_list').html(html);
                }
            } catch (e) {
                $('#exemption_list').html('<p class="text-danger">Error loading exemptions</p>');
            }
        }
    });
}

$('#add_exemption').click(function(){
    var task_id = $('#exempt_task_id').val();
    var position_id = $('#exempt_position').val();
    
    if (!position_id) {
        alert_toast("Please select an Academic Rank", "warning");
        return;
    }
    
    $.ajax({
        url: 'ajax.php?action=save_exemption',
        method: 'POST',
        data: { task_id: task_id, position_id: position_id },
        success: function(resp) {
            try {
                var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (data.status === 'success') {
                    alert_toast("Exemption added successfully", "success");
                    $('#exempt_position').val('');
                    loadExemptions(task_id);
                } else {
                    alert_toast(data.message || "Error adding exemption", "danger");
                }
            } catch (e) {
                alert_toast("Error adding exemption", "danger");
            }
        }
    });
});

$(document).on('click', '.remove-exemption', function(){
    var ex_id = $(this).data('exid');
    var task_id = $('#exempt_task_id').val();
    
    if (confirm("Remove this exemption?")) {
        $.ajax({
            url: 'ajax.php?action=delete_exemption',
            method: 'POST',
            data: { id: ex_id },
            success: function(resp) {
                try {
                    var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (data.status === 'success') {
                        alert_toast("Exemption removed", "success");
                        loadExemptions(task_id);
                    }
                } catch (e) {
                    alert_toast("Error removing exemption", "danger");
                }
            }
        });
    }
});
</script>
<?php endif; ?>
