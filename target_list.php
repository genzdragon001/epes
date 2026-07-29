<?php include 'db_connect.php' ?>
<?php
if (!function_exists('csrf_field')) { require_once __DIR__ . '/csrf_helper.php'; }
$login_type = $_SESSION['login_type'];
$faculty_id = $_SESSION['login_id'] ?? 0;

include 'includes/period_builder.php';

// Rating period code for new submissions — uses the SELECTED period
// (not necessarily the active one) so uploads are saved against the
// period the faculty is currently viewing.
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
if (empty($selected_period_code)) $selected_period_code = $active_period_code;
$rating_period = $selected_period_code;

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

// Build allocation label string for info strip
$alloc_labels = [];
if (isset($allocations['strategic'])) $alloc_labels[] = 'Strategic ' . $allocations['strategic'] . '%';
if (isset($allocations['core_total'])) $alloc_labels[] = 'Core ' . $allocations['core_total'] . '%';
if (isset($allocations['core_research'])) $alloc_labels[] = 'Research ' . $allocations['core_research'] . '%';
if (isset($allocations['core_extension'])) $alloc_labels[] = 'Extension ' . $allocations['core_extension'] . '%';
if (isset($allocations['support'])) $alloc_labels[] = 'Support ' . $allocations['support'] . '%';
if (empty($alloc_labels) && isset($allocations['core_instructions'])) $alloc_labels[] = 'Core ' . ($allocations['core_total'] ?? 90) . '%';
$alloc_text = implode(' · ', $alloc_labels);
?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-bullseye"></i> Target Management</h5>
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

            <?php if($login_type == 0): ?>
            <!-- Faculty info strip -->
            <div class="info-strip mb-3">
                <div class="info-item"><i class="fa fa-user-graduate text-info"></i> <b><?= htmlspecialchars($position_name) ?></b> (<?= $is_cos ? 'COS' : 'Permanent' ?>)</div>
                <?php if(!empty($alloc_text)): ?>
                <div class="info-item"><i class="fa fa-percentage text-info"></i> <?= htmlspecialchars($alloc_text) ?></div>
                <?php else: ?>
                <div class="info-item"><span class="badge badge-danger">No allocations set</span></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if($login_type == 2): ?>
            <!-- Admin info strip -->
            <div class="info-strip mb-3">
                <div class="info-item"><i class="fa fa-bullseye text-info"></i> <b><?= $total_targets ?></b> total targets</div>
            </div>
            <?php endif; ?>

            <?php if($login_type == 2): ?>
            <!-- Admin filter bar -->
            <div class="filter-bar mb-3">
                <div class="search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" class="form-control" id="card_search" placeholder="Search targets (success indicators, measures, category...)">
                </div>
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
                <select class="form-control form-control-sm filter-select" id="filter_rank">
                    <option value="">All Ranks</option>
                    <?php
                    $academic_ranks2 = $conn->query("SELECT * FROM position_list ORDER BY position ASC");
                    while($r = $academic_ranks2->fetch_assoc()):
                    ?>
                    <option value="<?php echo $r['id'] ?>"><?php echo $r['position'] ?></option>
                    <?php endwhile; ?>
                </select>
                <select class="form-control form-control-sm filter-select" id="filter_category">
                    <option value="">All Categories</option>
                    <option value="strategic">Strategic</option>
                    <option value="core">Core</option>
                    <option value="support">Support</option>
                </select>
                <select class="form-control form-control-sm filter-select" id="filter_status">
                    <option value="">All Status</option>
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
                <span class="text-muted small" style="white-space:nowrap;"><span id="card_count">0</span> of <span id="card_total">0</span> targets</span>
            </div>
            <?php endif; ?>

            <?php
            // Shared data fetch
            // Admin sees all targets (JS filter handles active/inactive); faculty sees only active
            $where = ($login_type == 2) ? "1=1" : "t.is_active = 1";
            if ($login_type == 0) {
                $where .= " AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $emp_position_id)";
                $where .= " AND " . task_designation_match($emp_designation_id, intval($faculty_id));
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
                ORDER BY FIELD(t.category, 'strategic', 'core', 'support'), t.sort_order, t.id");
            $tasks = $qry ? $qry->fetch_all(MYSQLI_ASSOC) : [];
            $matched_count = count($tasks);
            ?>

            <!-- Compact data table -->
            <div class="table-card">
                <div style="overflow-x:auto;">
                <table class="table" id="list">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <?php if($login_type == 2): ?><th style="width:30px;">&nbsp;</th><?php endif; ?>
                            <th>Success Indicators / Target</th>
                            <?php if($login_type != 2): ?><th style="width:25%;">Actual Accomplishment</th><?php endif; ?>
                            <th style="width:70px;">Rating</th>
                            <?php if($login_type == 0): ?>
                            <th style="width:110px;">Status</th>
                            <?php endif; ?>
                            <?php if($login_type == 2): ?>
                            <th style="width:90px;">Designation</th>
                            <th style="width:90px;">Rank</th>
                            <th style="width:70px;">Exempt</th>
                            <?php endif; ?>
                            <th style="width:140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    $current_cat = '';
                    $cat_labels = ['strategic' => 'Strategic Function', 'core' => 'Core Function', 'support' => 'Support Function'];
                    $cat_pct_map = [
                        'strategic' => ($allocations['strategic'] ?? 0),
                        'core' => ($allocations['core_total'] ?? 0),
                        'support' => ($allocations['support'] ?? 0),
                    ];

                    foreach($tasks as $row):
                        if ($login_type == 0) {
                            $exempt_qry = $conn->query("SELECT COUNT(*) as cnt FROM target_exemptions WHERE task_id = {$row['id']} AND position_id = $emp_position_id");
                            $is_exempted = $exempt_qry->fetch_assoc()['cnt'] > 0;
                            if ($is_exempted) continue;
                        }

                        $cat = $row['category'];
                        $sub_cat = $row['sub_category'] ?? '';

                        // Insert group separator when category changes
                        if ($cat !== $current_cat) {
                            $current_cat = $cat;
                            $pct_label = $cat_pct_map[$cat] > 0 ? " ({$cat_pct_map[$cat]}%)" : '';
                            $colspan = $login_type == 0 ? 6 : ($login_type == 2 ? 8 : 8);
                    ?>
                    <tr class="row-group" data-cat="<?= $cat ?>">
                        <td colspan="<?= $colspan ?>"><span class="cat-dot <?= $cat ?>"></span> <?= $cat_labels[$cat] ?? ucfirst($cat) ?><?= $pct_label ?></td>
                    </tr>
                    <?php
                        }

                        $rating_dims = '';
                        $rating_dims .= '<span class="' . ($row['quality'] == 'Applicable' ? 'active' : '') . '">Q</span> · ';
                        $rating_dims .= '<span class="' . ($row['timeliness'] == 'Applicable' ? 'active' : '') . '">T</span> · ';
                        $rating_dims .= '<span class="' . ($row['efficiency'] == 'Applicable' ? 'active' : '') . '">E</span>';

                        if ($login_type == 0) {
                            // Faculty: check submission status
                            $progress_qry = $conn->query("SELECT * FROM task_progress
                                WHERE faculty_id = $faculty_id AND task_id = {$row['id']} $period_filter
                                ORDER BY unix_timestamp(date_created) DESC LIMIT 1");
                            $hasSubmission = $progress_qry->num_rows > 0;
                            $isVerified = false; $isNA = false; $filePath = ''; $fileType = ''; $accomplishment = '';
                            if ($hasSubmission) {
                                $progress_row = $progress_qry->fetch_assoc();
                                $isVerified = (isset($progress_row['progress']) && $progress_row['progress'] === 'Verified');
                                $isNA = (isset($progress_row['progress']) && $progress_row['progress'] === 'N/A');
                                $filePath = epes_real_file_path($progress_row['file_path'], $progress_row['file_type']) ?: '';
                                $fileType = $progress_row['file_type'];
                                $accomplishment = $progress_row['actual_accomplishment'] ?? '';
                            }
                        }

                        // Build admin metadata
                        if ($login_type == 2) {
                            $desig_labels = [];
                            $jn_q = $conn->query("SELECT DISTINCT d.designation FROM task_designations td JOIN designation_list d ON d.id = td.designation_id WHERE td.task_id = " . intval($row['id']));
                            if ($jn_q) {
                                while ($jn = $jn_q->fetch_assoc()) $desig_labels[trim($jn['designation'])] = true;
                            }
                            if (empty($desig_labels) && !empty($row['designation_id'])) {
                                $ld = $conn->query("SELECT designation FROM designation_list WHERE id = " . intval($row['designation_id']));
                                if ($ld && $lr = $ld->fetch_assoc()) $desig_labels[trim($lr['designation'])] = true;
                            }
                            $desig_text = !empty($desig_labels) ? htmlspecialchars(implode(', ', array_keys($desig_labels))) : '<span class="text-muted">All</span>';
                            $rank_text = htmlspecialchars($row['rank_name']) ?: '<span class="text-muted">All</span>';
                            $ex_q = $conn->query("SELECT COUNT(*) as cnt FROM target_exemptions WHERE task_id = {$row['id']}");
                            $ex_n = $ex_q ? $ex_q->fetch_assoc()['cnt'] : 0;
                        }
                    ?>
                    <tr class="target-row"
                        data-task-id="<?php echo $row['id'] ?>"
                        data-designation="<?php echo $row['designation_id'] ?>"
                        data-rank="<?php echo $row['academic_rank_id'] ?>"
                        data-category="<?php echo $row['category'] ?>"
                        data-subcategory="<?php echo $row['sub_category'] ?? '' ?>"
                        data-status="<?php echo $row['is_active'] ?>"
                        data-search="<?php echo htmlspecialchars(strtolower(($row['success_indicators'] ?? '') . ' ' . ($row['targets_measures'] ?? '') . ' ' . ($row['category'] ?? '') . ' ' . ($row['sub_category'] ?? '') . ' ' . ($row['junction_designations'] ?? '') . ' ' . ($row['rank_name'] ?? ''))) ?>">
                        <td class="text-center font-weight-bold"><?= $i++ ?></td>
                        <?php if($login_type == 2): ?>
                        <td class="text-center drag-handle" style="cursor:grab;"><i class="fa fa-grip-vertical text-muted"></i></td>
                        <?php endif; ?>
                        <td>
                            <b><?= htmlspecialchars($row['success_indicators']) ?></b><?php if($row['is_active'] == 0): ?> <span class="badge badge-secondary" style="font-size:0.6rem;">Inactive</span><?php endif; ?>
                            <br><small class="text-muted"><?= htmlspecialchars($row['targets_measures']) ?></small>
                            <?php
                            $scale_parts = [];
                            $fix_nl = function($s) { return str_replace(["\\r\\n", "\\n", "\\r"], "\n", $s); };
                            if (($row['quality'] ?? '') == 'Applicable' && !empty(trim($row['quality_scale'] ?? ''))) {
                                $scale_parts[] = '<span class="badge badge-quality">Q</span> ' . nl2br(htmlspecialchars(trim($fix_nl($row['quality_scale']))));
                            }
                            if (($row['timeliness'] ?? '') == 'Applicable' && !empty(trim($row['timeliness_scale'] ?? ''))) {
                                $scale_parts[] = '<span class="badge badge-timeliness">T</span> ' . nl2br(htmlspecialchars(trim($fix_nl($row['timeliness_scale']))));
                            }
                            if (($row['efficiency'] ?? '') == 'Applicable' && !empty(trim($row['efficiency_scale'] ?? ''))) {
                                $scale_parts[] = '<span class="badge badge-efficiency">E</span> ' . nl2br(htmlspecialchars(trim($fix_nl($row['efficiency_scale']))));
                            }
                            if ($scale_parts) {
                                echo '<br><small class="text-muted rating-scale-inline">' . implode(' &nbsp; ', $scale_parts) . '</small>';
                            }
                            ?>
                        </td>
                        <?php if($login_type == 0): ?>
                        <td>
                            <?php if($isVerified): ?>
                                <div style="font-size:0.8rem; color:#555;"><?= !empty($accomplishment) ? nl2br(htmlspecialchars($accomplishment)) : '<span class="text-muted">—</span>' ?></div>
                            <?php elseif($hasSubmission && !$isNA): ?>
                                <textarea class="form-control form-control-sm accomplishment-edit" rows="3" data-task-id="<?= $row['id'] ?>" placeholder="Describe accomplishment..."><?= htmlspecialchars($accomplishment) ?></textarea>
                                <button class="btn btn-sm btn-outline-success btn-block mt-1 save-accomplishment" data-task-id="<?= $row['id'] ?>"><i class="fa fa-save mr-1"></i> Save</button>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><span class="rating-dims"><?= $rating_dims ?></span></td>
                        <?php if($login_type == 0): ?>
                        <td>
                            <?php if($isNA): ?>
                            <span class="st-pill st-na"><i class="fa fa-minus-circle"></i> N/A</span>
                            <?php elseif($isVerified): ?>
                            <span class="st-pill st-verified"><i class="fa fa-check-double"></i> Verified</span>
                            <?php elseif($hasSubmission): ?>
                            <span class="st-pill st-submitted"><i class="fa fa-check"></i> Submitted</span>
                            <?php else: ?>
                            <span class="st-pill st-pending"><i class="fa fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if($login_type == 2): ?>
                        <td><?= $desig_text ?></td>
                        <td><?= $rank_text ?></td>
                        <td><?= $ex_n > 0 ? '<span class="badge badge-warning">' . $ex_n . '</span>' : '<span class="badge badge-secondary">—</span>' ?></td>
                        <?php endif; ?>
                        <td>
                            <div class="action-btns">
                            <?php if($login_type == 0): ?>
                                <?php if($isNA): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="delete_file(<?= $row['id'] ?>, <?= $faculty_id ?>)" title="Remove N/A"><i class="fa fa-trash"></i></button>
                                <?php elseif($hasSubmission && !$isVerified): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary view-submit-file" data-file="<?= htmlspecialchars($filePath) ?>" data-filetype="<?= htmlspecialchars($fileType) ?>" title="View"><i class="fa fa-eye"></i></button>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="fcAction<?php echo $row['id']; ?>" data-toggle="dropdown" title="Options"><i class="fa fa-cog"></i></button>
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
                                <?php elseif($hasSubmission && $isVerified): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary view-submit-file" data-file="<?= htmlspecialchars($filePath) ?>" data-filetype="<?= htmlspecialchars($fileType) ?>" title="View"><i class="fa fa-eye"></i></button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-primary submit-btn" data-task-id="<?php echo $row['id']; ?>" title="Submit"><i class="fa fa-upload"></i></button>
                                <button class="btn btn-sm btn-outline-secondary na-btn" data-task-id="<?php echo $row['id']; ?>" title="N/A">N/A</button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if($login_type == 2): ?>
                            <button type="button" class="btn btn-sm btn-info view_task" data-id="<?php echo $row['id'] ?>" title="View"><i class="fa fa-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-warning manage_exemption" data-id="<?php echo $row['id'] ?>" title="Exemptions"><i class="fa fa-ban"></i></button>
                            <button type="button" class="btn btn-sm btn-primary manage_task" data-id="<?php echo $row['id'] ?>" title="Edit"><i class="fa fa-edit"></i></button>
                            <button type="button" class="btn btn-sm btn-danger delete_task" data-id="<?php echo $row['id'] ?>" title="Delete"><i class="fa fa-trash"></i></button>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- View File Modal -->
<div class="modal fade" id="submitFileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-file mr-2"></i>View File</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center" id="submitFileContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" id="submitDownloadBtn" class="btn btn-primary" download><i class="fa fa-download mr-1"></i>Download</a>
            </div>
        </div>
    </div>
</div>

<!-- Upload Submit Modal -->
<div class="modal fade" id="uploadSubmitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-upload mr-2"></i>Submit File</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="uploadSubmitForm" enctype="multipart/form-data">
                <input type="hidden" name="task_id" id="submitTaskId">
                <input type="hidden" name="rating_period" id="submitRatingPeriod" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="submitAccomplishment"><b>Actual Accomplishment</b></label>
                        <textarea name="actual_accomplishment" id="submitAccomplishment" class="form-control" rows="4" placeholder="Describe what was actually accomplished for this target..." required></textarea>
                    </div>
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
<!-- Bulk Upload Modal -->
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

<!-- Exemption Modal -->
<div class="modal fade" id="exemptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fa fa-ban mr-2"></i>Manage Exemptions</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
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
                            <button type="button" class="btn btn-primary btn-block" id="add_exemption"><i class="fa fa-plus"></i> Add</button>
                        </div>
                    </div>
                </div>
                <hr>
                <h6>Current Exemptions:</h6>
                <div id="exemption_list" class="mt-2"><p class="text-muted">Loading...</p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
table p { margin: unset !important; }
.table td { vertical-align: middle !important; }

/* Drag-and-drop sortable */
.sortable-ghost { opacity: 0.4; background: #eef2ff !important; }
.sortable-chosen { background: #f0f7ff !important; }
.drag-handle:active { cursor: grabbing !important; }

/* Info strip */
.info-strip { display: flex; gap: 16px; padding: 10px 16px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.05); font-size: 0.82rem; flex-wrap: wrap; }
.info-strip .info-item { display: flex; align-items: center; gap: 6px; }

/* Filter bar */
.filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-bar .search-box { flex: 1; min-width: 200px; position: relative; }
.filter-bar .search-box input { padding-left: 34px; border-radius: 6px; border: 1px solid #ddd; height: 36px; }
.filter-bar .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; }
.filter-bar select { border-radius: 6px; height: 36px; font-size: 0.85rem; max-width: 160px; }

/* Table card */
.table-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); overflow: hidden; }
.table-card .table { margin: 0; }
.table-card thead th { background: #f8f9fa; border-bottom: 2px solid #e9ecef; font-size: 0.75rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; padding: 10px 12px; white-space: nowrap; }
.table-card tbody td { padding: 10px 12px; border-top: 1px solid #f0f0f0; font-size: 0.85rem; vertical-align: middle; }
.table-card tbody tr:hover { background: #f8f9fa; }

/* Category color dot */
.cat-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
.cat-dot.strategic { background: #6f42c1; }
.cat-dot.core { background: #28a745; }
.cat-dot.support { background: #ffc107; }
.cat-label { font-weight: 600; font-size: 0.8rem; }

/* Status pills */
.st-pill { font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 12px; white-space: nowrap; display: inline-block; }
.st-verified { background: #d4edda; color: #155724; }
.st-submitted { background: #cce5ff; color: #004085; }
.st-na { background: #e2e3e5; color: #6c757d; }
.st-pending { background: #fff3cd; color: #856404; }

/* Rating dims */
.rating-dims { font-size: 0.75rem; color: #ccc; white-space: nowrap; }
.rating-dims .active { color: #28a745; font-weight: 600; }

/* Rating scale inline (inside Success Indicators / Target cell) */
.rating-scale-inline { display: block; margin-top: 2px; }
.rating-scale-inline .badge-quality,
.rating-scale-inline .badge-timeliness,
.rating-scale-inline .badge-efficiency { font-size: 0.6rem; font-weight: 700; padding: 1px 4px; border-radius: 3px; margin-right: 2px; vertical-align: middle; }
.rating-scale-inline .badge-quality { background: #28a745; color: #fff; }
.rating-scale-inline .badge-timeliness { background: #17a2b8; color: #fff; }
.rating-scale-inline .badge-efficiency { background: #6f42c1; color: #fff; }

.action-btns { display: flex; gap: 4px; }
.action-btns .btn { font-size: 0.75rem; padding: 4px 8px; line-height: 1.3; }

/* Sub-category badge */
.subcat-badge { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: #e8f4f8; color: #17a2b8; font-weight: 500; white-space: nowrap; }

/* Row group separator */
.row-group td { background: #f0f4f8 !important; font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: .03em; color: #495057; padding: 6px 12px; }
.dropdown-item.text-danger:hover { background-color: #f8d7da; color: #721c24; }
</style>

<script>
$(document).ready(function(){
    var isAdmin = <?php echo $login_type == 2 ? 'true' : 'false'; ?>;

    // Admin: combined filter (dropdowns + text search)
    function applyAdminFilters(){
        var designation = $('#filter_designation').val();
        var rank        = $('#filter_rank').val();
        var category    = $('#filter_category').val();
        var status      = $('#filter_status').val();
        var term        = ($('#card_search').val() || '').toLowerCase().trim();
        var visible = 0;
        var total = 0;

        $('#list .target-row').each(function(){
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

        // Show/hide group separators based on visible rows below them
        $('#list .row-group').each(function(){
            var $g = $(this);
            $g.show();
            var cat = $g.data('cat');
            var hasVisible = false;
            $g.nextAll('.target-row').each(function(){
                if ($(this).data('category') !== cat) return false;
                if ($(this).is(':visible')) { hasVisible = true; return false; }
            });
            if (!hasVisible) $g.hide();
        });

        $('#card_count').text(visible);
        $('#card_total').text(total);
    }

    $('.filter-select').change(function(){ if (isAdmin) applyAdminFilters(); });

    if (isAdmin) {
        var searchTimer = null;
        $('#card_search').on('input', function(){
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyAdminFilters, 200);
        });
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
        data: { task_id: taskId, faculty_id: facultyId, rating_period: '<?= $rating_period ?>' },
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
    $('#submitAccomplishment').val('');
    $('#uploadSubmitModal').modal('show');
});

$(document).on('click', '.na-btn', function(){
    var taskId = $(this).data('task-id');
    if (!confirm('Mark this target as N/A (not applicable)?')) return;
    start_load();
    $.ajax({
        url: 'ajax.php?action=submit_na',
        method: 'POST',
        data: { task_id: taskId, rating_period: '<?= $rating_period ?>' },
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

// Save accomplishment (inline edit, only when not verified)
$(document).on('click', '.save-accomplishment', function(){
    var taskId = $(this).data('task-id');
    var text = $('.accomplishment-edit[data-task-id="' + taskId + '"]').val();
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');
    $.ajax({
        url: 'ajax.php?action=update_accomplishment',
        method: 'POST',
        data: { task_id: taskId, actual_accomplishment: text },
        success: function(resp) {
            try {
                var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (r.status === 'success') {
                    alert_toast(r.message, 'success');
                } else {
                    alert_toast(r.message || 'Failed to update.', 'danger');
                }
            } catch (e) {
                alert_toast('Failed to update.', 'danger');
            }
            $btn.prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save');
        },
        error: function() {
            alert_toast('Connection error.', 'danger');
            $btn.prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Save');
        }
    });
});
</script>

<?php if($login_type == 2): ?>
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

<?php if($login_type == 2): ?>
<script>
// ── Drag-and-drop target reordering (admin only) ──
// Uses Sortable.js (CDN). Drag rows within a category group to reorder.
// Category group headers (Strategic/Core/Support) are not draggable.
(function(){
    var tbody = document.querySelector('#list tbody');
    if (!tbody) return;

    // Load Sortable.js from CDN if not already present
    if (typeof Sortable === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
        s.onload = initSortable;
        document.head.appendChild(s);
    } else {
        initSortable();
    }

    function initSortable() {
        Sortable.create(tbody, {
            handle: '.drag-handle',
            draggable: '.target-row',
            filter: '.row-group',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                // Collect all target-row task IDs in DOM order
                var rows = tbody.querySelectorAll('.target-row');
                var ids = [];
                rows.forEach(function(r) {
                    var tid = r.getAttribute('data-task-id');
                    if (tid) ids.push(parseInt(tid));
                });
                if (ids.length === 0) return;

                // Save to server
                var formData = new FormData();
                formData.append('orders', JSON.stringify(ids));

                fetch('ajax.php?action=save_target_order', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'success') {
                        alert_toast('Target order saved', 'success');
                        // Renumber the # column
                        var num = 1;
                        rows.forEach(function(r) {
                            var numCell = r.querySelector('td:first-child');
                            if (numCell) numCell.textContent = num++;
                        });
                    } else {
                        alert_toast('Failed to save order', 'error');
                    }
                })
                .catch(function() {
                    alert_toast('Error saving order', 'error');
                });
            }
        });
    }
})();
<?php endif; ?>
</script>