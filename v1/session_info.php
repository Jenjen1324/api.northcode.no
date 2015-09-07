<?php

include_once("priv/connect.php");
include_once("server_api.php");

$ssid = 0;
$code = 0;

header("Content-Type: application/json");

$api_caller;
if(!check_api_access($api_caller)) {
	die(json_encode($api_caller));
}

if (isset($_GET['ssid'])) {
	$ssid = $_GET['ssid'];
}

if (isset($_POST['ssid'])) {
	$ssid = $_POST['ssid'];
}

if (isset($_GET['code'])) {
	$code = $_GET['code'];
}

if (isset($_POST['code'])) {
	$code = $_POST['code'];
}

if(isset($_GET['logincheck'])) {
	$q = $mysql->prepare("SELECT active from loginsessions where id = ? and sessioncode = ?");
	$q->bind_param("ss",$ssid,$code);
	$q->execute();
	$q->bind_result($active);
	$q->fetch();
	$q->close();
	log_api_action($api_caller['id'],"checking for active session: ".$ssid);
	die(json_encode(array("active" => $active)));
}

log_api_action($api_caller['id'],"fetching session info for session: ".$ssid);

if($ssid != 0 && $code != 0) {
	//Get uid
	$session_uid_q = $mysql->prepare("SELECT uid from loginsessions where id = ? and sessioncode = ? and active = '1'");
	$session_uid_q->bind_param("ii",$ssid,$code);
	$session_uid_q->execute();
	$session_uid_q->bind_result($uid);
	$session_uid_q->fetch();

	if($uid != "") {
		$session_uid_q->close();

		//Get user info
		$user_info_q = $mysql->prepare("SELECT users.id as id,username,email,rank,user_titles.title as title from users left join user_titles on user_titles.id = users.rank where users.id = ?");
		$user_info_q->bind_param("i",$uid);
		$user_info_q->execute();
		$user_info_q->bind_result($id,$qusername,$qemail,$qrank,$qtitle);
		$user_info_q->fetch();
		$user_info_q->close();

		$user_info_array = array("uid" => $id, "username" => $qusername, "email" => $qemail, "rank" => $qrank, "title" => $qtitle);
		$user_info_json = json_encode($user_info_array);
		echo $user_info_json;
	}
	else
	{
		echo json_encode(array("error" => "parameters invalid or session not active: ssid: ".$ssid." , code: ".$code));
		$session_uid_q->close();
	}
}
else
{
	echo json_encode(array("error" => "no parameters specified"));
}


?>