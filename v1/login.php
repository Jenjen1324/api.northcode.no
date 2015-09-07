<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

include_once("connect.php");
include_once("passwordhash.php");
include_once("../api/server_api.php");

$username;
$password;

$ajax = false;

if(isset($_GET['username']) and isset($_GET['password'])) {
	$username 	= stripslashes($_GET['username']);
	$password 	= stripslashes($_GET['password']);
}

if(isset($_POST['username']) and isset($_POST['password'])) {
	$username 	= stripslashes($_POST['username']);
	$password 	= stripslashes($_POST['password']);
}

if (isset($_GET['ajax'])) {
	$ajax = true;
	header("Content-Type: application/json");

	$api_caller;
	if(!check_api_access($api_caller)) {
		die(json_encode($api_caller));
	}
	log_api_action($api_caller['id'],"logging in to user: ".$username);
}

if ($username and $password) {

	//COBALTVAULT COMPABILITY SCRIPT -- CHANGE MD5 PASSWORD TO SHA256
	$cv_q = $mysql->prepare("SELECT password from users where username = ?");
	$cv_q->bind_param("s",$username);
	$cv_q->execute();
	$cv_q->bind_result($cv_pass);
	$cv_q->fetch();
	$cv_q->close();
	if (isValidMd5($cv_pass)) {
		$cv_hash = cv_hash($password);
		if($cv_pass == $cv_hash) {
			$cv_uq = $mysql->prepare("UPDATE users set password = ? where username = ?");
			$newpass = hashpass($password);
			$cv_uq->bind_param('ss',$newpass,$username);
			$cv_uq->execute();
			$cv_uq->close();
		}
	}
	//END SCRIPT


	$ip			= stripslashes($_SERVER['REMOTE_ADDR']);

	$login_q 	= $mysql->prepare("SELECT users.id as id,username,email,rank,user_titles.title as title from users left join user_titles on user_titles.id = users.rank where (username = ? or email = ?) and password = ?");

	$password 	= hashpass($password);

	$login_q->bind_param('sss',$username,$username,$password);

	$login_q->execute();

	$login_q->bind_result($id,$qusername,$qemail,$qrank,$qtitle);

	$login_q->fetch();

	$login_q->close();

	if ($id and $qusername and $qemail) {

		$active = 1;
		$sessioncode = rand(1000,999999);

		$_SESSION['id']		 	= $id;
		$_SESSION['ip']			= $ip;
		$_SESSION['username'] 	= $qusername;
		$_SESSION['email'] 		= $qemail;
		$_SESSION['rank']		= $qrank;
		$_SESSION['title']		= $qtitle;
		$_SESSION['code']		= $sessioncode;

		$session_q = $mysql->prepare("INSERT into loginsessions (ip,uid,active,sessioncode,login_time) VALUES (?,?,?,?,NOW())");

		$session_q->bind_param('siii',$ip,$id,$active,$sessioncode);

		$session_q->execute();

		$sid = $session_q->insert_id;

		$session_q->close();

		$_SESSION['ssid'] 	= $sid;

		if ($ajax) {
			echo json_encode(array("ssid" => $sid, "code" => $sessioncode));
		} else {
			header("Location: ../index.php");
		}
	}
	else
	{
		if (!$ajax) {
			echo "Login failed!";
			echo '<META http-equiv="refresh" content="3;URL=../index.php" />';
		}
		else
		{
			echo json_encode(array("error" => "Invalid credentials!"));
		}

	}
} else {
	if (!$ajax) {
		echo "No data provided!";
		echo '<META http-equiv="refresh" content="3;URL=../index.php" />';
	}
	else
	{
		echo json_encode(array("error" => "Missing data"));
	}
}

?>