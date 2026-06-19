<?php include 'db_connect.php' ?>
<?php
if(isset($_GET['id'])){
	$stmt = $conn->prepare("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM employee_list where id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$qry = $stmt->get_result()->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}
$stmt_desig = $conn->prepare("SELECT * FROM designation_list where id = ?");
$stmt_desig->bind_param("i", $designation_id);
$stmt_desig->execute();
$designation = $stmt_desig->get_result();
$designation = $designation->num_rows > 0 ? $designation->fetch_array()['designation'] : 'Unknown Designation';
$stmt_pos = $conn->prepare("SELECT * FROM position_list where id = ?");
$stmt_pos->bind_param("i", $position_id);
$stmt_pos->execute();
$position = $stmt_pos->get_result();
$position = $position->num_rows > 0 ? $position->fetch_array()['position'] : 'Unknown Position';
$stmt_dept = $conn->prepare("SELECT * FROM department_list where id = ?");
$stmt_dept->bind_param("i", $department_id);
$stmt_dept->execute();
$department = $stmt_dept->get_result();
$department = $department->num_rows > 0 ? $department->fetch_array()['department'] : 'Unknown Department';
$stmt_eval = $conn->prepare("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM evaluator_list where id = ?");
$stmt_eval->bind_param("i", $evaluator_id);
$stmt_eval->execute();
$evaluator = $stmt_eval->get_result();
$evaluator = $evaluator->num_rows > 0 ? $evaluator->fetch_array()['name'] : 'Unknown Evaluator';
}
?>
<div class="container-fluid">
	<div class="card card-widget widget-user shadow">
      <div class="widget-user-header bg-dark">
        <h3 class="widget-user-username"><?php echo ucwords($name) ?></h3>
        <h5 class="widget-user-desc"><?php echo $email ?></h5>
      </div>
      <div class="widget-user-image">
      	<?php if(empty($avatar) || (!empty($avatar) && !is_file('assets/uploads/'.$avatar))): ?>
      	<span class="brand-image img-circle elevation-2 d-flex justify-content-center align-items-center bg-primary text-white font-weight-500" style="width: 90px;height:90px"><h4><?php echo strtoupper(substr($firstname, 0,1).substr($lastname, 0,1)) ?></h4></span>
      	<?php else: ?>
        <img class="img-circle elevation-2" src="assets/uploads/<?php echo $avatar ?>" alt="User Avatar"  style="width: 90px;height:90px;object-fit: cover">
      	<?php endif ?>
      </div>
      <div class="card-footer">
        <div class="container-fluid">
        	<dl>
        		<dt>Department</dt>
        		<dd><?php echo $department ?></dd>
        	</dl>
          <dl>
            <dt>Designation</dt>
            <dd><?php echo $designation ?></dd>
          </dl>
          <dl>
            <dt>Position</dt>
            <dd><?php echo $position ?></dd>
          </dl>
          <dl>
            <dt>Evaluator</dt>
            <dd><?php echo ucwords($evaluator) ?></dd>
          </dl>
        </div>
    </div>
	</div>
</div>
<div class="modal-footer display p-0 m-0">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
<style>
	#uni_modal .modal-footer{
		display: none
	}
	#uni_modal .modal-footer.display{
		display: flex
	}
</style>