<?php 
namespace modules;

use \Exception as Exception;

/**
* Total user token history about all accounts.
*/
class TotalUserTokenHistory {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The id of current user	
	*/		
	public function doGet($year, $month) {
		$mysqli = $this->mysqli;		

		$obj = new \stdClass();
		$obj->data = $this->getUserHistory($mysqli, $year, $month);		
		$obj->module = (new \ReflectionClass($this))->getShortName();
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
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
}