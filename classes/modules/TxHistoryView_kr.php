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
.border-confirmed {
	border-color:#28a745!important;
}
.border-rejected {
	border-color:#dc3545!important;
}
.border-withdrawal {
	border-color:#ffc107!important;
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
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage">My Page</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=userprofile">My Profile</a>		
			</li>
			<?php if ($isMember): ?>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=chatmessage">My Messages</a>
			</li>			
			<li class="nav-item">
				<a class="nav-link active" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=txhistory">Tx History</a>
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
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=txhistory&lang=de"><span class="flag-icon flag-icon-de"> </span>  Deutsch</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=txhistory&lang=us"><span class="flag-icon flag-icon-us"> </span>  English</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=txhistory&lang=kr"><span class="flag-icon flag-icon-kr"> </span>  한국어</a>
				</div>
			</li>		
		</ul>	
	</div>  
</nav>

<section class="container-section bg-light">	
	<div class="container-fluid mb-4">
		<div class="row pb-4">
			<div class="col-sm-5">
				<h2>Token Transaction History</h2>
			</div>
			<div class="col-sm-7">
				<div class="btn-toolbar justify-content-end">
				<button type="button" class="btn ml-1 mb-1">행 수:</button>
					<select class="btn btn-outline-info ml-1 mb-1" id="itemsPerPage" name="itemsPerPage" onchange="if(this.value != 0) { this.form.submit(); }">
					<?php for ($index = 25; $index <= 250; $index+=25): ?>
					<?php printf("<option value=\"%d\"%s>%s</option>", $index, ($index == $itemsPerPage) ? " selected=\"selected\"": "", $index); ?>
					<?php endfor; ?>
					</select>	
					<button type="button" class="btn btn-outline-info ml-1 mb-1">Total: <?php echo $total; ?></button>
					<button type="button" class="btn btn-outline-warning ml-1 mb-1" data-toggle="collapse" data-target="#filter">Filter</button>		
					<button type="button" class="btn btn-outline-success ml-1 mb-1">Submit »</button>
				</div>
			</div>
		</div>
		<div id="filter" class="collapse">
			<div class="row pb-4">
				<div class="col-sm-12">	
					<!-- Nav tabs -->
					<ul class="nav nav-tabs">
						<li class="nav-item">
							<a class="nav-link <?php echo ($sent) ? "active" : ""; ?>" data-toggle="tab" href="#sent">I Sent</a>
						</li>
						<li class="nav-item">
							<a class="nav-link <?php echo (!$sent) ? "active" : ""; ?>" data-toggle="tab" href="#received">I Received</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-toggle="tab" href="#others">Others</a>
						</li>
					</ul>				
				</div>
			</div>
			<div class="row pb-4">
				<div class="col-sm-12">
					<div class="tab-content">
						<div id="sent" class="tab-pane <?php echo ($sent) ? "active" : "fade"; ?>">					
							<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
								<input type="hidden" name="NGINX" value="" />
								<input type="hidden" name="module" value="TokenTransactionHistory">
								<?php if (count($arrOptionUserAccount) > 1): ?>
								<label for="senderId_sent" class="mr-sm-2">from:</label>
								<select class="form-control mb-2 mr-sm-2" id="senderId_sent" name="senderId">
									<?php echo implode("\n", $arrOptionUserAccount); ?>
								<select>
								<?php else: ?>
								<input type="hidden" name="senderId" value="<?php echo intval($mainAccount->accountId); ?>">
								<?php endif; ?>
								<label for="receiverId_sent" class="mr-sm-2">to:</label>
								<select class="form-control mb-2 mr-sm-2" id="receiverId_sent" name="receiverId">
									<option value="0">all...</option>
									<?php echo implode("\n", $arrOptionAccount); ?>
								<select>
								<label for="year" class="mr-sm-2">year:</label>
								<select class="form-control mb-2 mr-sm-2" name="year"><?php echo $txtOptionYear; ?><select>				
								<label for="month" class="mr-sm-2">month:</label>
								<select class="form-control mb-2 mr-sm-2" name="month"><?php echo $txtOptionMonth; ?><select>
								<button type="button" class="btn btn-primary module mt-2 mb-2">Submit</button>			
							</form>							
						</div>
						<div id="received" class="tab-pane <?php echo (!$sent) ? "active" : "fade"; ?>">						
							<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
								<input type="hidden" name="NGINX" value="" />
								<input type="hidden" name="module" value="TokenTransactionHistory">
								<label for="senderId_received" class="mr-sm-2">from:</label>
								<select class="form-control mb-2 mr-sm-2" id="senderId_received" name="senderId">
									<option value="0">all...</option>
									<?php echo implode("\n", $arrOptionAccount); ?>
								<select>					
								<?php if (count($arrOptionUserAccount) > 1): ?>
								<label for="receiverId_received" class="mr-sm-2">to:</label>
								<select class="form-control mb-2 mr-sm-2" id="receiverId_received" name="receiverId">
									<?php echo implode("\n", $arrOptionUserAccount); ?>
								<select>
								<?php else: ?>
								<input type="hidden" name="receiverId" value="<?php echo intval($mainAccount->accountId); ?>">
								<?php endif; ?>
								<label for="year" class="mr-sm-2">year:</label>
								<select class="form-control mb-2 mr-sm-2" name="year"><?php echo $txtOptionYear; ?><select>				
								<label for="month" class="mr-sm-2">month:</label>
								<select class="form-control mb-2 mr-sm-2" name="month"><?php echo $txtOptionMonth; ?><select>
								<button type="button" class="btn btn-outline-info text-dark">
									<div class="form-check">
										<label class="form-check-label cursor-pointer">
											<input type="checkbox" class="form-check-input cursor-pointer" name="includeFaucet" value="1">include Faucet Transaction
										</label>
									</div>
								</button>							
								<button type="button" class="btn btn-primary module mt-2 mb-2">Submit</button>		
							</form>							
						</div>
						<div id="others" class="tab-pane fade">						
							<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
								<input type="hidden" name="NGINX" value="" />
								<input type="hidden" name="module" value="TokenTransactionHistory">
								<label for="senderId_others" class="mr-sm-2">from:</label>
								<select class="form-control mb-2 mr-sm-2" id="senderId_others" name="senderId">
									<option value="0">all...</option>
									<?php echo implode("\n", $arrOptionAccount); ?>
								<select>					
								<label for="receiverId_others" class="mr-sm-2">to:</label>
								<select class="form-control mb-2 mr-sm-2" id="receiverId_others" name="receiverId">
									<option value="0">all...</option>
									<?php echo implode("\n", $arrOptionAccount); ?>
								<select>
								<label for="year" class="mr-sm-2">year:</label>
								<select class="form-control mb-2 mr-sm-2" name="year"><?php echo $txtOptionYear; ?><select>				
								<label for="month" class="mr-sm-2">month:</label>
								<select class="form-control mb-2 mr-sm-2" name="month"><?php echo $txtOptionMonth; ?><select>	
								<button type="button" class="btn btn-outline-info text-dark">
									<div class="form-check">
										<label class="form-check-label cursor-pointer">
											<input type="checkbox" class="form-check-input cursor-pointer" name="includeFaucet" value="1">include Faucet Transaction
										</label>
									</div>
								</button>
								<button type="button" class="btn btn-primary module mt-2 mb-2">Submit</button>	
							</form>							
						</div>
					</div>	
				</div>
			</div>
		</div>
		<div class="row pb-4">
			<div class="col-sm-12">
				<div class="viewTokenTransactionHistory">
					<h4 class="mt-3">Result table &quot;<?php echo ($sent) ? "I Sent" : "I Received"; ?>&quot;</h4>
					<div class="history mt-4">
					<?php foreach ($data as $row): ?>
						<div class="card border-<?php echo trim($row["status"]); ?> mt-4">
							<div class="card-body">
								<div class="row">
									<div class="col-sm-3 pb-4">
										<h4><?php echo trim($row["quantity"]); ?> 
										<img src="<?php echo trim($row["icon"]); ?>" style="height:38px;" class="rounded-circle" title="<?php echo trim($row["token"]); ?>">
										<?php echo trim($row["symbol"]); ?>
										</h4>
										(<?php echo trim($row["status"]); ?>)
									</div>
									<div class="col-sm-9">
										<div class="row">
											<div class="col"><?php echo trim($row["senderName"]); ?>:</div>
											<div class="col-sm-10">
												<p><?php echo trim($row["reference"]); ?></p>
												<p><?php echo trim($row["created"]); ?></p>
											</div>
										</div>
										<div class="row">
											<div class="col"><?php echo trim($row["receiverName"]); ?>:</div>
											<div class="col-sm-10">
												<p><?php echo trim($row["supplement"]); ?></p>
												<p><?php echo trim($row["modified"]); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
					</div>
				</div>
				<template id="viewTokenTransactionHistorySent">
					<h4 class="mt-3">Result table &quot;I Sent&quot;</h4>
					<div class="history mt-4">
					</div>
				</template>				
				<template id="viewTokenTransactionHistoryReceived">
					<h4 class="mt-3">Result table &quot;I Received&quot;</h4>
					<div class="history mt-4">
					</div>
				</template>				
				<template id="viewTokenTransactionHistoryOthers">
					<h4 class="mt-3">Result table &quot;Others&quot;</h4>
					<div class="history mt-4">
					</div>
				</template>					
				<template id="itemTokenTransactionHistory">
					<div class="card border-${status} mt-4">
						<div class="card-body">
							<div class="row">
								<div class="col-sm-3 pb-4">
									<h4>${quantity}
									<img src="${icon}" style="height:38px;" class="rounded-circle" title="${token}">
									${symbol}
									</h4>
									(${status})
								</div>
								<div class="col-sm-9">
									<div class="row">
										<div class="col">${senderName}</div>
										<div class="col-sm-10">
											<p>${reference}</p>
											<p>${created}</p>
										</div>
									</div>
									<div class="row">
										<div class="col">${receiverName}:</div>
										<div class="col-sm-10">
											<p>${supplement}</p>
											<p>${modified}</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</template>					
			</div>
		</div>
		<div class="row">
			<div class="col">
				<div class="nav-scroller py-1 mb-2"> 
					<nav class="nav d-flex justify-content-center"> 
						<ul class="pagination pagination-sm flex-sm-wrap"> 
						<?php for($index = 1 ; $index <= $pages; $index++): ?>
					<li class="page-item<?php echo ($page == $index) ? " active" : ""; ?>">
						<a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $index.$queryString; ?>"><?php echo $index; ?></a>
					</li>
					<?php endfor; ?>
						</ul> 
					</nav> 
				</div>		
			</div>
		</div>		
	</div>
</section>

<section class="container-section">
	<div class="container-fluid mb-3">
		<div class="row mb-3">
			<div class="col-sm-12">
				<textarea class="form-control" id="jsondata" rows="3">&#x1F608;</textarea>
			</div>
		</div>
	</div>
</section>

<section class="container-section">
	<div class="container-fluid mb-3">
		<div class="row mb-3">
			<div class="col-sm-12">
				<!-- spacer //-->
			</div>
		</div>
	</div>
</section>

<footer class="page-footer font-small pt-4">
	<div class="footer-copyright text-center py-3">© <?php echo date("Y");?> Copyright:
		<a href="https://epitomecl.com"> EpitomeCL.com</a>
	</div>
</footer>

<div class="modal fade" id="dialogDetail">
    <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
			  <h4 class="modal-title">Tx Transaction Details</h4>
			  <button type="button" class="close" data-dismiss="modal">×</button>
			</div>
			<div class="modal-body">
				<div class="row mb-3">
					<div class="col-sm-12 result">
						... 곧 온다
					</div>
					<template id="viewTransactionDetails">
					<table class="table table-striped">
					<thead>
						<th>Action</th><th>Actor</th><th>Quantity</th><th>Text</th><th>Date</th>
					</thead>
					<tbody>
						<tr>
							<td>Send</td><td>${senderName}</td><td>${quantity}</td><td>${reference}</th><td>${created}</td>
						</tr>
						<tr>
							<td>${status}</td><td>${receiverName}</td><td> </td><td>${supplement}</th><td>${modified}</td>
						</tr>
					</tbody>
					</table>
					</template>
				</div>
			</div>
			<div class="modal-footer">
			  <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
			</div>
		</div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
<script type="text/javascript">

$(".viewTokenTransactionHistory").on("click", ".btn", function(event) {
	event.preventDefault();
	event.stopPropagation();
	
	var formData = new FormData();
	
	formData.append("module", "TokenTransactionDetail");
	formData.append("transactionId", $(this).data("transactionid"));
	
	$("#dialogDetail").data("formData", formData);
	
	var query = new Array();
	for (var pair of formData.entries()) {
		query.push(pair[0] + "=" + pair[1]); 
	}

	requestGet(query.join("&"));		
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
					if (obj.data) {
						$('.viewTokenTransactionHistory').empty();
						var viewTpl = "";
						var itemTpl = "";
						switch (obj.view) {
							case "sent":
								viewTpl = $('#viewTokenTransactionHistorySent').html().split(/\$\{(.+?)\}/g);
								itemTpl = $('#itemTokenTransactionHistory').html().split(/\$\{(.+?)\}/g);
								break;
							case "received":
								viewTpl = $('#viewTokenTransactionHistoryReceived').html().split(/\$\{(.+?)\}/g);
								itemTpl = $('#itemTokenTransactionHistory').html().split(/\$\{(.+?)\}/g);
								break;
							case "others":
								viewTpl = $('#viewTokenTransactionHistoryOthers').html().split(/\$\{(.+?)\}/g);
								itemTpl = $('#itemTokenTransactionHistory').html().split(/\$\{(.+?)\}/g);
								break;
						}
						var view = [{ foo: "foo"}];
						$('.viewTokenTransactionHistory').html(view.map(function (item) {
							return viewTpl.map(render(item)).join('');
						}));						
						var items = obj.data;
						$('.viewTokenTransactionHistory div.history').empty();
						$('.viewTokenTransactionHistory div.history').append(
							items.map(function (item) {
								return itemTpl.map(render(item)).join('');
							})
						);
					}
					break;
				case "TokenTransactionDetail":
					var itemTpl = $('#viewTransactionDetails').html().split(/\$\{(.+?)\}/g);
					
					$("#dialogDetail").modal();
											
					if (obj.data && obj.data.length > 0) {

						var elm = obj.data[0];
						var items = [{senderName: elm.senderName, receiverName: elm.receiverName, 
							reference: elm.reference, status: elm.status, supplement: elm.supplement,
							quantity: elm.quantity, created: elm.created, modified: elm.modified}];
						$("#dialogDetail .modal-body .result").html(
							items.map(function (item) {
								return itemTpl.map(render(item)).join('');
							})
						);						
					}
					break;					
				case "TotalUserTokenHistory":
					var itemTpl = $('#itemTotalUserTokenHistory').html().split(/\$\{(.+?)\}/g);

					if (obj.data) {
						var items = obj.data;
						$('.viewTotalUserTokenHistory table.history').find('tbody').detach();
						$('.viewTotalUserTokenHistory table.history').append($('<tbody>'));  
						$('.viewTotalUserTokenHistory table.history').find('tbody:last').append(
							items.map(function (item) {
								return itemTpl.map(render(item)).join('');
							})
						);	
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

					$('.viewDirectTokenTransfer').empty();
					
					if (obj.data) {
						$.each( obj.data, function( key, elm ) {
							var view = [{transactionId: elm.transactionId, sender: elm.sender,
								receiver: elm.receiver, quantity: elm.quantity, reference: elm.reference,
								datetime: elm.datetime}];
							$('.viewDirectTokenTransfer').html(view.map(function (item) {
								return viewTpl.map(render(item)).join('');
							}));
						});
					}			
					break;	
				case "VerifiedTokenTransfer":
					var viewTpl = $('#viewVerifiedTokenTransfer').html().split(/\$\{(.+?)\}/g);
					var itemTpl = $('#itemPendingTransaction').html().split(/\$\{(.+?)\}/g);
					
					$('.viewVerifiedTokenTransfer').empty();
					
					if (obj.data) {
						$.each( obj.data, function( key, elm ) {
							if (!elm.pendingId) {
								return;
							}							
							var view = [{pendingId: elm.pendingId, sender: elm.sender,
								receiver: elm.receiver, quantity: elm.quantity, reference: elm.reference,
								datetime: elm.datetime}];
							$('.viewVerifiedTokenTransfer').prepend(view.map(function (item) {
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