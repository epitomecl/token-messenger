<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("api/classes/Autoloader.php");

use \Exception as Exception;
use modules\Faucet as Faucet;

$config = parse_ini_file("api/include/db.mysql.ini");
$mysqli = new mysqli($config['HOST'], $config['USER'], $config['PASS'], $config['NAME']);

try {
	if ($mysqli->connect_error) {
		throw new Exception("Cannot connect to the database: ".$mysqli->connect_errno, 503);
	}
	
	$mysqli->set_charset("utf8");
	
	(new Faucet($mysqli))->distributeToken();
	
} catch (Exception $e) {
	$msg = $e->getMessage();
} finally {
	$mysqli->close();
}
