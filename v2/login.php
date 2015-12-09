<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("IN_PHPBB", true);
define("ROOT_PATH", "../../forum.cobaltvault.no");

if (!defined('IN_PHPBB') || !defined('ROOT_PATH')) {
    exit();
}

$phpEx = "php";
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : ROOT_PATH . '/';
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);

//print_r($user);

$auth->login("Northcode", "y-dfgh10-.", true, 1, 0);

print_r($user);
print_r($auth);

