<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("classes/Autoloader.php");

use \Exception as Exception;

use modules\Login as Login;
use modules\MyPage as MyPage;
use modules\TxHistory as TxHistory;
use modules\UserHistory as UserHistory;
use modules\Admin as Admin;
use modules\AccountCreate as AccountCreate;
use modules\AccountUpdate as AccountUpdate;
use modules\AccountSuspend as AccountSuspend;
use modules\UserCreate as UserCreate;
use modules\UserUpdate as UserUpdate;
use modules\UserProfile as UserProfile; 
use modules\Signup as Signup; 
use modules\Community as Community; 

header("Content-Type: text/html; charset=utf-8");

session_start();

function getParam($array, $param, $label = '') {
	if (array_key_exists($param, $array)) {
		
		if (strcmp($label, "array") == 0) {
			return (is_array($array[$param])) ? $array[$param] : array($array[$param]);
		} elseif (strcmp($label, "int") == 0) {
			return intval(trim($array[$param]));
		} elseif (strcmp($label, "double") == 0) {
			return doubleval(trim($array[$param]));
		} else {
			return strip_tags(stripslashes(trim($array[$param])));
		}
	} else if (strcmp($label, "array") == 0) {
		return array();
	}

	return null;
}

function isAdmin($mysqli, $userId) {
	$value = 0;
	$sql = "SELECT account.account_id FROM management ";
	$sql .= "LEFT JOIN account ON (account.user_id = management.user_id) ";
	$sql .= sprintf("WHERE account.user_id = %d;", $userId);

	if ($result = $mysqli->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$value = intval($row["account_id"]);			
		}
	}

	return $value;	
}

function getArrUserAccount($mysqli, $userId) {
	$data = array();
	
	$sql = "SELECT account.account_id AS accountId, account.name AS accountName, account.suspended, ";
	$sql .= "account.token AS tokenName, account.symbol AS tokenSymbol, account.icon AS tokenIcon ";
	$sql .= "FROM account ";
	$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
	$sql .= sprintf("WHERE account.user_id = %d;", $userId);
	
	if ($result = $mysqli->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$obj = new \stdClass();
			$obj->accountId = intval($row["accountId"]);
			$obj->accountName = trim($row["accountName"]);
			$obj->tokenName = trim($row["tokenName"]);
			$obj->tokenSymbol = trim($row["tokenSymbol"]);
			$obj->tokenIcon = trim($row["tokenIcon"]);
			$obj->suspended = intval($row["suspended"]);
			array_push($data, $obj);
		}
	}
	
	return $data;
}
	
$httpMethod = $_SERVER["REQUEST_METHOD"];
if ($httpMethod == 'POST' && array_key_exists('NGINX', $_POST)) {
    if ($_POST['NGINX'] == 'DELETE') {
        $httpMethod = 'DELETE';
    } else if ($_POST['NGINX'] == 'PUT') {
        $httpMethod = 'PUT';
    }
}

$module = getParam($_POST, "module");
if (empty($module)) {
	$module = getParam($_GET, "module");
}

$acceptLang = ['kr', 'de', 'us'];
$lang = getParam($_POST, "lang");
if (empty($lang)) {
	$lang = getParam($_GET, "lang");
}
if (empty($lang)) {
	if (isset($_SESSION['LANG'])) {
		$lang = trim($_SESSION["LANG"]);
	} else {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	}
}
$lang = in_array(strToLower($lang), $acceptLang) ? $lang : 'us';

$_SESSION["LANG"] = $lang;

$config = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/inssa/api/include/db.mysql.ini");
$mysqli = new mysqli($config['HOST'], $config['USER'], $config['PASS'], $config['NAME']);
$userId = isset($_SESSION['USERID']) ? intval($_SESSION["USERID"]) : 0;

try {
	if ($mysqli->connect_error) {
		throw new Exception("Cannot connect to the database: ".$mysqli->connect_errno, 503);
	}
	$mysqli->set_charset("utf8");
	
	switch(strtoupper($module)) {
		case "LOGIN":
			$email = getParam($_POST, "email");
			$password = getParam($_POST, "password");
			
			$sha256 = "";
			$sql = "SELECT salt FROM user WHERE email='%s';";
			$sql = sprintf($sql, $mysqli->real_escape_string($email));
			$result = $mysqli->query($sql);

			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					$salt = trim($row["salt"]);
					$sha256 = hash_hmac("sha256", $password, $salt);
				}
			}
			
			$sql = "SELECT user_id FROM user WHERE email='%s' AND password='%s';";
			$sql = sprintf($sql, $mysqli->real_escape_string($email), $sha256);
			
			if ($result = $mysqli->query($sql)) {
				while($row = $result->fetch_assoc()) {
					$userId = intval($row["user_id"]);
				}
			}
			
			$_SESSION["USERID"] = $userId;

			break;
		case "LOGOUT":
			$_SESSION = array();

			// If it's desired to kill the session, also delete the session cookie.
			// Note: This will destroy the session, and not just the session data!
			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
				);
			}

			// Finally, destroy the session.
			session_destroy();
			$userId = 0;
			break;	
	}
	
	$isAdmin = isAdmin($mysqli, $userId);
	$arrUserAccount = getArrUserAccount($mysqli, $userId);
	
	if ($userId > 0) {
		if (strcmp(strtoupper($module), "LOGIN") == 0) {
			$module = "MYPAGE";
		} 
		if (count($arrUserAccount) == 0) {
			if (!in_array(strtoupper($module), array("MYPAGE", "USERPROFILE"))) {
				$module = "MYPAGE";
			}
		} else {
			$module = (empty($module)) ? "MYPAGE" : $module;
		}
	} else {
		$module = (strcmp(strtoupper($module), "SIGNUP") != 0) ? "LOGIN" : $module;
	}

	switch(strtoupper($module)) {
		case "SIGNUP":
			$instance = new Signup($mysqli);
			if ($httpMethod == "GET") {
				$token = getParam($_GET, "token");
				$instance->doGet($token, $lang);
			} else {
				$userName = getParam($_POST, "userName");	
				$email = getParam($_POST, "email");	
				$comment = getParam($_POST, "comment");	
				$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";				
				$instance->doPost($userName, $email, $comment, $link, $lang);
			}
			break;			
		case "MYPAGE":
			$instance = new MyPage($mysqli);
			if ($httpMethod == "GET") {
				$instance->doGet($userId, $lang);
			} else {
				$instance->doPost($userId, $lang);
			}
			break;	
		case "TXHISTORY":
			$instance = new TxHistory($mysqli);
			if ($httpMethod == "GET") {
				$instance->doGet($userId, $lang);
			}
			break;
		case "USERHISTORY":
			$instance = new UserHistory($mysqli);
			if ($httpMethod == "GET") {
				$instance->doGet($userId, $lang);
			}
			break;			
		case "ADMIN":
			$instance = new Admin($mysqli);
			if ($httpMethod == "GET") {
				$page = getParam($_GET, "page", "int");
				$itemsPerPage = getParam($_GET, "itemsPerPage", "int");
				$searchText = getParam($_GET, "searchText"); 
				$tokenName = getParam($_GET, "tokenName");
				$suspended = getParam($_GET, "suspended", "int");
				$instance->doGet($page, $itemsPerPage, $searchText, $tokenName, $suspended, $lang);
			} else {
				$instance->doPost(0, 0, "", "", 0, $lang);
			}
			break;
		case "COMMUNITY":
			$instance = new Community($mysqli);
			if ($httpMethod == "GET") {
				$page = getParam($_GET, "page", "int");
				$itemsPerPage = getParam($_GET, "itemsPerPage", "int");
				$searchText = getParam($_GET, "searchText"); 
				$instance->doGet($userId, $page, $itemsPerPage, $searchText, $lang);
			} else {
				$page = getParam($_POST, "page", "int");
				$itemsPerPage = getParam($_POST, "itemsPerPage", "int");
				$searchText = getParam($_POST, "searchText"); 
				$memberId = getParam($_POST, "memberId"); 
				$instance->doPost($userId, $page, $itemsPerPage, $searchText, $lang, $memberId);
			}
			break;
		case "USERCREATE":
			$instance = new UserCreate($mysqli);
			if ($httpMethod == "POST") {
				$userId = getParam($_POST, "userId", "int");
				$userName = getParam($_POST, "userName");
				$email = getParam($_POST, "email");
				$password = getParam($_POST, "password");
				$accountName = getParam($_POST, "accountName");
				$suspended = getParam($_POST, "suspended", "int");				
				$tokenName = getParam($_POST, "tokenName");
				$tokenSymbol = getParam($_POST, "tokenSymbol");
				$tokenIcon = $_FILES["tokenIcon"];					
				$instance->doPost($userId, $userName, $email, $password, $accountName, $suspended, $tokenName, $tokenSymbol, $tokenIcon, $lang);
			} else {
				$instance->doGet(0, $lang);
			}
			break;
		case "USERUPDATE":
			$instance = new UserUpdate($mysqli);
			if ($httpMethod == "POST") {
				$userId = getParam($_POST, "userId", "int");
				$userName = getParam($_POST, "userName");
				$email = getParam($_POST, "email");
				$password = getParam($_POST, "password");
				$instance->doPost($userId, $userName, $email, $password, $lang);
			} else {
				$userId = getParam($_GET, "userId", "int");
				$instance->doGet($userId, $lang);
			}
			break;
		case "USERPROFILE":
			$instance = new UserProfile($mysqli);
			if ($httpMethod == "POST") {
				$userId = getParam($_POST, "userId", "int");
				$userName = getParam($_POST, "userName");
				$email = getParam($_POST, "email");
				$password = getParam($_POST, "password");
				$comment = getParam($_POST, "comment");
				$birthday = getParam($_POST, "birthday");
				$instance->doPost($userId, $userName, $email, $password, $comment, $birthday, $lang);
			} else {
				$instance->doGet($userId, $lang);
			}
			break;				
		case "ACCOUNTCREATE":
			$instance = new AccountCreate($mysqli);
			if ($httpMethod == "POST") {
				$userId = getParam($_POST, "userId", "int");
				$accountName = getParam($_POST, "accountName");
				$suspended = getParam($_POST, "suspended", "int");
				$tokenName = getParam($_POST, "tokenName");
				$tokenSymbol = getParam($_POST, "tokenSymbol");
				$tokenIcon = $_FILES["tokenIcon"];
				$instance->doPost($userId, $accountName, $suspended, $tokenName, $tokenSymbol, $tokenIcon, $lang);
			} else {
				$instance->doGet(0, $lang);
			}
			break;
		case "ACCOUNTUPDATE":
			$instance = new AccountUpdate($mysqli);
			if ($httpMethod == "POST") {		
				$accountId = getParam($_POST, "accountId", "int");
				$accountName = getParam($_POST, "accountName");
				$tokenName = getParam($_POST, "tokenName");
				$tokenSymbol = getParam($_POST, "tokenSymbol");
				$tokenIcon = $_FILES["tokenIcon"];	
				$instance->doPost($accountId, $accountName, $tokenName, $tokenSymbol, $tokenIcon, $lang);
			} else {
				$accountId = getParam($_GET, "accountId", "int");
				$instance->doGet($accountId, $lang);
			}				
			break;
		case "ACCOUNTSUSPEND":
			$instance = new AccountSuspend($mysqli);
			if ($httpMethod == "POST") {
				$accountId = getParam($_POST, "accountId", "int");
				$suspended = getParam($_POST, "suspended", "int");
				$comment = getParam($_POST, "comment");
				$instance->doPost($accountId, $suspended, $comment, $lang);
			} else {
				$accountId = getParam($_GET, "accountId", "int");
				$instance->doGet($accountId, $lang);
			}	
			break;	
		case "LOGIN":
			(new Login())->doGet($lang);		
			break;
		default:
			break;			
	}
	
} catch (Exception $e) {
	$msg = $e->getMessage();
	$code = $e->getCode();
	http_response_code(($code == 0) ? 400 : $code);
	echo sprintf("Exception occurred in: %s", $msg);
} finally {
	$mysqli->close();
}
