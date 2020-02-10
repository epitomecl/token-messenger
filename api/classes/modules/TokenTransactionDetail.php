<?php 
namespace modules;

use \Exception as Exception;

/**
* Token Trading Status page accessible to all users
* Token transaction history sent and received by a specific account
*/
class TokenTransactionDetail {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The id of current user	
	*/		
	public function doGet($transactionId, $userId) {
		$mysqli = $this->mysqli;		

		$obj = new \stdClass();
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->transactionId = $transactionId;
		$obj->data = $this->getData($mysqli, $userId > 0 ? $transactionId : 0);
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function getData($mysqli, $transactionId) {
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
		$sql .= sprintf("WHERE transaction.transaction_id = %d;", $transactionId);
		
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