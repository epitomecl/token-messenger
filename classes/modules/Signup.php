<?php

namespace modules;

use \Exception as Exception;

class Signup {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($userName, $email, $comment, $link, $lang) {
		$mysqli = $this->mysqli;

		$token = md5($email.date("Y-m-d H:i:s")); 
		$validEmail = $this->checkEmail($mysqli, $email);
		$userId = 0;
		
		if (strlen($userName) > 0 && $validEmail && strlen($email) > 0 && strlen($comment) > 0) {
			$sql = "INSERT user SET ";
			$sql .= sprintf("user.salt='%s', ", $mysqli->real_escape_string($token));
			$sql .= sprintf("user.name='%s', ", $mysqli->real_escape_string($userName));
			$sql .= sprintf("email='%s', ", $mysqli->real_escape_string($email));
			$sql .= sprintf("comment='%s', ", $mysqli->real_escape_string($comment));
			$sql .= "modified = (NOW() + INTERVAL 15 MINUTE), ";
			$sql .= "created = NOW();";
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
			
			$userId = $mysqli->insert_id;
			$subject = "Membership Confirmation Step 1";
			$body = sprintf("Confirmation link: %s?module=signup&lang=%s&token=%s", $link, $lang, $token);
			
			$this->utf8mail($email, $userName, $subject, $body);
		}
		
		$data = $this->getData($mysqli, $userId);
		
		if (!isset($data)) {
			$data = new \stdClass;
			$data->userId = 0;
			$data->email = $email;
			$data->userName = $userName;
			$data->comment = $comment;
			$data->password = "";
			$data->error = "Error user name or email or comment. In most cases the email address is already in use.";
		}
		
		require_once(sprintf("SignupView.php", $lang)); 
	}
	
	public function doGet($token, $lang) {
		$mysqli = $this->mysqli;
			
		$data = $this->getDataByToken($mysqli, $token);
		
		if (isset($data)) {
			$password = $this->getRandomPassword();
			$salt = md5($data->email);
			$sha256 = hash_hmac("sha256", $password, $salt);
			$sql = "UPDATE user SET ";
			$sql .= sprintf("password='%s', salt='%s', ", $sha256, $salt);
			$sql .= "modified = NOW() ";
			$sql .= sprintf("WHERE user_id = %d;", $data->userId);
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
			
			$data = $this->getData($mysqli, $data->userId);	
			$data->password = $password;			
		} else {
			$data = new \stdClass;
			$data->userId = 0;
			$data->email = "";
			$data->userName = "";
			$data->comment = "";
			$data->password = "";
			$data->error = (strlen($token) > 0) ? "Invalid token or token already in use." : "";
		}
		
		require_once(sprintf("SignupView.php", $lang)); 	
	}
	
	private function getData($mysqli, $userId) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, user.name AS userName, LENGTH(user.password) AS password, user.email, user.comment, user.modified, user.created ";
		$sql .= "FROM user ";
		$sql .= sprintf("WHERE user.user_id = %d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
				$data->email = trim($row["email"]);
				$data->userName = trim($row["userName"]);
				$data->comment = trim($row["comment"]);
				$data->modified = trim($row["modified"]);
				$data->created = trim($row["created"]);
				$data->password = trim($row["password"]);
			}
		}	
		return $data;
	}

	private function getDataByToken($mysqli, $token) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, user.name AS userName, LENGTH(user.password) AS password, user.email, user.comment, user.modified, user.created ";
		$sql .= "FROM user ";
		$sql .= sprintf("WHERE user.salt = '%s' ", $token);
		$sql .= "AND user.password = '' ";
		$sql .= "AND user.created < NOW() AND user.modified > NOW();";

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
				$data->email = trim($row["email"]);
				$data->userName = trim($row["userName"]);
				$data->comment = trim($row["comment"]);
				$data->modified = trim($row["modified"]);
				$data->created = trim($row["created"]);	
				$data->password = trim($row["password"]);				
			}
		}	
		return $data;
	}
	
	private function getRandomPassword( $length = 8 ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
		$password = substr( str_shuffle( $chars ), 0, $length );
		return $password;
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
	
	private function utf8mail($toEmail, $toName="", $subject, $body, $fromEmail="info@mariankulisch.de", $fromName="Marian Kulisch", $reply="") {
		$to = $toEmail;
		if (isset($toName) && strlen($toName) > 0) {
			$to = '=?UTF-8?B?'.base64_encode($toName).'?= <'.$toEmail.'>';
		}
		$subject = empty($subject) ? "Kein Betreff" : "=?utf-8?b?".base64_encode($subject)."?=";
		$headers = "MIME-Version: 1.0\r\n";
		$headers.= "From: =?utf-8?b?".base64_encode($fromName)."?= <".$fromEmail.">\r\n";
		$headers.= "Content-Type: text/html; charset=utf-8\r\n";
		$headers.= (isset($reply) && strlen($reply) > 0) ? "Reply-To: $reply\r\n" : "";
		$headers.= "X-Mailer: PHP/" . phpversion();

		return mail($to, $subject, nl2br($body), $headers);
	}	
}