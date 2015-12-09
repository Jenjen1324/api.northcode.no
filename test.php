<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("IN_PHPBB", true);
define("ROOT_PATH", "../forum.cobaltvault.no");
DEFINE('NC_ROOT_URL','http://api.northcode.no/v2');
define("API_CODE", "d4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35");

if (!defined('IN_PHPBB') || !defined('ROOT_PATH')) {
    die("PHPBB INSTALLATION NOT FOUND");
}


if (API_CODE == "") {
	die("NC API CODE MISSING");
}

@session_start();

$phpEx = "php";
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : ROOT_PATH . '/';
include($phpbb_root_path . 'common.' . $phpEx);
include("../forum.cobaltvault.no/includes/functions_user.php");
require_once("v2/priv/connectbb.php");
require_once("v2/priv/connectnc.php");

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



$user->session_begin();
$auth->acl($user->data);

foreach($data as $d)
{
	$user_row = array(
	    'username'              => $d['username'],
	    'user_email'            => $d['user_email'],
	    'group_id'              => 3,
	    'user_type'             => 3,
	    'user_regdate'          => $d['user_regdate']
	);

	$stmt = $mysqlbb->prepare("SELECT user_id FROM f_users WHERE username = ?");
	
	$stmt->bind_param('s',$d['username']);
	$stmt->execute();
	$stmt->store_result();
	$val = $stmt->num_rows;
	$stmt->fetch();
	$stmt->close();


	if($val < 1) {
		$uid = user_add($user_row);
		$uids[] = $uid;
	} else {
		$skips[] = $d['username'];
	}
}

print_r($uids);
print_r($skips);

 ?>