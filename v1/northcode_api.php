<?php
/***

01001110 01101111 01110010 01110100 01101000 01100011 01101111 01100100 01100101  01000001 01010000 01001001 
	  _   _            _   _                   _           _    ____ ___ 
	 | \ | | ___  _ __| |_| |__   ___ ___   __| | ___     / \  |  _ \_ _|
	 |  \| |/ _ \| '__| __| '_ \ / __/ _ \ / _` |/ _ \   / _ \ | |_) | | 
	 | |\  | (_) | |  | |_| | | | (_| (_) | (_| |  __/  / ___ \|  __/| | 
	 |_| \_|\___/|_|   \__|_| |_|\___\___/ \__,_|\___| /_/   \_\_|  |___|
	                                                                     
01001110 01101111 01110010 01110100 01101000 01100011 01101111 01100100 01100101  01000001 01010000 01001001 

Welcome to the northcode.no API

The api consists of 2 parts:
 - The session manager
 - The information fetcher

The session manager is going to be the biggest bulk of what you use it for.
It handles:
 - Logging in to a user
 - Fetching userdata for logged in user
 - Editing userdata for logged in user
 - Logging out user
 - Storing and saving northcode login sessions to the $_SESSION vars
 - Checking if the session is stored and if the session is valid (if the user is logged in or not)

The information fetcher currently does one thing: Fetching userdata for all users or a spesific one

-------------------------------------------------------------------------------------------------------------

!! IMPORTANT BEFORE USE: Please check that your api key is inserted into the API_CODE definition below !!

***/
DEFINE('API_CODE','');
/***

HOW TO USE THE SESSION MANAGER:

*** LOGGING IN ***
To login to a user, create a new instace of the nc_session class, passing in the username and password
The session will then be registered, or throw an exception if there was an error logging in,
it is therefore useful to put the login procedure in a try-catch block.

*** GETTING USER DATA ***
Pretty simple, just call: $s->get_user_info(); where $s is the nc_session instance
This will either throw an exception if it fails (most likely because the session is expired!) so again, try-catch blocks are useful!
If it succeeds it will return an associative array with the keys:
 - uid
 - username
 - email
 - rank
 - title
Each corresponding to the information about the user
HINT: To get more information, such as the user bio or image link, use the information fetcher

*** LOGGING OFF ***
$s->logout(); simple right?
This will make the session not active, however your site may still have it stored in its session variables,
to get rid of them, call: $s->destroy_session(); THIS WILL NOT WIPE YOUR OTHER SESSION VARIABLES, JUST THE ONES FROM THIS API!

*** STORING AND LOADING TO $_SESSION ***
When you have logged in the nc_session stores a session id and a private code to identify the session on the northcode server.
However you may want to store the session in your $_SESSION vars for use later to keep them logged in.
To do this use: $s->store_in_session(); this will create two new variables in the $_SESSION vars.
When you need to use the api again, you can use nc_session::load_from_session();
This will load the session id and code from the $_SESSION vars, however you may want to check if they are even there in the first place.
To do this, simply use the nc_session::is_saved(); this will return true or false depending on if the nc_session is stored.
Finally to remove the nc_session from the $_SESSION vars, use the $s->destroy_session;

!! NOTICE: YOU CAN NOT STORE MORE THAN ONE NC_SESSION AT A TIME (two users cannot be logged in at the same time) !!
!! TRYING TO DO SO WILL OVERRIDE THE PREVIOUS SESSION !!

*** EDITING A USER ***
To change something about the user, say their email, you use the $s->edit_user($data); function.
Pass it an associative array containing the key "change";
The key "change" must also be an associative array, where each key is the thing you want to change,
and the value is the new value to set it to.
EXAMLPE: $s->edit_user(array("change" => array("email" => "something.else@domain.com","info" => "I just changed my email address!")));
This will change the users email and bio to the new values.


*** REGISTER A USER? ***
Users are handled by northcode.no, to register a new one, redirect them to http://dev.northcode.no/register.php


-------------------------------------------------------------------------------------------------------------

HOW TO USE THE INFORMATION FETCHER:

Pretty simple.

*** GET INFORMATION ABOUT ALL THE USERS ***
nc_api::get_user_info();
returns an array of associative arrays with where each array entry of the top array is a user.
The associative array has the keys:
 - uid
 - username
 - email
 - img
 - info

*** GET INFORMATION ON A SPESIFIC USER ***
nc_api::get_user_info($uid);
returns only one associative array for only the user with that id,
keys are the same as when you call it without an argument


-------------------------------------------------------------------------------------------------------------

***/


@session_start();

DEFINE('ROOT_URL','http://dev.northcode.no');

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

	public function __construct($username,$password,$loading = false) {
		if($loading) {
			$this->ssid = $username;
			$this->code = $password;
		} else {
			$user_ip = $_SERVER['REMOTE_ADDR'];
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			$str = post_call(ROOT_URL."/mysql/login.php?ajax",array("username" => $username, "password" => $password, "api_code" => API_CODE, "user_ip" => $user_ip, "user_agent" => $user_agent));
			$logindata = json_decode($str,true);
			if(isset($logindata['error'])) {
				print_r($logindata);
				throw new Exception('Error logging in: ' + $logindata['error']);
			} else {
				$this->ssid = $logindata['ssid'];
				$this->code = $logindata['code'];
			}
		}
	}

	public function get_user_info() {
		$str = post_call(ROOT_URL."/api/session_info.php",$this->get_array());
		$userdata = json_decode($str,true);
		if (isset($userdata['error'])) {
			print_r($userdata);
			throw new Exception('Error fetching user data: ' + $userdata['error']);
		} else {
			return $userdata;
		}
	}

	public function logout() {
		$str = post_call(ROOT_URL."/mysql/logout.php?ajax",$this->get_array());
	}

	

	public function store_in_session() {
		$_SESSION['nc_ssid'] = $this->ssid;
		$_SESSION['nc_code'] = $this->code;
	}

	public static function load_from_session() {
		$ssid = $_SESSION['nc_ssid'];
		$code = $_SESSION['nc_code'];
		$result = new nc_session($ssid,$code,true);
		return $result;
	}

	public function destroy_session() {
		unset($_SESSION['nc_ssid']);
		unset($_SESSION['nc_code']);
	}

	public static function is_saved() {
		return (isset($_SESSION['nc_ssid']) and isset($_SESSION['nc_code']));
	}

	public function is_logged_in() {
		$data = json_decode(post_call(ROOT_URL."/api/session_info.php?ajax&logincheck",$this->get_array()),true);
		return $data['active'];
	}

	public function edit_user($array_data) {
		$data = json_decode(post_call(ROOT_URL."/api/edit_user.php",array_merge($this->get_array(),$array_data)),true);
		return $data;
	}

	/**/

	public function get_array() {
		$user_ip = $_SERVER['REMOTE_ADDR'];
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		return array("ssid" => $this->ssid, "code" => $this->code, "api_code" => API_CODE, "user_ip" => $user_ip,"user_agent" => $user_agent);
	}
};

class nc_api
{
	public static function get_user_info($id = "") {
		if($id == "") {
			return json_decode(post_call(ROOT_URL."/api/user_info.php",	array("api_code" => API_CODE)),true);
		} 
		return json_decode(post_call(ROOT_URL."/api/user_info.php",	array("id" => $id , "api_code" => API_CODE)),true);
	}

	public static function get_user_info_by_uname($uname = "") {
		if($uname == "") {
			return json_decode(post_call(ROOT_URL."/api/user_info.php",	array("api_code" => API_CODE)),true);
		} 
		return json_decode(post_call(ROOT_URL."/api/user_info.php",	array("username" => $uname , "api_code" => API_CODE)),true);
	}
};

if (API_CODE == "") {
	# code...
	die("Your api_code is missing, please put in the api_code you got from us in the DEFINE statement at the top of this file");
}



?>