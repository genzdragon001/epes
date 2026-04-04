<?php
ob_start();
date_default_timezone_set("Asia/Manila");

$action = $_GET['action'];
include 'admin_class.php';
$crud = new Action();

//new function
if($action == 'forgot_password'){
	$forgot_password = $crud->forgot_password();
	if($forgot_password)
		echo $forgot_password;
}

if($action == 'reset_password'){
	$reset_password = $crud->reset_password();
	if($reset_password)
		echo $reset_password;
}
if($action == 'reset_employee'){
	$reset_employee = $crud->reset_employee();
	if($reset_employee)
		echo $reset_employee;
}
if($action == 'reset_evaluator'){
	$reset_evaluator = $crud->reset_evaluator();
	if($reset_evaluator)
		echo $reset_evaluator;
}
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login;
}
if($action == 'login2'){
	$login = $crud->login2();
	if($login)
		echo $login;
}
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}
if($action == 'logout2'){
	$logout = $crud->logout2();
	if($logout)
		echo $logout;
}

if($action == 'signup'){
	$save = $crud->signup();
	if($save)
		echo $save;
}
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save;
}
if($action == 'update_user'){
	$save = $crud->update_user();
	if($save)
		echo $save;
}
if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save;
}
if($action == 'save_department'){
	$save = $crud->save_department();
	if($save)
		echo $save;
}
if($action == 'delete_department'){
	$save = $crud->delete_department();
	if($save)
		echo $save;
}
if($action == 'save_designation'){
	$save = $crud->save_designation();
	if($save)
		echo $save;
}
if($action == 'delete_designation'){
	$save = $crud->delete_designation();
	if($save)
		echo $save;
}
if($action == 'save_employee'){
	$save = $crud->save_employee();
	if($save)
		echo $save;
}
if($action == 'delete_employee'){
	$save = $crud->delete_employee();
	if($save)
		echo $save;
}
if($action == 'save_evaluator'){
	$save = $crud->save_evaluator();
	if($save)
		echo $save;
}
if($action == 'delete_evaluator'){
	$save = $crud->delete_evaluator();
	if($save)
		echo $save;
}
if($action == 'save_academic_rank'){
	$save = $crud->save_academic_rank();
	if($save)
		echo $save;
}
if($action == 'delete_academic_rank'){
	$save = $crud->delete_academic_rank();
	if($save)
		echo $save;
}
if($action == 'update_evaluator_department'){
	$save = $crud->update_evaluator_department();
	if($save)
		echo $save;
}
if($action == 'save_task'){
	$save = $crud->save_task();
	if($save)
		echo $save;
}
if($action == 'delete_task'){
	$save = $crud->delete_task();
	if($save)
		echo $save;
}
if($action == 'get_exemptions'){
	$get = $crud->get_exemptions();
	if($get)
		echo $get;
}
if($action == 'save_exemption'){
	$save = $crud->save_exemption();
	if($save)
		echo $save;
}
if($action == 'delete_exemption'){
	$save = $crud->delete_exemption();
	if($save)
		echo $save;
}
if($action == 'save_progress'){
	$save = $crud->save_progress();
	if($save)
		echo $save;
}
if($action == 'delete_progress'){
	$save = $crud->delete_progress();
	if($save)
		echo $save;
}
if($action == 'save_evaluation'){
	$save = $crud->save_evaluation();
	if($save)
		echo $save;
}
if($action == 'delete_evaluation'){
	$save = $crud->delete_evaluation();
	if($save)
		echo $save;
}
if($action == 'get_emp_tasks'){
	$get = $crud->get_emp_tasks();
	if($get)
		echo $get;
}
if($action == 'get_progress'){
	$get = $crud->get_progress();
	if($get)
		echo $get;
}
if($action == 'get_report'){
	$get = $crud->get_report();
	if($get)
		echo $get;
}
if($action == 'save_rating'){
	$save = $crud->save_rating();
	if($save)
		echo $save;
}
if($action == 'save_status'){
	$save_status = $crud->save_status();
	if($save_status)
		echo $save_status;
}
if($action == 'delete_file'){
	$delete_file = $crud->delete_file();
	if($delete_file)
		echo $delete_file;
}
if($action == 'update_semester'){
	$update_semester = $crud->update_semester();
	if($update_semester)
		echo $update_semester;
}

if($action == 'fetch_user_by_id'){
	$fetch_user_by_id = $crud->fetch_user_by_id();
	if($fetch_user_by_id)
		echo $fetch_user_by_id;
}
if($action == 'register_user'){
	$register_user = $crud->register_user();
	if($register_user)
		echo $register_user;
}

if($action == 'save_comment'){
	$save_comment = $crud->save_comment();
	if($save_comment)
		echo $save_comment;
}
if($action == 'submit_file'){
	$submit_file = $crud->submit_file();
	if($submit_file)
		echo $submit_file;
}
if($action == 'save_renewal_recommendation'){
	echo $crud->save_renewal_recommendation();
}
if($action == 'submit_dean_decision'){
	echo $crud->submit_dean_decision();
}
if($action == 'get_renewal_recommendations'){
	echo $crud->get_renewal_recommendations();
}
if($action == 'get_rec_details'){
	echo $crud->get_rec_details();
}
if($action == 'update_rec'){
	echo $crud->update_rec();
}
if($action == 'delete_rec'){
	echo $crud->delete_rec();
}
if($action == 'save_function_category'){
	echo $crud->save_function_category();
}
if($action == 'get_function_category'){
	echo $crud->get_function_category();
}
if($action == 'delete_function_category'){
	echo $crud->delete_function_category();
}
if($action == 'save_percentage_allocation'){
	echo $crud->save_percentage_allocation();
}
if($action == 'get_percentage_allocation'){
	echo $crud->get_percentage_allocation();
}
if($action == 'delete_percentage_allocation'){
	echo $crud->delete_percentage_allocation();
}
if($action == 'save_percentage_allocation_quick'){
	echo $crud->save_percentage_allocation_quick();
}
ob_end_flush();
?>
