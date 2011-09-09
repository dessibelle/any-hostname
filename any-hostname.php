<?php
/*
Plugin Name: Any Hostname
Plugin URI: http://dessibelle.se
Description: Alters all WordPress-generated URLs according to the servers current hostname, so that they will always correspond to the actual hostname as entered by the user.
Author: Simon Fransson
Version: 1.0b1
Author URI: http://dessibelle.se/
*/

class AnyHostname {
	
	const SETTINGS_KEY = 'any_hostname_settings';
	const ALLOWED_HOSTS_KEY = 'any_hostname_allowed_hosts';
	
	protected static $instance;
	protected $allowed_hosts = array();
	
	public function __construct() {
	}
	
	protected function initialize() {
		
		$this->load_options();
		
		add_filter('option_home', array(&$this, 'home'), 20);
		add_filter('option_siteurl', array(&$this, 'siteurl'), 20);
		add_filter('theme_root_uri', array(&$this, 'theme_root_uri'), 20);
		//add_filter('allowed_redirect_hosts', array(&$this, 'allowed_redirect_hosts'), 20);
		
		add_action('admin_enqueue_scripts', array(&$this, 'init_admin'));
		add_action('admin_init', array(&$this, 'init_settings'));
	}
	
	public static function instance() {
		if (!isset(self::$instance)) {
            $className = __CLASS__;
            self::$instance = new $className;
			
			self::$instance->initialize();
        }
        return self::$instance;
	}
	
	public function init_admin() {
		wp_enqueue_script('any_hostname', WP_PLUGIN_URL . '/any-hostname/js/admin.js', array('jquery'));
		//wp_enqueue_style('any_hostname', WP_PLUGIN_URL . '/any-hostname/css/admin.css');
	}
	
	public function init_settings() {
			
			$page = 'privacy';
			
		 	add_settings_section(self::SETTINGS_KEY,
				'Any Hostname',
				array(&$this, 'render_settings'),
				$page);

		 	add_settings_field(self::ALLOWED_HOSTS_KEY,
				__('Allowed hosts', 'anyhostname'),
				array(&$this, 'render_allowed_hosts_field'),
				$page,
				self::SETTINGS_KEY);

		 	register_setting($page, self::ALLOWED_HOSTS_KEY, array(&$this, 'sanitize_allowed_hosts'));
	}
	
	protected function option_value_to_array($val) {
		
		$val = trim($val);
		$val = preg_replace('/\s+/sm', ',', $val);
		$hosts = explode(",", $val);
		$hosts = array_unique($hosts);
		//$hosts = array_filter($hosts, create_function('$o', 'return !empty($o);'));

		return $hosts;
	}
	
	public function sanitize_allowed_hosts($val) {
		
		$hosts = $this->option_value_to_array($val);
		
		return implode("\n", $hosts);
	}
	
	public function render_settings() {
		$intro = __("Let's you alter all WordPress-generated URLs according to the servers current hostname, so that they will always correspond to the actual hostname as entered by the user.", 'anyhostname');
		
		printf('<p>%s</p>', $intro);
	}
	
	public function render_allowed_hosts_field() {
		
		$option = get_option(self::ALLOWED_HOSTS_KEY);
		?><p><textarea class="code" name="<?php echo self::ALLOWED_HOSTS_KEY; ?>" id="<?php echo self::ALLOWED_HOSTS_KEY; ?>" cols="35" rows="6"><?php echo $option; ?></textarea></p><p class="description"><?php
		
		$all_link = sprintf('<a href="#" id="any_hostname_all_link">.*</a>');
		$com_link = sprintf('<a href="#" id="any_hostname_dotcom_link">.*\.com</a>');
		$regex_link = sprintf('<a href="%s">%s</a>', 'http://en.wikipedia.org/wiki/Regular_expression', 'Regular expressions');
		
		printf(__('One host per row. This field uses %s, which means you can also use %s for any host, or %s for all .com-hosts. It is recommended that you use the input field below when adding hosts if you are unfamiliar with regular expressions.', 'anyhostname'), $regex_link, $all_link, $com_link);
		
		?></p>
		
		<p><input id="any_hostname_add_host_field" class="regular-text" placeholder="<?php _e('example.com', 'anyhostname') ?>"> <a href="#" id="any_hostname_add_host_link" class="button"><?php _e('Add a host', 'anyhostname'); ?></a></p><?php
	}
	
	protected function load_options() {
		$o = get_option(self::ALLOWED_HOSTS_KEY);
		if (!$o) {
			$o = '.*';
			update_option(self::ALLOWED_HOSTS_KEY, $o);
		}
		
		$this->allowed_hosts = $this->option_value_to_array($o);
	}
	
	protected function filter_url($url) {
		
		if (!$this->host_allowed($_SERVER['HTTP_HOST'])) {
			return $url;
		}
		
		$parts = parse_url($url);
		
		$host = apply_filters('any_hostname_host',  $_SERVER['HTTP_HOST']);
		$user_pass = $port = $query = $fragment = null;
		
		if ($parts['user']) {
			$user_pass = $parts['user'];
			if ($parts['pass']) {
				$user_pass .= ":" . $parts['pass'];
			}
			
			$user_pass .= "@";
		}
		
		if ($parts['port']) {
			$port = ":" . $parts['port'];
		}
		
		if ($parts['query']) {
			$query = "?" . $parts['query'];
		}
		
		if ($parts['fragment']) {
			$query = "#" . $parts['fragment'];
		}
				
		$url = sprintf('%s://%s%s%s%s%s%s', $parts['scheme'], $user_pass, $host, $port, $parts['path'], $query, $fragment);
		
		return $url;
	}
	
	public function theme_root_uri($theme_root_uri, $siteurl = null, $stylesheet_or_template = null) {
		
		return $this->filter_url($theme_root_uri);
	}
	
	public function home($url) {
		return maybe_unserialize($this->filter_url($url));
	}
	
	public function siteurl($url) {
		return $this->home($url);
	}
	
	/*
	public function allowed_redirect_hosts($wpp = null, $lp = null) {
		return $wpp;
	}
	*/
	
	public function allowed_hosts() {
		return $this->allowed_hosts;
	}
	
	public function add_allowed_host($host) {
		$this->allowed_hosts[] = $host;
	}
	
	public function host_allowed($host) {
		
		$hosts = apply_filters('any_hostname_allowed_hosts', $this->allowed_hosts);
		
		if (!count($hosts))
			return true;
		
		$result = false;
		foreach ($this->allowed_hosts as $pattern) {
			$result = $result || (bool)(preg_match(sprintf('/%s/', $pattern), $host));
		}
		
		return $result;
	}
}

function any_hostname() {
	return AnyHostname::instance();
}

$anyhostname = any_hostname();
