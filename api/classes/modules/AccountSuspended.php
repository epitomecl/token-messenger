<?php

namespace modules;

use \Exception as Exception;

/**
* A POST request will update account data.
*/
class AccountSuspended {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function doPost($accountId, $column, $checked) {

		if (strlen($column) > 0 && $accountId > 0) {
			if (in_array($column, array("suspended"))) {
				$sql = "UPDATE account SET %s='%d' WHERE account_id=%d";
				$sql = sprintf($sql, $column, $checked, $accountId);

				if ($this->mysqli->query($sql) === false) {
					throw new Exception(sprintf("%s, %s", get_class($this), $this->mysqli->error), 507);
				}
			}
		}
		
		echo json_encode(array("accountId" => $accountId), JSON_UNESCAPED_UNICODE);
	}
}