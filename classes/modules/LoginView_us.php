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

    </ul>
	<ul class="navbar-nav ml-auto">		
		<li class="nav-item dropdown">
			<?php $language = trim(array("de"=>"Deutsch", "us"=>"English", "kr"=>"한국어")[$lang]); ?>		
			<a class="nav-link dropdown-toggle" id="dropdownlang" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="flag-icon flag-icon-<?php echo $lang; ?>"> </span> <?php echo $language; ?></a>
			<div class="dropdown-menu" aria-labelledby="dropdownlang">
				<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=login&lang=de"><span class="flag-icon flag-icon-de"> </span>  Deutsch</a>
				<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=login&lang=us"><span class="flag-icon flag-icon-us"> </span>  English</a>
				<a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?module=login&lang=kr"><span class="flag-icon flag-icon-kr"> </span>  한국어</a>
			</div>
		</li>		
	</ul>
  </div>  
</nav>

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

<section class="container-section">
	<div class="container-fluid mb-4">
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="module" value="signup">
			<div class="row">
				<div class="col-sm-4">
					<h4>Membership signup</h4>
				</div>
				<div class="col-sm-8">
					<div class="form-group">
						<label for="userName">User name:</label>
						<input type="text" class="form-control" placeholder="Enter user name" id="userName" name="userName" required="required">
					</div>				
					<div class="form-group">
						<label for="email">Email:</label>
						<input type="email" class="form-control" id="email" name="email" required="required">
					</div>
					<div class="form-group">
						<label for="comment">About me:</label>
						<textarea class="form-control" rows="5" id="comment" name="comment" placeholder="Something about me..." required="required"></textarea>
					</div>
					<button type="submit" class="btn btn-primary btn-block">Send</button>
				</div>
			</div>
		</form>
	</div>
</section>

<section class="container-section">
	<div class="container-fluid mb-3">
		<div class="row mb-3">
			<div class="col-sm-12">

			</div>
		</div>
	</div>
</section>

<footer class="page-footer font-small pt-4 fixed-bottom">
	<div class="footer-copyright text-center py-3">© <?php echo date("Y");?> Copyright:
		<a href="https://epitomecl.com"> EpitomeCL.com</a>
	</div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
<script type="text/javascript">

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