<?php 
include('db_connect.php');
session_start();
if(isset($_GET['id'])){
$type = array("employee_list","evaluator_list","users");
$user = $conn->query("SELECT * FROM {$type[$_SESSION['login_type']]} where id =".$_GET['id']);
foreach($user->fetch_array() as $k =>$v){
	$meta[$k] = $v;
}
}
?>
<div class="container-fluid">
	<div id="msg"></div>
	
	<form action="" id="manage-user">	
		<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
		<div class="form-group">
			<label for="name">First Name</label>
			<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="name">Last Name</label>
			<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="email">Email</label>
			<input type="text" name="email" id="email" class="form-control" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" required  autocomplete="off">
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" class="form-control" value="" autocomplete="off">
			<small><i>Leave this blank if you dont want to change the password.</i></small>
		</div>

		<?php if ($_SESSION['login_type'] != 2): ?>

	<div class="form-group">
    <label for="position_id">Position</label>
    <select name="position_id" id="position_id" class="form-control" required>
        <option value="">-- Select Position --</option>
        <?php
        $position_qry = $conn->query("SELECT id, position FROM position_list ORDER BY id ASC");
        while ($row = $position_qry->fetch_assoc()):
            $selected = (isset($meta['position_id']) && $meta['position_id'] == $row['id']) ? 'selected' : '';
        ?>
            <option value="<?php echo $row['id'] ?>" <?php echo $selected ?>>
                <?php echo htmlspecialchars($row['position']) ?>
            </option>
        <?php endwhile; ?>
	</select>
	</div>

	<div class="form-group">
    <label for="department_id">Department</label>
    <select name="department_id" id="department_id" class="form-control" required>
        <option value="">-- Select Department --</option>
        <?php
        $dept_qry = $conn->query("SELECT id, department, description FROM department_list ORDER BY id ASC");
        while ($row = $dept_qry->fetch_assoc()):
            $selected = (isset($meta['department_id']) && $meta['department_id'] == $row['id']) ? 'selected' : '';
        ?>
            <option value="<?php echo $row['id'] ?>" <?php echo $selected ?>>
                <?php echo htmlspecialchars($row['department']) . " - " . htmlspecialchars($row['description']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

	<div class="form-group" id="designation_group">
    <label for="designation_id">Designation</label>
    <select name="designation_id" id="designation_id" class="form-control">
        <?php
        $desig_qry = $conn->query("SELECT id, designation, description FROM designation_list ORDER BY id ASC");
        while ($row = $desig_qry->fetch_assoc()):
            $selected = (isset($meta['designation_id']) && $meta['designation_id'] == $row['id']) ? 'selected' : '';
        ?>
            <option value="<?php echo $row['id'] ?>" <?php echo $selected ?>>
                <?php echo htmlspecialchars($row['designation']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    <small class="text-muted" id="designation_note" style="display:none;">Contract of Service faculty cannot have a designation</small>
</div>
<?php endif; ?>
		<div class="form-group">
			<label for="" class="control-label">Avatar</label>
			<div class="custom-file">
              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
              <label class="custom-file-label" for="customFile">Choose file</label>
            </div>
		</div>
		<div class="form-group d-flex justify-content-center">
			<img src="<?php echo isset($meta['avatar']) ? 'assets/uploads/'.$meta['avatar'] :'' ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
		</div>
		

	</form>
</div>
<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: cover;
		border-radius: 100% 100%;
	}
</style>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	$('#manage-user').submit(function(e){
		e.preventDefault();
		start_load()
		$.ajax({
			url:'ajax.php?action=update_user',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				if(resp ==1){
					alert_toast("Data successfully saved",'success')
					setTimeout(function(){
						location.reload()
					},1500)
				}else{
					$('#msg').html('<div class="alert alert-danger">Username already exist</div>')
					end_load()
				}
			}
		})
	})

	$('#position_id').change(function(){
		var positionId = $(this).val();
		var cosPositionIds = [19];
		if (cosPositionIds.includes(parseInt(positionId))) {
			$('#designation_id').val('').prop('disabled', true);
			$('#designation_note').show();
		} else {
			$('#designation_id').prop('disabled', false);
			$('#designation_note').hide();
		}
	}).trigger('change');

	$('#manage-user').on('submit', function(){
		var positionId = $('#position_id').val();
		var cosPositionIds = [19];
		if (cosPositionIds.includes(parseInt(positionId))) {
			$('#designation_id').val('');
		}
	});

</script>