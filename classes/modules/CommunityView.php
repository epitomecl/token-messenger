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
				<a class="nav-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=mypage">My Page</a>
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
				<a class="nav-link active" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=community">Community</a>
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

<section class="container-section">
	<div class="container-fluid mb-4">
		<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="filter">
			<input type="hidden" name="module" value="community">
			<div class="row pb-4">
			  <div class="col-sm-5"><h2>Our Community</h2></div>
			  <div class="col-sm-7">
				<div class="btn-toolbar justify-content-end">
				<button type="button" class="btn ml-1 mb-1">Number of rows:</button>
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
			<div class="row pb-4">
				<div class="col-sm-12">
				<p>This poll finished in <?php echo $closed->h; ?> hours and <?php echo $closed->i; ?> minutes. 
				The attendance must go at least over 50% and so far <?php echo trim($attendance); ?>% of the members have voted. 
				The poll winner must get over 50%. Your current vote is still <?php echo trim($remain); ?> days valid. 
				The next master could be... <?php echo trim($winner->userName); ?></p>
				</div>
			</div>
			<div id="filter" class="collapse">
				<div class="row pb-4">
					<div class="col-sm-12">
						<label for="searchText" class="mr-sm-2">Search text:</label>
						<input type="text" class="form-control mb-2 mr-sm-2" id="searchText" name="searchText"  value="<?php echo $searchText; ?>">
					</div>	
				</div>
			</div>
		</form>

		<div class="row mb-3">
			<div class="col">
			<?php if (count($data) > 0) { ?>
				<?php $colors = array("warning", "info", "danger"); ?>
				<div class="card-columns">
						<?php foreach ($data as $row): ?>
						<div class="card bg-light border-<?php echo $colors[intval($row["group"])]; ?>">
							<div class="card-body">
								<h5 class="card-title"><?php echo trim($row["userName"]); ?></h5>
								<p class="card-text"><?php echo trim($row["comment"]); ?></p>
								<p class="card-text">since <?php echo trim($row["created"]); ?>, <?php echo trim($row["counter"]); ?> account(s)</p>	
								<?php if (intval($row["group"]) > 0): ?>
								<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="ballot">
									<input type="hidden" name="module" value="community">
									<input type="hidden" name="searchText" value="<?php echo $searchText; ?>">
									<input type="hidden" name="page" value="<?php echo $page; ?>">
									<input type="hidden" name="itemsPerPage" value="<?php echo $itemsPerPage; ?>">
									<input type="hidden" name="memberId" value="<?php echo intval($row["userId"]); ?>">
									<div class="btn-toolbar justify-content-end">
									<?php if (intval($row["voted"]) == 0) :?>
										<button type="submit" class="btn btn-outline-success btn-block mb-1">Vote »</button>
									<?php else: ?>
										<button type="submit" class="btn btn-outline-secondary btn-block mb-1" disabled>The user you voted for</button>
									<?php endif; ?>										
									</div>
								</form>
								<?php if (intval($row["percent"]) > 0): ?>
								<div class="progress mt-2">
									<div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo trim($row["percent"]); ?>%"><?php echo trim($row["percent"]); ?>%</div>
								</div>
								<p class="card-text text-center mt-2">Obtained <?php echo trim($row["votes"]); ?> votes out of <?php echo trim($row["voters"]); ?></p>
								<?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
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