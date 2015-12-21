<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

if (!isset($_SERVER["HTTP_HOST"])) {
  parse_str($argv[1], $_GET);
  parse_str($argv[1], $_POST);
}

include_once("priv/connect.php");
include_once("priv/passwordhash.php");
include_once("server_api.php");

function updatePassword($uid, $password) {
	global $mysql;

	$options = [
	    'cost' => 11,
	    'salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM),
	];
	$pwd_h = password_hash($password, PASSWORD_BCRYPT, $options);

	$stmt = $mysql->prepare("UPDATE users set password = ?, password_salt = ?, state = 3 where id = ?");
	$stmt->bind_param('ssi', $pwd_h, $options['salt'], $uid);
	$stmt->execute();
	$stmt->close();
}

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
	$login = false;

	// Old Password hashing method check
	$stmt = $mysql->prepare("SELECT state,password,password_salt,id from users where (username = ? or email = ?)");
	$stmt->bind_param('ss', $username, $username);
	$stmt->execute();
	$stmt->bind_result($method, $password_h, $password_salt, $uid);
	$stmt->fetch();
	$stmt->close();

	if(isValidMd5($password_h)) {
		$cv_hash = cv_hash($password);
		if($password_h == $cv_hash) {
			updatePassword($uid, $password);
			$login = true;
		}
	} elseif ($method != 3) {
		$nc_hash = hashpass($password);
		if($password_h == $nc_hash) {
			updatePassword($uid, $password);
			$login = true;
		}
	} else {
		$options = [
		    'cost' => 11,
		    'salt' => $password_salt,
		];
		$pwd_h = password_hash($password, PASSWORD_BCRYPT, $options);
		if($password_h == $pwd_h) {
			$login = true;
		}
	}

	if($login) {
		$ip			= stripslashes($_SERVER['REMOTE_ADDR']);
		$login_q 	= $mysql->prepare("SELECT users.id as id,username,email,rank,user_titles.title as title from users left join user_titles on user_titles.id = users.rank where users.id = ?");
		$login_q->bind_param('i',$uid);
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
			// Legacy, should never happen but leaving it for now
			if (!$ajax) {
				echo "Login failed! Dafuq";
				echo '<META http-equiv="refresh" content="3;URL=../index.php" />';
			}
			else
			{
				echo json_encode(array("error" => "Invalid credentials!"));
			}

		}
	}
	else
	{
		if (!$ajax) {
			echo "Login failed! Invalid Credentials";
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