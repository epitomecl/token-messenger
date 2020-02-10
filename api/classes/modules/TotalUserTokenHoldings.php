<?php 
namespace modules;

use \Exception as Exception;

/**
* Token Trading Status page accessible to all users
* Total user token holdings (monthly / total)
*/
class TotalUserTokenHoldings {
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
		$obj->data = $this->getTokenHoldings($mysqli, $year, $month);
		$obj->module = (new \ReflectionClass($this))->getShortName();
				
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function getTokenHoldings($mysqli, $year, $month) {
		$data = array();		
		$sql = "SELECT account.account_id as 'accountId', account.name AS 'account', ";
		$sql .= "(SELECT COUNT(balance.balance_id) AS token  FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "WHERE balance.account_id = token.account_id ";
		$sql .= "AND balance.account_id = account.account_id ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d  ", $month);
		}
		$sql .= ") AS 'unique', ";
		$sql .= "(SELECT COUNT(balance.balance_id) AS token  FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "WHERE balance.account_id != token.account_id ";
		$sql .= "AND balance.account_id = account.account_id ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(balance.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(balance.modified) = %d  ", $month);
		}
		$sql .= ") AS 'received' ";
		$sql .= "FROM account";
		
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