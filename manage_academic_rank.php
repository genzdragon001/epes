<?php
include 'db_connect.php';
$month = date('m');
$day = date('d');
$year = date('Y');
$today = $year . '-' . $month . '-' . $day;

$qry = $conn->query("SELECT * FROM position_list WHERE id = {$_GET['id']}") or die(mysqli_error($conn));
foreach($qry->fetch_array() as $k => $v){
    $$k = $v;
}
?>
<div class="container-fluid">
    <form action="" id="manage-rank">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="form-group">
            <label for="" class="control-label">Position</label>
            <input type="text" class="form-control" name="position" value="<?php echo isset($position) ? $position : '' ?>" required>
        </div>
    </form>
</div>
<script>
$('#manage-rank').submit(function(e){
    e.preventDefault()
    start_load()
    $.ajax({
        url:'ajax.php?action=save_academic_rank',
        method:'POST',
        data:$(this).serialize(),
        success:function(resp){
            if(resp==1){
                alert_toast("Data successfully saved",'success')
                setTimeout(function(){
                    end_load()
                    $('.modal').modal('close')
                    location.reload()
                },1500)
            } else {
                alert_toast("Error saving data",'danger')
                end_load()
            }
        }
    })
})
</script>
