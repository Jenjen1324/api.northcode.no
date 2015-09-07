<?php 
/*
 * Legacy
include_once("../utils.php");

enable_errors();
*/

include_once("priv/connect.php");
include_once("server_api.php");

header("Content-Type: application/json");

$api_caller;
if(!check_api_access($api_caller)) {
	die(json_encode($api_caller));
}

if (isset($_POST['id']) || isset($_POST['username'])) {
	# code...



	if(isset($_POST['id']))
	{
		$id = $_POST['id'];
		$str = "SELECT users.id,users.username,users.email,users.rank,DATE_FORMAT(users.registered,'%Y-%m-%d'),user_titles.title,users.info from users left join user_titles on user_titles.id = users.rank where users.id = ?";
		$p = 'i';
	} elseif (isset($_POST['username'])) {
		$id = $_POST['username'];
		$str = "SELECT users.id,users.username,users.email,users.rank,DATE_FORMAT(users.registered,'%Y-%m-%d'),user_titles.title,users.info from users left join user_titles on user_titles.id = users.rank where users.username = ?";
		$p = 's';
	} else { die("WTF"); }
	
	$q = $mysql->prepare($str);
	$q->bind_param($p,$id);
	$q->execute();
	$q->bind_result($qid,$qusername,$qemail,$qimg,$qregistered,$qtitle,$qinfo);

	$q->fetch();
	# code...
	$data = array("uid" => $qid,"username" => $qusername,"email" => $qemail,"img" => $qimg,"info" => $qinfo,"title" => $qtitle,"registered" => $qregistered);

	
	echo json_encode($data);
} else {

	$q = $mysql->prepare("SELECT users.id as id,username,email,rank,user_titles.title as title from users left join user_titles on user_titles.id = users.rank");
	$q->execute();
	$q->bind_result($qid,$qusername,$qemail,$qimg,$qinfo);

	$data = array();

	while ($q->fetch()) {
	# code...
		$arr = array("uid" => $qid,"username" => $qusername,"email" => $qemail,"img" => $qimg,"info" => $qinfo);
		array_push($data, $arr);
	}
	echo json_encode($data);
}







?>