<?php

namespace modules;

class Login {
	public function __construct() {
	}
	
	public function doGet($lang) {
		require_once(sprintf("LoginView_%s.php", $lang)); 
	}
}