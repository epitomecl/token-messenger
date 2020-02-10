<?php

namespace modules;

class AccountSuspend {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($accountId, $suspended, $comment, $lang) {
		$mysqli = $this->mysqli;

		$transactionId = 0;

		if (count($accountIds) > 0 && strlen($comment) > 0) {
			$sql = "UPDATE account SET ";
			$sql .= sprintf("suspended ='%d' ", $suspended);
			$sql .= sprintf("WHERE account_id = %d;", $accountId);
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}

			$transactionId = insertTransaction($mysqli, $accountId, $accountId, 0, $comment);					
		}
		
		$data = $this->getData($mysqli, $accountId);
		
		require_once(sprintf("AccountSuspendView_%s.php", $lang)); 
	}
	
	public function doGet($accountId, $lang) {
		$mysqli = $this->mysqli;
		
		$data = $this->getData($mysqli, $accountId);
		
		require_once(sprintf("AccountSuspendView_%s.php", $lang)); 
	}

	private function getTokenImage($source) {
		if (empty($source)) {
			return "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
		}

		return $source;
	}
	
	private function getData($mysqli, $accountId) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, email, account_id, account.name AS accountName, suspended, ";
		$sql .= "account.token AS tokenName, symbol AS tokenSymbol, icon AS tokenPath ";
		$sql .= "FROM user LEFT JOIN account ON (account.user_id = user.user_id) ";
		$sql .= sprintf("WHERE account.account_id = %d;", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
				$data->email = trim($row["email"]);
				$data->accountId = intval($row["account_id"]);
				$data->accountName = trim($row["accountName"]);
				$data->suspended = intval($row["suspended"]);
				$data->tokenName = trim($row["tokenName"]);
				$data->tokenSymbol = trim($row["tokenSymbol"]);
				$data->tokenPath = $this->getTokenImage(trim($row["tokenPath"]));
			}
		}	
		return $data;
	}
}