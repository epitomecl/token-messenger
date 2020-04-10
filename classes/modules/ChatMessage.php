<?php

namespace modules;

use \Exception as Exception;

class ChatMessage {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doGet($userId, $page, $itemsPerPage, $senderId, $receiverId, $lang) {
		$mysqli = $this->mysqli;
		$module = (new \ReflectionClass($this))->getShortName();		
		$mainAccount = $this->getMainAccount($mysqli, $userId);
		$txtOptionMonth = $this->getOptionArrayMonth(date("n"));
		$txtOptionYear = $this->getOptionArrayYear(date("Y"));
		$arrOptionAccount = $this->getArrOptionAccount($mysqli, $receiverId);
		$arrOptionUserAccount = $this->getArrOptionUserAccount($mysqli, $userId, $mainAccount->accountId);
		$obj = new \stdClass();
		if ($receiverId == 0) {
			$obj = $this->getChatData($mysqli, $page, $itemsPerPage, ($senderId == 0) ? $mainAccount->accountId : $senderId);
		} else {
			$obj = $this->getMessageData($mysqli, $page, $itemsPerPage, $senderId, $receiverId, date("Y"), date("m"));
		}
		$total = $obj->total;
		$data = $obj->data;
		$page = $obj->page;
		$pages = $obj->pages;

		$itemsPerPage = $obj->itemsPerPage;	
		$queryString = $this->getQueryString(array("module" => $module, "itemsPerPage" => $itemsPerPage, "senderId" => $senderId, "receiverId" => $receiverId));		
		$isAdmin = $this->isAdmin($mysqli, $userId);
		$isMember = $this->isMember($mysqli, $userId);
		
		require_once(sprintf("ChatMessageView.php")); 
	}

	private function getQueryString($array) {
		$items = array();
		$params = array_filter($array);
		
		foreach ($params as $key => $value) {
			array_push($items, sprintf("&%s=%s", $key, urlencode($value)));
		}
		
		return implode($items);
	}
	
	private function getArrOptionAccount($mysqli, $accountId) {
		$data = array();
		$sql = "SELECT account.account_id, account.name, user.name AS userName FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		
		$data = array();
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				array_push($data, $row);			
			}
		}
		
		$option = array();	
		foreach ($data as $index => $row) {
			$userName = trim($row["userName"]);
			$value = intval($row["account_id"]);
			$selected = ($value == $accountId) ? " selected=\"selected\"" : "";
			$text = trim($row["name"]);
			array_push($option, sprintf("<option value=\"%d\"%s>%s (%s)</option>", $value, $selected, $text, $userName));			
		}
		
		return $option;	
	}

	private function getMainAccount($mysqli, $userId) {
		$sql = "SELECT account.account_id AS accountId, account.name AS accountName, account.suspended, user.name AS userName ";
		$sql .= "FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= sprintf("WHERE account.user_id = %d ", $userId);
		$sql .= "ORDER BY account.community ";		
		$sql .= "LIMIT 0,1;";
		
		$data = new \stdClass();
		$data->accountId = 0;
		$data->accountName = "";
		$data->userName = "";
		$data->suspended = 0;
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$data->accountId = intval($row["accountId"]);
				$data->accountName = trim($row["accountName"]);
				$data->userName = trim($row["userName"]);
				$data->suspended = intval($row["suspended"]);
			}
		}
		
		return $data;
	}
	
	private function getArrOptionUserAccount($mysqli, $userId, $accountId) {
		$sql = "SELECT account.account_id AS accountId, account.name AS accountName ";
		$sql .= "FROM account ";
		$sql .= sprintf("WHERE account.user_id = %d ", $userId);
		$sql .= "ORDER BY account.community;";
		
		$option = array();
		
		if ($result = $mysqli->query($sql)) {
			while($row = $result->fetch_assoc()) {
				$value = intval($row["accountId"]);
				$text = trim($row["accountName"]);
				$selected = ($value == $accountId) ? " selected=\selected\"" : "";

				array_push($option, sprintf("<option value=\"%d\"%s>%s</option>", $value, $selected, $text));
			}
		}
		
		return $option;
	}
	
	private function getOptionArrayMonth($month) {
		$data = array("<option value=\"0\"> </option>");
		
		for ($index = 1; $index <= 12; $index++) {
			$selected = ($index == $month) ? " selected=\selected\"" : "";
			$text = date('F', mktime(0, 0, 0, $index, 10));
			array_push($data, sprintf("<option value=\"%d\"%s>%s</option>", $index, $selected, $text));
		}
		
		return implode("\n", $data);
	}

	private function getOptionArrayYear($year) {
		$data = array("<option value=\"0\"> </option>");
		
		for ($index = 2019; $index <= 2030; $index++) {
			$selected = ($index == $year) ? " selected=\selected\"" : "";
			$text = $index;
			array_push($data, sprintf("<option value=\"%d\"%s>%s</option>", $index, $selected, $text));
		}
		
		return implode("\n", $data);	
	}
	
	private function getChatData($mysqli, $page, $itemsPerPage, $senderId) {
		$total = 0;		
		$data = array();

		// total
		$sql = "SELECT COUNT(*) AS total FROM transaction ";
		$sql .= "LEFT JOIN ( ";
		$sql .= "SELECT accountId, MAX(modified) as modified FROM ( ";
		$sql .= "SELECT receiver_id AS accountId, modified FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		$sql .= sprintf("AND transaction.sender_id = %d ", $senderId);
		$sql .= "UNION ";
		$sql .= "SELECT sender_id AS accountId, modified FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 AND transaction.sender_id > 0 ";
		$sql .= sprintf("AND transaction.receiver_id = %d ", $senderId);
		$sql .= ") tbl ";
		$sql .= "GROUP BY tbl.accountId) foo ";
		$sql .= "ON (((transaction.receiver_id = foo.accountId ";
		$sql .= sprintf("AND transaction.sender_id = %s) ", $senderId);
		$sql .= "OR (transaction.sender_id = foo.accountId ";
		$sql .= sprintf("AND transaction.receiver_id = %d)) ", $senderId); 
		$sql .= "AND transaction.modified = foo.modified) ";
		$sql .= "WHERE accountId IS NOT NULL;";

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
		$sql = "SELECT accountId, (SELECT user.name FROM user LEFT JOIN account ON (user.user_id = account.user_id) WHERE account.account_id = accountId) AS userName, ";
		$sql .= "IFNULL(transaction.sender_id, 0) AS 'senderId', ";
		$sql .= "IFNULL((SELECT account.name FROM account WHERE account.account_id = transaction.sender_id), 'Faucet') AS 'senderName', ";
		$sql .= "IFNULL(transaction.receiver_id, 0) AS 'receiverId', ";
		$sql .= "(SELECT account.name FROM account WHERE account.account_id = transaction.receiver_id) AS 'receiverName', ";
		$sql .= "quantity, account.token, account.symbol, account.icon, reference, status, supplement, ";
		$sql .= "date_format(transaction.created, '%m/%d/%Y %H:%i') AS created, ";
		$sql .= "date_format(transaction.modified, '%m/%d/%Y %H:%i') AS modified ";
		$sql .= "FROM transaction ";
		$sql .= "LEFT JOIN ( ";
		$sql .= "SELECT accountId, MAX(created) as created FROM ( ";
		$sql .= "SELECT receiver_id AS accountId, created FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		$sql .= sprintf("AND transaction.sender_id = %d ", $senderId);
		$sql .= "UNION ";
		$sql .= "SELECT sender_id AS accountId, created FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 AND transaction.sender_id > 0 ";
		$sql .= sprintf("AND transaction.receiver_id = %d ", $senderId);
		$sql .= ") tbl ";
		$sql .= "GROUP BY tbl.accountId) foo ";
		$sql .= "ON (((transaction.receiver_id = foo.accountId ";
		$sql .= sprintf("AND transaction.sender_id = %s) ", $senderId);
		$sql .= "OR (transaction.sender_id = foo.accountId ";
		$sql .= sprintf("AND transaction.receiver_id = %d)) ", $senderId); 
		$sql .= "AND transaction.created = foo.created) ";
		$sql .= "LEFT JOIN account ON (account.account_id = IF(transaction.sender_id = 0, transaction.receiver_id, transaction.sender_id)) ";
		$sql .= "WHERE accountId IS NOT NULL ";
		$sql .= "ORDER BY transaction.created DESC ";
		$sql .= sprintf("LIMIT %d, %d;", $start, $itemsPerPage);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				if (intval($row["accountId"]) == intval($row["senderId"])) {
					$senderId = intval($row["senderId"]);
					$receiverId = intval($row["receiverId"]);
					$row["senderId"] = $receiverId;
					$row["receiverId"] = $senderId;
				}
				array_push($data, $row);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}

		$obj = new \stdClass;
		$obj->total = $total;
		$obj->data = $data;
		$obj->page = $page;
		$obj->pages = $pages;
		$obj->sql = $sql;
		$obj->itemsPerPage = $itemsPerPage;
		
		return $obj;	
	}
	
	private function getMessageData($mysqli, $page, $itemsPerPage, $senderId, $receiverId, $year, $month) {
		$total = 0;		
		$data = array();

		// total
		$sql = "SELECT SUM(counter) AS total FROM ( ";
		$sql .= "SELECT COUNT(transaction_id) AS counter ";
		$sql .= "FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= sprintf("AND transaction.sender_id = %d ", $senderId);
		$sql .= sprintf("AND transaction.receiver_id = %d ", $receiverId);
		$sql .= "UNION ALL ";
		$sql .= "SELECT COUNT(transaction_id) AS counter ";
		$sql .= "FROM transaction ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= sprintf("AND transaction.sender_id = %d ", $receiverId);
		$sql .= sprintf("AND transaction.receiver_id = %d ", $senderId);
		$sql .= ") tbl";

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
		$sql = "SELECT * FROM ( ";
		$sql .= "SELECT transaction_id AS transactionId, 0 AS accountId, ";
		$sql .= "IFNULL(transaction.sender_id, 0) AS 'senderId', ";
		$sql .= "IFNULL((SELECT account.name FROM account WHERE account.account_id = transaction.sender_id), 'Faucet')  AS 'senderName', ";
		$sql .= "IFNULL(transaction.receiver_id, 0) AS 'receiverId', ";
		$sql .= "(SELECT account.name FROM account WHERE account.account_id = transaction.receiver_id) AS 'receiverName', ";
		$sql .= "quantity, account.token, account.symbol, account.icon, reference, status, supplement, ";
		$sql .= "date_format(transaction.created, '%m/%d/%Y %H:%i') AS created, ";
		$sql .= "date_format(transaction.modified, '%m/%d/%Y %H:%i') AS modified ";
		$sql .= "FROM transaction ";
		$sql .= "LEFT JOIN account ON (account.account_id = IF(transaction.sender_id = 0, transaction.receiver_id, transaction.sender_id)) ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= sprintf("AND transaction.sender_id = %d ", $senderId);
		$sql .= sprintf("AND transaction.receiver_id = %d ", $receiverId);
		$sql .= "UNION ALL ";
		$sql .= "SELECT transaction_id AS transactionId, 0 AS accountId, ";
		$sql .= "IFNULL(transaction.sender_id, 0) AS 'senderId', ";
		$sql .= "IFNULL((SELECT account.name FROM account WHERE account.account_id = transaction.sender_id), 'Faucet')  AS 'senderName', ";
		$sql .= "IFNULL(transaction.receiver_id, 0) AS 'receiverId', ";
		$sql .= "(SELECT account.name FROM account WHERE account.account_id = transaction.receiver_id) AS 'receiverName', ";
		$sql .= "quantity, account.token, account.symbol, account.icon, reference, status, supplement, ";
		$sql .= "date_format(transaction.created, '%m/%d/%y %H:%i') AS created, ";
		$sql .= "date_format(transaction.modified, '%m/%d/%y %H:%i') AS modified ";
		$sql .= "FROM transaction ";
		$sql .= "LEFT JOIN account ON (account.account_id = IF(transaction.sender_id = 0, transaction.receiver_id, transaction.sender_id)) ";
		$sql .= "WHERE transaction.transaction_id > 0 ";
		if ($year > 0) {
			$sql .= sprintf("AND YEAR(transaction.modified) = %d ", $year);
		}
		if ($month > 0) {
			$sql .= sprintf("AND MONTH(transaction.modified) = %d ", $month);
		}
		$sql .= sprintf("AND transaction.sender_id = %d ", $receiverId);
		$sql .= sprintf("AND transaction.receiver_id = %d ", $senderId);
		$sql .= ") tbl ";		
		$sql .= "ORDER BY tbl.created DESC ";
		$sql .= sprintf("LIMIT %d, %d;", $start, $itemsPerPage);
		
		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				array_push($data, $row);
			}
		} else {
			throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
		}

		$obj = new \stdClass;
		$obj->total = $total;
		$obj->data = $data;
		$obj->page = $page;
		$obj->pages = $pages;
		$obj->itemsPerPage = $itemsPerPage;
		
		return $obj;	
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
}