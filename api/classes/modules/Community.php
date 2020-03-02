<?php

namespace modules;

use \Exception as Exception;

/**
* Community module gives an overview about all community members and information about current voting.
* 
*/
class Community {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}

	/**
	* something describes this method
	*
	* @param int $userId The user id of current user
	* @param int $memberId The user id of the user you voted for
	* @param int $page The current page
	* @param int $itemsPerPage The count of items per page
	* @param string $searchText The search text for looking into email, username and comment
	*/	
	public function doPost($userId, $memberId, $page, $itemsPerPage, $searchText) {
		$mysqli = $this->mysqli;
		
		$this->cleanupVotes($mysqli);
		$this->updateVote($mysqli, $userId, $memberId);
		
		$module = (new \ReflectionClass($this))->getShortName();
		$poll = $this->getPoll($mysqli);
		$obj = $this->getData($mysqli, $userId, $page, $itemsPerPage, $searchText, $poll);
		$total = $obj->total;
		$data = $obj->data;
		$page = $obj->page;
		$pages = $obj->pages;
		$itemsPerPage = $obj->itemsPerPage;
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);		
		$queryString = $this->getQueryString(array("module" => $module, "itemsPerPage" => $itemsPerPage, "searchText" => $searchText));
		$attendance = $this->getVoterTurnout($mysqli);
		$winner = $this->getWinner($poll);		
		$closed = (new \DateTime())->diff(new \DateTime(date("Y-m-d 23:59:59", time())));
		$remain = $this->getRemainDays($mysqli, $userId);
		
		$obj = new \stdClass();
		$obj->userId = $userId;
		$obj->total = $total;
		$obj->data = $data;
		$obj->page = $page;
		$obj->pages = $pages;
		$obj->itemsPerPage = $itemsPerPage;
		$obj->queryString = $queryString;
		$obj->isAdmin = $isAdmin;
		$obj->isMember = $isMember;		
		$obj->attendance = $attendance;
		$obj->winner = $winner;		
		$obj->closed = $closed;
		$obj->remain = $remain;
		$obj->module = $module;
		$obj->method = "POST";
		$obj->hashValue = md5(json_encode($data));
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	/**
	* something describes this method
	*
	* @param int $userId The user id of current user
	* @param string $hashValue The hash value of current content on page
	* @param int $page The current page
	* @param int $itemsPerPage The count of items per page
	* @param string $searchText The search text for looking into email, username and comment
	*/
	public function doGet($userId, $hashValue, $page, $itemsPerPage, $searchText) {
		$mysqli = $this->mysqli;
		
		$this->cleanupVotes($mysqli);
				
		$module = (new \ReflectionClass($this))->getShortName();
		$poll = $this->getPoll($mysqli);
		$obj = $this->getData($mysqli, $userId, $page, $itemsPerPage, $searchText, $poll);
		$total = $obj->total;
		$data = $obj->data;
		$page = $obj->page;
		$pages = $obj->pages;
		$itemsPerPage = $obj->itemsPerPage;
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);		
		$queryString = $this->getQueryString(array("module" => $module, "itemsPerPage" => $itemsPerPage, "searchText" => $searchText));
		$attendance = $this->getVoterTurnout($mysqli);
		$winner = $this->getWinner($poll);	
		$closed = (new \DateTime())->diff(new \DateTime(date("Y-m-d 23:59:59", time())));
		$remain = $this->getRemainDays($mysqli, $userId);
		
		$obj = new \stdClass();
		$obj->userId = $userId;
		$obj->total = $total;
		$obj->data = $data;
		$obj->page = $page;
		$obj->pages = $pages;
		$obj->itemsPerPage = $itemsPerPage;
		$obj->queryString = $queryString;
		$obj->isAdmin = $isAdmin;
		$obj->isMember = $isMember;		
		$obj->attendance = $attendance;
		$obj->winner = $winner;		
		$obj->closed = $closed;
		$obj->remain = $remain;
		$obj->module = $module;
		$obj->method = "GET";
		$obj->hashValue = md5(json_encode($data));
		
		// no change to the data, avoid deliver same data twice
		if (strcmp($obj->hashValue, $hashValue) == 0) {
			$obj->data = array();
		}
		
		echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	}
	
	private function getQueryString($array) {
		$items = array();
		$params = array_filter($array);
		
		foreach ($params as $key => $value) {
			array_push($items, sprintf("&%s=%s", $key, urlencode($value)));
		}
		
		return implode($items);
	}

	private function getData($mysqli, $userId, $page, $itemsPerPage, $searchText, $poll) {
		$total = 0;
		$data = array();
		$where = array();
	
		if (strlen($searchText) > 0) {
			$text = "%".$mysqli->real_escape_string($searchText)."%";
			array_push($where, sprintf("(user.email LIKE '%s' OR user.name LIKE '%s' OR user.comment LIKE '%s') ", $text, $text, $text));
		}

		if (count($where) > 0) {
			// add spaceholder item for AND
			array_unshift($where, "");
		}

		// total
		$sql = "SELECT COUNT(user.user_id) AS total FROM user ";
		$sql .= "WHERE LENGTH(user.password) > 0 ";
		$sql .= implode("AND ", $where);

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$total = intval($row["total"]);
			}	
		}
		
		$itemsPerPage = ($itemsPerPage == 0) ? 50 : $itemsPerPage;
		$page = ($page == 0) ? 1 : $page; 
		$pages = ceil($total / $itemsPerPage); 
		$start = ($page - 1) * $itemsPerPage;
		
		// data
		$sql = "SELECT DISTINCT user.user_id AS userId, user.name AS userName, user.comment, ";
		$sql .= "COUNT(account.user_id) AS counter, ";
		$sql .= "IFNULL(management.user_id, 0) AS 'isMaster', ";
		$sql .= "date_format(user.modified, '%m/%d/%Y') AS modified, ";	
		$sql .= "date_format(user.created, '%m/%d/%Y') AS created, ";
		$sql .= sprintf("(SELECT COUNT(poll.user_id) FROM poll WHERE member_id=user.user_id AND user_id='%d') AS 'voted', ", $userId);
		$sql .= "'0' AS 'userGroup', ";
		$sql .= "'0' AS 'votes', ";
		$sql .= "'0' AS 'voters', ";
		$sql .= "'0' AS 'percent', ";
		$sql .= "'0' AS 'remain' ";		
		$sql .= "FROM user ";
		$sql .= "LEFT JOIN account ON (account.user_id = user.user_id) ";
		$sql .= "LEFT JOIN management ON (management.user_id = user.user_id) ";
		$sql .= "WHERE LENGTH(user.password) > 0 ";
		$sql .= implode("AND ", $where);
		$sql .= "GROUP BY user.user_id ";
		$sql .= "ORDER BY isMaster DESC, counter, userName ";
		$sql .= sprintf("LIMIT %d, %d;", $start, $itemsPerPage);

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$group = 0;
				$group = (intval($row["counter"]) > 0) ? 1 : $group;
				$group = (intval($row["isMaster"]) > 0) ? 2 : $group;
				$memberId = $row["userId"];
				if (array_key_exists($memberId, $poll)) {
					$row["votes"] = $poll[$memberId]["votes"];
					$row["voters"] = $poll[$memberId]["voters"];
					$row["percent"] = $poll[$memberId]["percent"];
				}
				$row["group"] = $group;
				array_push($data, $row);
			}	
		}
		
		$obj = new \stdClass;
		$obj->total = $total;
		$obj->data = $data;
		$obj->page = $page;
		$obj->pages = $pages;
		$obj->itemsPerPage = $itemsPerPage;
		
		return $obj;
	}
	
	private function getRemainDays($mysqli, $userId) {
		$remain = 0;
		$sql = "SELECT 30-FLOOR(HOUR(TIMEDIFF(poll.modified, NOW())) / 24) AS remain ";
		$sql .= "FROM poll ";
		$sql .= sprintf("WHERE poll.user_id=%d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$remain = intval($row["remain"]);
			}
		}

		return $remain;
	}
	
	private function isAdmin($mysqli, $userId) {
		$value = 0;
		$sql = "SELECT account.account_id FROM management ";
		$sql .= "LEFT JOIN account ON (account.user_id = management.user_id) ";
		$sql .= sprintf("WHERE account.user_id = %d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["account_id"]);			
			}
		}

		return $value;	
	}
	
	private function isMember($mysqli, $userId) {
		$value = 0;
		$sql = "SELECT COUNT(account.user_id) AS counter  ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.user_id = %d;", $userId);

		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["counter"]);			
			}
		}

		return $value;	
	}	
	
	private function cleanupVotes($mysqli) {
		$sql = "DELETE FROM poll WHERE modified < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))";
		
		if (!$mysqli->query($sql) === TRUE) {
			throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
		}		
	}
	
	private function getPoll($mysqli) {
		$data = array();

		$sql = "SELECT user.name AS userName, member_id AS memberId, ";
		$sql .= "COUNT(poll.member_id) AS votes, ";
		$sql .= "(SELECT COUNT(poll.user_id) FROM poll) AS voters, ";
		$sql .= "'0' AS percent, ";
		$sql .= "30 -FLOOR(HOUR(TIMEDIFF(poll.modified, NOW())) / 24) AS remain ";
		$sql .= "FROM `poll` ";
		$sql .= "LEFT JOIN user ON (user.user_id = poll.member_id) ";
		$sql .= "GROUP BY poll.member_id ";
		$sql .= "ORDER BY votes DESC;";

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$row["percent"] = round(doubleval(intval($row["votes"]) / intval($row["voters"])) * 100.0, 2);
				$data[$row["memberId"]] = $row;
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
		$sql = "SELECT COUNT(poll.user_id) AS total FROM poll;";

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
	
	private function getWinner($poll) {
		$winner = new \stdClass();
		$winner->userId = 0;
		$winner->userName = "";
		$winner->percent = 0.00;
		
		$data = array_values($poll);
		
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
	
	private function hasVoted($mysqli, $userId) {
		$voted = 0;
		
		$sql = "SELECT COUNT(poll.user_id) AS counter ";
		$sql .= "FROM poll ";
		$sql .= sprintf("WHERE poll.user_id = %d;", $userId);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$voted = intval($row["counter"]);
			}	
		}
		return $voted;		
	}
	
	private function updateVote($mysqli, $userId, $memberId) {
		if ($this->hasVoted($mysqli, $userId) > 0) {
			$sql = "UPDATE poll SET ";
			$sql .= sprintf("member_id='%d', ", $memberId);
			$sql .= "modified=NOW() ";			
			$sql .= sprintf("WHERE user_id='%d';", $userId);
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
		} else {
			$sql = "INSERT poll SET ";
			$sql .= sprintf("member_id='%d', ", $memberId);
			$sql .= sprintf("user_id='%d', ", $userId);
			$sql .= "modified=NOW();";
			
			if (!$mysqli->query($sql) === TRUE) {
				throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
			}
		}
	}
}