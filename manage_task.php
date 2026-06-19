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
}
?>
<div class="container-fluid">
    <form action="" id="manage-output">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">

        <div class="row">
            <div class="col-md-3">
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
            <div class="col-md-3" id="sub_category_wrapper" style="<?php echo (isset($category) && $category == 'core') ? '' : 'display:none;' ?>">
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
            <div class="col-md-3">
                <div class="form-group">
                    <label><b>Designation</b></label>
                    <select class="form-control form-control-sm" name="designation_id" id="designation_id">
                        <option value="">-- All Designations --</option>
                        <?php while($d = $designations->fetch_assoc()): ?>
                        <option value="<?php echo $d['id'] ?>" <?php echo (isset($designation_id) && $designation_id == $d['id']) ? "selected" : "" ?>><?php echo $d['designation'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Leave empty to apply to all designations</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label><b>Academic Rank</b></label>
                    <select class="form-control form-control-sm" name="academic_rank_id" id="academic_rank_id">
                        <option value="">-- All Academic Ranks --</option>
                        <?php while($r = $academic_ranks->fetch_assoc()): ?>
                        <option value="<?php echo $r['id'] ?>" <?php echo (isset($academic_rank_id) && $academic_rank_id == $r['id']) ? "selected" : "" ?>><?php echo $r['position'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Leave empty to apply to all academic ranks</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label><b>Success Indicators</b></label>
            <textarea name="success_indicators" class="form-control form-control-sm" rows="3" required><?php echo isset($success_indicators) ? htmlspecialchars($success_indicators) : '' ?></textarea>
        </div>

        <div class="form-group">
            <label><b>Targets + Measures</b></label>
            <textarea name="targets_measures" class="form-control form-control-sm" rows="3" required><?php echo isset($targets_measures) ? htmlspecialchars($targets_measures) : '' ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Quality</b></label>
                    <select name="quality" class="form-control form-control-sm" required>
                        <option value="N/A" <?php echo (isset($quality) && $quality == "N/A") ? "selected" : "" ?>>N/A</option>
                        <option value="Applicable" <?php echo (isset($quality) && $quality == "Applicable") ? "selected" : "" ?>>Applicable</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Timeliness</b></label>
                    <select name="timeliness" class="form-control form-control-sm" required>
                        <option value="N/A" <?php echo (isset($timeliness) && $timeliness == "N/A") ? "selected" : "" ?>>N/A</option>
                        <option value="Applicable" <?php echo (isset($timeliness) && $timeliness == "Applicable") ? "selected" : "" ?>>Applicable</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><b>Efficiency</b></label>
                    <select name="efficiency" class="form-control form-control-sm" required>
                        <option value="N/A" <?php echo (isset($efficiency) && $efficiency == "N/A") ? "selected" : "" ?>>N/A</option>
                        <option value="Applicable" <?php echo (isset($efficiency) && $efficiency == "Applicable") ? "selected" : "" ?>>Applicable</option>
                    </select>
                </div>
            </div>
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
