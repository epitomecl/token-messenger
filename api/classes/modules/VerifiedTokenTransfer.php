<?php 
namespace modules;

use \Exception as Exception;
use modules\DirectTokenTransfer as DirectTokenTransfer;

/**
* Token transfer from one account to another account 
* with prove of agreement and with using escrow account.
* 
*/
class VerifiedTokenTransfer extends DirectTokenTransfer {
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
		$pendingId = 0;
		$mustHave = count($receiverIds) * $quantity;
		$data = array();
		
		if ($this->hasAccountQuantity($mysqli, $senderId, $mustHave)) {
			foreach ($receiverIds as $index => $receiverId) {		
				if ($this->hasReceiverAccount($mysqli, $receiverId)) {
					if ($this->hasAccountQuantity($mysqli, $senderId, $quantity)) {
						if ($this->updateBalance($mysqli, $senderId, 0, $quantity)) {
							$pendingId = $this->insertPending($mysqli, $senderId, $receiverId, $quantity, $reference);
						}
					}
				}
				
				$obj = new \stdClass();
				$obj->pendingId = $pendingId;
				$obj->sender = $this->getAccountName($mysqli, $senderId);			
				$obj->receiver = $this->getAccountName($mysqli, $receiverId);
				$obj->account = $obj->receiver;
				$obj->accountId = $receiverId;	
				$obj->quantity = $quantity;
				$obj->reference = $reference;
				$obj->datetime = date("m/d/y H:i", time());
				$obj->method = "POST";

				array_push($data, $obj);					
			}
		}
		$obj = new \stdClass();
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->data = $data;
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	/**
	* something describes this method
	*
	* @param int $senderId The id of sender account
	*/		
	public function doPut($pendingId, $senderId, $confirmed, $message) {
		$mysqli = $this->mysqli;
		$pending = $this->getPendingObjectBySenderId($mysqli, $pendingId, $senderId);
		$accountId = 0;
		$transactionId = 0;
		$quantity = 0;
		$reference = "";
		
		if (isset($pending)) {
			if ($this->deletePendingBySenderId($mysqli, $pendingId, $senderId)) {
				$accountId = ($confirmed) ? $pending->receiverId : $senderId;
				$quantity = $pending->quantity;
				$reference = $pending->reference;
				$supplement = $message; 
				$status = ($confirmed) ? "confirmed" : "rejected";
				$created = $pending->modified; 
				
				if ($this->updateBalanceOnPut($mysqli, $senderId, $accountId, $quantity)) {
					$transactionId = $this->insertTransaction($mysqli, $senderId, $pending->receiverId, $quantity, $reference, $supplement, $status, $created);
				}
			}
		}
		
		$obj = new \stdClass();
		$obj->pendingId = $pendingId;		
		$obj->transactionId = $transactionId;
		$obj->account = $this->getAccountName($mysqli, $accountId);	
		$obj->accountId = $accountId;		
		$obj->quantity = $quantity;
		$obj->reference = $reference;
		$obj->datetime = date("m/d/y H:i", time());	
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->method = "PUT";
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	/**
	* something describes this method
	*
	* @param int $receiverId The id of receiver account	
	*/	
	public function doDelete($pendingId, $receiverId, $message) {
		$mysqli = $this->mysqli;
		$pending = $this->getPendingObjectByReceiverId($mysqli, $pendingId, $receiverId);
		$accountId = 0;
		$transactionId = 0;
		$quantity = 0;
		$reference = "";
		
		if (isset($pending)) {
			if ($this->deletePendingByReceiverId($mysqli, $pendingId, $receiverId)) {
				$accountId = $pending->senderId;
				$quantity = $pending->quantity;
				$reference = $pending->reference;
				$supplement = $message; 
				$status = "withdrawal";
				$created = $pending->modified; 

				if ($this->updateBalanceOnDelete($mysqli, $accountId, $quantity)) {
					$transactionId = $this->insertTransaction($mysqli, $accountId, $receiverId, $quantity, $reference, $supplement, $status, $created);
				}
			}
		}
		
		$obj = new \stdClass();
		$obj->pendingId = $pendingId;		
		$obj->transactionId = $transactionId;
		$obj->account = $this->getAccountName($mysqli, $accountId);	
		$obj->accountId = $accountId;		
		$obj->quantity = $quantity;
		$obj->reference = $reference;
		$obj->datetime = date("m/d/y H:i", time());	
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->method = "DELETE";
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function insertTransaction($mysqli, $senderId, $receiverId, $quantity, $reference, $supplement, $status, $created) {
		$sql = "INSERT transaction SET ";
		$sql .= sprintf("sender_id = %d, ", $senderId);
		$sql .= sprintf("receiver_id = %d, ", $receiverId);		
		$sql .= sprintf("quantity = %d, ", $quantity);
		$sql .= sprintf("reference = '%s', ", $mysqli->real_escape_string($reference));
		$sql .= sprintf("supplement = '%s', ", $mysqli->real_escape_string($supplement));	
		$sql .= sprintf("status = '%s', ", $status);			
		$sql .= sprintf("created = '%s', ", $created);	
		$sql .= sprintf("modified = NOW();");	
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $mysqli->insert_id;
	}
	
	private function insertPending($mysqli, $senderId, $receiverId, $quantity, $reference) {
		$sql = "INSERT pending SET ";
		$sql .= sprintf("sender_id = %d, ", $senderId);
		$sql .= sprintf("receiver_id = %d, ", $receiverId);		
		$sql .= sprintf("quantity = %d, ", $quantity);
		$sql .= sprintf("reference = '%s';", $mysqli->real_escape_string($reference));
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $mysqli->insert_id;
	}
	
	private function getPendingObjectBySenderId($mysqli, $pendingId, $senderId) {
		$obj = NULL;
		$sql = "SELECT pending_id, sender_id, receiver_id, quantity, reference, modified ";
		$sql .= "FROM pending ";
		$sql .= "WHERE pending_id = %d AND sender_id = %d;";
		$sql = sprintf($sql, $pendingId, $senderId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$obj = new \stdClass();
				$obj->pendingId = intval($row["pending_id"]);
				$obj->senderId = intval($row["sender_id"]);
				$obj->receiverId = intval($row["receiver_id"]);
				$obj->quantity = intval($row["quantity"]);
				$obj->reference = trim($row["reference"]);
				$obj->modified = trim($row["modified"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $obj;		
	}
	
	private function getPendingObjectByReceiverId($mysqli, $pendingId, $receiverId) {
		$obj = NULL;
		$sql = "SELECT pending_id, sender_id, receiver_id, quantity, reference, modified ";
		$sql .= "FROM pending ";
		$sql .= "WHERE pending_id = %d AND receiver_id = %d;";
		$sql = sprintf($sql, $pendingId, $receiverId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$obj = new \stdClass();
				$obj->pendingId = intval($row["pending_id"]);
				$obj->senderId = intval($row["sender_id"]);
				$obj->receiverId = intval($row["receiver_id"]);
				$obj->quantity = intval($row["quantity"]);
				$obj->reference = trim($row["reference"]);
				$obj->modified = intval($row["modified"]);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $obj;		
	}
	
	private function deletePendingBySenderId($mysqli, $pendingId, $senderId) {
		$sql = "DELETE FROM pending ";
		$sql .= sprintf("WHERE pending.pending_id = %d ", $pendingId);
		$sql .= sprintf("AND pending.sender_id = %d;", $senderId);
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $mysqli->affected_rows;
	}
	
	private function deletePendingByReceiverId($mysqli, $pendingId, $receiverId) {
		$sql = "DELETE FROM pending ";
		$sql .= sprintf("WHERE pending.pending_id = %d ", $pendingId);
		$sql .= sprintf("AND pending.receiver_id = %d;", $receiverId);
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $mysqli->affected_rows;
	}	
	
	private function updateBalanceOnDelete($mysqli, $senderId, $quantity) {
		$data = array();
		$sql = "SELECT token.token_id FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "WHERE balance.account_id = 0 ";
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
				$sql .= sprintf("SET account_id = %d, ", $senderId);
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
	
	private function updateBalanceOnPut($mysqli, $senderId, $accountId, $quantity) {
		$data = array();
		$sql = "SELECT token.token_id FROM balance ";
		$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
		$sql .= "WHERE balance.account_id = 0 ";
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
				$sql .= sprintf("SET account_id = %d, ", $accountId);
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