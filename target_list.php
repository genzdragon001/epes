<?php include 'db_connect.php' ?>
<?php
$login_type = $_SESSION['login_type'];
$faculty_id = $_SESSION['login_id'] ?? 0;

// Get current rating period
$rating_period = '';
$col_check = $conn->query("SHOW COLUMNS FROM rating_period LIKE 'is_active'");
$has_is_active = $col_check->num_rows > 0;
$rp_query = "SELECT code FROM rating_period" . ($has_is_active ? " WHERE is_active = 1" : "") . " LIMIT 1";
$rp_qry = $conn->query($rp_query);
if ($rp_qry && $rp_qry->num_rows > 0) {
    $rp_row = $rp_qry->fetch_assoc();
    $rating_period = $rp_row['code'];
}

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
            <?php if($login_type == 2): ?>
            <div class="card-tools">
                <button class="btn btn-sm btn-default btn-flat border-primary" id="new_task">
                    <i class="fa fa-plus"></i> Add New Target
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if($login_type == 2): ?>
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

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Success Indicators</th>
                            <th style="width: 150px;">Targets + Measures</th>
                            <?php if($login_type == 0): ?>
                            <th class="text-center" style="width: 90px;">Category</th>
                            <th class="text-center" style="width: 100px;">Sub-Category</th>
                            <?php endif; ?>
                            <?php if($login_type == 2): ?>
                            <th class="text-center" style="width: 100px;">Category</th>
                            <th class="text-center" style="width: 100px;">Sub-Category</th>
                            <th class="text-center" style="width: 120px;">Designation</th>
                            <th class="text-center" style="width: 130px;">Academic Rank</th>
                            <th class="text-center" style="width: 90px;">Exemption</th>
                            <?php endif; ?>
                            <th class="text-center" style="width: 80px;">Quality</th>
                            <th class="text-center" style="width: 100px;">Timeliness</th>
                            <th class="text-center" style="width: 90px;">Efficiency</th>
                            <th class="text-center" style="width: 80px;">Status</th>
                            <?php if($login_type == 0): ?>
                            <th class="text-center" style="width: 150px;">Submission</th>
                            <?php endif; ?>
                            <?php if($login_type == 2): ?>
                            <th style="width: 120px;">Date Created</th>
                            <th class="text-center" style="width: 100px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        
                        $where = "t.is_active = 1";
                        
                        if ($login_type == 0) {
                            $where .= " AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $emp_position_id)";
                            $where .= " AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $emp_designation_id OR t.designation_id IS NULL)";
                            
                            $cat_filters = [];
                            $has_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0;
                            $is_admin_role = false;
                            $is_director = false;
                            $is_dean = false;
                            if ($emp_designation_id > 0) {
                                $desig_qry = $conn->query("SELECT designation FROM designation_list WHERE id = $emp_designation_id");
                                if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
                                    $desig_name = $desig_row['designation'];
                                    if (stripos($desig_name, 'Dean') !== false) {
                                        $has_strategic = true;
                                        $is_admin_role = true;
                                        $is_dean = true;
                                    }
                                    if (stripos($desig_name, 'Head') !== false || 
                                        stripos($desig_name, 'Vice President') !== false) {
                                        $has_strategic = true;
                                        $is_admin_role = true;
                                    }
                                    if (stripos($desig_name, 'Director') !== false) {
                                        $has_strategic = true;
                                        $is_admin_role = true;
                                        $is_director = true;
                                    }
                                }
                            }
                            $has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
                            $has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && !$is_cos;
                            $has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && !$is_cos;
                            $has_support = isset($allocations['support']) && $allocations['support'] > 0;
                            
                            if ($is_dean || $is_director) {
                                $has_instructions = true;
                                $has_research = true;
                                $has_extension = true;
                                $has_support = true;
                            }
                            
                            if ($has_strategic) $cat_filters[] = "t.category = 'strategic'";
                            if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
                            if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
                            if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
                            if ($has_support) $cat_filters[] = "t.category = 'support'";
                            
                            if (!empty($cat_filters)) {
                                $where .= " AND (" . implode(" OR ", $cat_filters) . ")";
                            }
                        }
                        
                        $qry = $conn->query("SELECT t.*, d.designation as designation_name, r.position as rank_name 
                            FROM task_list t 
                            LEFT JOIN designation_list d ON t.designation_id = d.id 
                            LEFT JOIN position_list r ON t.academic_rank_id = r.id 
                            WHERE $where 
                            ORDER BY t.category, t.sub_category, t.id");
                        
                        $matched_count = $qry ? $qry->num_rows : 0;
                        
                        while($row = $qry->fetch_assoc()):
                            if ($login_type == 0) {
                                $exempt_qry = $conn->query("SELECT COUNT(*) as cnt FROM target_exemptions WHERE task_id = {$row['id']} AND position_id = $emp_position_id");
                                $is_exempted = $exempt_qry->fetch_assoc()['cnt'] > 0;
                                if ($is_exempted) continue;
                            }
                        ?>
                        <tr class="task-row" 
                            data-designation="<?php echo $row['designation_id'] ?>" 
                            data-rank="<?php echo $row['academic_rank_id'] ?>"
                            data-category="<?php echo $row['category'] ?>"
                            data-subcategory="<?php echo $row['sub_category'] ?? '' ?>"
                            data-status="<?php echo $row['is_active'] ?>">
                            <td class="text-center font-weight-bold"><?php echo $i++ ?></td>
                            <td><?php echo nl2br(htmlspecialchars($row['success_indicators'])) ?></td>
                            <td>
                                <span class="d-inline-block text-truncate" style="max-width: 130px;" title="<?php echo htmlspecialchars($row['targets_measures']); ?>" data-toggle="tooltip">
                                    <?php echo htmlspecialchars(mb_strimwidth($row['targets_measures'], 0, 30, "...")); ?>
                                </span>
                            </td>
                            <?php if($login_type == 0): ?>
                            <td class="text-center">
                                <?php 
                                $cat = $row['category'];
                                $cat_class = $cat == 'strategic' ? 'badge-primary' : ($cat == 'core' ? 'badge-success' : 'badge-warning');
                                ?>
                                <span class="badge <?php echo $cat_class ?>"><?php echo ucfirst($cat) ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $sub_cat = $row['sub_category'] ?? '';
                                if (!empty($sub_cat)): ?>
                                    <span class="badge badge-info"><?php echo ucfirst($sub_cat) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Main</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <?php if($login_type == 2): ?>
                            <td class="text-center">
                                <?php 
                                $cat = $row['category'];
                                $cat_class = $cat == 'strategic' ? 'badge-primary' : ($cat == 'core' ? 'badge-success' : 'badge-warning');
                                ?>
                                <span class="badge <?php echo $cat_class ?>"><?php echo ucfirst($cat) ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $sub_cat = $row['sub_category'] ?? '';
                                if (!empty($sub_cat)): ?>
                                    <span class="badge badge-info"><?php echo ucfirst($sub_cat) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['designation_name']) ?: '<span class="text-muted">All</span>' ?></td>
                            <td><?php echo htmlspecialchars($row['rank_name']) ?: '<span class="text-muted">All</span>' ?></td>
                            <td class="text-center">
                                <?php 
                                $exempt_qry2 = $conn->query("SELECT COUNT(*) as cnt FROM target_exemptions WHERE task_id = {$row['id']}");
                                $exempt_count = $exempt_qry2->fetch_assoc()['cnt'];
                                ?>
                                <span class="badge <?php echo $exempt_count > 0 ? 'badge-warning' : 'badge-secondary' ?> exemption-count" data-task="<?php echo $row['id'] ?>">
                                    <?php echo $exempt_count > 0 ? $exempt_count . ' Exempted' : 'None' ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?php if($row['quality'] == 'Applicable'): ?>
                                    <span class="badge badge-pill badge-success"><i class="fa fa-check mr-1"></i>Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-pill badge-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['timeliness'] == 'Applicable'): ?>
                                    <span class="badge badge-pill badge-success"><i class="fa fa-check mr-1"></i>Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-pill badge-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['efficiency'] == 'Applicable'): ?>
                                    <span class="badge badge-pill badge-success"><i class="fa fa-check mr-1"></i>Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-pill badge-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['is_active'] == 1): ?>
                                    <span class="badge badge-pill badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-pill badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <?php if($login_type == 0): ?>
                            <td class="text-center">
                                <?php
                                    $progress_qry = $conn->query("SELECT * FROM task_progress 
                                        WHERE faculty_id = $faculty_id AND task_id = {$row['id']}
                                        ORDER BY unix_timestamp(date_created) DESC LIMIT 1");
                                    $hasSubmission = $progress_qry->num_rows > 0;
                                    
                                    if ($hasSubmission):
                                        $progress_row = $progress_qry->fetch_assoc();
                                        $isVerified = (isset($progress_row['progress']) && $progress_row['progress'] === 'Verified');
                                        $filePath = $progress_row['file_path'].".".$progress_row['file_type'];
                                        $fileType = $progress_row['file_type'];
                                ?>
                                    <span class="badge badge-<?= $isVerified ? 'info' : 'success' ?> mb-1 d-block">
                                        <i class="fa fa-<?= $isVerified ? 'check-double' : 'check' ?> mr-1"></i>
                                        <?= $isVerified ? 'Verified' : 'Submitted' ?>
                                    </span>
                                    <button type="button" class="btn btn-outline-primary btn-sm view-submit-file" 
                                            data-file="<?= htmlspecialchars($filePath) ?>"
                                            data-filetype="<?= htmlspecialchars($fileType) ?>">
                                        <i class="fa fa-eye mr-1"></i> View
                                    </button>
                                <?php else: ?>
                                    <span class="badge badge-secondary mb-1 d-block">
                                        <i class="fa fa-clock mr-1"></i> Not Submitted
                                    </span>
                                    <button class="btn btn-primary btn-sm submit-btn" data-task-id="<?php echo $row['id']; ?>">
                                        <i class="fa fa-upload mr-1"></i> Submit
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($hasSubmission && !$isVerified): ?>
                                    <div class="dropdown d-inline-block ml-1">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                                type="button" id="actionMenu<?php echo $row['id']; ?>" 
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="actionMenu<?php echo $row['id']; ?>">
                                            <form action="reupload_file.php" method="POST" enctype="multipart/form-data" class="px-3 py-2">
                                                <label class="small text-muted">Re-upload:</label>
                                                <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                                <input type="file" name="document" class="form-control form-control-sm mb-2" required>
                                                <button type="submit" class="btn btn-sm btn-primary btn-block">Update</button>
                                            </form>
                                            <button class="dropdown-item text-danger" onclick="delete_file(<?= $row['id'] ?>, <?= $faculty_id ?>)">
                                                <i class="fa fa-trash mr-2"></i>Delete File
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>

                            <?php if($login_type == 2): ?>
                            <td class="text-muted small"><?php echo date("M d, Y", strtotime($row['date_created'])) ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info view_task" data-id="<?php echo $row['id'] ?>">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning manage_exemption" data-id="<?php echo $row['id'] ?>" title="Manage Exemptions">
                                        <i class="fa fa-ban"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary manage_task" data-id="<?php echo $row['id'] ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete_task" data-id="<?php echo $row['id'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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

<style>
.card-header { background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%); color: white; }
table p { margin: unset !important; }
table td { vertical-align: middle !important; }
.table-hover tbody tr:hover { background-color: rgba(0,123,255,.05); }
.dropdown-item.text-danger:hover { background-color: #f8d7da; color: #721c24; }
</style>

<script>
$(document).ready(function(){
    var table = $('#list').DataTable({
        "dom": 'Bfrtip',
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
        "ordering": true,
        "order": [[0, 'asc']],
        "pageLength": 25
    });

    $('.filter-select').change(function(){
        var designation = $('#filter_designation').val();
        var rank = $('#filter_rank').val();
        var category = $('#filter_category').val();
        var status = $('#filter_status').val();

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
    });

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
