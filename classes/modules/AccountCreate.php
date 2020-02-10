<?php

namespace modules;

class AccountCreate {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($userId, $accountName, $suspended, $tokenName, $tokenSymbol, $tokenIcon, $lang) {
		$mysqli = $this->mysqli;
		$accountId = 0;
		
		if ($userId > 0 && strlen($accountName) >= 1 && strlen($tokenName) >= 1) {
			$sql = "INSERT INTO account SET ";
			$sql .= sprintf("user_id ='%d', ", $userId);
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

		$data = $this->getData($mysqli, $accountId);
			
		require_once(sprintf("AccountCreateView_%s.php", $lang)); 
	}

	public function doGet($userId, $lang) {
		$mysqli = $this->mysqli;
		
		$data = $this->getData($mysqli, 0);
		$arrOptionUser = $this->getArrOptionUser($mysqli);
		
		require_once(sprintf("AccountCreateView_%s.php", $lang)); 
	}
	
	private function getArrOptionUser($mysqli) {
		$data = array();
		$sql = "SELECT user.user_id, user.email, user.name AS userName, COUNT(account.user_id) AS counter ";
		$sql .= "FROM user ";
		$sql .= "LEFT JOIN account ON (user.user_id = account.user_id) ";
		$sql .= "GROUP BY user.user_id ";
		$sql .= "ORDER BY counter, user.name;";

		$data = array();
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				array_push($data, $row);			
			}
		}
		
		$option = array();	
		foreach ($data as $index => $row) {
			$sign = (intval($row["counter"]) > 0) ? "&#x1F60A;" : "&#x1F633;";
			$value = intval($row["user_id"]);
			$text = sprintf("%s %s (%s)", $sign, $row["userName"], $row["email"]);
			array_push($option, sprintf("<option value=\"%d\">%s</option>", $value, $text));			
		}
		
		return $option;	
	}
	
	private function getData($mysqli, $accountId) {
		$data = NULL;
		
		$sql = "SELECT user.user_id, email, account_id, account.name AS accountName, suspended, ";
		$sql .= "account.token AS tokenName, symbol AS tokenSymbol, icon AS tokenPath ";
		$sql .= "FROM user LEFT JOIN account ON (account.user_id = user.user_id) ";
		$sql .= sprintf("WHERE account.account_id = %d;", $accountId);
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data = new \stdClass;
				$data->userId = intval($row["user_id"]);
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
					if ($counter > 0) {
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