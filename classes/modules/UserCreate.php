<?php

namespace modules;

class UserCreate {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($userId, $userName, $email, $password, $accountName, $suspended, $tokenName, $tokenSymbol, $tokenIcon, $lang) {
		$mysqli = $this->mysqli;
		
		$salt = md5($email);
		$sha256 = hash_hmac("sha256", $password, $salt);	
		$validEmail = $this->checkEmail($mysqli, $email);			
			
		if ($userId == 0 && strlen($userName) > 0 && $validEmail && strlen($accountName) >= 1 && strlen($tokenName) >= 1 && strlen($password) >= 4) {
			$sql = sprintf("INSERT INTO user SET name='%s', email='%s', password='%s', salt='%s';", $mysqli->real_escape_string($userName), $mysqli->real_escape_string($email), $sha256, $salt);
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
			
			$newUserId = $mysqli->insert_id;
			
			$sql = "INSERT INTO account SET ";
			$sql .= sprintf("user_id ='%d', ", $newUserId);
			$sql .= sprintf("name ='%s', ", $mysqli->real_escape_string($accountName));
			$sql .= sprintf("suspended ='%d', ", $suspended);
			$sql .= sprintf("token ='%s', ", $mysqli->real_escape_string($tokenName));
			$sql .= sprintf("symbol ='%s';", $mysqli->real_escape_string($tokenSymbol));
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
			
			$accountId = $mysqli->insert_id;
			$tokenPath = $this->uploadFile($tokenIcon, "images/token/", 256, $accountId);	

			if (strlen($tokenPath) > 0) {
				$sql = "UPDATE account SET ";
				$sql .= sprintf("icon ='%s' ", $mysqli->real_escape_string($tokenPath));
				$sql .= sprintf("WHERE account_id = %d;", $accountId);
				
				if (!$mysqli->query($sql) === TRUE) {
					throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
				}
			}				
		}

		$data = $this->getData($mysqli, $userId);
			
		require_once(sprintf("UserCreateView_%s.php", $lang)); 
	}

	public function doGet($userId, $lang) {
		$mysqli = $this->mysqli;
		
		$data = $this->getData($mysqli, $userId);
			
		require_once(sprintf("UserCreateView_%s.php", $lang)); 
	}
	
	private function getData($mysqli, $userId) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, user.name AS userName, email, account_id, account.name AS accountName, suspended, ";
		$sql .= "account.token AS tokenName, symbol AS tokenSymbol, icon AS tokenPath ";
		$sql .= "FROM user LEFT JOIN account ON (account.user_id = user.user_id) ";
		$sql .= sprintf("WHERE user.user_id = %d LIMIT 0,1;", $userId);
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
				$data->userName = trim($row["userName"]);
				$data->email = trim($row["email"]);
				$data->accountId = intval($row["account_id"]);
				$data->accountName = trim($row["accountName"]);
				$data->suspended = intval($row["suspended"]);
				$data->tokenName = trim($row["tokenName"]);
				$data->tokenSymbol = trim($row["tokenSymbol"]);
				$data->tokenPath = trim($row["tokenPath"]);
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
					if ($counter > 1) {
						$success = 0;
					}
				}
			}	
		}
		return $success;
	}
	
	private function rebuildImageAsSquaredPng($targetFile, $imageFileType, $endSize) {
		if($imageFileType == "jpg" || $imageFileType == "jpeg" ){
			$src = imagecreatefromjpeg($targetFile);
		} else if($imageFileType == "png"){
			$src = imagecreatefrompng($targetFile);
		} else {
			$src = imagecreatefromgif($targetFile);
		}
		
		list($x, $y) = getimagesize($targetFile);
		
		// horizontal rectangle
		if ($x > $y) {
			$square = $y;              // $square: square side length
			$offsetX = ($x - $y) / 2;  // x offset based on the rectangle
			$offsetY = 0;              // y offset based on the rectangle
		}
		// vertical rectangle
		elseif ($y > $x) {
			$square = $x;
			$offsetX = 0;
			$offsetY = ($y - $x) / 2;
		}
		// it's already a square
		else {
			$square = $x;
			$offsetX = $offsetY = 0;
		}	
		
		$endSize = ($endSize == 0) ? 256 : $endSize;
		$tmp = imagecreatetruecolor($endSize, $endSize);
		
		imagecopyresampled($tmp, $src, 0, 0, $offsetX, $offsetY, $endSize, $endSize, $square, $square);
		imagepng($tmp, $targetFile);
		imagedestroy($src); 
		imagedestroy($tmp);	
	}

	private function uploadFile($file, $targetDir, $endSize, $accountId) {
		$targetFile = $targetDir . basename($file["name"]);
		$uploadOk = 1;
		$imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));
		// Check if image file is a actual image or fake image
		$check = (strlen($file["tmp_name"]) > 0) ? getimagesize($file["tmp_name"]) : false;
		if($check !== false) {
			$uploadOk = 1;
		} else {
			// echo "File is not an image.";
			$uploadOk = 0;
		}
		// Check file size
		if ($file["size"] > 500*1024) {
			// echo "Sorry, your file is too large.";
			$uploadOk = 0;
		}
		// Allow certain file formats
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
			&& $imageFileType != "gif" ) {
			// echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
			$uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 1) {
			$targetFile = sprintf("%s%s.%s", $targetDir, md5($accountId), $imageFileType);
			if (move_uploaded_file($file["tmp_name"], $targetFile)) {
				$this->rebuildImageAsSquaredPng($targetFile, $imageFileType, $endSize);
			}
		}
		
		return $uploadOk ? $targetFile : "";
	}
}