<?php 
session_start();

include_once("priv/connect.php");
include_once("server_api.php");

$ajax = false;

$ssid;
$code; 

if(isset($_GET['ssid'])) {
	$ssid = $_GET['ssid'];
}

if(isset($_POST['ssid'])) {
	$ssid = $_POST['ssid'];
}

if(isset($_GET['code'])) {
	$code = $_GET['code'];
}

if(isset($_POST['code'])) {
	$code = $_POST['code'];
}

if(isset($_SESSION['ssid'])) {
	$ssid = $_SESSION['ssid'];
	$code = $_SESSION['code'];
}

if (isset($_GET['ajax'])) {
	$ajax = true;
	header("Content-Type: application/json");

	$api_caller;
	if(!check_api_access($api_caller)) {
		die(json_encode($api_caller));
	}
	log_api_action($api_caller['id'],"logging out of session: ".$ssid);
}

$status = false;

$active = 0;

$session_deactivate_q = $mysql->prepare("UPDATE loginsessions set active = ? where id = ? and sessioncode = ?");

$session_deactivate_q->bind_param("iii",$active,$ssid,$code);

$session_deactivate_q->execute();

$session_deactivate_q->close();

unset($_SESSION['id']);
unset($_SESSION['ip']);
unset($_SESSION['username']);
unset($_SESSION['email']);
unset($_SESSION['ssid']);
unset($_SESSION['title']);
unset($_SESSION['code']);
session_destroy();

$status = true;

if (isset($_SESSION['id'])) {
	$status = false;
}



if (!$ajax) {
	header("Location: ../index.php");
}
else
{
	echo json_encode(array("success" => $status));
}

?>