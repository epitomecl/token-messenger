<!DOCTYPE html>
<html lang="en">
<head>
  <title>Inssa Project</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.1.0/css/flag-icon.min.css" rel="stylesheet">  
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
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="collapsibleNavbar">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link active" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage">My Page</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=userprofile">My Profile</a>		
			</li>
			<?php if ($isMember): ?>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=txhistory">Tx History</a>
			</li>	  
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=userhistory">User History</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=community">Community</a>
			</li>
			<?php endif; ?>
			<?php if ($isAdmin): ?>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=admin">Member Accounts</a>
			</li>	
			<?php endif; ?>			
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=logout">Logout</a>
			</li>	 
		</ul>
		<ul class="navbar-nav ml-auto">		
			<li class="nav-item dropdown">
				<?php $language = trim(array("de"=>"Deutsch", "us"=>"English", "kr"=>"한국어")[$lang]); ?>
				<a class="nav-link dropdown-toggle" id="dropdownlang" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="flag-icon flag-icon-<?php echo $lang; ?>"> </span> <?php echo $language; ?></a>
				<div class="dropdown-menu" aria-labelledby="dropdownlang">
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage&lang=de"><span class="flag-icon flag-icon-de"> </span>  Deutsch</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage&lang=us"><span class="flag-icon flag-icon-us"> </span>  English</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage&lang=kr"><span class="flag-icon flag-icon-kr"> </span>  한국어</a>
				</div>
			</li>		
		</ul>	
	</div>  
</nav>

<section class="container-section bg-light">
	<div class="container-fluid mb-4">
		<div class="row">
			<div class="col-sm-12"><h2>Welcome back, <?php echo trim($userData->userName); ?>!</h2></div>
		</div>
		
		<?php foreach ($data as $account): ?>
		<div class="card border-<?php echo (intval($account->suspended)) ? "danger bg-light" : "info"; ?> mt-4">
		<div class="card-body">
		<div class="row">
			<div class="col-sm-6">
				<div class="row">
					<div class="col-4">
						<p>Account</p>
						<h4><?php echo trim($account->accountName); ?></h4>
					</div>
					  <div class="col-4">
						<p>in total</p>
						<h4 class="d-inline"><?php echo intval($account->received); ?><h4>
					  </div>
					  <div class="col-4">
						<p>in month</p>
						<h4><?php echo intval($account->month); ?><h4>				  
					  </div>			  
				</div>
			</div>
			<div class="col-sm-6">
				<div class="row">			
					<div class="col-4">
						<p><?php echo trim($account->tokenName); ?> (<?php echo trim($account->tokenSymbol); ?>)<p>
						<img src="<?php echo trim($account->tokenIcon); ?>" style="height:38px;" class="rounded-circle" title="<?php echo trim($account->tokenName); ?>"> 						
					 </div>
					<div class="col-4">
						<p>remain</p>
						<h4><?php echo intval($account->remain); ?><h4>
					</div>
					<div class="col-4">
						<p>total</p>
						<h4><?php echo intval($account->total); ?><h4>
					</div>			  
				</div>
			</div>			
		</div>
		</div>
		<?php if (intval($account->suspended)): ?>
		<div class="card-footer">This account is suspended. No own unique tokens are generated.</div>
		<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
</section>

<?php if (count($data) > 0): ?>
<section class="container-section">
	<div class="container-fluid mb-4">
		<div class="row pb-4">
			<div class="col-sm-8">
				<h2>We ask for your attention</h2>
			</div>
			<div class="col-sm-4">
				<div class="btn-toolbar justify-content-end">

				</div>				
			</div>
		</div>
		<div class="row pb-4">
			<div class="col-sm-12">
				<div class="card-columns viewPendingTransaction">
					<?php foreach ($listPendingTransactionByReceiver as $index => $row): ?>
						<div class="card border-info cursor-pointer" data-NGINX="PUT" data-pendingId="<?php echo trim($row["pendingId"]); ?>" data-dialog="1" data-accountId="<?php echo trim($row["accountId"]); ?>">
							<div class="card-header bg-info"><?php echo intval($row["quantity"]); ?> token from <?php echo trim($row["account"]); ?></div>
							<div class="card-body">
								<h5 class="card-title"><?php echo trim($row["reference"]); ?></h5>
								<p class="card-text"><?php echo trim($row["datetime"]); ?></p>							
							</div>
						</div>
					<?php endforeach; ?>
					<?php foreach ($listPendingTransactionBySender as $index => $row): ?>
						<div class="card border-warning cursor-pointer" data-NGINX="DELETE" data-pendingId="<?php echo trim($row["pendingId"]); ?>" data-dialog="1" data-accountId="<?php echo trim($row["accountId"]); ?>">
							<div class="card-header bg-warning"><?php echo intval($row["quantity"]); ?> token to <?php echo trim($row["account"]); ?></div>							
							<div class="card-body">
								<h5 class="card-title"><?php echo trim($row["reference"]); ?></h5>
								<p class="card-text"><?php echo trim($row["datetime"]); ?></p>							
							</div>
						</div>
					<?php endforeach; ?>
					<?php if (count($listPendingTransactionBySender) == 0 && count($listPendingTransactionByReceiver) == 0): ?>
						<div class="card" data-NGINX="" data-pendingId="0" data-dialog="0" data-accountId="0">
							<div class="card-header">10 token to X</div>
							<div class="card-body">
								<h5 class="card-title">A confirmed or withdrawal message will be grayed and removed.</h5>
								<p class="card-text">20/02/2002 20:02</p>							
							</div>
						</div>
						<div class="card border-warning" data-NGINX="" data-pendingId="0" data-dialog="0" data-accountId="0">
							<div class="card-header bg-warning">10 token to Y</div>
							<div class="card-body">
								<h5 class="card-title">An outgoing message is yellow marked.</h5>
								<p class="card-text">20/02/2002 20:02</p>							
							</div>
						</div>
						<div class="card border-info" data-NGINX="" data-pendingId="0" data-dialog="0" data-accountId="0">
							<div class="card-header bg-info">10 token to Z</div>						
							<div class="card-body">
								<h5 class="card-title">An incoming message is blue marked.</h5>
								<p class="card-text">20/02/2002 20:02</p>						
							</div>
						</div>						
					<?php endif; ?>
				</div>
				<template id="itemPendingTransaction">
					<div class="card ${border} cursor-pointer" data-NGINX="${NGINX}" data-pendingId="${pendingId}" data-dialog="1" data-accountId="${accountId}">
						<div class="card-header ${background}">${quantity} token ${direction} ${account}</div>						
						<div class="card-body">
							<h5 class="card-title">${reference}</h5>
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
					<input type="hidden" name="senderId" value="<?php echo $data[0]->accountId; ?>">
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
<?php else: ?>
<section class="container-section">
	<div class="container-fluid mb-3">
		<div class="row mb-3">
			<div class="col-sm-12">
				<p>Please wait for creating your account.</p>
				<p>Internal confirmation process is running...</p>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="container-section">
	<div class="container-fluid mb-3">
		<div class="row mb-3">
			<div class="col-sm-12 output blue">
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
	
<footer class="page-footer font-small pt-4 fixed-bottom">
	<div class="footer-copyright text-center py-3">© <?php echo date("Y");?> Copyright:
		<a href="https://epitomecl.com"> EpitomeCL.com</a>
	</div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
<script type="text/javascript">

function updateBoard(path) {
	var formData = new FormData();
	formData.append("module", "PendingTransaction");
	
	var query = new Array();
	for (var pair of formData.entries()) {
		query.push(pair[0] + "=" + pair[1]); 
	}

	requestGet(query.join("&"));
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
						
						if (obj.data.length > 0) {
							$('.card-columns.viewPendingTransaction').empty();
						}
						$.each( obj.data, function( key, elm ) {
							if (!elm.pendingId) {
								return;
							}
							var items = [{pendingId: elm.pendingId, accountId: elm.accountId, NGINX: elm.NGINX, 
								border: (elm.NGINX == "DELETE") ? "border-warning" : "border-info",
								direction : (elm.NGINX == "DELETE") ? "to" : "from",
								background: (elm.NGINX == "DELETE") ? "bg-warning" : "bg-info",
								datetime: elm.datetime, account: elm.account, quantity: elm.quantity, reference: elm.reference}];
							$('.card-columns.viewPendingTransaction').append(
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
							$('.card-columns.viewPendingTransaction').prepend(
								items.map(function (item) {
									return itemTpl.map(render(item)).join('');
								})
							);
						});
					}

					if((obj.pendingId) && obj.pendingId > 0 && (obj.transactionId) && obj.transactionId > 0) {
						$(".card-columns.viewPendingTransaction [data-pendingid='"+obj.pendingId+"']").removeClass('border-warning border-info bg-warning bg-info');
						$(".card-columns.viewPendingTransaction [data-pendingid='"+obj.pendingId+"']").data("dialog", 0);
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