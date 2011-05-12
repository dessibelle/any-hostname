<?php
/*
Plugin Name: Any Hostname
Plugin URI: http://dessibelle.se
Description: Alters all WordPress-generated links according to the current hostname, so that they will always point to the hostname entered by the user.
Author: Simon Fransson
Version: 1.0b1
Author URI: http://dessibelle.se/
*/

class AnyHostname {
	
	public function __construct() {
		add_filter('option_home', array(&$this, 'home_url'));
	}
	
	public function home_url($url) {
		return maybe_unserialize('http://' . $_SERVER['HTTP_HOST']);
	}
}

$any_hostname = new AnyHostname();