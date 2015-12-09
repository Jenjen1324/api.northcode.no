<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("priv/connectbb.php");
require_once("server_api.php");




header("Content-Type: application/json");
$api_caller;
if(!check_api_access($api_caller)) {
	die(json_encode($api_caller));
}


if (isset($_POST['id']) || isset($_POST['username']) || isset($_POST['oid'])) {
	if(isset($_POST['id'])) {
		$data = $_POST['id'];
		$q = 'user_id';
	} elseif (isset($_POST['username'])) {
		$data = '\'' . strtolower($_POST['username']) .'\'';
		$q = 'username_clean';
	} elseif (isset($_POST['oid'])) {
		// Get uid from transition table

	}
	$q = "SELECT * FROM f_users WHERE $q = $data LIMIT 1";
	$result = $mysqlbb->query($q); // TODO: INJECTION PREVENTION
	
	if($row = $result->fetch_assoc())
	{
		echo json_encode($row);
	}
	else
	{
		echo json_encode(array('error' => 'user not found'));
	}
	$result->free();
} else {
	echo json_encode(array('error' => 'no data provided'));
}

exit();