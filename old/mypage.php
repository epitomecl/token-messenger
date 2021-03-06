<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getParam($array, $param, $label = '') {
	if (array_key_exists($param, $array)) {
		if (strcmp($label, "array") == 0) {
			return $array[$param];
		} elseif (strcmp($label, "int") == 0) {
			return intval(trim($array[$param]));
		} elseif (strcmp($label, "double") == 0) {
			return doubleval(trim($array[$param]));
		} else {
			return strip_tags(stripslashes(trim($array[$param])));
		}
	}

	return null;
}

function getOptionArrayMonth($month) {
	$data = array("<option value=\"0\"> </option>");
	
	for ($index = 1; $index <= 12; $index++) {
		$selected = ($index == $month) ? " selected=\selected\"" : "";
		$text = date('F', mktime(0, 0, 0, $index, 10));
		array_push($data, sprintf("<option value=\"%d\"%s>%s</option>", $index, $selected, $text));
	}
	
	return implode("\n", $data);
}

function getOptionArrayYear($year) {
	$data = array("<option value=\"0\"> </option>");
	
	for ($index = 2019; $index <= 2030; $index++) {
		$selected = ($index == $year) ? " selected=\selected\"" : "";
		$text = $index;
		array_push($data, sprintf("<option value=\"%d\"%s>%s</option>", $index, $selected, $text));
	}
	
	return implode("\n", $data);	
}

function getOptionArrayAccount($mysqli) {
	$sql = "SELECT account.account_id, account.name FROM account;";

	$data = array();
	if ($result = $mysqli->query($sql)) {
		while($row = $result->fetch_assoc()) {
			array_push($data, $row);			
		}
	}
	
	$option = array();
	foreach ($data as $index => $row) {
		$value = intval($row["account_id"]);
		$text = trim($row["name"]);
		array_push($option, sprintf("<option value=\"%d\">%s</option>", $value, $text));			
	}
	
	return implode("\n", $option);	
}

function getOptionArraySender($mysqli, $userId) {
	$data = array();
	$sql = "SELECT account.account_id, account.name FROM account ";
	$sql .= sprintf("WHERE user_id = %d;", $userId);
	
	$data = array();
	if ($result = $mysqli->query($sql)) {
		while($row = $result->fetch_assoc()) {
			array_push($data, $row);			
		}
	}
	
	$option = array();	
	foreach ($data as $index => $row) {
		$value = intval($row["account_id"]);
		$selected = (count($data) == 1) ? "selected=\"selected\"" : "";		
		$text = trim($row["name"]);
		array_push($option, sprintf("<option value=\"%d\"%s>%s</option>", $value, $selected, $text));			
	}
	
	return $option;
}

function getUserMainAccount($mysqli, $userId) {
	$sql = "SELECT account.account_id AS accountId, account.name AS accountName, account.suspended, user.email AS userName ";
	$sql .= "FROM account ";
	$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
	$sql .= sprintf("WHERE account.user_id = %d ", $userId);
	$sql .= "LIMIT 0,1;";
	
	$data = new stdClass();
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

function getOptionArrayReceiver($mysqli, $userId) {
	$data = array();
	$sql = "SELECT account.account_id, account.name FROM account ";
	$sql .= "WHERE user_id NOT IN (SELECT user_id FROM management);";

	$data = array();
	if ($result = $mysqli->query($sql)) {
		while($row = $result->fetch_assoc()) {
			array_push($data, $row);			
		}
	}
	
	$option = array();	
	foreach ($data as $index => $row) {
		$value = intval($row["account_id"]);
		$text = trim($row["name"]);
		array_push($option, sprintf("<option value=\"%d\">%s</option>", $value, $text));			
	}
	
	return implode("\n", $option);	
}

function getListPendingTransactionByReceiver($mysqli, $userId) {
	$data = array();		
	$sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
	$sql .= "pending.pending_id AS pendingId, quantity, reference, ";
	$sql .= "date_format(pending.modified, '%m/%d/%y %H:%i') AS datetime ";
	$sql .= "FROM pending ";
	$sql .= "LEFT JOIN account ON (account.account_id = pending.sender_id) ";
	$sql .= "WHERE pending.receiver_id IN ";
	$sql .= sprintf("(SELECT account.account_id FROM account WHERE account.user_id = %d) ", $userId);
	$sql .= "ORDER BY pending.modified DESC;";
	
	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			array_push($data, $row);
		}
	}
	
	return $data;
}
	
function getListPendingTransactionBySender($mysqli, $userId) {
	$data = array();		
	$sql = "SELECT account.account_id 'accountId', account.name AS 'account', ";
	$sql .= "pending.pending_id AS pendingId, quantity, reference, ";
	$sql .= "date_format(pending.modified, '%m/%d/%y %H:%i') AS datetime ";
	$sql .= "FROM pending ";
	$sql .= "LEFT JOIN account ON (account.account_id = pending.receiver_id) ";
	$sql .= "WHERE pending.sender_id IN ";
	$sql .= sprintf("(SELECT account.account_id FROM account WHERE account.user_id = %d) ", $userId);
	$sql .= "ORDER BY pending.modified DESC;";

	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			array_push($data, $row);
		}
	}
	
	return $data;
}

function isAdmin($mysqli, $userId) {
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

function getUniqueTokenRemain($mysqli, $accountId) {
	$token = 0;
	$sql = "SELECT COUNT(balance.balance_id) AS token ";
	$sql .= "FROM balance ";
	$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
	$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
	$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
	$sql .= "AND balance.account_id = token.account_id ";
	$sql .= "GROUP BY token.account_id ";
	
	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			$token = intval($row["token"]);
		}
	} else {
		throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
	}

	return $token;		
}
	
function getUniqueTokenTotal($mysqli, $accountId) {
	$token = 0;
	$sql = "SELECT COUNT(balance.balance_id) AS token ";
	$sql .= "FROM balance ";
	$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
	$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
	$sql .= sprintf("WHERE token.account_id = %d ", $accountId);
	$sql .= "GROUP BY token.account_id;";
	
	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			$token = intval($row["token"]);
		}
	} else {
		throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
	}

	return $token;		
}
	
function getReceivedTokenMonth($mysqli, $accountId) {
	$token = 0;	
	$sql = "SELECT COUNT(balance.balance_id) AS token, account.account_id AS 'accountId', account.name AS 'account' ";
	$sql .= "FROM balance ";
	$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
	$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
	$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
	$sql .= "AND balance.account_id != token.account_id ";
	$sql .= sprintf("AND YEAR(balance.modified) = %d ", date("Y"));
	$sql .= sprintf("AND MONTH(balance.modified) = %d  ", date("m"));
	$sql .= "GROUP BY token.account_id ORDER BY token DESC;";
	
	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			$token += intval($row["token"]);
		}
	} else {
		throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
	}
	
	return $token;
}

function getReceivedTokenTotal($mysqli, $accountId) {
	$token = 0;		
	$sql = "SELECT COUNT(balance.balance_id) AS token, account.account_id AS 'accountId', account.name AS 'account' ";
	$sql .= "FROM balance ";
	$sql .= "LEFT JOIN token ON (token.token_id = balance.token_id) ";
	$sql .= "LEFT JOIN account ON (account.account_id = token.account_id) ";
	$sql .= sprintf("WHERE balance.account_id = %d ", $accountId);
	$sql .= "AND balance.account_id != token.account_id ";
	$sql .= "GROUP BY token.account_id ORDER BY token DESC;";
	
	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			$token += intval($row["token"]);
		}
	} else {
		throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
	}
	
	return $token;
}
	
session_start();
			
$config = parse_ini_file("api/include/db.mysql.ini");
$mysqli = new mysqli($config['HOST'], $config['USER'], $config['PASS'], $config['NAME']);
$userId = isset($_SESSION['USERID']) ? intval($_SESSION["USERID"]) : 0;
$module = getParam($_POST, "module");
$txtOptionMonth = getOptionArrayMonth(date("n"));
$txtOptionYear = getOptionArrayYear(date("Y"));
$txtOptionMonthUnselected = getOptionArrayMonth(0);
$txtOptionYearUnselected = getOptionArrayYear(0);
$listPendingTransactionByReceiver = array();
$listPendingTransactionBySender = array();
$isAdmin = 0;
$data = new stdClass();
$data->accountName = "";
$data->accountId = 0;
$data->userName = "";
$data->remain = 0;
$data->total = 0;
$data->received = 0;
$data->month = 0;
$data->suspended = 0;

try {
	if ($mysqli->connect_error) {
		throw new Exception("Cannot connect to the database: ".$mysqli->connect_errno, 503);
	}
	$mysqli->set_charset("utf8");

	switch(strtoupper($module)) {
		case "LOGIN":
			$email = getParam($_POST, "email");
			$password = getParam($_POST, "password");
			
			$sha256 = "";
			$sql = "SELECT salt FROM user WHERE email='%s';";
			$sql = sprintf($sql, $mysqli->real_escape_string($email));
			$result = $mysqli->query($sql);

			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					$salt = trim($row["salt"]);
					$sha256 = hash_hmac("sha256", $password, $salt);
				}
			}
			
			$sql = "SELECT user_id FROM user WHERE email='%s' AND password='%s';";
			$sql = sprintf($sql, $mysqli->real_escape_string($email), $sha256);
			
			if ($result = $mysqli->query($sql)) {
				while($row = $result->fetch_assoc()) {
					$userId = intval($row["user_id"]);
				}
			}
			
			if ($userId > 0) {
				$_SESSION["USERID"] = $userId;
			}

			break;
		case "LOGOUT":
			$_SESSION = array();

			// If it's desired to kill the session, also delete the session cookie.
			// Note: This will destroy the session, and not just the session data!
			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
				);
			}

			// Finally, destroy the session.
			session_destroy();
			$userId = 0;
			break;		
	}
	
	$txtOptionAccount = getOptionArrayAccount($mysqli);
	$arrOptionSender = getOptionArraySender($mysqli, $userId);
	$txtOptionReceiver = getOptionArrayReceiver($mysqli, $userId);
	$listPendingTransactionByReceiver = getListPendingTransactionByReceiver($mysqli, $userId);
	$listPendingTransactionBySender = getListPendingTransactionBySender($mysqli, $userId);
	$isAdmin = isAdmin($mysqli, $userId);
	
	$mainAccount = getUserMainAccount($mysqli, $userId);
	$data->accountId = $mainAccount->accountId;
	$data->accountName = $mainAccount->accountName;
	$data->userName	= $mainAccount->userName;
	$data->remain = getUniqueTokenRemain($mysqli, $mainAccount->accountId);
	$data->total = getUniqueTokenTotal($mysqli, $mainAccount->accountId);	
	$data->received = getReceivedTokenTotal($mysqli, $mainAccount->accountId);
	$data->month = getReceivedTokenMonth($mysqli, $mainAccount->accountId);	
	$data->suspended = $mainAccount->suspended;
	
} catch (Exception $e) {
	$msg = $e->getMessage();
	$code = $e->getCode();
	http_response_code(($code == 0) ? 400 : $code);
	echo sprintf("Exception occurred in: %s", $msg);
} finally {
	$mysqli->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Inssa Project</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
 <style>
.jumbotron {
	background-color: lightgray;
	background-image: url(images/jumbotron.png);
	background-size: cover;
	height: 100%;
	color: gray;
	text-shadow: 1px 1px #000;
}
.container-section {
    padding-top: 1rem;
    padding-bottom: 1rem;
}
.cursor-pointer {
  cursor: pointer;
}
 </style>
</head>
<body>

<div class="jumbotron jumbotron-fluid mb-0">
  <div class="container">
    <h1>Inssa Project</h1>
    <p>Token based communication...</p>
  </div>
</div>

<nav class="navbar navbar-expand-sm bg-dark navbar-dark">
<a class="navbar-brand" href="/<?php echo $_SERVER['PHP_SELF']; ?>">
    <img src="images/inssa.png" alt="Logo" style="width:40px;">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="collapsibleNavbar">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage">My page</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="txhistory.php">Tx History</a>
      </li>	  
      <li class="nav-item">
        <a class="nav-link" href="userhistory.php">User History</a>
      </li>
<?php if ($isAdmin > 0): ?>	  
      <li class="nav-item">
		<form method="post" action="admin.php">
			<button type="submit" class="btn btn-outline-danger mr-1 mb-1">Admin</button>
		</form>
      </li>	  
<?php endif; ?>
<?php if ($userId > 0): ?>	
      <li class="nav-item">
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" name="module" value="logout">
			<button type="submit" class="btn btn-outline-info mr-1 mb-1">Logout</button>
		</form>
      </li>	 
<?php endif; ?>	  
    </ul>
  </div>  
</nav>

<?php if ($userId == 0): ?>
<section class="container-section bg-light">
	<div class="container-fluid mb-4">
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="module" value="login">
			<div class="row">
				<div class="col-sm-4">
					<h4>Login</h4>
				</div>
				<div class="col-sm-8">
					<div class="form-group">
						<label for="email">Email address:</label>
						<input type="email" class="form-control" name="email" required>
					</div>
					<div class="form-group">
						<label for="pwd">Password:</label>
						<input type="password" class="form-control" name="password" required>
					</div>
					<button type="submit" class="btn btn-primary btn-block">Submit</button>
				</div>
			</div>
		</form>
	</div>
</section>

<? else: ?>

<section class="container-section bg-light">
	<div class="container-fluid mb-4">
		<div class="row">
			<div class="col-sm-6"><h2>Welcome! (<?php echo trim($data->userName); ?>)</h2></div>
			<div class="col-sm-6">
				<?php if (intval($data->suspended)): ?>
				<div class="btn-toolbar justify-content-end">
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<button type="submit" class="btn btn-outline-danger mr-1 mb-1">This account is suspended.</button>
					</form>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="row">
			<div class="col-6">
				<div class="card bg-light text-dark">
				  <div class="card-body">
					  <p class="card-text"><?php echo trim($data->accountName); ?> Token</p>
					  <div class="row">
						<div class="col-6">
							<p class="card-text d-inline mr-1">remain</p>
							<h4 class="d-inline"><?php echo intval($data->remain); ?><h4>
						</div>
						<div class="col-6">
							<p class="card-text d-inline mr-1">total</p>
							<h4 class="d-inline"><?php echo intval($data->total); ?><h4>
						</div>			  
					  </div>
				  </div>
				</div>
			</div>
			<div class="col-6">
				<div class="card bg-light text-dark">
				  <div class="card-body">
					  <p class="card-text">Received Token</p>
					  <div class="row">
						  <div class="col-6">
							<p class="card-text d-inline mr-1">total</p>
							<h4 class="d-inline"><?php echo intval($data->received); ?><h4>
						  </div>
						  <div class="col-6">
							<p class="card-text d-inline mr-1">month</p>
							<h4 class="d-inline"><?php echo intval($data->month); ?><h4>				  
						  </div>			  
					  </div>
				  </div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="container-section">
	<div class="container-fluid mb-4">
		<div class="row pb-4">
			<div class="col-sm-8">
				<h2>We ask for your attention</h2>
			</div>
			<div class="col-sm-4">
				<div class="btn-toolbar justify-content-end">
					<button type="button" class="btn btn-outline-success mr-1 mb-1">&#x23F3; <i class="refresh">10</i></button>
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<button type="submit" class="btn btn-outline-success mr-1 mb-1">Refresh</button>
					</form>
				</div>				
			</div>
		</div>
		<div class="row pb-4">
			<div class="col-sm-12">
				<div class="card-columns viewPendingTransaction">
					<?php foreach ($listPendingTransactionByReceiver as $index => $row): ?>
						<div class="card border-info" data-NGINX="PUT" data-pendingId="<?php echo trim($row["pendingId"]); ?>" data-dialog="1" data-accountId="<?php echo trim($row["accountId"]); ?>">
							<div class="card-body">
								<h5 class="card-title"><?php echo trim($row["reference"]); ?></h5>
								<p class="card-text"><?php echo intval($row["quantity"]); ?> token from <?php echo trim($row["account"]); ?></p>
								<p class="card-text"><?php echo trim($row["datetime"]); ?></p>							
							</div>
						</div>
					<?php endforeach; ?>
					<?php foreach ($listPendingTransactionBySender as $index => $row): ?>
						<div class="card border-warning" data-NGINX="DELETE" data-pendingId="<?php echo trim($row["pendingId"]); ?>" data-dialog="1" data-accountId="<?php echo trim($row["accountId"]); ?>">
							<div class="card-body">
								<h5 class="card-title"><?php echo trim($row["reference"]); ?></h5>
								<p class="card-text"><?php echo intval($row["quantity"]); ?> token to <?php echo trim($row["account"]); ?></p>
								<p class="card-text"><?php echo trim($row["datetime"]); ?></p>							
							</div>
						</div>
					<?php endforeach; ?>
					<?php if (count($listPendingTransactionBySender) == 0 && count($listPendingTransactionByReceiver) == 0): ?>
						<div class="card border-secondary" data-NGINX="" data-pendingId="0" data-dialog="0" data-accountId="0">
							<div class="card-body">
								<h5 class="card-title">Don't hesitate to involve someone into your ideas.</h5>
								<p class="card-text">1 token to a member</p>
								<p class="card-text">at first step</p>							
							</div>
						</div>
						<div class="card border-secondary" data-NGINX="" data-pendingId="0" data-dialog="0" data-accountId="0">
							<div class="card-body">
								<h5 class="card-title">Ask someone for joining your activity.</h5>
								<p class="card-text">2 token to a member</p>
								<p class="card-text">at second step</p>							
							</div>
						</div>
						<div class="card border-secondary" data-NGINX="" data-pendingId="0" data-dialog="0" data-accountId="0">
							<div class="card-body">
								<h5 class="card-title">Let group members known about your requirements.</h5>
								<p class="card-text">3 token to a member</p>
								<p class="card-text">at next step</p>						
							</div>
						</div>						
					<?php endif; ?>
				</div>
				<template id="itemPendingTransaction">
					<div class="card ${border}" data-NGINX="${NGINX}" data-pendingId="${pendingId}" data-dialog="1" data-accountId="${accountId}">
						<div class="card-body">
							<h5 class="card-title">${reference}</h5>
							<p class="card-text">${quantity} token ${direction} ${account}</p>
							<p class="card-text">${datetime}</p>							
						</div>
					</div>
				</template>		
			</div>
		</div>
	</div>
</section>

<section class="container-section bg-light">	
	<div class="container-fluid mb-4">
		<div class="row pb-4">
			<div class="col-sm-12">
				<h2>Verified token transfer</h2>
			</div>
		</div>
		<div class="row pb-4">
			<div class="col-sm-12">
				<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
					<input type="hidden" name="NGINX" value="" />
					<input type="hidden" name="module" value="VerifiedTokenTransfer">
					<?php if (count($arrOptionSender) == 1): ?>
					<input type="hidden" name="senderId" value="<?php echo $data->accountId; ?>">
					<?php else: ?>
					<label for="senderId" class="mr-sm-2">from:</label>
					<select class="form-control mb-2 mr-sm-2" name="senderId">
						<option value="0">From account ...</option>
						<?php echo implode("\n", $arrOptionSender); ?>
					<select>
					<?php endif; ?>
					<label for="receiverId" class="mr-sm-2">to:</label>
					<select class="form-control mb-2 mr-sm-2" name="receiverIds[]" multiple="multiple" required="required">
						<option value="0">To account ...</option>
						<?php echo $txtOptionReceiver; ?>
					<select>
					<label for="quantity" class="mr-sm-2">quantity:</label>
					<input type="text" class="form-control mb-2 mr-sm-2" name="quantity" value="1">
					<label for="reference" class="mr-sm-2">Reference:</label>
					<textarea class="form-control mb-2 mr-sm-2" name="reference" rows="2" placeholder="Insert reference..." required="required"></textarea>
					<button type="button" class="btn btn-outline-info text-dark">
						<div class="form-check">
							<label class="form-check-label cursor-pointer">
								<input type="checkbox" class="form-check-input cursor-pointer" id="checkboxDirectTokenTransfer" value="1">Give tokens direct to a thankful person
							</label>
						</div>
					</button>					
					<button type="button" class="btn btn-primary module mt-2 mb-2">Submit</button>
				</form>
				<div class="container p-0 viewTokenTransfer"></div>
				<template id="viewVerifiedTokenTransfer">
				<h4 class="mt-3">Transaction receipt</h4>
				<table class="table table-striped received">
					<tbody>
						<tr class="m-0 d-flex">
							<th class="col-4">Pending ID:</th>
							<th class="col-8">${pendingId}</th>
						</tr>	
						<tr class="m-0 d-flex">
							<th class="col-4">Sender:</th>
							<th class="col-8">${sender}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Receiver:</th>
							<th class="col-8">${receiver}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Quantity:</th>
							<th class="col-8">${quantity}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Reference:</th>
							<th class="col-8">${reference}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Date:</th>
							<th class="col-8">${datetime}</th>
						</tr>					
					</tbody>
				</table>
				</template>
				<template id="viewDirectTokenTransfer">
				<h4 class="mt-3">Transaction receipt</h4>
				<table class="table table-striped received">
					<tbody>
						<tr class="m-0 d-flex">
							<th class="col-4">Transaction ID:</th>
							<th class="col-8">${transactionId}</th>
						</tr>	
						<tr class="m-0 d-flex">
							<th class="col-4">Sender:</th>
							<th class="col-8">${sender}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Receiver:</th>
							<th class="col-8">${receiver}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Quantity:</th>
							<th class="col-8">${quantity}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Reference:</th>
							<th class="col-8">${reference}</th>
						</tr>
						<tr class="m-0 d-flex">
							<th class="col-4">Date:</th>
							<th class="col-8">${datetime}</th>
						</tr>					
					</tbody>
				</table>
				</template>			
			</div>
		</div>
	</div>
</section>

<? endif; ?>

<section class="container-section">
	<div class="container-fluid mb-3">
		<div class="row mb-3">
			<div class="col-sm-12">
				<textarea class="form-control" id="jsondata" rows="3">&#x1F608;</textarea>
			</div>
		</div>
	</div>
</section>

<div class="modal fade" id="dialogPut">
    <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
			  <h4 class="modal-title">Do you accept this transaction?</h4>
			  <button type="button" class="close" data-dismiss="modal">×</button>
			</div>
			<div class="modal-body">
					<div class="row mb-3">
						<div class="col-sm-12">
							<textarea class="form-control message" name="message" rows="3" placeholder="Your message..."></textarea>
							<p>Enter message for recipient to understand your rejection or in other cases just leave a thankful message. 
							Note, that everybody will be able to read this as part of transaction message.</p>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-sm-12">
							<button type="button" class="btn btn-warning btn-lg btn-block mb-3" data-dismiss="modal">No, i don't.</button>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-sm-12">
							<button type="button" class="btn btn-success btn-lg btn-block mb-3" data-dismiss="modal">Yes, i will.</button>
						</div>	
					</div>					
				</div>
			<div class="modal-footer">
			  <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
			</div>
		</div>
    </div>
</div>
	
<div class="modal fade" id="dialogDelete">
    <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
			  <h4 class="modal-title">Do you want to cancel this transaction?</h4>
			  <button type="button" class="close" data-dismiss="modal">×</button>
			</div>
			<div class="modal-body">
					<div class="row mb-3">
						<div class="col-sm-12">
							<textarea class="form-control message" name="message" rows="3" placeholder="Your message..."></textarea>
							<p>Enter message for every user to understand your abortation. 
							Note, that everybody will be able to read this as part of transaction message.</p>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-sm-12">
							<button type="button" class="btn btn-success btn-lg btn-block mb-3" data-dismiss="modal">Yes, i will.</button>
						</div>	
					</div>					
				</div>
			<div class="modal-footer">
			  <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
			</div>
		</div>
    </div>
</div>
	
<div class="jumbotron jumbotron-fluid text-center" style="margin-bottom:0">
  <p>Copyright © EpitomeCL 2019</p>
</div>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
<script type="text/javascript">

function updateBoard(path) {
	if ($(path).text().length) {
		var refresh = parseInt("0" + $(path).text(), 10);
		
		if (refresh <= 1) {
			$(path).text(10);
			
			var formData = new FormData();
			formData.append("module", "PendingTransaction");
			
			var query = new Array();
			for (var pair of formData.entries()) {
				query.push(pair[0] + "=" + pair[1]); 
			}

			requestGet(query.join("&"));
		} else {
			$(path).text(((refresh - 1) < 10 ? '0' : '') + (refresh - 1));
		}
	}
}

$(document).ready(function() {
	setInterval('updateBoard(".refresh")', 1000);
});

$(".module").on("click", function(event) {
	event.preventDefault();
	event.stopPropagation();
	
	var form = $(this).parents('form:first');

	if (form.find("#checkboxDirectTokenTransfer").length > 0){
		if (form.find("#checkboxDirectTokenTransfer").is(":checked")) {
			form.find('input:hidden[name=module]').val("DirectTokenTransfer");
		} else {
			form.find('input:hidden[name=module]').val("VerifiedTokenTransfer");
		}
	}
	
	if (form.prop("method") == "post") {
		requestPost(form.serialize());
	} else {
		requestGet(form.serialize());
	}
});

$(".viewPendingTransaction").on("click", ".card", function(event) {
	event.preventDefault();
	event.stopPropagation();
	
	var NGINX = $(this).data("nginx");
	var dialog = $(this).data("dialog");
	var formData = new FormData();
	
	formData.append("module", "VerifiedTokenTransfer");
	formData.append("pendingId", $(this).data("pendingid"));
	formData.append("accountId", $(this).data("accountid"));
	formData.append("NGINX", NGINX);
	
	if (dialog && dialog == 1) {
		switch (NGINX) {
			case "PUT":
				$("#dialogPut").data("formData", formData);
				$("#dialogPut").modal();
				break;
			case "DELETE":
				$("#dialogDelete").data("formData", formData);
				$("#dialogDelete").modal();
				break;
		}
	}
});

$("#dialogDelete .modal-body").on("click", "button.btn-success", function(event) {
	event.preventDefault();
	event.stopPropagation();
		
	$("#dialogDelete").modal("hide");
	
	var formData = $("#dialogDelete").data("formData");
	formData.append("confirmed", 1);
	formData.append("message", $("#dialogDelete").find(".message").val());
	
	var query = new Array();
	for (var pair of formData.entries()) {
		query.push(pair[0] + "=" + pair[1]); 
	}

	requestPost(query.join("&"));	
});

$("#dialogPut .modal-body").on("click", "button.btn-success", function(event) {
	event.preventDefault();
	event.stopPropagation();
		
	$("#dialogPut").modal("hide");
	
	var formData = $("#dialogPut").data("formData");
	formData.append("confirmed", 1);
	formData.append("message", $("#dialogPut").find(".message").val());
	
	var query = new Array();
	for (var pair of formData.entries()) {
		query.push(pair[0] + "=" + pair[1]); 
	}

	requestPost(query.join("&"));	
});

$("#dialogPut .modal-body").on("click", "button.btn-warning", function(event) {
	event.preventDefault();
	event.stopPropagation();
		
	$("#dialogPut").modal("hide");
	
	var formData = $("#dialogPut").data("formData");
	formData.append("confirmed", 0);
	formData.append("message", $("#dialogPut").find(".message").val());
	
	if (formData.get("NGINX") == "PUT") {
		var query = new Array();
		for (var pair of formData.entries()) {
			query.push(pair[0] + "=" + pair[1]); 
		}
		
		requestPost(query.join("&"));
	}
});

function requestGet(data) {
	$.get(
		"/inssa/api/", data
	).done(
		function( data ) {
			$("#jsondata").text(data);

			var obj = JSON.parse(data);
			
			switch(obj.module) {
				case "PendingTransaction":
					if (obj.data) {
						var itemTpl = $('#itemPendingTransaction').html().split(/\$\{(.+?)\}/g);
						$('.viewPendingTransaction .card-columns').empty();

						$.each( obj.data, function( key, elm ) {
							if (!elm.pendingId) {
								return;
							}
							var items = [{pendingId: elm.pendingId, accountId: elm.accountId, NGINX: elm.NGINX, 
								border: (elm.NGINX == "DELETE") ? "border-warning" : "border-info",
								direction : (elm.NGINX == "DELETE") ? "to" : "from",
								datetime: elm.datetime, account: elm.account, quantity: elm.quantity, reference: elm.reference}];
							$('.card-columns .viewPendingTransaction').append(
								items.map(function (item) {
									return itemTpl.map(render(item)).join('');
								})
							);
						});
					}
					break;
				case "TokenTransactionHistory":
					var viewTpl = $('#viewTokenTransactionHistory').html().split(/\$\{(.+?)\}/g);
					var itemTpl = $('#itemTokenTransactionHistory').html().split(/\$\{(.+?)\}/g);

					$('.viewTokenTransactionHistory').empty();
					
					if (obj.account) {
						var view = [{ account: obj.account}];
						$('.viewTokenTransactionHistory').html(view.map(function (item) {
							return viewTpl.map(render(item)).join('');
						}));
						
						var items = obj.sent;
						$('.viewTokenTransactionHistory table.sent').find('tbody').detach();
						$('.viewTokenTransactionHistory table.sent').append($('<tbody>'));  
						$('.viewTokenTransactionHistory table.sent').find('tbody:last').append(
							items.map(function (item) {
								return itemTpl.map(render(item)).join('');
							})
						);	
						
						var items = obj.received;
						$('.viewTokenTransactionHistory table.received').find('tbody').detach();
						$('.viewTokenTransactionHistory table.received').append($('<tbody>'));  
						$('.viewTokenTransactionHistory table.received').find('tbody:last').append(
							items.map(function (item) {
								return itemTpl.map(render(item)).join('');
							})
						);
						
						$(".viewTokenTransactionHistory tr[data-dialog='1']").addClass('bg-warning');
					}
					break;
				case "TotalUserTokenHoldings":
					var viewTpl = $('#viewTotalUserTokenHoldings').html().split(/\$\{(.+?)\}/g);
					var itemTpl = $('#itemTotalUserTokenHoldings').html().split(/\$\{(.+?)\}/g);

					var view = [{ module: obj.module}];
					$('.viewTotalUserTokenHoldings').html(view.map(function (item) {
						return viewTpl.map(render(item)).join('');
					}));
					
					var items = obj.data;
					$('.viewTotalUserTokenHoldings table.total').find('tbody').detach();
					$('.viewTotalUserTokenHoldings table.total').append($('<tbody>'));  
					$('.viewTotalUserTokenHoldings table.total').find('tbody:last').append(
						items.map(function (item) {
							return itemTpl.map(render(item)).join('');
						})
					);				
					break;
				case "NumberOfTokens":
					var viewTpl = $('#viewNumberOfTokens').html().split(/\$\{(.+?)\}/g);
					var itemTpl = $('#itemNumberOfTokens').html().split(/\$\{(.+?)\}/g);

					$('.viewNumberOfTokens').empty();
					
					if (obj.account) {
						var view = [{ account: obj.account, unique : obj.unique, total : obj.total}];
						$('.viewNumberOfTokens').html(view.map(function (item) {
							return viewTpl.map(render(item)).join('');
						}));
						
						var items = obj.received;
						$('.viewNumberOfTokens table.received').find('tbody').detach();
						$('.viewNumberOfTokens table.received').append($('<tbody>'));  
						$('.viewNumberOfTokens table.received').find('tbody:last').append(
							items.map(function (item) {
								return itemTpl.map(render(item)).join('');
							})
						);	
					}					
					break;
			}
		}
	).fail( function(xhr, textStatus, error) {
        $("#jsondata").text(xhr.status + " :: " + xhr.statusText + " :: " + xhr.responseText);
    });
}

function requestPost(data) {
	$.post(
		"/inssa/api/", data
	).done(
		function( data ) {
			$("#jsondata").text(data);
			
			var obj = JSON.parse(data);
			
			switch(obj.module) {
				case "Faucet":
					var viewTpl = $('#viewFaucet').html().split(/\$\{(.+?)\}/g);

					$('.viewFaucet').empty();
					
					var view = [{token: obj.token}];
					$('.viewFaucet').html(view.map(function (item) {
						return viewTpl.map(render(item)).join('');
					}));
					break;				
				case "DirectTokenTransfer":
					var viewTpl = $('#viewDirectTokenTransfer').html().split(/\$\{(.+?)\}/g);

					$('.viewTokenTransfer').empty();
					
					if (obj.data) {
						$.each( obj.data, function( key, elm ) {
							var view = [{transactionId: elm.transactionId, sender: elm.sender,
								receiver: elm.receiver, quantity: elm.quantity, reference: elm.reference,
								datetime: elm.datetime}];
							$('.viewTokenTransfer').html(view.map(function (item) {
								return viewTpl.map(render(item)).join('');
							}));
						});
					}			
					break;	
				case "VerifiedTokenTransfer":
					var viewTpl = $('#viewVerifiedTokenTransfer').html().split(/\$\{(.+?)\}/g);
					var itemTpl = $('#itemPendingTransaction').html().split(/\$\{(.+?)\}/g);
					
					$('.viewTokenTransfer').empty();
					
					if (obj.data) {
						$.each( obj.data, function( key, elm ) {
							if (!elm.pendingId) {
								return;
							}							
							var view = [{pendingId: elm.pendingId, sender: elm.sender,
								receiver: elm.receiver, quantity: elm.quantity, reference: elm.reference,
								datetime: elm.datetime}];
							$('.viewTokenTransfer').prepend(view.map(function (item) {
								return viewTpl.map(render(item)).join('');
							}));
							var items = [{pendingId: elm.pendingId, accountId: elm.accountId, 
								NGINX: "DELETE", border: "border-warning", direction : "from",
								datetime: elm.datetime, account: elm.receiver, quantity: elm.quantity, reference: elm.reference}];
							$('.card-columns .viewPendingTransaction').prepend(
								items.map(function (item) {
									return itemTpl.map(render(item)).join('');
								})
							);
						});
					}

					if((obj.pendingId) && obj.pendingId > 0 && (obj.transactionId) && obj.transactionId > 0) {
						var itemTpl = $('#itemTokenTransactionHistory').html().split(/\$\{(.+?)\}/g);
						
						switch (obj.method) {
							case "PUT":
								var items = [{transactionId: obj.transactionId, accountId: obj.accountId, 
									datetime: obj.datetime, account: obj.account, quantity: obj.quantity, reference: obj.reference}];
								$('.viewTokenTransactionHistory table.sent').find('tbody:first').append(
									items.map(function (item) {
										return itemTpl.map(render(item)).join('');
									})
								);
								break;
							case "DELETE":
								var items = [{transactionId: obj.transactionId, accountId: obj.accountId, 
									datetime: obj.datetime, account: obj.account, quantity: obj.quantity, reference: obj.reference}];
								$('.viewTokenTransactionHistory table.received').find('tbody:first').append(
									items.map(function (item) {
										return itemTpl.map(render(item)).join('');
									})
								);
								break;
						}
						
						$(".card-columns .viewPendingTransaction [data-pendingid='"+obj.pendingId+"']").removeClass('border-warning border-info');
						$(".card-columns .viewPendingTransaction [data-pendingid='"+obj.pendingId+"']").data("dialog", 0);
					}
					
					break;				
			}			
		}
	).fail( function(xhr, textStatus, error) {
        $("#jsondata").text(xhr.status + " :: " + xhr.statusText + " :: " + xhr.responseText);
    });
}

function render(props) {
  return function(tok, i) { return (i % 2) ? props[tok] : tok; };
}

$(document).ready(function(){
    $(window).keydown(function(event){
        if(event.keyCode == 13 && event.target.nodeName!='TEXTAREA')
        {
          event.preventDefault();
          return false;
        }
    });
});
</script>


</body>
</html>