<?php include'db_connect.php' ?>
<div class="col-lg-12">
	<div class="card card-outline card-success">
		<div class="card-header">
			<h5 class="card-title"><i class="fa fa-user-shield"></i> Evaluator List</h5>
			<div class="card-tools">
				<a class="btn btn-success btn-sm" href="./index.php?page=new_evaluator"><i class="fa fa-plus"></i> Add New Evaluator</a>
			</div>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered" id="list">
					<thead class="thead-dark">
						<tr>
							<th class="text-center" style="width: 50px;">#</th>
							<th style="width: 25%;">Name</th>
							<th style="width: 15%;">Type</th>
							<th style="width: 15%;">Department</th>
							<th style="width: 20%;">Email</th>
							<th class="text-center" style="width: 100px;">Status</th>
							<th class="text-center" style="width: 120px;">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$departments = $conn->query("SELECT * FROM department_list");
						$dept_arr = [];
						while($d = $departments->fetch_assoc()){
							$dept_arr[$d['id']] = $d['department'];
						}
						$qry = $conn->query("SELECT ev.*, concat(ev.lastname,', ',ev.firstname,' ',ev.middlename) as name FROM evaluator_list ev WHERE ev.type IN ('0', '1') order by concat(ev.lastname,', ',ev.firstname,' ',ev.middlename) asc");
						while($row= $qry->fetch_assoc()):
							$type_text = $row['type'] == '1' ? 'Dean' : 'Department Head';
							$type_class = $row['type'] == '1' ? 'primary' : 'warning';
							$status_class = $row['isBlocked'] == 1 ? 'danger' : ($row['failed_login'] > 3 ? 'warning' : 'success');
							$status_text = $row['isBlocked'] == 1 ? 'Blocked' : ($row['failed_login'] > 3 ? 'Locked' : 'Active');
						?>
						<tr>
							<td class="text-center font-weight-bold"><?php echo $i++ ?></td>
							<td>
								<div class="d-flex align-items-center">
									<div class="avatar avatar-md mr-3">
										<span class="avatar-initial rounded-circle bg-success">
											<i class="fa fa-user-tie"></i>
										</span>
									</div>
									<div>
										<p class="mb-0 font-weight-bold"><?php echo ucwords($row['name']) ?></p>
										<small class="text-muted">ID: <?php echo $row['id'] ?></small>
									</div>
								</div>
							</td>
							<td>
								<span class="badge badge-<?php echo $type_class ?>"><?php echo $type_text ?></span>
							</td>
							<td>
								<select class="dept-select form-control form-control-sm" data-id="<?php echo $row['id'] ?>">
									<option value="0" <?php echo ($row['department_id'] ?? '') == '0' || $row['department_id'] == '' ? 'selected' : '' ?>>None</option>
									<?php foreach($dept_arr as $id => $name): ?>
									<option value="<?php echo $id ?>" <?php echo ($row['department_id'] ?? '') == $id ? 'selected' : '' ?>><?php echo $name ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><?php echo $row['email'] ?></td>
							<td class="text-center">
								<span class="badge badge-<?php echo $status_class ?>"><?php echo $status_text ?></span>
							</td>
							<td class="text-center">
								<div class="btn-group">
									<button type="button" class="btn btn-sm btn-default btn-flat border-info view_evaluator" data-id="<?php echo $row['id'] ?>" title="View">
										<i class="fa fa-eye text-info"></i>
									</button>
									<a href="./index.php?page=edit_evaluator&id=<?php echo $row['id'] ?>" class="btn btn-sm btn-default btn-flat border-primary" title="Edit">
										<i class="fa fa-edit text-primary"></i>
									</a>
									<button type="button" class="btn btn-sm btn-default btn-flat border-danger delete_evaluator" data-id="<?php echo $row['id'] ?>" title="Delete">
										<i class="fa fa-trash text-danger"></i>
									</button>
								</div>
							</td>
						</tr>	
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<style>
.card-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
.card-title { margin: 0; font-weight: 600; }
.avatar-initial {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
}
.btn-group .btn {
    padding: 5px 8px;
}
</style>

<script>
	$(document).ready(function(){
		$('#list').DataTable({
			"dom": 'Bfrtip',
			"buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
			"ordering": true,
			"order": [[1, 'asc']]
		});
		
		$(document).on('click', '.view_evaluator', function(){
			var id = $(this).attr('data-id');
			uni_modal("<i class='fa fa-id-card'></i> Evaluator Details","view_evaluator.php?id="+id,'modal-md')
		})
		
		$(document).on('click', '.delete_evaluator', function(){
			var id = $(this).attr('data-id');
			_conf("Are you sure to delete this evaluator?","delete_evaluator",[id])
		})
		
		$(document).on('change', '.dept-select', function(){
			var id = $(this).data('id');
			var dept_id = $(this).val();
			start_load();
			$.ajax({
				url: 'ajax.php?action=update_evaluator_department',
				method: 'POST',
				data: {id: id, department_id: dept_id},
				success: function(resp){
					if(resp == 1){
						alert_toast("Department updated successfully",'success');
					}else{
						alert_toast("Failed to update department",'danger');
					}
					end_load();
				}
			});
		});
	})
	
	function delete_evaluator($id){
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_evaluator',
			method: 'POST',
			data: {id: $id},
			success: function(resp){
				if(resp == 1){
					alert_toast("Data successfully deleted",'success')
					setTimeout(function(){
						location.reload()
					},1500)
				} else {
					end_load();
					alert_toast("Failed to delete evaluator",'danger')
				}
			},
			error: function(){
				end_load();
				alert_toast("Error occurred",'danger')
			}
		})
	}
</script>
