<?php

namespace modules;

class UserHistory {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doGet($userId, $lang) {
		$mysqli = $this->mysqli;
		$txtOptionMonth = $this->getOptionArrayMonth(date("n"));
		$txtOptionYear = $this->getOptionArrayYear(date("Y"));
		$data = $this->getUserHistory($mysqli, date("Y"), date("m"));
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);
		
		require_once(sprintf("UserHistoryView_%s.php", $lang)); 
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
	
	private function getUserHistory($mysqli, $year, $month) {
		$data = array();
		$sql = "SELECT account.name AS accountName, user.name AS userName, ";
		$sql .= "(SELECT COUNT(balance.balance_id) AS token ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "WHERE balance.account_id = account.account_id "; 
		$sql .= "AND balance.account_id = token.account_id "; 
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d ", $month);
		}
		$sql .= ") AS uniqueToken, ";
		$sql .= "(SELECT COUNT(balance.balance_id) AS token ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) "; 
		$sql .= "WHERE balance.account_id != account.account_id "; 
		$sql .= "AND token.account_id = account.account_id ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d ", $month);
		}
		$sql .= ") AS sentToken, ";
		$sql .= "(SELECT COUNT(balance.balance_id) AS token "; 
		$sql .= "FROM balance "; 
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) "; 
		$sql .= "WHERE balance.account_id = account.account_id "; 
		$sql .= "AND token.account_id != account.account_id ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d ", $month);
		}
		$sql .= ") AS receivedToken, ";
		$sql .= "date_format(account.created, '%m/%d/%y') AS created ";
		$sql .= "FROM account ";
		$sql .= "LEFT JOIN user ON (account.user_id = user.user_id);";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
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
}