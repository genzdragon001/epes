<?php
include 'db_connect.php';
$stmt = $conn->prepare("SELECT * FROM evaluator_list where id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$qry = $stmt->get_result()->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}
include 'new_evaluator.php';
?>