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

function getQueryString($array) {
	$items = array();
	$params = array_filter($array);
	
	foreach ($params as $key => $value) {
		array_push($items, sprintf("&%s=%s", $key, urlencode($value)));
	}
	
	return implode($items);
}

function rebuildImageAsSquaredPng($targetFile, $imageFileType, $endSize) {
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

function uploadFile($file, $targetDir, $endSize, $accountId) {
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
			rebuildImageAsSquaredPng($targetFile, $imageFileType, $endSize);
		}
	}
	
	return $uploadOk ? $targetFile : "";
}

function getData($mysqli, $accountId) {
	$data = NULL;
	
	$sql = "SELECT user.user_id, email, account_id, account.name AS accountName, suspended, ";
	$sql .= "account.token AS tokenName, symbol AS tokenSymbol, icon AS tokenPath ";
	$sql .= "FROM user LEFT JOIN account ON (account.user_id = user.user_id) ";
	$sql .= sprintf("WHERE account.account_id = %d;", $accountId);
	
	if ($result = $mysqli->query($sql)) {
		while($row = $result->fetch_assoc()) {
			$data = new stdClass;
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

function checkEmail($mysqli, $email) {
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

function getListAccount($mysqli) {
	$data = array();		
	$sql = "SELECT account_id AS accountId FROM account ";
	$sql .= "WHERE account.suspended = 0 ";
	$sql .= "AND community = 0;";
	
	if ($result = $mysqli->query($sql)) {
		while ($row = $result->fetch_assoc()) {
			array_push($data, intval($row["accountId"]));
		}
	} else {
		throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
	}
	
	return $data;
}
	
function insertTransaction($mysqli, $senderId, $receiverId, $quantity, $reference) {
	$sql = "INSERT transaction SET ";
	$sql .= sprintf("sender_id = %d, ", $senderId);
	$sql .= sprintf("receiver_id = %d, ", $receiverId);		
	$sql .= sprintf("quantity = %d, ", $quantity);
	$sql .= sprintf("reference = '%s', ", $mysqli->real_escape_string($reference));
	$sql .= sprintf("status = '%s';", "confirmed");	
	
	if (!$mysqli->query($sql) === TRUE) {
		throw new Exception(sprintf("%s, %s", get_class($this), $sql.$mysqli->error), 507);
	}
	
	return $mysqli->insert_id;
}
	
session_start();
			
$config = parse_ini_file("api/include/db.mysql.ini");
$mysqli = new mysqli($config['HOST'], $config['USER'], $config['PASS'], $config['NAME']);
$userId = isset($_SESSION['USERID']) ? intval($_SESSION["USERID"]) : 0;
$module = getParam($_POST, "module");
$accountId = getParam($_GET, "accountId", "int");
$isAdmin = 0;
$data = NULL;

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
		case "ACCOUNTCREATE":
			$accountId = getParam($_POST, "accountId", "int");
			$email = getParam($_POST, "email");
			$password = getParam($_POST, "password");
			$salt = md5($email);
			$sha256 = hash_hmac("sha256", $password, $salt);	
			$accountName = getParam($_POST, "accountName");
			$suspended = getParam($_POST, "suspended", "int");
			$tokenName = getParam($_POST, "tokenName");
			$tokenSymbol = getParam($_POST, "tokenSymbol");
			$tokenIcon = $_FILES["tokenIcon"];
			$validEmail = checkEmail($mysqli, $email);
			
			if ($accountId == 0 && $validEmail && strlen($accountName) >= 1 && strlen($tokenName) >= 1 && strlen($password) >= 4) {
				$sql = sprintf("INSERT INTO user SET email ='%s', password='%s', salt='%s';", $mysqli->real_escape_string($email), $sha256, $salt);
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
				$tokenPath = uploadFile($tokenIcon, "images/token/", 256, $accountId);	

				if (strlen($tokenPath) > 0) {
					$sql = "UPDATE account SET ";
					$sql .= sprintf("icon ='%s' ", $mysqli->real_escape_string($tokenPath));
					$sql .= sprintf("WHERE account_id = %d;", $accountId);
					
					if (!$mysqli->query($sql) === TRUE) {
						throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
					}
				}				
			}
			break;
		case "ACCOUNTUPDATE":
			$accountId = getParam($_POST, "accountId", "int");
			$email = getParam($_POST, "email");
			$password = getParam($_POST, "password");
			$salt = md5($email);
			$sha256 = hash_hmac("sha256", $password, $salt);	
			$accountName = getParam($_POST, "accountName");
			$tokenName = getParam($_POST, "tokenName");
			$tokenSymbol = getParam($_POST, "tokenSymbol");
			$tokenIcon = $_FILES["tokenIcon"];
			$validEmail = checkEmail($mysqli, $email);			
			$data = getData($mysqli, $accountId);
			
			if (isset($data)) {
				$email = (strlen($email) == 0 || !$validEmail) ? $data->email : $email;
				$accountName = (strlen($accountName) == 0) ? $data->accountName : $accountName;
				$tokenName = (strlen($tokenName) == 0) ? $data->tokenName : $tokenName;
				$tokenSymbol = (strlen($tokenSymbol) == 0) ? $data->tokenSymbol : $tokenSymbol;
				
				$sql = "UPDATE user SET ";
				if (strlen($password) >= 4) {
					$sql .= sprintf("password='%s', salt='%s' ", $sha256, $salt);
				}
				$sql .= sprintf("email ='%s' ", $mysqli->real_escape_string($email));
				$sql .= sprintf("WHERE user_id = %d;", $data->userId);
				
				if (!$mysqli->query($sql) === TRUE) {
					throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
				}
				
				$sql = "UPDATE account SET ";
				$sql .= sprintf("name ='%s', ", $mysqli->real_escape_string($accountName));
				$sql .= sprintf("token ='%s', ", $mysqli->real_escape_string($tokenName));
				$sql .= sprintf("symbol ='%s' ", $mysqli->real_escape_string($tokenSymbol));				
				$sql .= sprintf("WHERE account_id = %d;", $accountId);
				
				if (!$mysqli->query($sql) === TRUE) {
					throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
				}
				
				$tokenPath = uploadFile($tokenIcon, "images/token/", 256, $accountId);	

				if (strlen($tokenPath) > 0) {
					$sql = "UPDATE account SET ";
					$sql .= sprintf("icon ='%s' ", $mysqli->real_escape_string($tokenPath));
					$sql .= sprintf("WHERE account_id = %d;", $accountId);
					
					if (!$mysqli->query($sql) === TRUE) {
						throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
					}
				}				
			}
			break;	
		case "ACCOUNTSUSPENDED":
			$accountId = getParam($_POST, "accountId", "int");
			$suspended = getParam($_POST, "suspended", "int");
			$comment = getParam($_POST, "comment");
			
			if (count($accountIds) > 0 && strlen($comment) > 0) {
				$sql = "UPDATE account SET ";
				$sql .= sprintf("suspended ='%d' ", $suspended);
				$sql .= sprintf("WHERE account_id = %d;", $accountId);
				
				if (!$mysqli->query($sql) === TRUE) {
					throw new Exception(sprintf("%s", $sql.$mysqli->error), 507);
				}

				$transactionId = insertTransaction($mysqli, $accountId, $accountId, 0, $comment);					
			}
			
			break;
	}
	
	$data = getData($mysqli, $accountId);
	$isAdmin = isAdmin($mysqli, $userId);
	
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
.image-center {
    width: 128px;
    height: 128px;
    margin: auto;    
    display: block;
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

<?php elseif ($accountId == 0): ?>

<section class="container-section">
	<div class="container mb-4">
		<div class="row pb-4">
			<div class="col-sm-12"><h2>New Member Account</h2></div>
		</div>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
			<input type="hidden" name="module" value="accountcreate">
			<input type="hidden" name="accountId" value="0">
			<div class="row mb-3">
				<div class="col-sm-4"><h4>User</h4></div>
				<div class="col-sm-8">
					<div class="form-group">
						<label for="email">Email Address:</label>
						<input type="email" class="form-control" placeholder="Enter email" id="email" name="email" required="required">
					</div>
					<div class="form-group">
						<label for="password">Password:</label>
						<input type="password" class="form-control" placeholder="Enter password" id="password" name="password" required="required">
					</div>
				</div>
			</div>
			<div class="row mb-3">			
				<div class="col-sm-4"><h4>Account</h4></div>
				<div class="col-sm-8">
					<div class="form-group">
						<label for="accountName">Account Name:</label>
						<input type="text" class="form-control" placeholder="Enter Account Name" id="accountName" name="accountName" required="required">
					</div>
					<div class="form-check">
					  <label class="form-check-label">
						<input type="checkbox" class="form-check-input" name="suspended" value="1">Suspended
					  </label>
					</div>
				</div>
			</div>
			<div class="row mb-3">			
				<div class="col-sm-4"><h4>Token</h4></div>
				<div class="col-sm-8">					  
					<div class="form-group">
						<label for="tokenName">Token Name:</label>
						<input type="text" class="form-control" placeholder="Enter Token Name" id="tokenName" name="tokenName" required="required">
					</div>
					<div class="form-group">
						<label for="tokenSymbol">Token Symbol:</label>
						<input type="text" class="form-control" placeholder="Enter Token Symbol" id="tokenSymbol" name="tokenSymbol" required="required">
					</div>
					<div class="form-group">
						<label for="tokenSymbol">Token Image (256x256, PNG):</label>
					  <div class="custom-file">
						<input type="file" class="custom-file-input" id="inputGroupFile01" aria-describedby="inputGroupFileAddon01" name="tokenIcon">
						<label class="custom-file-label" for="inputGroupFile01">Choose file</label>
					  </div>
					</div>					
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-outline-primary btn-block mb-1">Submit</button>
		</form>		
	</div>
</section>

<?php elseif (isset($data)): ?>

<section class="container-section">
	<div class="container mb-4">
		<div class="row pb-4">
			<div class="col-sm-12"><h2>Suspend / Release Member Account</h2></div>
		</div>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" name="module" value="accountsuspended">
			<input type="hidden" name="accountId" value="<?php echo $data->accountId; ?>">
			<div class="row mb-3">			
				<div class="col-sm-4">
					<h4>Account</h4>
					<img class="img-fluid rounded-circle image-center mb-3 mt-3" src="<?php echo $data->tokenPath; ?>" alt="<?php echo $data->tokenName; ?>">	
				</div>
				<div class="col-sm-8">
					<div class="table-responsive">
						<table class="table table-striped">
							<tr>
								<td>Email Address:</td>
								<td><?php echo $data->email; ?></td>
							</tr>
							<tr>
								<td>Account Name:</td>
								<td><?php echo $data->accountName; ?></td>
							</tr>
							<tr>
								<td>Token Name:</td>
								<td><?php echo $data->tokenName; ?></td>
							</tr>
							<tr>
								<td>Token Symbol:</td>
								<td><?php echo $data->tokenSymbol; ?></td>
							</tr>
						</table>
					</div>
					<div class="form-group">
						<label for="comment">Reason:</label>
						<textarea class="form-control" rows="5" id="comment" name="comment" required="required"></textarea>
					</div>	
					<div class="form-check">
						<label class="form-check-label">
							<input type="checkbox" class="form-check-input" name="suspended" value="1" <?php echo (intval($data->suspended) == 1) ? "checked=\"checked\"" : ""; ?>>Suspended
						</label>
					</div>					
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-outline-primary btn-block mb-1">Submit</button>			
		</form>		
	</div>
</section>

<?php endif; ?>

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

