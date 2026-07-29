<?php
include 'db_connect.php';
if(isset($_GET['id'])){
    $stmt = $conn->prepare("SELECT * FROM college_office_list WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $qry = $stmt->get_result()->fetch_array();
    foreach($qry as $k => $v){ $$k = $v; }
    $stmt->close();
}
?>
<div class="container-fluid">
    <form action="" id="manage-college_office">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div id="msg" class="form-group"></div>
        <div class="form-group">
            <label for="name" class="control-label">College / Office Name</label>
            <input type="text" class="form-control form-control-sm" name="name" id="name" value="<?php echo isset($name) ? $name : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="code" class="control-label">Code (optional)</label>
            <input type="text" class="form-control form-control-sm" name="code" id="code" value="<?php echo isset($code) ? $code : '' ?>" maxlength="20" placeholder="e.g. CCET, CCIT">
        </div>
        <div class="form-group">
            <label class="control-label">Status</label>
            <select name="is_active" class="form-control form-control-sm">
                <option value="1" <?php echo (isset($is_active) && $is_active == 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?php echo (isset($is_active) && $is_active == 0) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </form>
</div>
<script>
    $(document).ready(function(){
        $('#manage-college_office').submit(function(e){
            e.preventDefault();
            start_load();
            $('#msg').html('');
            $.ajax({
                url:'ajax.php?action=save_college_office',
                method:'POST',
                data:$(this).serialize(),
                success:function(resp){
                    if(resp == 1){
                        alert_toast("Data successfully saved.","success");
                        setTimeout(function(){ location.reload() },1750);
                    } else if(resp == 2){
                        $('#msg').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> College/Office already exists.</div>');
                        end_load();
                    }
                }
            });
        });
    });
</script>