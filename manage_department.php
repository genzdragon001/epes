<?php
include 'db_connect.php';
if(isset($_GET['id'])){
	$stmt = $conn->prepare("SELECT * FROM department_list where id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$qry = $stmt->get_result()->fetch_array();
	foreach($qry as $k => $v){
		$$k = $v;
	}
	$stmt->close();
}
?>
<div class="container-fluid">
	<form action="" id="manage-department">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<div id="msg" class="form-group"></div>
		<div class="form-group">
			<label for="department" class="control-label">Department</label>
			<input type="text" class="form-control form-control-sm" name="department" id="department" value="<?php echo isset($department) ? $department : '' ?>">
		</div>
		<div class="form-group">
			<label for="college_office_id" class="control-label">College / Office</label>
			<select name="college_office_id" id="college_office_id" class="form-control form-control-sm">
				<option value="">— None —</option>
				<?php
				$colleges = $conn->query("SELECT * FROM college_office_list WHERE is_active = 1 ORDER BY name ASC");
				while($co = $colleges->fetch_assoc()):
				?>
				<option value="<?php echo $co['id'] ?>" <?php echo isset($college_office_id) && $college_office_id == $co['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($co['name']) ?><?php echo $co['code'] ? ' ('.$co['code'].')' : '' ?></option>
				<?php endwhile; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="description" class="control-label">Description</label>
			<textarea name="description" id="description" cols="30" rows="4" class="form-control"><?php echo isset($description) ? $description : '' ?></textarea>
		</div>
	</form>
</div>
<script>
	$(document).ready(function(){
		$('#manage-department').submit(function(e){
			e.preventDefault();
			start_load()
			$('#msg').html('')
			$.ajax({
				url:'ajax.php?action=save_department',
				method:'POST',
				data:$(this).serialize(),
				success:function(resp){
					if(resp == 1){
						alert_toast("Data successfully saved.","success");
						setTimeout(function(){
							location.reload()
						},1750)
					}else if(resp == 2){
						$('#msg').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> Department already exist.</div>')
						end_load()
					}
				}
			})
		})
	})

</script>