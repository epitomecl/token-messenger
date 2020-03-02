<?php 
namespace modules;

use \Exception as Exception;

/**
* List all user account data.
*/
class UserAccount {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The id of current user	
	*/		
	public function doGet($userId) {
		$mysqli = $this->mysqli;
		
		$data = array();
		$arrUserAccount = $this->getArrUserAccount($mysqli, $userId);
		foreach ($arrUserAccount as $account) {
			$obj = new \stdClass();
			$obj->accountId = $account->accountId;
			$obj->accountName = $account->accountName;
			$obj->tokenName = $account->tokenName;
			$obj->tokenSymbol = $account->tokenSymbol;
			$obj->tokenIcon = $this->getTokenImage($account->tokenIcon);
			$obj->remain = $this->getUniqueTokenRemain($mysqli, $account->accountId);
			$obj->total = $this->getUniqueTokenTotal($mysqli, $account->accountId);	
			$obj->received = $this->getReceivedTokenTotal($mysqli, $account->accountId);
			$obj->month = $this->getReceivedTokenMonth($mysqli, $account->accountId);	
			$obj->suspended = $account->suspended;
			array_push($data, $obj);
		}
		
		$obj = new \stdClass();
		$obj->data = $data;
		$obj->module = (new \ReflectionClass($this))->getShortName();
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function getTokenImage($source) {
		if (empty($source)) {
			return "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
		}

		return $source;
	}
	
	private function getArrUserAccount($mysqli, $userId) {
		$data = array();
		
		$sql = "SELECT account.account_id AS accountId, account.name AS accountName, account.suspended, ";
		$sql .= "account.token AS tokenName, account.symbol AS tokenSymbol, account.icon AS tokenIcon ";
		$sql .= "FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= sprintf("WHERE account.user_id = %d ", $userId);
		$sql .= "ORDER BY account.suspended, account.name;";
		
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
	
	private function getUniqueTokenRemain($mysqli, $accountId) {
		$token = 0;
		$sql = "SELECT COUNT(balance.balance_id) AS token ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
		$sql .= "AND balance.account_id = token.account_id ";
		$sql .= "GROUP BY token.account_id ";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$token = intval($row["token"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}

		return $token;		
	}
		
	private function getUniqueTokenTotal($mysqli, $accountId) {
		$token = 0;
		$sql = "SELECT COUNT(balance.balance_id) AS token ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
		$sql .= sprintf("WHERE token.account_id = %d ", $accountId);
		$sql .= "GROUP BY token.account_id;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$token = intval($row["token"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}

		return $token;		
	}
		
	private function getReceivedTokenMonth($mysqli, $accountId) {
		$token = 0;	
		$sql = "SELECT COUNT(balance.balance_id) AS token, account.account_id AS 'accountId', account.name AS 'account' ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
		$sql .= "AND balance.account_id != token.account_id ";
		$sql .= sprintf("AND YEAR(balance.modified) = %d ", date("Y"));
		$sql .= sprintf("AND MONTH(balance.modified) = %d  ", date("m"));
		$sql .= "GROUP BY token.account_id ORDER BY token DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$token += intval($row["token"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $token;
	}

	private function getReceivedTokenTotal($mysqli, $accountId) {
		$token = 0;		
		$sql = "SELECT COUNT(balance.balance_id) AS token, account.account_id AS 'accountId', account.name AS 'account' ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
		$sql .= "AND balance.account_id != token.account_id ";
		$sql .= "GROUP BY token.account_id ORDER BY token DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$token += intval($row["token"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $token;
	}
}