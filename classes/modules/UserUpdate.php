<?php

namespace modules;

use \Exception as Exception;

class UserUpdate {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($userId, $userName, $email, $password, $lang) {
		$mysqli = $this->mysqli;

		$salt = md5($email);
		$sha256 = hash_hmac("sha256", $password, $salt);	
		$validEmail = $this->checkEmail($mysqli, $email);
		$data = $this->getData($mysqli, $userId);
		
		if (isset($data)) {
			$userName = (strlen($userName) == 0) ? $data->userName : $userName;
			$email = (strlen($email) == 0 || !$validEmail) ? $data->email : $email;
			
			$sql = "UPDATE user SET ";
			if (strlen($password) >= 4) {
				$sql .= sprintf("password='%s', salt='%s', ", $sha256, $salt);
			}
			$sql .= sprintf("user.name='%s', ", $mysqli->real_escape_string($userName));
			$sql .= sprintf("email='%s' ", $mysqli->real_escape_string($email));
			$sql .= sprintf("WHERE user_id = %d;", $data->userId);
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
		}
			
		$data = $this->getData($mysqli, $userId);
		
		require_once(sprintf("UserUpdateView_%s.php", $lang)); 
	}
	
	public function doGet($userId, $lang) {
		$mysqli = $this->mysqli;
			
		$data = $this->getData($mysqli, $userId);
		
		require_once(sprintf("UserUpdateView_%s.php", $lang)); 		
	}
	
	private function getData($mysqli, $userId) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, user.name AS userName, user.email ";
		$sql .= "FROM user ";
		$sql .= sprintf("WHERE user.user_id = %d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
				$data->email = trim($row["email"]);
				$data->userName = trim($row["userName"]);
			}
		}	
		return $data;
	}
	
	private function checkEmail($mysqli, $email) {
		$success = 1;
		
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$success = 0;
		} else {
			$sql = "SELECT count(email) AS counter ";
			$sql .= "FROM user ";
			$sql .= sprintf("WHERE user.email = '%s';", $mysqli->real_escape_string($email));
			
			if ($result = $mysqli->query($sql)) {
				while($row = $result->fetch_assoc()) {
					$counter = intval($row["counter"]);
					if ($counter > 0) {
						$success = 0;
					}
				}
			}	
		}
		return $success;
	}
}