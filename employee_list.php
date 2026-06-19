<?php include'db_connect.php' ?>
<div class="col-lg-12">
	<div class="card card-outline card-primary">
		<div class="card-header">
			<h5 class="card-title"><i class="fa fa-users"></i> Employee List</h5>
			<div class="card-tools">
				<a class="btn btn-primary btn-sm" href="./index.php?page=new_employee"><i class="fa fa-plus"></i> Add New Employee</a>
			</div>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered" id="list">
					<thead class="thead-dark">
						<tr>
							<th class="text-center" style="width: 50px;">#</th>
							<th style="width: 20%;">Name</th>
							<th style="width: 15%;">Email</th>
							<th style="width: 12%;">Position</th>
							<th style="width: 12%;">Department</th>
							<th style="width: 12%;">Designation</th>
							<th class="text-center" style="width: 100px;">Status</th>
							<th class="text-center" style="width: 120px;">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$designations = $conn->query("SELECT * FROM designation_list");
						$design_arr[0]= "Not Set";
						while($row=$designations->fetch_assoc()){
							$design_arr[$row['id']] =$row['designation'];
						}
						$departments = $conn->query("SELECT * FROM department_list");
						$dept_arr[0]= "Not Set";
						while($row=$departments->fetch_assoc()){
							$dept_arr[$row['id']] =$row['department'];
						}
						$position = $conn->query("SELECT * FROM position_list");
						$posi_arr[0]= "Not Set";
						while($row=$position->fetch_assoc()){
							$posi_arr[$row['id']] =$row['position'];
						}
						$qry = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM employee_list order by concat(lastname,', ',firstname,' ',middlename) asc");
						while($row= $qry->fetch_assoc()):
							$status_class = $row['isBlocked'] == 1 ? 'danger' : ($row['failed_login'] > 3 ? 'warning' : 'success');
							$status_text = $row['isBlocked'] == 1 ? 'Blocked' : ($row['failed_login'] > 3 ? 'Locked' : 'Active');
						?>
						<tr>
							<td class="text-center font-weight-bold"><?php echo $i++ ?></td>
							<td>
								<div class="d-flex align-items-center">
									<div class="avatar avatar-md mr-3">
										<span class="avatar-initial rounded-circle bg-primary">
											<i class="fa fa-user"></i>
										</span>
									</div>
									<div>
										<p class="mb-0 font-weight-bold"><?php echo ucwords($row['name']) ?></p>
										<small class="text-muted">ID: <?php echo $row['employee_id'] ?></small>
									</div>
								</div>
							</td>
							<td><?php echo $row['email'] ?></td>
							<td>
								<span class="badge badge-info"><?php echo isset($posi_arr[$row['position_id']]) ? $posi_arr[$row['position_id']] : 'Unknown' ?></span>
							</td>
							<td><?php echo isset($dept_arr[$row['department_id']]) ? $dept_arr[$row['department_id']] : 'Unknown' ?></td>
							<td><?php echo isset($design_arr[$row['designation_id']]) ? $design_arr[$row['designation_id']] : 'Not Set' ?></td>
							<td class="text-center">
								<span class="badge badge-<?php echo $status_class ?>"><?php echo $status_text ?></span>
							</td>
							<td class="text-center">
								<div class="btn-group">
									<button type="button" class="btn btn-sm btn-default btn-flat border-info view_employee" data-id="<?php echo $row['id'] ?>" title="View">
										<i class="fa fa-eye text-info"></i>
									</button>
									<a href="./index.php?page=edit_employee&id=<?php echo $row['id'] ?>" class="btn btn-sm btn-default btn-flat border-primary" title="Edit">
										<i class="fa fa-edit text-primary"></i>
									</a>
									<button type="button" class="btn btn-sm btn-default btn-flat border-danger delete_employee" data-id="<?php echo $row['id'] ?>" title="Delete">
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
.card-header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: white; }
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
		
		$(document).on('click', '.view_employee', function(){
			var id = $(this).attr('data-id');
			uni_modal("<i class='fa fa-id-card'></i> Employee Details","view_employee.php?id="+id,'modal-md')
		})
		
		$(document).on('click', '.delete_employee', function(){
			var id = $(this).attr('data-id');
			_conf("Are you sure to delete this employee?","delete_employee",[id])
		})
	})
	
	function delete_employee($id){
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_employee',
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
					alert_toast("Failed to delete employee",'danger')
				}
			},
			error: function(){
				end_load();
				alert_toast("Error occurred",'danger')
			}
		})
	}
</script>
