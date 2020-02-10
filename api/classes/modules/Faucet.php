<?php 
namespace modules;

use \Exception as Exception;

/**
* Minted new token and update the account balance and submit a direct transaction with automatic reference.
*/
class Faucet {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	* For all user accounts every day 6 token maximal will be minted.
	* Each minted unique token will be transfered directly to related user account.
	*/	
	public function doPost() {
		$obj = new \stdClass();
		$obj->token = $this->distributeToken();
		$obj->module = (new \ReflectionClass($this))->getShortName();
				
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);		
	}
	
	public function distributeToken() {
		$mysqli = $this->mysqli;		
		$total = 6;
		$accountIds = $this->getListAccount($mysqli);
		$mintedTokens = $this->getMintedTokens($mysqli, $accountIds);
		$openDays = $this->getOpenDays($mysqli, $accountIds);
		$todayTokens = $this->getQuantityForToday($mysqli, $accountIds);
		$counter = 0;

		foreach ($accountIds as $index => $accountId) {
			$token = $this->getAccountToken($mysqli, $accountId);
			
			if (!isset($token)) {
				continue;
			}
			
			$tokenName = $token->tokenName;
			$tokenSymbol = $token->tokenSymbol;

			$minted = $mintedTokens[$accountId];
			$today = $todayTokens[$accountId];
			$reference = "as gift and energy of the community";
			
			// $quantity = ($openDays[$accountId] > 0 && $minted > 0 && $minted < $total) ? $total - $minted : 0;
			// $quantity += ($openDays[$accountId] - 1 > 0) ? ($openDays[$accountId] - 1) * $total : 0;

			// if ($quantity > 0) {
				// $datetime = date('Y-m-d 23:59:00', strtotime('-1 day', time()));				
				// $tokenIds = $this->mintToken($mysqli, $accountId, $tokenName, $tokenSymbol, $quantity, $datetime);
				// $this->insertBalance($mysqli, $accountId, $tokenIds, $datetime);
				// $this->insertTransaction($mysqli, $accountId, $quantity, $reference, $datetime);
				// $counter += $quantity;
			// }
			
			$quantity = ($today < $total) ? 1 : 0;		

			if ($quantity > 0) {
				$datetime = date('Y-m-d H:i:s', time());
				$tokenIds = $this->mintToken($mysqli, $accountId, $tokenName, $tokenSymbol, $quantity, $datetime);
				$this->insertBalance($mysqli, $accountId, $tokenIds, $datetime);
				$this->insertTransaction($mysqli, $accountId, $quantity, $reference, $datetime);
				$counter += $quantity;
			}
		}
		
		return $counter;
	}
	
	private function getMintedTokens($mysqli, $accountIds) {
		$data = array();		
		$sql = "SELECT COUNT(t.token_id) AS 'token', account_id AS accountId ";
		$sql .= "FROM token t ";
		$sql .= "WHERE DATEDIFF(modified, (SELECT max(modified) FROM token WHERE token.account_id = t.account_id)) = 0 ";
		$sql .= "GROUP BY t.account_id";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$key = trim($row["accountId"]);
				$value = intval($row["token"]);
				$data[$key] = $value;
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		foreach ($accountIds as $index => $accountId) {
			if (!array_key_exists($accountId, $data)) {
				$data[$accountId] = 0;
			}
		}
		
		return $data;	
	}
	
	private function getOpenDays($mysqli, $accountIds) {
		$data = array();		
		$sql = "SELECT account_id AS accountId, DATEDIFF(NOW(), max(modified)) AS days ";
		$sql .= "FROM token ";
		$sql .= "GROUP BY account_id;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$key = trim($row["accountId"]);
				$value = intval($row["days"]);
				$data[$key] = $value;
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		foreach ($accountIds as $index => $accountId) {
			if (!array_key_exists($accountId, $data)) {
				$data[$accountId] = 0;
			}
		}
		
		return $data;	
	}
	
	private function getQuantityForToday($mysqli, $accountIds) {
		$data = array();		
		$sql = "SELECT COUNT(t.token_id) AS 'token', account_id AS accountId ";
		$sql .= "FROM token t ";
		$sql .= "WHERE modified BETWEEN CURDATE() AND NOW() ";
		$sql .= "GROUP BY t.account_id;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$key = trim($row["accountId"]);
				$value = intval($row["token"]);
				$data[$key] = $value;
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		foreach ($accountIds as $index => $accountId) {
			if (!array_key_exists($accountId, $data)) {
				$data[$accountId] = 0;
			}
		}
		
		return $data;		
	}
	
	private function getAccountToken($mysqli, $accountId) {
		$obj = NULL;	
		$sql = "SELECT account.token AS 'tokenName', account.symbol AS tokenSymbol ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.account_id = %d ", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$tokenName = trim($row["tokenName"]);
				$tokenSymbol = trim($row["tokenSymbol"]);
				
				if (strlen($tokenName) > 0 && strlen($tokenSymbol) > 0) {
					$obj = new \stdClass;
					$obj->tokenName = $tokenName;
					$obj->tokenSymbol = $tokenSymbol;
				}
				break;
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $obj;
	}
	
	private function getListAccount($mysqli) {
		$data = array();		
		$sql = "SELECT account_id AS accountId FROM account ";
		$sql .= "WHERE account.suspended = 0 ";
		$sql .= "AND community = 0;";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, intval($row["accountId"]));
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $data;
	}
	
	private function insertTransaction($mysqli, $receiverId, $quantity, $reference, $datetime) {
		$sql = "INSERT transaction SET ";
		$sql .= sprintf("sender_id = %d, ", 0);
		$sql .= sprintf("receiver_id = %d, ", $receiverId);		
		$sql .= sprintf("quantity = %d, ", $quantity);
		$sql .= sprintf("reference = '%s', ", $mysqli->real_escape_string($reference));
		$sql .= sprintf("created = '%s', ", $datetime);		
		$sql .= sprintf("modified = '%s';", $datetime);
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		return $mysqli->insert_id;
	}
	
	private function insertBalance($mysqli, $receiverId, $tokenIds, $datetime) {
		foreach ($tokenIds as $index => $tokenId) {
			$sql = "INSERT balance ";
			$sql .= sprintf("SET account_id = %d, ", $receiverId);
			$sql .= sprintf("token_id = %d, ", $tokenId);
			$sql .= sprintf("modified = '%s';", $datetime);			

			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
			}
		}
	}
	
	private function mintToken($mysqli, $accountId, $tokenName, $tokenSymbol, $quantity, $datetime) {
		$data = array();
		for ($index = 0; $index < $quantity; $index++) {
			$sql = "INSERT token ";
			$sql .= sprintf("SET account_id = %d, ", $accountId);
			$sql .= sprintf("name = '%s', ", $mysqli->real_escape_string($tokenName));
			$sql .= sprintf("symbol = '%s', ", $mysqli->real_escape_string($tokenSymbol));			
			$sql .= sprintf("modified = '%s';", $datetime);
		
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
			}
			
			array_push($data, $mysqli->insert_id);
		}
		
		return $data;
	}	
}
