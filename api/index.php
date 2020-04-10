<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("classes/Autoloader.php");

use \Exception as Exception;

use admin\TestFormular as TestFormular;

use modules\PendingTransaction as PendingTransaction;
use modules\TotalUserTokenHoldings as TotalUserTokenHoldings;
use modules\TokenTransactionHistory as TokenTransactionHistory;
use modules\NumberOfTokens as NumberOfTokens;
use modules\DirectTokenTransfer as DirectTokenTransfer;
use modules\VerifiedTokenTransfer as VerifiedTokenTransfer;
use modules\TotalUserTokenHistory as TotalUserTokenHistory;
use modules\Faucet as Faucet;
use modules\Ballot as Ballot;
use modules\AccountSuspended as AccountSuspended;
use modules\TokenTransactionDetail as TokenTransactionDetail;
use modules\PushToken as PushToken;
use modules\PushNotification as PushNotification;
use modules\UserAccount as UserAccount;
use modules\Community as Community;
use modules\ChatMessage as ChatMessage;

// Allow CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); 
}   
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Headers: *");
}

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

$module = getParam($_POST, "module");
$httpMethod = $_SERVER["REQUEST_METHOD"];

if ($httpMethod == 'POST' && array_key_exists('NGINX', $_POST)) {
    if ($_POST['NGINX'] == 'DELETE') {
        $httpMethod = 'DELETE';
    } else if ($_POST['NGINX'] == 'PUT') {
        $httpMethod = 'PUT';
    }
}

if (empty($module)) {
	$module = getParam($_GET, "module");
}

$config = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/inssa/api/include/db.mysql.ini");
$mysqli = new mysqli($config['HOST'], $config['USER'], $config['PASS'], $config['NAME']);

try {
	if ($mysqli->connect_error) {
		throw new Exception("Cannot connect to the database: ".$mysqli->connect_errno, 503);
	}
	$mysqli->set_charset("utf8");
	
	switch(strtoupper($module)) {
		case "FAUCET":
			$instance = new Faucet($mysqli);		
			if ($httpMethod == "POST") {
				$instance->doPost();
			}
			break;		
		case "BALLOT":
			$instance = new Ballot($mysqli);		
			if ($httpMethod == "POST") {
				$instance->doPost();
			}
			break;
		case "CHATMESSAGE":
			$instance = new ChatMessage($mysqli);		
			if ($httpMethod == "GET") {
				$userId = getParam($_SESSION, "USERID", "int");		
				$page = getParam($_GET, "page", "int");	
				$itemsPerPage = getParam($_GET, "itemsPerPage", "int");				
				$senderId = getParam($_GET, "senderId", "int");				
				$receiverId = getParam($_GET, "receiverId", "int");
				$year = getParam($_GET, "year", "int");	
				$month = getParam($_GET, "month", "int");
				$instance->doGet($userId, $page, $itemsPerPage, $senderId, $receiverId, $year, $month);
			}
			break;		
		case "PENDINGTRANSACTION":
			$instance = new PendingTransaction($mysqli);		
			if ($httpMethod == "GET") {
				$userId = getParam($_SESSION, "USERID", "int");
				$instance->doGet($userId);
			}
			break;
		case "USERACCOUNT":
			$instance = new UserAccount($mysqli);		
			if ($httpMethod == "GET") {
				$userId = getParam($_SESSION, "USERID", "int");
				$instance->doGet($userId);
			}
			break;			
		case "TOTALUSERTOKENHOLDINGS":
			$instance = new TotalUserTokenHoldings($mysqli);
			if ($httpMethod == "GET") {
				$year = getParam($_GET, "year", "int");	
				$month = getParam($_GET, "month", "int");					
				$instance->doGet($year, $month);
			}
			break;		
		case "TOKENTRANSACTIONHISTORY":
			$instance = new TokenTransactionHistory($mysqli);		
			if ($httpMethod == "GET") {
				$senderId = getParam($_GET, "senderId", "int");				
				$receiverId = getParam($_GET, "receiverId", "int");
				$year = getParam($_GET, "year", "int");	
				$month = getParam($_GET, "month", "int");
				$userId = getParam($_SESSION, "USERID", "int");
				$includeFaucet = getParam($_GET, "includeFaucet", "int");				
				$instance->doGet($senderId, $receiverId, $year, $month, $userId, $includeFaucet);
			}
			break;
		case "TOKENTRANSACTIONDETAIL":
			$instance = new TokenTransactionDetail($mysqli);		
			if ($httpMethod == "GET") {
				$transactionId = getParam($_GET, "transactionId", "int");				
				$userId = getParam($_SESSION, "USERID", "int");
				$instance->doGet($transactionId, $userId);
			}
			break;
		case "TOTALUSERTOKENHISTORY":
			$instance = new TotalUserTokenHistory($mysqli);		
			if ($httpMethod == "GET") {
				$year = getParam($_GET, "year", "int");	
				$month = getParam($_GET, "month", "int");
				$instance->doGet($year, $month);
			}
			break;			
		case "NUMBEROFTOKENS":
			$instance = new NumberOfTokens($mysqli);		
			if ($httpMethod == "GET") {
				$accountId = getParam($_GET, "accountId", "int");	
				$year = getParam($_GET, "year", "int");	
				$month = getParam($_GET, "month", "int");						
				$instance->doGet($accountId, $year, $month);
			}
			break;
		case "DIRECTTOKENTRANSFER":
			$instance = new DirectTokenTransfer($mysqli);		
			if ($httpMethod == "POST") {
				$senderId = getParam($_POST, "senderId", "int");	
				$receiverIds = getParam($_POST, "receiverIds", "array");
				$quantity = getParam($_POST, "quantity", "int");	
				$reference = getParam($_POST, "reference");	
				$instance->doPost($senderId, $receiverIds, $quantity, $reference);
			}
			break;
		case "VERIFIEDTOKENTRANSFER":
			$instance = new VerifiedTokenTransfer($mysqli);		
			if ($httpMethod == "POST") {
				$senderId = getParam($_POST, "senderId", "int");	
				$receiverIds = getParam($_POST, "receiverIds", "array");
				$quantity = getParam($_POST, "quantity", "int");	
				$reference = getParam($_POST, "reference");	
				$instance->doPost($senderId, $receiverIds, $quantity, $reference);
			} else if ($httpMethod == "PUT") {
				$pendingId = getParam($_POST, "pendingId", "int");
				$senderId = getParam($_POST, "accountId", "int");	
				$confirmed = getParam($_POST, "confirmed", "int");
				$message = getParam($_POST, "message");
				$instance->doPut($pendingId, $senderId, $confirmed, $message);
			} else if ($httpMethod == "DELETE") {
				$pendingId = getParam($_POST, "pendingId", "int");
				$receiverId = getParam($_POST, "accountId", "int");
				$message = getParam($_POST, "message");				
				$instance->doDelete($pendingId, $receiverId, $message);
			}
			break;
		case "ACCOUNTSUSPENDED":
			$instance = new AccountSuspended($mysqli);		
			if ($httpMethod == "POST") {
				$accountId = getParam($_POST, "accountId", "int");	
				$column = getParam($_POST, "column");
				$checked = getParam($_POST, "checked", "int");	
				$instance->doPost($accountId, $column, $checked);
			}
			break;
		case "PUSHTOKEN":
			$instance = new PushToken($mysqli);		
			if ($httpMethod == "POST") {
				$userId = getParam($_SESSION, "USERID", "int");
				$pushToken = getParam($_POST, "pushToken");	
				$instance->doPost($userId, $pushToken);
			} else if ($httpMethod == "DELETE") {
				$userId = getParam($_SESSION, "USERID", "int");	
				$pushToken = getParam($_POST, "pushToken");			
				$instance->doDelete($userId, $pushToken);
			}
			break;
		case "PUSHNOTIFICATION":
			$instance = new PushNotification($mysqli);		
			if ($httpMethod == "POST") {
				$pushToken = getParam($_POST, "pushToken");	
				$title = getParam($_POST, "title");	
				$body = getParam($_POST, "body");	
				$instance->doPost($pushToken, $title, $body);
			} 
			break;
		case "COMMUNITY":
			$instance = new Community($mysqli);		
			if ($httpMethod == "POST") {
				$userId = getParam($_SESSION, "USERID", "int");
				$memberId = getParam($_POST, "memberId", "int");	
				$page = getParam($_POST, "page", "int");	
				$itemsPerPage = getParam($_POST, "itemsPerPage", "int");	
				$searchText = getParam($_POST, "searchText");
				$instance->doPost($userId, $memberId, $page, $itemsPerPage, $searchText);
			} else if ($httpMethod == "GET") {
				$userId = getParam($_SESSION, "USERID", "int");	
				$hashValue = getParam($_GET, "hashValue");
				$page = getParam($_GET, "page", "int");	
				$itemsPerPage = getParam($_GET, "itemsPerPage", "int");	
				$searchText = getParam($_GET, "searchText");			
				$instance->doGET($userId, $hashValue, $page, $itemsPerPage, $searchText);
			}
			break;			
		default:
			(new TestFormular())->execute($module);		
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
