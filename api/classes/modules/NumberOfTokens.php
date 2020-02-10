<?php 
namespace modules;

use \Exception as Exception;

/**
* Token Trading Status page accessible to all users
* Number of tokens held by a specific account (monthly / total)
*/
class NumberOfTokens {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The id of current user	
	*/		
	public function doGet($accountId, $year, $month) {
		$mysqli = $this->mysqli;		

		$obj = new \stdClass();
		$obj->account = $this->getAccountName($mysqli, $accountId);		
		$obj->unique = $this->getUniqueToken($mysqli, $accountId, $year, $month);
		$obj->received = $this->getReceivedToken($mysqli, $accountId, $year, $month);
		$obj->total = $this->getTotalReceivedToken($obj->received);
		$obj->module = (new \ReflectionClass($this))->getShortName();
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function getAccountName($mysqli, $accountId) {
		$account = "";		
		$sql = "SELECT account.name AS 'account' ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.account_id = %d ", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$account = trim($row["account"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $account;
	}
	
	private function getTotalReceivedToken($received) {
		$total = 0;
		
		if (isset($received) && is_array($received)) {
			foreach ($received as $index => $row) {
				$total += intval($row["token"]);
			}
		}
		
		return $total;
	}
	
	private function getReceivedToken($mysqli, $accountId, $year, $month) {
		$data = array();		
		$sql = "SELECT COUNT(balance.balance_id) AS token, account.account_id AS 'accountId', account.name AS 'account' ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
		$sql .= "AND balance.account_id != token.account_id ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d  ", $month);
		}
		$sql .= "GROUP BY token.account_id ORDER BY token DESC;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $data;
	}
	
	private function getUniqueToken($mysqli, $accountId, $year, $month) {
		$token = 0;
		$sql = "SELECT COUNT(balance.balance_id) AS token ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
		$sql .= "AND balance.account_id = token.account_id ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d  ", $month);
		}
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
}