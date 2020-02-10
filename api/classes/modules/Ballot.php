<?php

namespace modules;

use \Exception as Exception;

class Ballot {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost() {
		$this->confirm();
		
		$obj = new \stdClass();

		$obj->module = (new \ReflectionClass($this))->getShortName();
				
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);		
	}
	
	public function confirm() {
		$mysqli = $this->mysqli;
		
		$this->cleanupVotes($mysqli);
		
		$date1 = time();
		$date2 = strtotime(date("Y-m-d 00:00:00"));
		$date3 = strtotime(date("Y-m-d 05:00:00"));
		
		if ($date1 >= $date2 && $date1 <= $date3)
		{
			$data = $this->getData($mysqli);

			if (count($data) == 0){
				$attendance = $this->getVoterTurnout($mysqli);
				
				if ($attendance > 50.0) {
					$data = $this->getData($mysqli);
					$winner = $this->getWinner($data);
					
					$userId = $winner->userId;
					$adminId = $this->getAdminId($mysqli);
					$voted = $this->isVoted($mysqli, $adminId, date("Y-m-d"));
					
					if (!$voted && $userId > 0 && $adminId > 0) {
						$sql = sprintf("UPDATE account SET user_id=%d, modified=NOW() WHERE user_id=%d;", $userId, $adminId);
						
						if (!$mysqli->query($sql) === TRUE) {
							throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
						}
						
						$sql = sprintf("UPDATE management SET user_id=%d, voted=NOW(), modified=NOW() WHERE user_id=%d;", $userId, $adminId);
						
						if (!$mysqli->query($sql) === TRUE) {
							throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
						}
					}
				}
			}		
		}
	}
	
	private function cleanupVotes($mysqli) {
		$sql = "DELETE FROM poll WHERE modified < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))";
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
		}		
	}
	
	private function getData($mysqli) {
		$data = array();

		$sql = "SELECT user.name AS userName, COUNT(poll.member_id) AS votes, member_id AS memberId, ";
		$sql .= "(SELECT COUNT(poll.user_id) FROM poll) AS voters, ";
		$sql .= "'0' AS percent ";
		$sql .= "FROM `poll` ";
		$sql .= "LEFT JOIN user ON (user.user_id = poll.member_id) ";
		$sql .= "GROUP BY poll.member_id ";
		$sql .= "ORDER BY votes DESC;";

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$row["percent"] = round(doubleval(intval($row["votes"]) / intval($row["voters"])) * 100.0, 2);
				array_push($data, $row);
			}	
		}
		
		return $data;
	}
	
	private function getTotalUsers($mysqli) {
		$total = 0;
		$sql = "SELECT COUNT(DISTINCT account.user_id) AS total FROM account";
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$total = intval($row["total"]);
			}	
		}

		return $total;
	}
	
	private function getTotalVoters($mysqli) {
		$total = 0;		
		$sql = "SELECT COUNT(poll.user_id) AS total FROM poll ";

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$total = intval($row["total"]);
			}	
		}
		
		return $total;
	}
	
	private function getVoterTurnout($mysqli) {
		$users = $this->getTotalUsers($mysqli);
		$voters = $this->getTotalVoters($mysqli);
		$percent = round(doubleval($voters / $users) * 100.0, 2);

		return $percent;
	}
	
	private function getWinner($data) {
		$winner = new \stdClass();
		$winner->userId = 0;
		$winner->userName = "";
		$winner->percent = 0.00;
		
		if (count($data) == 1) {
			$one = $data[0];
			
			if (doubleval($one["percent"]) > 50.0) {
				$winner->userId = intval($one["memberId"]);
				$winner->userName = trim($one["userName"]);
				$winner->percent = doubleval($one["percent"]);				
			}
		}
		
		if (count($data) > 1) {
			$one = $data[0];
			$two = $data[1];
			
			if (doubleval($one["percent"]) > doubleval($two["percent"]) && doubleval($one["percent"]) > 50.0) {
				$winner->userId = intval($one["memberId"]);
				$winner->userName = trim($one["userName"]);
				$winner->percent = doubleval($one["percent"]);				
			}
		}
		
		return $winner;
	}

	// admin has access for community account
	private function getAdminId($mysqli) {
		$value = 0;
		$sql = "SELECT management.user_id AS userId, voted, COUNT(account.account_id) AS accounts ";
		$sql .= "FROM management ";
		$sql .= "LEFT JOIN account ON (account.user_id = management.user_id) ";
		$sql .= "WHERE account.community = 1 ";
		$sql .= "GROUP BY management.user_id;";

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["userId"]);			
			}
		}

		return $value;	
	}
	
	private function isVoted($mysqli, $userId, $strDate) {
		$value = 0;
		$sql = "SELECT user_id AS userId FROM management ";
		$sql .= sprintf("WHERE DATE(voted)='%s' ", $strDate);
		$sql .= sprintf("AND user_id=%d;", $userId);
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["userId"]);			
			}
		}
		
		return $value > 0;
	}
}