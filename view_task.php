<?php 
include 'db_connect.php';
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT t.*, d.designation as designation_name, r.position as rank_name 
        FROM task_list t 
        LEFT JOIN designation_list d ON t.designation_id = d.id 
        LEFT JOIN position_list r ON t.academic_rank_id = r.id 
        WHERE t.id = ".$_GET['id'])->fetch_array();
    foreach($qry as $k => $v){
        $$k = $v;
    }
}
?>
<div class="container-fluid">
    <div class="col-lg-12">
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt><b class="border-bottom border-primary">Category</b></dt>
                    <dd>
                        <?php 
                        $cat = $category ?? '';
                        $cat_class = $cat == 'strategic' ? 'badge-primary' : ($cat == 'core' ? 'badge-success' : 'badge-warning');
                        ?>
                        <span class="badge <?php echo $cat_class ?>"><?php echo ucfirst($cat) ?: 'Not Set' ?></span>
                    </dd>
                </dl>
                <?php if ($cat == 'core'): ?>
                <dl>
                    <dt><b class="border-bottom border-primary">Sub-Category</b></dt>
                    <dd>
                        <?php 
                        $sub_cat = $sub_category ?? '';
                        ?>
                        <span class="badge badge-info"><?php echo ucfirst($sub_cat) ?: 'Not Set' ?></span>
                    </dd>
                </dl>
                <?php endif; ?>
                <dl>
                    <dt><b class="border-bottom border-primary">Designation</b></dt>
                    <dd><?php echo htmlspecialchars($designation_name ?? 'All Designations') ?></dd>
                </dl>
                <dl>
                    <dt><b class="border-bottom border-primary">Academic Rank</b></dt>
                    <dd><?php echo htmlspecialchars($rank_name ?? 'All Academic Ranks') ?></dd>
                </dl>
                <dl>
                    <dt><b class="border-bottom border-primary">Status</b></dt>
                    <dd>
                        <?php if(($is_active ?? 1) == 1): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl>
                    <dt><b class="border-bottom border-primary">Quality</b></dt>
                    <dd>
                        <?php if(($quality ?? '') == 'Applicable'): ?>
                            <span class="badge badge-success"><i class="fa fa-check mr-1"></i> Applicable</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">N/A</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                <dl>
                    <dt><b class="border-bottom border-primary">Timeliness</b></dt>
                    <dd>
                        <?php if(($timeliness ?? '') == 'Applicable'): ?>
                            <span class="badge badge-success"><i class="fa fa-check mr-1"></i> Applicable</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">N/A</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                <dl>
                    <dt><b class="border-bottom border-primary">Efficiency</b></dt>
                    <dd>
                        <?php if(($efficiency ?? '') == 'Applicable'): ?>
                            <span class="badge badge-success"><i class="fa fa-check mr-1"></i> Applicable</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">N/A</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                <dl>
                    <dt><b class="border-bottom border-primary">Date Created</b></dt>
                    <dd><?php echo date("M d, Y", strtotime($date_created ?? 'now')) ?></dd>
                </dl>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <dl>
                    <dt><b class="border-bottom border-primary">Success Indicators</b></dt>
                    <dd><?php echo nl2br(htmlspecialchars($success_indicators ?? '')) ?></dd>
                </dl>
                <dl>
                    <dt><b class="border-bottom border-primary">Targets + Measures</b></dt>
                    <dd><?php echo nl2br(htmlspecialchars($targets_measures ?? '')) ?></dd>
                </dl>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <h5><b class="border-bottom border-primary">Employee Submissions</b></h5>
                <?php
                $submissions = $conn->query("SELECT tp.*, CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) as employee_name 
                    FROM task_progress tp 
                    INNER JOIN employee_list e ON tp.faculty_id = e.id 
                    WHERE tp.task_id = ".$_GET['id']." 
                    ORDER BY tp.date_created DESC");
                ?>
                <?php if($submissions->num_rows > 0): ?>
                    <table class="table table-bordered table-striped mt-2">
                        <thead class="thead-dark">
                            <tr>
                                <th>Employee</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($sub = $submissions->fetch_assoc()): 
                                $filePath = $sub['file_path'].".".$sub['file_type'];
                                $isVerified = ($sub['progress'] === 'Verified');
                            ?>
                                <tr>
                                    <td><?php echo ucwords($sub['employee_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $isVerified ? 'info' : 'warning' ?>">
                                            <?php echo $sub['progress'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(!empty($sub['file_path'])): ?>
                                            <button type="button" class="btn btn-sm btn-primary view-submitted-file" 
                                                data-file="<?php echo htmlspecialchars($filePath) ?>"
                                                data-filetype="<?php echo htmlspecialchars($sub['file_type']) ?>">
                                                <i class="fa fa-eye mr-1"></i> View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date("M d, Y h:i A", strtotime($sub['date_created'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mt-2">No submissions yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewFileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-file mr-2"></i>View File</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" id="viewFileContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" id="downloadFileBtn" class="btn btn-primary" download><i class="fa fa-download mr-1"></i>Download</a>
            </div>
        </div>
    </div>
</div>

<style>
#uni_modal .modal-footer{
    display: none
}
#uni_modal .modal-footer.display{
    display: flex
}
</style>

<script>
$(document).on('click', '.view-submitted-file', function(){
    var filePath = $(this).data('file');
    var fileType = $(this).data('filetype').toLowerCase();
    var modal = $('#viewFileModal');
    var content = $('#viewFileContent');
    var downloadBtn = $('#downloadFileBtn');
    
    content.empty();
    downloadBtn.attr('href', filePath);
    
    var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    
    if (imageExts.includes(fileType)) {
        content.html('<img src="' + filePath + '" class="img-fluid" style="max-height: 70vh;">');
    } else if (fileType === 'pdf') {
        content.html('<iframe src="' + filePath + '" style="width: 100%; height: 70vh; border: none;"></iframe>');
    } else {
        content.html('<p class="text-muted"><i class="fa fa-file-o fa-5x"></i><br><br>Cannot preview this file type. Please download to view.</p>');
    }
    
    modal.modal('show');
});
</script>

<div class="modal-footer display p-0 m-0">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
