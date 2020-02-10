<?php 
namespace modules;

use \Exception as Exception;

/**
* Token Trading Status page accessible to all users
* Token transaction history sent and received by a specific account
*/
class TokenTransactionHistory {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The id of current user	
	*/		
	public function doGet($senderId, $receiverId, $year, $month, $userId, $showTransactionFaucet) {
		$mysqli = $this->mysqli;		

		$obj = new \stdClass();
		$obj->data = array();
		$obj->view = '';
		
		$account = $this->getUserMainAccount($mysqli, $userId);
		
		if ($account->accountId == $senderId) {
			$obj->data = $this->getDataSent($mysqli, $senderId, $year, $month, $showTransactionFaucet);
			$obj->view = 'sent';
		} else if ($account->accountId == $receiverId) {
			$obj->data = $this->getDataReceived($mysqli, $receiverId, $year, $month, $showTransactionFaucet);
			$obj->view = 'received';
		} else {
			$obj->data = $this->getDataOthers($mysqli, $senderId, $receiverId, $year, $month, $showTransactionFaucet);
			$obj->view = 'others';
		}

		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->accountId = $account->accountId;
		$obj->senderId = $senderId;
		$obj->receiverId = $receiverId;
		$obj->year = $year;
		$obj->month = $month;
		$obj->userId = $userId;
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	function getUserMainAccount($mysqli, $userId) {
		$sql = "SELECT account.account_id AS accountId, account.name AS accountName, user.email AS userName ";
		$sql .= "FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= sprintf("WHERE account.user_id = %d ", $userId);
		$sql .= "LIMIT 0,1;";
		
		$data = new \stdClass();
		$data->accountId = 0;
		$data->accountName = "";
		$data->userName = "";
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data->accountId = intval($row["accountId"]);
				$data->accountName = trim($row["accountName"]);
				$data->userName = trim($row["userName"]);
			}
		}
		
		return $data;
	}
	
	private function getView($senderId, $receiverId, $userId) {
		$account = $this->getUserMainAccount($mysqli, $userId);
		$view = 'others';
		
		if ($account->accountId == $senderId) {
			$view = 'sent';
		} else if ($account->accountId == $receiverId) {
			$view = 'received';
		} 
		
		return $view;
	}	
	
	private function getDataReceived($mysqli, $accountId, $year, $month, $includeFaucet) {
		$data = array();		
		$sql = "SELECT IFNULL(account.account_id, 0) AS 'accountId', ";
		$sql .= "IFNULL(account.name, 'Faucet') AS 'senderName', ";
		$sql .= "transaction.transaction_id AS transactionId, quantity, reference, status, ";
		$sql .= "date_format(transaction.modified, '%m/%d/%y %H:%i') AS datetime ";
		$sql .= "FROM transaction ";
		$sql .= "LEFT JOIN account ON (account.account_id = transaction.sender_id) ";
		$sql .= sprintf("WHERE transaction.receiver_id = %d ", $accountId);
		if (!$includeFaucet) {
			$sql .= "AND transaction.sender_id > 0 ";
		}		
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= "ORDER BY transaction.modified DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $data;
	}
	
	private function getDataSent($mysqli, $accountId, $year, $month) {
		$data = array();
		$sql = "SELECT IFNULL(account.account_id, 0) AS 'accountId', account.name AS 'receiverName', ";
		$sql .= "transaction.transaction_id AS transactionId, quantity, reference, status, ";
		$sql .= "date_format(transaction.modified, '%m/%d/%y %H:%i') AS datetime ";
		$sql .= "FROM transaction ";
		$sql .= "LEFT JOIN account ON (account.account_id = transaction.receiver_id) ";
		$sql .= sprintf("WHERE transaction.sender_id = %d ", $accountId);
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= "ORDER BY transaction.modified DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}

		return $data;		
	}
	
	private function getDataOthers($mysqli, $senderId, $receiverId, $year, $month, $includeFaucet) {
		$data = array();		
		$sql = "SELECT transaction.transaction_id AS transactionId, ";
		$sql .= "IFNULL(transaction.sender_id, 0) AS 'senderId', ";
		$sql .= "IFNULL((SELECT account.name FROM account WHERE account.account_id = transaction.sender_id), 'Faucet')  AS 'senderName', ";
		$sql .= "IFNULL(transaction.receiver_id, 0) AS 'receiverId', ";
		$sql .= "(SELECT account.name FROM account WHERE account.account_id = transaction.receiver_id) AS 'receiverName', ";	
		$sql .= "quantity, reference, status, supplement, ";
		$sql .= "date_format(transaction.created, '%m/%d/%y %H:%i') AS created, ";		
		$sql .= "date_format(transaction.modified, '%m/%d/%y %H:%i') AS modified ";
		$sql .= "FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		if (!$includeFaucet) {
			$sql .= "AND transaction.sender_id > 0 ";
		}
		if ($senderId > 0) {
			$sql .= sprintf("AND transaction.sender_id = %d ", $senderId);
		}
		if ($receiverId > 0) {
			$sql .= sprintf("AND transaction.receiver_id = %d ", $receiverId);
		}
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= "ORDER BY transaction.modified DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}

		return $data;		
	}
}