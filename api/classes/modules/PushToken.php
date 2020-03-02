<?php 
namespace modules;

use \Exception as Exception;

class PushToken {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($userId, $pushToken) {
		$mysqli = $this->mysqli;
		$counter = 0;
		
		$sql = "SELECT COUNT(notification.token) AS counter FROM notification ";
		$sql .= sprintf("WHERE notification.token = '%s' ", $mysqli->real_escape_string($pushToken));
		$sql .= sprintf("AND notification.user_id=%d;", $userId);
	
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$counter = intval($row["counter"]);
			}
		}

		if ($counter == 0 && $userId > 0) {
			$sql = "INSERT notification SET ";
			$sql .= sprintf("user_id=%d, ", $userId);
			$sql .= sprintf("token='%s';", $mysqli->real_escape_string($pushToken));
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
			}
		}
		
		$obj = new \stdClass();
		$obj->userId = $userId;
		$obj->pushToken = $pushToken;
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->method = "POST";
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);		
	}
	
	public function doDelete($userId, $pushToken) {
		$mysqli = $this->mysqli;
		
		$sql = "DELETE FROM notification ";
		$sql .= sprintf("WHERE notification.token = '%s' ", $mysqli->real_escape_string($pushToken));
		$sql .= sprintf("AND notification.user_id = %d;", $userId);
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}
		
		$obj = new \stdClass();
		$obj->userId = $userId;	
		$obj->pushToken = $pushToken;
		$obj->module = (new \ReflectionClass($this))->getShortName();
		$obj->method = "DELETE";
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);	
	}
}