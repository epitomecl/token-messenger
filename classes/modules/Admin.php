<?php

namespace modules;

class Admin {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($page, $itemsPerPage, $searchText, $tokenName, $suspended, $lang) {
		$mysqli = $this->mysqli;
		$module = (new \ReflectionClass($this))->getShortName();
		$obj = $this->getData($mysqli, $page, $itemsPerPage, $searchText, $tokenName, $suspended);
		$total = $obj->total;
		$data = $obj->data;
		$page = $obj->page;
		$pages = $obj->pages;
		$itemsPerPage = $obj->itemsPerPage;
		$queryString = $this->getQueryString(array("module" => $module, "itemsPerPage" => $itemsPerPage, "suspended" => $suspended, "searchText" => $searchText, "tokenName" => $tokenName));
		$tokenNameOption = $this->getTokenNameOption($mysqli);
		
		require_once(sprintf("AdminView_%s.php", $lang)); 
	}
	
	public function doGet($page, $itemsPerPage, $searchText, $tokenName, $suspended, $lang) {
		$mysqli = $this->mysqli;
		$module = (new \ReflectionClass($this))->getShortName();
		$obj = $this->getData($mysqli, $page, $itemsPerPage, $searchText, $tokenName, $suspended);
		$total = $obj->total;
		$data = $obj->data;
		$page = $obj->page;
		$pages = $obj->pages;
		$itemsPerPage = $obj->itemsPerPage;
		$queryString = $this->getQueryString(array("module" => $module, "itemsPerPage" => $itemsPerPage, "suspended" => $suspended, "searchText" => $searchText, "tokenName" => $tokenName));
		$tokenNameOption = $this->getTokenNameOption($mysqli);
		
		require_once(sprintf("AdminView_%s.php", $lang)); 
	}
	
	private function getTokenNameOption($mysqli) {
		$data = array();
		$sql = "SELECT DISTINCT token.name AS tokenName ";
		$sql .= "FROM token ";
		$sql .= "ORDER BY token.name;";

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$key = trim($row["tokenName"]);
				$value = trim($row["tokenName"]);
				$data[$key] = $value;
			}	
		}
		return $data;
	}
		
	private function getQueryString($array) {
		$items = array();
		$params = array_filter($array);
		
		foreach ($params as $key => $value) {
			array_push($items, sprintf("&%s=%s", $key, urlencode($value)));
		}
		
		return implode($items);
	}

	private function getTokenImage($source) {
		if (empty($source)) {
			return "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
		}

		return $source;
	}
	
	private function getData($mysqli, $page, $itemsPerPage, $searchText, $tokenName, $suspended) {
		$total = 0;
		$data = array();
		
		$where = array();
		if ($suspended == 1) {
			array_push($where, sprintf("account.suspended=%d ", $suspended));
		}	
		if (strlen($searchText) > 0) {
			$text = "%".$mysqli->real_escape_string($searchText)."%";
			array_push($where, sprintf("(user.email LIKE '%s' OR account.name LIKE '%s') ", $text, $text));		
		}
		if (strlen($tokenName) > 0) {
			$text = "%".$mysqli->real_escape_string($tokenName)."%";		
			array_push($where, sprintf("token.name LIKE '%s' ", $text));		
		}
		if (count($where) > 0) {
			// add spaceholder item for AND
			array_unshift($where, "");
		}

		// total
		$sql = "SELECT COUNT(DISTINCT account.account_id) AS total FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= "LEFT JOIN token ON (token.account_id = account.account_id) ";	
		$sql .= "WHERE account.community = 0 ";
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
		$sql = "SELECT DISTINCT account.account_id AS accountId, account.name AS accountName, account.suspended, ";
		$sql .= "user.name AS userName, account.token AS tokenName, account.icon AS tokenIcon, account.symbol AS tokenSymbol, ";
		$sql .= "date_format(account.modified, '%m/%d/%y %H:%i') AS datetime ";	
		$sql .= "FROM account ";
		$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
		$sql .= "WHERE account.community = 0 ";
		$sql .= implode("AND ", $where);
		$sql .= "ORDER BY user.email, tokenName ";
		$sql .= sprintf("LIMIT %d, %d", $start, $itemsPerPage);

		if ($result = $mysqli->query($sql)) {
			while ($row = $result->fetch_assoc()) {
				$row["tokenIcon"] = $this->getTokenImage($row["tokenIcon"]);
				
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
}