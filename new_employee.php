<?php
require_once 'csrf_helper.php';
?>
<div class="col-lg-12">
	<div class="card">
		<div class="card-body">
			<form action="" id="manage_employee">
				<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
				<?php echo csrf_field(); ?>
				<div class="row">
					<div class="col-md-6 border-right">
						<div class="form-group">
							<label for="" class="control-label">First Name</label>
							<input type="text" name="firstname" class="form-control form-control-sm" required value="<?php echo isset($firstname) ? $firstname : '' ?>">
						</div>
						<div class="form-group">
							<label for="" class="control-label">Middle Name (optional)</label>
							<input type="text" name="middlename" class="form-control form-control-sm" value="<?php echo isset($middlename) ? $middlename : '' ?>">
						</div>
						<div class="form-group">
							<label for="" class="control-label">Last Name</label>
							<input type="text" name="lastname" class="form-control form-control-sm" required value="<?php echo isset($lastname) ? $lastname : '' ?>">
						</div>
						<div class="form-group">
							<label for="" class="control-label">Department</label>
							<select name="department_id" id="department_id" class="form-control form-control-sm select2">
								<option value=""></option>
								<?php 
								$departments = $conn->query("SELECT * FROM department_list order by department asc");
								while($row=$departments->fetch_assoc()):
								?>
								<option value="<?php echo $row['id'] ?>" <?php echo isset($department_id) && $department_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['department'] ?></option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="form-group">
							<label for="" class="control-label">Designation</label>
							<select name="designation_id" id="designation_id" class="form-control form-control-sm select2">
								<option value=""></option>
								<?php 
								$designations = $conn->query("SELECT * FROM designation_list order by designation asc");
								while($row=$designations->fetch_assoc()):
								?>
								<option value="<?php echo $row['id'] ?>" <?php echo isset($designation_id) && $designation_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['designation'] ?></option>
								<?php endwhile; ?>
							</select>
						</div>

						<div class="form-group">
							<label for="" class="control-label">Position</label>
							<select name="position_id" id="position_id" class="form-control form-control-sm select2">
								<option value=""></option>
								<?php 
								$position = $conn->query("SELECT * FROM position_list order by position asc");
								while($row=$position->fetch_assoc()):
								?>
								<option value="<?php echo $row['id'] ?>" <?php echo isset($position_id) && $position_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['position'] ?></option>
								<?php endwhile; ?>
							</select>
						</div>

						<div class="form-group">
							<label for="" class="control-label">Evaluator</label>
							<select name="evaluator_id" id="evaluator_id" class="form-control form-control-sm select2">
								<option value=""></option>
								<?php 
								$evaluators = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM evaluator_list order by concat(lastname,', ',firstname,' ',middlename) asc");
								while($row=$evaluators->fetch_assoc()):
								?>
								<option value="<?php echo $row['id'] ?>" <?php echo isset($evaluator_id) && $evaluator_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['name'] ?></option>
								<?php endwhile; ?>
							</select>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="" class="control-label">Avatar</label>
							<div class="custom-file">
		                      <input type="file" class="custom-file-input" id="customFile" name="img" onchange="displayImg(this,$(this))">
		                      <label class="custom-file-label" for="customFile">Choose file</label>
		                    </div>
						</div>
						<div class="form-group d-flex justify-content-center align-items-center">
							<img src="<?php echo isset($avatar) ? 'assets/uploads/'.$avatar :'' ?>" alt="Avatar" id="cimg" class="img-fluid img-thumbnail ">
						</div>
						<div class="form-group">
							<label class="control-label">Email</label>
							<input type="email" class="form-control form-control-sm" name="email" required value="<?php echo isset($email) ? $email : '' ?>">
							<small id="#msg"></small>
						</div>
						<div class="form-group">
							<label class="control-label">Password</label>
							<input type="password" class="form-control form-control-sm" name="password" <?php echo !isset($id) ? "required":'' ?>>
							<small><i><?php echo isset($id) ? "Leave this blank if you dont want to change you password":'' ?></i></small>
						</div>
						<div class="form-group">
							<label class="label control-label">Confirm Password</label>
							<input type="password" class="form-control form-control-sm" name="cpass" <?php echo !isset($id) ? 'required' : '' ?>>
							<small id="pass_match" data-status=''></small>
						</div>

						<div class="form-group">
							<label class="control-label">Account Status</label><br>
							<b>Failed Login Attempts: </b> <?php echo isset($failed_login) ? $failed_login : 0; ?><br>
							<b>Blocked: </b> <?php echo isset($isBlocked) && $isBlocked == 1 ? '<span class="text-danger">Yes</span>' : '<span class="text-success">No</span>'; ?>
						</div>

						<?php if(isset($id) && isset($isBlocked) && $isBlocked == 1): ?>
							<div class="form-group">
								<button type="button" class="btn btn-warning btn-sm" id="reset_employee">Unblock & Reset Failed Login</button>
							</div>
						<?php endif; ?>																

					</div>
				</div>
				<hr>
				<div class="col-lg-12 text-right justify-content-center d-flex">
					<button class="btn btn-primary mr-2">Save</button>
					<button class="btn btn-secondary" type="button" onclick="location.href = 'index.php?page=employee_list'">Cancel</button>
				</div>
			</form>
		</div>
	</div>
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
	$('[name="password"],[name="cpass"]').keyup(function(){
		var pass = $('[name="password"]').val()
		var cpass = $('[name="cpass"]').val()
		if(cpass == '' ||pass == ''){
			$('#pass_match').attr('data-status','')
		}else{
			if(cpass == pass){
				$('#pass_match').attr('data-status','1').html('<i class="text-success">Password Matched.</i>')
			}else{
				$('#pass_match').attr('data-status','2').html('<i class="text-danger">Password does not match.</i>')
			}
		}
	})
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	$('#manage_employee').submit(function(e){
		e.preventDefault()
		$('input').removeClass("border-danger")
		start_load()
		$('#msg').html('')
		if($('[name="password"]').val() != '' && $('[name="cpass"]').val() != ''){
			if($('#pass_match').attr('data-status') != 1){
				if($("[name='password']").val() !=''){
					$('[name="password"],[name="cpass"]').addClass("border-danger")
					end_load()
					return false;
				}
			}
		}
		$.ajax({
			url:'ajax.php?action=save_employee',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				if(resp == 1){
					alert_toast('Data successfully saved.',"success");
					setTimeout(function(){
						location.replace('index.php?page=employee_list')
					},750)
				}else if(resp == 2){
					$('#msg').html("<div class='alert alert-danger'>Email already exist.</div>");
					$('[name="email"]').addClass("border-danger")
					end_load()
				}
			}
		})
	})

	$('#reset_employee').click(function(){
    if(confirm("Are you sure you want to unblock and reset failed login attempts for this user?")){
        start_load()
        $.ajax({
            url:'ajax.php?action=reset_employee',
            method:'POST',
            data:{id: '<?php echo isset($id) ? $id : '' ?>'},
            success:function(resp){
                console.log("Server response:", resp);
                if(resp == 1){
                    alert_toast("Account successfully unblocked and reset.","success")
                    setTimeout(function(){
                        location.reload()
                    },800)
                }else{
                    alert_toast("Failed to reset account.","danger")
                    end_load()
                }
            },
            error:function(xhr, status, error){
                console.log("AJAX error:", status, error);
                alert_toast("An error occurred. Check console for details.","danger")
                end_load()
            }
        })
    }
})

$('#position_id').change(function(){
    var position_id = $(this).val();
    var cos_position_id = 19;
    
    if (position_id == cos_position_id) {
        $('#designation_id').val(3).trigger('change');
        $('#designation_id').prop('disabled', true);
    } else {
        $('#designation_id').prop('disabled', false);
    }
});

$(document).ready(function(){
    var current_position_id = $('#position_id').val();
    var cos_position_id = 19;
    
    if (current_position_id == cos_position_id) {
        $('#designation_id').val(3).trigger('change');
        $('#designation_id').prop('disabled', true);
    }
});


</script>