<?php 
include 'db_connect.php';

$designations = $conn->query("SELECT * FROM designation_list ORDER BY designation ASC");
$academic_ranks = $conn->query("SELECT * FROM position_list ORDER BY position ASC");

if(isset($_GET['id'])){
    $stmt = $conn->prepare("SELECT * FROM task_list where id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $qry = $stmt->get_result()->fetch_array();
    foreach($qry as $k => $v){
        $$k = $v;
    }
    // Load currently-assigned designations (junction). Empty (designation_id=0/NULL) = All.
    $assigned_desigs = [];
    if (!empty($designation_id)) {
        $assigned_desigs[] = $designation_id;
    }
    $tdq = $conn->query("SELECT designation_id FROM task_designations WHERE task_id = " . intval($_GET['id']));
    while ($td = $tdq->fetch_assoc()) { $assigned_desigs[] = $td['designation_id']; }
    $assigned_desigs = array_values(array_unique($assigned_desigs));
}
// Convert literal \r\n (stored as text in DB) to real newlines for textarea display
$fix_nl = function($s) { return $s !== null ? str_replace(["\\r\\n", "\\n", "\\r"], "\n", $s) : ''; };
?>
<div class="container-fluid">
    <form action="" id="manage-output">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">

        <!-- Row 1: Category (left) + Sub-Category (right) -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><b>Category</b></label>
                    <select class="form-control form-control-sm" name="category" id="task_category" required>
                        <option value="">-- Select Category --</option>
                        <option value="strategic" <?php echo (isset($category) && $category == 'strategic') ? "selected" : "" ?>>Strategic</option>
                        <option value="core" <?php echo (isset($category) && $category == 'core') ? "selected" : "" ?>>Core</option>
                        <option value="support" <?php echo (isset($category) && $category == 'support') ? "selected" : "" ?>>Support</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6" id="sub_category_wrapper" style="<?php echo (isset($category) && $category == 'core') ? '' : 'display:none;' ?>">
                <div class="form-group">
                    <label><b>Sub-Category</b></label>
                    <select class="form-control form-control-sm" name="sub_category" id="task_sub_category">
                        <option value="">-- Select --</option>
                        <option value="instructions" <?php echo (isset($sub_category) && $sub_category == 'instructions') ? "selected" : "" ?>>Instructions</option>
                        <option value="research" <?php echo (isset($sub_category) && $sub_category == 'research') ? "selected" : "" ?>>Research</option>
                        <option value="extension" <?php echo (isset($sub_category) && $sub_category == 'extension') ? "selected" : "" ?>>Extension</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Row 2: Designation (left) + Academic Rank (right) -->
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label><b>Designation(s)</b></label>
                    <select class="form-control form-control-sm select2" name="designation_id[]" id="designation_id" multiple="multiple" style="width:100%;">
                        <?php
                        $desigs2 = $conn->query("SELECT * FROM designation_list ORDER BY designation ASC");
                        while($d = $desigs2->fetch_assoc()):
                            $sel = (in_array($d['id'], $assigned_desigs ?? [])) ? "selected" : "";
                        ?>
                        <option value="<?php echo $d['id'] ?>" <?php echo $sel ?>><?php echo $d['designation'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Leave empty to apply to <b>All Designations</b>. Select multiple (e.g. Dept Head + Dean) as needed.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Academic Rank</b></label>
                    <select class="form-control form-control-sm" name="academic_rank_id" id="academic_rank_id">
                        <option value="">-- All Academic Ranks --</option>
                        <?php
                        $ranks2 = $conn->query("SELECT * FROM position_list ORDER BY position ASC");
                        while($r = $ranks2->fetch_assoc()):
                        ?>
                        <option value="<?php echo $r['id'] ?>" <?php echo (isset($academic_rank_id) && $academic_rank_id == $r['id']) ? "selected" : "" ?>><?php echo $r['position'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Optional</small>
                </div>
            </div>
        </div>

        <!-- Success Indicators + Targets + Measures: full width -->
        <div class="form-group">
            <label><b>Success Indicators</b></label>
            <textarea name="success_indicators" class="form-control form-control-sm" rows="3" required><?php echo isset($success_indicators) ? htmlspecialchars($fix_nl($success_indicators)) : '' ?></textarea>
        </div>

        <div class="form-group">
            <label><b>Targets + Measures</b></label>
            <textarea name="targets_measures" class="form-control form-control-sm" rows="3" required><?php echo isset($targets_measures) ? htmlspecialchars($fix_nl($targets_measures)) : '' ?></textarea>
        </div>

        <!-- Q / T / E selects: three columns side by side -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Quality</b></label>
                    <select name="quality" id="quality_sel" class="form-control form-control-sm" required>
                        <option value="N/A" <?php echo (isset($quality) && $quality == "N/A") ? "selected" : "" ?>>N/A</option>
                        <option value="Applicable" <?php echo (isset($quality) && $quality == "Applicable") ? "selected" : "" ?>>Applicable</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Timeliness</b></label>
                    <select name="timeliness" id="timeliness_sel" class="form-control form-control-sm" required>
                        <option value="N/A" <?php echo (isset($timeliness) && $timeliness == "N/A") ? "selected" : "" ?>>N/A</option>
                        <option value="Applicable" <?php echo (isset($timeliness) && $timeliness == "Applicable") ? "selected" : "" ?>>Applicable</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Efficiency</b></label>
                    <select name="efficiency" id="efficiency_sel" class="form-control form-control-sm" required>
                        <option value="N/A" <?php echo (isset($efficiency) && $efficiency == "N/A") ? "selected" : "" ?>>N/A</option>
                        <option value="Applicable" <?php echo (isset($efficiency) && $efficiency == "Applicable") ? "selected" : "" ?>>Applicable</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Rating Scale textareas: full width, shown only when Applicable -->
        <div class="form-group quality-scale-wrap" style="<?php echo (isset($quality) && $quality == 'Applicable') ? '' : 'display:none;' ?>">
            <label class="text-muted"><small>Quality Rating Scale</small></label>
            <textarea name="quality_scale" class="form-control form-control-sm" rows="3" placeholder="e.g. 5 – Excellent, 4 – Very Good, 3 – Good, 2 – Fair, 1 – Poor"><?php echo isset($quality_scale) ? htmlspecialchars($fix_nl($quality_scale)) : '' ?></textarea>
        </div>
        <div class="form-group timeliness-scale-wrap" style="<?php echo (isset($timeliness) && $timeliness == 'Applicable') ? '' : 'display:none;' ?>">
            <label class="text-muted"><small>Timeliness Rating Scale</small></label>
            <textarea name="timeliness_scale" class="form-control form-control-sm" rows="3" placeholder="e.g. 5 – before the deadline, 3 – on the deadline, 2 – beyond the deadline, 1 – no submission"><?php echo isset($timeliness_scale) ? htmlspecialchars($fix_nl($timeliness_scale)) : '' ?></textarea>
        </div>
        <div class="form-group efficiency-scale-wrap" style="<?php echo (isset($efficiency) && $efficiency == 'Applicable') ? '' : 'display:none;' ?>">
            <label class="text-muted"><small>Efficiency Rating Scale</small></label>
            <textarea name="efficiency_scale" class="form-control form-control-sm" rows="3" placeholder="e.g. 5 – 100%, 4 – 90-99%, 3 – 80-89%, 2 – 51-79%, 1 – 50% and below"><?php echo isset($efficiency_scale) ? htmlspecialchars($fix_nl($efficiency_scale)) : '' ?></textarea>
        </div>

        <div class="form-group">
            <label><b>Status</b></label>
            <select name="is_active" class="form-control form-control-sm" required>
                <option value="1" <?php echo (isset($is_active) && $is_active == 1) ? "selected" : "" ?>>Active</option>
                <option value="0" <?php echo (isset($is_active) && $is_active == 0) ? "selected" : "" ?>>Inactive</option>
            </select>
        </div>
    </form>
</div>

<script>
$('#task_category').change(function(){
    var cat = $(this).val();
    if (cat === 'core') {
        $('#sub_category_wrapper').show();
    } else {
        $('#sub_category_wrapper').hide();
        $('#task_sub_category').val('');
    }
});

// Show/hide each rating-scale textarea based on its dimension select
function toggleScale(sel, wrap) {
    if ($(sel).val() === 'Applicable') {
        $(wrap).show();
    } else {
        $(wrap).hide();
    }
}
$('#quality_sel').change(function(){ toggleScale(this, '.quality-scale-wrap'); });
$('#timeliness_sel').change(function(){ toggleScale(this, '.timeliness-scale-wrap'); });
$('#efficiency_sel').change(function(){ toggleScale(this, '.efficiency-scale-wrap'); });

$(document).ready(function(){
    if ($.fn.select2) {
        $('#designation_id').select2({
            placeholder: 'All Designations (leave empty)',
            allowClear: true,
            width: '100%'
        });
    }
});

$('#manage-output').submit(function(e){
    e.preventDefault()
    start_load()
    $.ajax({
        url:'ajax.php?action=save_task',
        data: new FormData($(this)[0]),
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        type: 'POST',
        success:function(resp){
            if(resp == 1){
                alert_toast('Data successfully saved',"success");
                setTimeout(function(){
                    location.reload()
                },1500)
            } else {
                alert_toast("Error: " + resp,"danger");
                end_load()
            }
        }
    })
})
</script>
