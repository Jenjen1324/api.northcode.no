<?php

include_once("../mysql/connect.php");

function check_api_access(&$api_data = null) {
	if(!isset($_POST['api_code'])) {
		$api_data = array("error" => "no api code provided");
		return false;
	}
	$api_code = $_POST['api_code'];

	$data = get_api_caller_data($api_code);
	$api_data = $data;
	if(isset($data['error'])) {
		return false;
	} else {
		return true;
	}
}

function get_api_caller_data($code) {
	global $mysql;
	$q = $mysql->prepare("SELECT count(*),id,name,code from api_access where code = ?");
	$q->bind_param('i',$code);
	$q->execute();
	$q->bind_result($count,$id,$name,$qcode);
	$q->fetch();
	if ($count > 0) {
		# code...
		$q->close();
		return array("id" => $id, "name" => $name, "code" => $code);
	}
	else {
		return array("error" => "no api user found");
	}
}

function log_api_action($uid,$action) {
	global $mysql;
	if(isset($_POST['user_ip']) && isset($_POST['user_agent'])) {
		$q = $mysql->prepare("INSERT INTO api_access_log (uid,action,ip,agent) VALUES (?,?,?,?)");
		$q->bind_param('isss',$uid,$action,$_POST['user_ip'],$_POST['user_agent']);
		$q->execute();
		$q->close();
	} else {
		$q = $mysql->prepare("INSERT INTO api_access_log (uid,action) VALUES (?,?)");
		$q->bind_param('is',$uid,$action);
		$q->execute();
		$q->close();
	}
}

?>