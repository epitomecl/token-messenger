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
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=admin">Member Accounts</a>
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
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=userupdate&lang=de&userId=<?php echo $data->userId; ?>"><span class="flag-icon flag-icon-de"> </span>  Deutsch</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=userupdate&lang=us&userId=<?php echo $data->userId; ?>"><span class="flag-icon flag-icon-us"> </span>  English</a>
					<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=userupdate&lang=kr&userId=<?php echo $data->userId; ?>"><span class="flag-icon flag-icon-kr"> </span>  한국어</a>
				</div>
			</li>		
		</ul>	
	</div>  
</nav>

<section class="container-section">
	<div class="container mb-4">
		<div class="row pb-4">
			<div class="col-sm-12"><h2>Update User Data</h2></div>
		</div>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
			<input type="hidden" name="module" value="userupdate">
			<input type="hidden" name="userId" value="<?php echo $data->userId; ?>">
			<div class="row mb-3">
				<div class="col-sm-4"><h4>User</h4></div>
				<div class="col-sm-8">
					<div class="form-group">
						<label for="userName">User name:</label>
						<input type="text" class="form-control" placeholder="Enter user name" id="userName" name="userName" required="required" value="<?php echo $data->userName; ?>">
					</div>				
					<div class="form-group">
						<label for="email">Email Address:</label>
						<input type="email" class="form-control" placeholder="Enter email" id="email" name="email" required="required" value="<?php echo $data->email; ?>">
					</div>
					<div class="form-group">
						<label for="password">Password:</label>
						<input type="password" class="form-control" placeholder="Enter password" id="password" name="password">
					</div>					
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-outline-primary btn-block mb-1">Submit</button>			
		</form>		
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