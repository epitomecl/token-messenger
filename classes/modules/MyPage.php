<?php

namespace modules;

use \Exception as Exception;

class MyPage {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doGet($userId, $lang) {
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
			$tokenData = $this->getReceivedTokenWithinTheCurrentMonth($mysqli, $account->accountId);
			$obj->month = $tokenData->total;
			$obj->tokenList = $tokenData->data;	
			$receiverData = $this->getTokenReceiverWithinTheCurrentMonth($mysqli, $account->accountId);
			$obj->sentPerMonth = $receiverData->total;
			$obj->receiverList = $receiverData->data;
			$obj->suspended = $account->suspended;
			array_push($data, $obj);
		}
		$userData = $this->getUserData($mysqli, $userId);
		$listPendingTransactionByReceiver = $this->getListPendingTransactionByReceiver($mysqli, $userId);
		$listPendingTransactionBySender = $this->getListPendingTransactionBySender($mysqli, $userId);
		$arrOptionSender = $this->getOptionArraySender($mysqli, $userId);
		$txtOptionReceiver = $this->getOptionArrayReceiver($mysqli, $userId);	
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);
		
		require_once(sprintf("MyPageView_%s.php", $lang)); 
	}

	public function doPost($userId, $lang) {
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
			$tokenData = $this->getReceivedTokenWithinTheCurrentMonth($mysqli, $account->accountId);
			$obj->month = $tokenData->total;
			$obj->tokenList = $tokenData->data;
			$receiverData = $this->getTokenReceiverWithinTheCurrentMonth($mysqli, $account->accountId);
			$obj->sentPerMonth = $receiverData->total;
			$obj->receiverList = $receiverData->data;
			$obj->suspended = $account->suspended;
			array_push($data, $obj);
		}
		$userData = $this->getUserData($mysqli, $userId);
		$listPendingTransactionByReceiver = $this->getListPendingTransactionByReceiver($mysqli, $userId);
		$listPendingTransactionBySender = $this->getListPendingTransactionBySender($mysqli, $userId);
		$arrOptionSender = $this->getOptionArraySender($mysqli, $userId);
		$txtOptionReceiver = $this->getOptionArrayReceiver($mysqli, $userId);
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);
		
		require_once(sprintf("MyPageView_%s.php", $lang)); 
	}
	
	private function getTokenImage($source) {
		if (empty($source)) {
			return "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
		}

		return $source;
	}
	
	private function getOptionArrayReceiver($mysqli, $userId) {
		$data = array();
		$sql = "SELECT account.account_id, account.name, user.name as userName FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= "WHERE account.community=0;";

		$data = array();
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				array_push($data, $row);			
			}
		}
		
		$option = array();	
		foreach ($data as $index => $row) {
			$userName = trim($row["userName"]);
			$value = intval($row["account_id"]);
			$text = trim($row["name"]);
			array_push($option, sprintf("<option value=\"%d\">%s (%s)</option>", $value, $text, $userName));			
		}
		
		return implode("\n", $option);	
	}
	
	private function getOptionArraySender($mysqli, $userId) {
		$data = array();
		$sql = "SELECT account.account_id, account.name FROM account ";
		$sql .= sprintf("WHERE user_id = %d ", $userId);
		$sql .= "ORDER BY account.community;";
		
		$data = array();
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				array_push($data, $row);			
			}
		}
		
		$option = array();	
		foreach ($data as $index => $row) {
			$value = intval($row["account_id"]);
			$selected = (count($data) == 1) ? "selected=\"selected\"" : "";		
			$text = trim($row["name"]);
			array_push($option, sprintf("<option value=\"%d\"%s>%s</option>", $value, $selected, $text));			
		}
		
		return $option;
	}
	
	private function getUserData($mysqli, $userId) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, user.name AS userName, user.email, user.comment ";
		$sql .= "FROM user ";
		$sql .= sprintf("WHERE user.user_id = %d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
				$data->email = trim($row["email"]);
				$data->userName = trim($row["userName"]);
				$data->comment = trim($row["comment"]);
			}
		}	
		return $data;
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
	
	private function isAdmin($mysqli, $userId) {
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
	
	private function isMember($mysqli, $userId) {
		$value = 0;
		$sql = "SELECT COUNT(account.user_id) AS counter  ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.user_id = %d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["counter"]);			
			}
		}

		return $value;	
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
		
	private function getReceivedTokenWithinTheCurrentMonth($mysqli, $accountId) {
		$total = 0;	
		$data = array();
		$sql = "SELECT COUNT(balance.balance_id) AS token, account.account_id AS 'accountId', account.name AS 'account', account.icon, account.symbol, account.token AS tokenName ";
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
				array_push($data, $row);
				$total += intval($row["token"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		$obj = new \stdClass;
		$obj->total = $total;
		$obj->data = $data;
		
		return $obj;
	}

	private function getTokenReceiverWithinTheCurrentMonth($mysqli, $senderId) {
		$total = 0;	
		$data = array();
		$sql = "SELECT IFNULL(account.account_id, 0) AS 'accountId', account.name AS 'receiverName', ";
		$sql .= "SUM(quantity) AS token, ";
		$sql .= "(SELECT symbol FROM account WHERE account.account_id = transaction.sender_id) AS 'symbol' ";
		$sql .= "FROM transaction ";
		$sql .= "LEFT JOIN account ON (account.account_id = transaction.receiver_id)  ";
		$sql .= sprintf("WHERE transaction.sender_id =%d ", $senderId);
		$sql .= sprintf("AND YEAR(transaction.modified) = %d ", date("Y"));
		$sql .= sprintf("AND MONTH(transaction.modified) = %d  ", date("m"));
		$sql .= "GROUP BY account.account_id ";
		$sql .= "ORDER BY transaction.modified DESC ";
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
				$total += intval($row["token"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		$obj = new \stdClass;
		$obj->total = $total;
		$obj->data = $data;
		
		return $obj;
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

	private function getListPendingTransactionByReceiver($mysqli, $userId) {
		$data = array();		
		$sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
		$sql .= "pending.pending_id AS pendingId, quantity, reference, ";
		$sql .= "date_format(pending.modified, '%m/%d/%Y %H:%i') AS datetime ";
		$sql .= "FROM pending ";
		$sql .= "LEFT JOIN account ON (account.account_id = pending.sender_id) ";
		$sql .= "WHERE pending.receiver_id IN ";
		$sql .= sprintf("(SELECT account.account_id FROM account WHERE account.user_id = %d) ", $userId);
		$sql .= "ORDER BY pending.modified DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		}
		
		return $data;
	}
		
	private function getListPendingTransactionBySender($mysqli, $userId) {
		$data = array();		
		$sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
		$sql .= "pending.pending_id AS pendingId, quantity, reference, ";
		$sql .= "date_format(pending.modified, '%m/%d/%Y %H:%i') AS datetime ";
		$sql .= "FROM pending ";
		$sql .= "LEFT JOIN account ON (account.account_id = pending.receiver_id) ";
		$sql .= "WHERE pending.sender_id IN ";
		$sql .= sprintf("(SELECT account.account_id FROM account WHERE account.user_id = %d) ", $userId);
		$sql .= "ORDER BY pending.modified DESC;";

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		}
		
		return $data;
	}
}