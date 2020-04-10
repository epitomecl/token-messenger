<?php 
namespace modules;

use \Exception as Exception;

/**
* Direct transfer of quantity of owned unique token with specific message and without possibility of cancelation.
*/
class DirectTokenTransfer {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The id of current user	
	*/		
	public function doPost($senderId, $receiverIds, $quantity, $reference) {
		$mysqli = $this->mysqli;		
		$transactionId = 0;
		$mustHave = count($receiverIds) * $quantity;
		$data = array();
		
		if ($this->hasAccountQuantity($mysqli, $senderId, $mustHave)) {
			foreach ($receiverIds as $index => $receiverId) {
				if ($this->hasReceiverAccount($mysqli, $receiverId)) {
					if ($this->hasAccountQuantity($mysqli, $senderId, $quantity)) {
						if ($this->updateBalance($mysqli, $senderId, $receiverId, $quantity)) {
							$transactionId = $this->insertTransaction($mysqli, $senderId, $receiverId, $quantity, $reference);
						}
					}
				}
				
				$obj = new \stdClass();
				$obj->transactionId = $transactionId;
				$obj->sender = $this->getAccountName($mysqli, $senderId);			
				$obj->receiver = $this->getAccountName($mysqli, $receiverId);	
				$obj->quantity = $quantity;
				$obj->reference = $reference;
				$obj->datetime = date("m/d/Y H:i", time());
				
				array_push($data, $obj);
			}
		}
		
		$obj = new \stdClass();
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->data = $data;
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	protected function getAccountName($mysqli, $accountId) {
		$account = "";		
		$sql = "SELECT account.name AS 'account' ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.account_id = %d;", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$account = trim($row["account"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $account;
	}
	
	protected function hasAccountQuantity($mysqli, $accountId, $quantity) {
		$counter = 0;		
		$sql = "SELECT COUNT(token.account_id) AS quantity ";
		$sql .= "FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
		$sql .= sprintf("AND token.account_id = %d;", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$counter = intval($row["quantity"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $counter >= abs($quantity);
	}
	
	protected function hasReceiverAccount($mysqli, $accountId) {
		$counter = 0;		
		$sql = "SELECT COUNT(account.name) AS counter FROM account ";
		$sql .= sprintf("WHERE account.account_id = %d;", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$counter = intval($row["counter"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $counter > 0;
	}
	
	private function insertTransaction($mysqli, $senderId, $receiverId, $quantity, $reference) {
		$sql = "INSERT transaction SET ";
		$sql .= sprintf("sender_id = %d, ", $senderId);
		$sql .= sprintf("receiver_id = %d, ", $receiverId);		
		$sql .= sprintf("quantity = %d, ", $quantity);
		$sql .= sprintf("reference = '%s', ", $mysqli->real_escape_string($reference));
		$sql .= sprintf("status = '%s';", "confirmed");	
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $mysqli->insert_id;
	}
	
	protected function updateBalance($mysqli, $senderId, $receiverId, $quantity) {
		$data = array();
		$sql = "SELECT token.token_id FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= sprintf("WHERE balance.account_id = %d ", $senderId);
		$sql .= sprintf("AND token.account_id = %d ", $senderId);
		$sql .= sprintf("LIMIT 0, %d;", $quantity);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, intval($row["token_id"]));
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		if (count($data) == $quantity) {
			foreach ($data as $index => $tokenId) {
				$sql = "UPDATE balance ";
				$sql .= sprintf("SET account_id = %d, ", $receiverId);
				$sql .= "modified=NOW() ";
				$sql .= sprintf("WHERE balance.token_id = %d;", $tokenId);

				if (!$mysqli->query($sql) === TRUE) {
					throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
				}
			}
			
			return true;
		}
		
		return false;
	}
}