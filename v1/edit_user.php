<?php

include_once("../mysql/connect.php");
include_once("server_api.php");
include_once("../mysql/passwordhash.php");

class BindParam{ 
    private $values = array(), $types = ''; 
    
    public function add( $type, &$value ){ 
        $this->values[] = $value; 
        $this->types .= $type; 
    } 
    
    public function get(){ 
        return array_merge(array($this->types), $this->values); 
    } 
} 

function refValues($arr){ 
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+ 
    { 
        $refs = array(); 
        foreach($arr as $key => $value) 
            $refs[$key] = &$arr[$key]; 
        return $refs; 
    } 
    return $arr; 
} 

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

if (isset($_POST['change'])) {

	$get_uid_q = $mysql->prepare("SELECT count(*) as count,uid FROM loginsessions WHERE id = ? AND sessioncode = ? AND active = '1'");
	$get_uid_q->bind_param("is",$ssid,$code);
	$get_uid_q->execute();
	$get_uid_q->bind_result($count,$uid);
	$get_uid_q->fetch();
	$get_uid_q->close();
	if($count == 0) {
		die(json_encode(array("error" => "no match for login session, cannot change data", "data_recieved" => array("ssid" => $ssid, "sscode" => $code, "count" => $count))));
	}

	# code...
	$change = $_POST['change'];

	$param_keys = array();

	foreach ($change as $key => $value) {
		if ($key == "username") {
			$param_keys[] = "username = ?";
		} elseif ($key == "email") {
			$param_keys[] = "email = ?";
		} elseif ($key == "password") {
			$param_keys[] = "password = ?";
		} elseif ($key == "img") {
			$param_keys[] = "img = ?";
		} elseif ($key == "info") {
			$param_keys[] = "info = ?";
		}
	}

	$sql_q = "UPDATE users SET ".implode(',', $param_keys)." WHERE id = ?";

	$sql_s = $mysql->prepare($sql_q);

	$params = new BindParam();

	foreach ($change as $key => $value) {
		if ($key == "username") {
			$params->add('s',$value);
		} elseif ($key == "email") {
			$params->add('s',$value);
		} elseif ($key == "password") {
			$passhash = hashpass($value);
			$params->add('s',$params);
		} elseif ($key == "img") {
			$params->add('s',$value);
		} elseif ($key == "info") {
			$params->add('s',$value);
		}
	}

	$params->add('i',$uid);

	call_user_func_array(array($sql_s, "bind_param"),refValues($params->get()));

	$sql_s->execute();
	$sql_s->close();

	if(isset($change['password'])) {
		$change['password'] = '****';
	}

	log_api_action($api_caller['id'],"editing user: ".$uid." change data: ".http_build_query($change));
}

?>