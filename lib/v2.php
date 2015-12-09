<?php 

define("IN_PHPBB", true);
define("ROOT_PATH", "../../forum.cobaltvault.no");
DEFINE('NC_ROOT_URL','http://api.northcode.no/v2');
define("API_CODE", "");

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

$user->session_begin();
$auth->acl($user->data);



function post_call($url,$data) {
	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\nUser-Agent:NorthcodeAPI/1.0\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
			),
		);

	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	return $result;
}


class nc_session
{
	private $ssid;
	private $code;

	/**
	 * Constructor, logs user in
	 * @deprecated since v2
	 */
	public function __construct($username,$password,$loading = false) {
		self::login($username, $password);
	}

	public static function login($username, $password, $remember = false) {
		global $auth;
		$auth->login($username, $password, $remember, 1, 0);
	}

	public static function logout() {
		global $user;
		$user->session_kill();
		$user->session_begin();
	}

	public static function get_user_info() {
		global $user;
		return $user;
	}

	public static function is_logged_in() {
		if ($user->data['user_id'] == ANONYMOUS) {
		  return false;
		} else {
		   return true;
		}
	}

	/**
	 * @deprecated since v2
	 */
	public function get_user_info() {
		return self::get_user_info();
	}

	/**
	 * @deprecated since v2
	 */
	public function logout() {
		self::logout();
	}
	
	/**
	 * @deprecated since v2
	 */
	public function store_in_session() {
		return;
	}

	/**
	 * @deprecated since v2
	 */
	public static function load_from_session() {
		return;
	}

	/**
	 * @deprecated since v2
	 */
	public function destroy_session() {
		return;
	}

	/**
	 * @deprecated since v2
	 */
	public static function is_saved() {
		return;
	}

	/**
	 * @deprecated since v2
	 */
	public function is_logged_in() {
		return self::is_logged_in();
	}

	/**
	 * @deprecated since v2
	 */
	public function edit_user($array_data) {
		return;
	}

	/**
	 * @deprecated since v2
	 */
	public function get_array() {
		return;
	}
};

class nc_api
{
	public static function get_user_info($id = "") {
		if($id == "") {
			return json_decode(post_call(NC_ROOT_URL."/user_info.php",	array("api_code" => API_CODE)),true);
		} 
		return json_decode(post_call(NC_ROOT_URL."/user_info.php",	array("id" => $id , "api_code" => API_CODE)),true);
	}

	public static function get_user_info_by_uname($uname = "") {
		if($uname == "") {
			return json_decode(post_call(NC_ROOT_URL."/user_info.php",	array("api_code" => API_CODE)),true);
		} 
		return json_decode(post_call(NC_ROOT_URL."/user_info.php",	array("username" => $uname , "api_code" => API_CODE)),true);
	}
};




