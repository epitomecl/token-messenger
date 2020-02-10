<?php 
namespace modules;

use \Exception as Exception;

/**
* List all pending transaction data.
*/
class PendingTransaction {
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

		$data = NULL;
		
		if ($userId > 0) {
			$data = array_merge(array(), $this->getListPendingTransactionByReceiver($mysqli, $userId));
			$data = array_merge($data, $this->getListPendingTransactionBySender($mysqli, $userId));
		}
		
		$obj = new \stdClass();
		$obj->data = $data;
		$obj->module = (new \ReflectionClass($this))->getShortName();
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function getListPendingTransactionByReceiver($mysqli, $userId) {
		$data = array();		
		$sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
		$sql .= "pending.pending_id AS pendingId, quantity, reference, 'PUT' AS 'NGINX', ";
		$sql .= "date_format(pending.modified, '%m/%d/%Y %H:%i') AS datetime ";
		$sql .= "FROM pending ";
		$sql .= "LEFT JOIN account ON (account.account_id = pending.sender_id) ";
		$sql .= "WHERE pending.receiver_id IN ";
		$sql .= sprintf("(SELECT account.account_id FROM account WHERE account.user_id = %d) ", $userId);
		$sql .= "ORDER BY pending.modified DESC;";
		
		// if ($this->isAdmin($mysqli, $userId)) {
			// $sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
			// $sql .= "pending.pending_id AS pendingId, quantity, reference, 'PUT' AS 'NGINX', ";
			// $sql .= "date_format(pending.modified, '%m/%d/%Y %H:%i') AS datetime ";
			// $sql .= "FROM pending ";
			// $sql .= "LEFT JOIN account ON (account.account_id = pending.sender_id) ";
			// $sql .= "ORDER BY pending.modified DESC;";			
		// }
		
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
		$sql .= "pending.pending_id AS pendingId, quantity, reference, 'DELETE' AS 'NGINX', ";
		$sql .= "date_format(pending.modified, '%m/%d/%Y %H:%i') AS datetime ";
		$sql .= "FROM pending ";
		$sql .= "LEFT JOIN account ON (account.account_id = pending.receiver_id) ";
		$sql .= "WHERE pending.sender_id IN ";
		$sql .= sprintf("(SELECT account.account_id FROM account WHERE account.user_id = %d) ", $userId);
		$sql .= "ORDER BY pending.modified DESC;";
		
		// if ($this->isAdmin($mysqli, $userId)) { 
			// $sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
			// $sql .= "pending.pending_id AS pendingId, quantity, reference, 'DELETE' AS 'NGINX', ";
			// $sql .= "date_format(pending.modified, '%m/%d/%Y %H:%i') AS datetime ";
			// $sql .= "FROM pending ";
			// $sql .= "LEFT JOIN account ON (account.account_id = pending.receiver_id) ";
			// $sql .= "ORDER BY pending.modified DESC;";			
		// }
		
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
}