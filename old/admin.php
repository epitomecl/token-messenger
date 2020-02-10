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
	
	return implode("\n", $option);
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

function getTokenNameOption($mysqli) {
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
	
function getQueryString($array) {
	$items = array();
	$params = array_filter($array);
	
	foreach ($params as $key => $value) {
		array_push($items, sprintf("&%s=%s", $key, urlencode($value)));
	}
	
	return implode($items);
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
$suspended = getParam($_GET, "suspended", "int");
$searchText = getParam($_GET, "searchText"); 
$tokenName = getParam($_GET, "tokenName");
$itemsPerPage = getParam($_GET, "itemsPerPage", "int");
$page = getParam($_GET, "page", "int");
$queryString = getQueryString(array("itemsPerPage" => $itemsPerPage, "suspended" => $suspended, "searchText" => $searchText, "tokenName" => $tokenName));
$isAdmin = 0;
$tokenNameOption = array();
$total = 0;
$data = array();

try {
	if ($mysqli->connect_error) {
		throw new Exception("Cannot connect to the database: ".$mysqli->connect_errno, 503);
	}
	$mysqli->set_charset("utf8");

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
	$txtOptionSender = getOptionArraySender($mysqli, $userId);
	$txtOptionReceiver = getOptionArrayReceiver($mysqli, $userId);
	$listPendingTransactionByReceiver = getListPendingTransactionByReceiver($mysqli, $userId);
	$listPendingTransactionBySender = getListPendingTransactionBySender($mysqli, $userId);
	$isAdmin = isAdmin($mysqli, $userId);
	$tokenNameOption = getTokenNameOption($mysqli);
	
	
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
	$sql .= "user.email, account.token AS tokenName, account.icon AS tokenIcon, account.symbol AS tokenSymbol, ";
	$sql .= "date_format(account.modified, '%m/%d/%y %H:%i') AS datetime ";	
	$sql .= "FROM account ";
	$sql .= "LEFT JOIN user ON (user.user_id = account.user_id) ";
	$sql .= "WHERE account.community = 0 ";
	$sql .= implode("AND ", $where);
	$sql .= sprintf("LIMIT %d, %d", $start, $itemsPerPage);

	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			array_push($data, $row);
		}	
	}	
	
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
  <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">  
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
 </style>
</head>
<body>

<div class="jumbotron jumbotron-fluid mb-0">
  <div class="container">
    <h1>Inssa Project</h1>
    <p>Admin</p>
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
<?php if ($isAdmin > 0): ?>	
      <li class="nav-item">
		<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" name="module" value="AccountCreate">
			<button type="submit" class="btn btn-outline-warning mr-1 mb-1">Create Account</button>
		</form>
      </li>
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

<?php if ($isAdmin == 0): ?>
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

<section class="container-section">
	<div class="container-fluid mb-4">
		<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="filter">
			<div class="row pb-4">
			  <div class="col-sm-5"><h2>Member Accounts</h2></div>
			  <div class="col-sm-7">
				<div class="btn-toolbar justify-content-end">
				<button type="button" class="btn mr-1 mb-1">Number of rows:</button>
				<select class="btn btn-outline-info mr-1 mb-1" id="itemsPerPage" name="itemsPerPage" onchange="if(this.value != 0) { this.form.submit(); }">
				<?php for ($index = 25; $index <= 250; $index+=25): ?>
				<?php printf("<option value=\"%d\"%s>%s</option>", $index, ($index == $itemsPerPage) ? " selected=\"selected\"": "", $index); ?>
				<?php endfor; ?>
					</select>	

					<button type="button" class="btn btn-outline-info mr-1 mb-1">Total: <?php echo $total; ?></button>
					<button type="button" class="btn btn-outline-warning mr-1 mb-1" data-toggle="collapse" data-target="#filter">Filter</button>		
					<button type="submit" class="btn btn-outline-success mr-1 mb-1">Submit »</button>
				</div>
			  </div>
			</div>

			<div id="filter" class="collapse">
				<div class="row pb-4">
					<div class="col-sm-4">			
						<div class="form-check mb-2 mr-sm-2">
							<label class="form-check-label">
								<input class="form-check-input" type="checkbox" id="suspended" name="suspended" value="1" <?php echo ($suspended == 1) ? "checked=\"checked\"": ""; ?>> suspended only
							</label>
						</div>		
					</div>	
					<div class="col-sm-4">
						<label for="searchText" class="mr-sm-2">Search text:</label>
						<input type="text" class="form-control mb-2 mr-sm-2" id="searchText" name="searchText"  value="<?php echo $searchText; ?>">
					</div>	
					<div class="col-sm-4">			
						<label for="tokenName" class="mr-sm-2">Token name:</label>
						<select class="form-control" id="tokenName" name="tokenName">
							<option value="">&nbsp;</option>
							<?php foreach ($tokenNameOption as $key => $value): ?>
							<?php echo sprintf("<option value=\"%s\"%s>%s</option>", $key, ($key == $tokenName) ? "selected=\"selected\"" : "", $value); ?>
							<?php endforeach; ?>
						</select>
					</div>	
				</div>
			</div>
		</form>

		<div class="row mb-3">
			<div class="col">
			<?php if (count($data) > 0) { ?>
				<div class="table-responsive viewTokenTransactionHistory">
					<h4 class="mt-3">Result table</h4>
					<table class="table table-striped history">
						<thead>
							<tr class="m-0 d-flex">
								<th class="col">Account</th>
								<th class="col">Email</th>
								<th class="col">Token</th>
								<th class="col">Icon</th>
								<th class="col">Suspended</th>
								<th class="col">Date</th>
								<th class="col">More</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($data as $row): ?>
							<tr class="m-0 d-flex">
								<td class="col"><?php echo trim($row["accountName"]); ?></td>
								<td class="col"><?php echo trim($row["email"]); ?></td>
								<td class="col"><?php echo trim($row["tokenName"]); ?></th>
								<td class="col">
									<img src="<?php echo trim($row["tokenIcon"]); ?>" class="img-fluid rounded-circle" title="<?php echo trim($row["tokenSymbol"]); ?>">
								</td>
								<td class="col">
									<input type="checkbox" data-id="<?php echo intval($row["accountId"]); ?>" name="suspended" <?php echo (intval($row["suspended"]) == 1) ? "checked=\"checked\"" : ""; ?> data-size="sm" data-toggle="toggle" data-on="Yes" data-off="No" value="1" data-onstyle="danger" data-offstyle="success">
								</td>
								<td class="col"><?php echo trim($row["datetime"]); ?></td>
								<td class="col"><button type="button" data-transactionid="<?php echo intval($row["accountId"]); ?>" class="btn btn-outline-secondary">details</button></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>				
				</div>
			<?php } ?>
			</div>
		</div>
		
		<div class="row">
			<div class="col">
				<div class="nav-scroller py-1 mb-2"> 
					<nav class="nav d-flex justify-content-center"> 
						<ul class="pagination pagination-sm flex-sm-wrap"> 
						<?php for($index = 1 ; $index <= $pages; $index++): ?>
					<li class="page-item<?php echo ($page == $index) ? " active" : ""; ?>">
						<a class="page-link" href="/<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $index.$queryString; ?>"><?php echo $index; ?></a>
					</li>
					<?php endfor; ?>
						</ul> 
					</nav> 
				</div>		
			</div>
		</div>
	</div>
</section>

<? endif; ?>

<div class="container mb-3">
	<div class="row mb-3">
		<div class="col-sm-12">
			<textarea class="form-control" id="jsondata" rows="3">&#x1F608;</textarea>
		</div>
	</div>
</div>
	
<div class="jumbotron jumbotron-fluid text-center" style="margin-bottom:0">
  <p>Copyright © EpitomeCL 2019</p>
</div>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
<script type="text/javascript">

$('.table').on('change', 'input[type=checkbox]', function(e) {
	event.preventDefault();
	event.stopPropagation();
	
	$(this).bootstrapToggle('disable');
	
	var formData = new FormData();
	formData.append("module", "AccountSuspended");
	formData.append("accountId", $(this).data("id"));	
	formData.append("column", this.name);
	formData.append("checked", this.checked ? 1 : 0);

	$.ajax({
		url: "/inssa/api/",
		type: "POST",
		data: formData,
		processData: false, // tell jQuery not to process the data
		contentType: false, // tell jQuery not to set contentType
		dataType: 'json'
	}).done(function(json) {
		console.log(json);
		if (json.accountId == formData.get("accountId")) {
			$(':checkbox[data-id="'+formData.get("accountId")+'"]').bootstrapToggle('enable');	
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR);
		console.log(textStatus);
		console.log(errorThrown);
	});
});
		
function requestGet(data) {
	$.get(
		"/inssa/api/", data
	).done(
		function( data ) {
			$("#jsondata").text(data);

			var obj = JSON.parse(data);
			
			switch(obj.module) {

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

