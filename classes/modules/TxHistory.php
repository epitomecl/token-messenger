<?php

namespace modules;

class TxHistory {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doGet($userId, $lang) {
		$mysqli = $this->mysqli;
		$mainAccount = $this->getMainAccount($mysqli, $userId);
		$txtOptionMonth = $this->getOptionArrayMonth(date("n"));
		$txtOptionYear = $this->getOptionArrayYear(date("Y"));
		$arrOptionAccount = $this->getArrOptionAccount($mysqli);		
		$arrOptionUserAccount = $this->getArrOptionUserAccount($mysqli, $userId, $mainAccount->accountId);
		$data = $this->getDataSent($mysqli, $mainAccount->accountId, date("Y"), date("m"));
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);
		
		require_once(sprintf("TxHistoryView_%s.php", $lang)); 
	}

	private function getArrOptionAccount($mysqli) {
		$data = array();
		$sql = "SELECT account.account_id, account.name, user.name AS userName FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		
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
		
		return $option;	
	}

	private function getMainAccount($mysqli, $userId) {
		$sql = "SELECT account.account_id AS accountId, account.name AS accountName, account.suspended, user.name AS userName ";
		$sql .= "FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= sprintf("WHERE account.user_id = %d ", $userId);
		$sql .= "ORDER BY account.community ";		
		$sql .= "LIMIT 0,1;";
		
		$data = new \stdClass();
		$data->accountId = 0;
		$data->accountName = "";
		$data->userName = "";
		$data->suspended = 0;
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data->accountId = intval($row["accountId"]);
				$data->accountName = trim($row["accountName"]);
				$data->userName = trim($row["userName"]);
				$data->suspended = intval($row["suspended"]);
			}
		}
		
		return $data;
	}
	
	private function getArrOptionUserAccount($mysqli, $userId, $accountId) {
		$sql = "SELECT account.account_id AS accountId, account.name AS accountName ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.user_id = %d ", $userId);
		$sql .= "ORDER BY account.community;";
		
		$option = array();
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["accountId"]);
				$text = trim($row["accountName"]);
				$selected = ($value == $accountId) ? " selected=\selected\"" : "";

				array_push($option, sprintf("<option value=\"%d\"%s>%s</option>", $value, $selected, $text));
			}
		}
		
		return $option;
	}
	
	private function getOptionArrayMonth($month) {
		$data = array("<option value=\"0\"> </option>");
		
		for ($index = 1; $index <= 12; $index++) {
			$selected = ($index == $month) ? " selected=\selected\"" : "";
			$text = date('F', mktime(0, 0, 0, $index, 10));
			array_push($data, sprintf("<option value=\"%d\"%s>%s</option>", $index, $selected, $text));
		}
		
		return implode("\n", $data);
	}

	private function getOptionArrayYear($year) {
		$data = array("<option value=\"0\"> </option>");
		
		for ($index = 2019; $index <= 2030; $index++) {
			$selected = ($index == $year) ? " selected=\selected\"" : "";
			$text = $index;
			array_push($data, sprintf("<option value=\"%d\"%s>%s</option>", $index, $selected, $text));
		}
		
		return implode("\n", $data);	
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
}