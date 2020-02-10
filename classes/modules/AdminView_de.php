<!DOCTYPE html>
<html lang="en">
<head>
  <title>Inssa Project</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">  
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
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="collapsibleNavbar">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link active" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=admin">Member Accounts</a>
			</li>		
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=usercreate">Create User</a>		
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=accountcreate">Create Account</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage">My Page</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=logout">Logout</a>
			</li>	 
		</ul>
		<ul class="navbar-nav ml-auto">		
			<li class="nav-item dropdown">
				<?php $language = trim(array("de"=>"Deutsch", "us"=>"English", "kr"=>"한국어")[$lang]); ?>
				<a class="nav-link dropdown-toggle" id="dropdownlang" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="flag-icon flag-icon-<?php echo $lang; ?>"> </span> <?php echo $language; ?></a>
				<div class="dropdown-menu" aria-labelledby="dropdownlang">
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=admin&lang=de"><span class="flag-icon flag-icon-de"> </span>  Deutsch</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=admin&lang=us"><span class="flag-icon flag-icon-us"> </span>  English</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=admin&lang=kr"><span class="flag-icon flag-icon-kr"> </span>  한국어</a>
				</div>
			</li>		
		</ul>	
	</div>  
</nav>

<section class="container-section">
	<div class="container-fluid mb-4">
		<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="filter">
			<input type="hidden" name="module" value="admin">
			<div class="row pb-4">
			  <div class="col-sm-5"><h2>Mitgliederkonten</h2></div>
			  <div class="col-sm-7">
				<div class="btn-toolbar justify-content-end">
				<button type="button" class="btn ml-1 mb-1">Zeilen pro Seite:</button>
					<select class="btn btn-outline-info ml-1 mb-1" id="itemsPerPage" name="itemsPerPage" onchange="if(this.value != 0) { this.form.submit(); }">
					<?php for ($index = 25; $index <= 250; $index+=25): ?>
					<?php printf("<option value=\"%d\"%s>%s</option>", $index, ($index == $itemsPerPage) ? " selected=\"selected\"": "", $index); ?>
					<?php endfor; ?>
					</select>	

					<button type="button" class="btn btn-outline-info ml-1 mb-1">Total: <?php echo $total; ?></button>
					<button type="button" class="btn btn-outline-warning ml-1 mb-1" data-toggle="collapse" data-target="#filter">Filter</button>		
					<button type="submit" class="btn btn-outline-success ml-1 mb-1">Submit »</button>
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
						<label for="searchText" class="mr-sm-2">Suchtext:</label>
						<input type="text" class="form-control mb-2 mr-sm-2" id="searchText" name="searchText"  value="<?php echo $searchText; ?>">
					</div>	
					<div class="col-sm-4">			
						<label for="tokenName" class="mr-sm-2">Tokenname:</label>
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
					<h4 class="mt-3">Ergebnis&uuml;bersicht</h4>
					<table class="table table-striped history">
						<thead>
							<tr class="m-0 d-flex">
								<th class="col">Konto</th>
								<th class="col">Benutzer</th>
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
								<td class="col"><?php echo trim($row["userName"]); ?></td>
								<td class="col"><?php echo trim($row["tokenName"]); ?></th>
								<td class="col">
									<img src="<?php echo trim($row["tokenIcon"]); ?>" style="height:38px;" class="rounded-circle" title="<?php echo trim($row["tokenSymbol"]); ?>">
								</td>
								<td class="col">
									<input type="checkbox" data-id="<?php echo intval($row["accountId"]); ?>" name="suspended" <?php echo (intval($row["suspended"]) == 1) ? "checked=\"checked\"" : ""; ?> data-size="sm" data-toggle="toggle" data-on="Yes" data-off="No" value="1" data-onstyle="danger" data-offstyle="success">
								</td>
								<td class="col"><?php echo trim($row["datetime"]); ?></td>
								<td class="col">
									<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
										<input type="hidden" name="module" value="accountupdate">
										<input type="hidden" name="accountId" value="<?php echo intval($row["accountId"]); ?>">
										<button type="submit" data-transactionid="<?php echo intval($row["accountId"]); ?>" class="btn btn-outline-secondary">Mehr...</button>
									</form>	
								</td>
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

<div class="container mb-3">
	<div class="row mb-3">
		<div class="col-sm-12">
			<textarea class="form-control" id="jsondata" rows="3">&#x1F608;</textarea>
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