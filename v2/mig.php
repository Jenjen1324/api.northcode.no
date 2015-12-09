<?php 
require_once("priv/connectbb.php");
require_once("priv/connectnc.php");

header("Content-Type: application/json");
$result = $mysql->query("SELECT * FROM users");

while($row = $result->fetch_assoc())
{
	$data[] = array('username' => $row['username'],
		'username_clean' => strtolower($row['username']),
		'user_email' => $row['email'],
		'user_rank' => $row['rank'] > 1 ? 1 : 0,
		'user_regdate' => strtotime($row['registered']) ? strtotime($row['registered']) : time());
}


$result->free();

 ?>