<?php
/*
Plugin Name: Any Hostname
Plugin URI: http://wordpress.org/extend/plugins/any-hostname/
Description: Alters all WordPress-generated URLs according to the servers current hostname, so that they will always correspond to the actual hostname as entered by the user.
Author: Simon Fransson
Version: 1.0.6
Author URI: http://dessibelle.se/
*/

class AnyHostname {

    const SETTINGS_KEY = 'any_hostname_settings';
    const ALLOWED_HOSTS_KEY = 'any_hostname_allowed_hosts';
    const FILTER_PRIORITY = 'any_hostname_filter_priority';

    protected static $instance;
    protected $options_page;
    protected $host_patterns = array();
    protected $validated_hosts = array();

    public function __construct() {
    }

    /*
     * ===============
     * == Accessors ==
     * ===============
     */

    public function host_patterns() {
        return $this->host_patterns;
    }

    public function add_host_pattern($host) {
        $this->host_patterns[] = $host;
    }

    /*
     * =========================
     * == Protected functions ==
     * =========================
     */

    protected function initialize() {

        load_plugin_textdomain( 'anyhostname', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        $this->options_page = version_compare(get_bloginfo('version'), '3.5') < 0 ? 'privacy' : 'general';

        $this->load_options();
        $this->enable_filters();

        add_action('admin_enqueue_scripts', array(&$this, 'init_admin'), 1);
        add_action('admin_init', array(&$this, 'init_settings'), 1);

        // Setup functions to disable the filter on options-general.php
        add_action('load-options-general.php', array(&$this, 'general_options_page_init'));

        add_filter('plugin_action_links', array(&$this, 'action_links'), 10, 2 );
    }

    public function action_links($links, $file) {
        static $this_plugin;
        if (!$this_plugin)
            $this_plugin = plugin_basename(__FILE__);

        if ($file == $this_plugin){
        $settings_link = sprintf('<a href="options-%s.php#any-hostname">%s</a>', $this->options_page, __("Settings", "anyhostname"));
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    protected function toggle_filters($activate) {
        $f = (bool)$activate ? 'add_filter' : 'remove_filter';
        $priority = get_option(self::FILTER_PRIORITY, 20);

        $f('option_home', array(&$this, 'home'), $priority);
        $f('option_siteurl', array(&$this, 'siteurl'), $priority);
        $f('theme_root_uri', array(&$this, 'theme_root_uri'), $priority);

        $f('plugins_url', array(&$this, 'plugins_url'), $priority, 3);
        $f('content_url', array(&$this, 'content_url'), $priority, 2);
        $f('upload_dir', array(&$this, 'upload_dir'), $priority);

        $theme_slug = get_option( 'stylesheet' );
        $f('option_theme_mods_' . $theme_slug, array(&$this, 'option_theme_mods_theme'), $priority);

        //$f('allowed_redirect_hosts', array(&$this, 'allowed_redirect_hosts'), $priority);
    }

    protected function enable_filters() {
        self::toggle_filters(true);
    }

    protected function disable_filters() {
        self::toggle_filters(false);
    }

    protected function option_value_to_array($val) {

        $val = trim($val);
        $val = preg_replace('/\s+/sm', ',', $val);
        $hosts = explode(",", $val);
        $hosts = array_unique($hosts);
        //$hosts = array_filter($hosts, create_function('$o', 'return !empty($o);'));

        return $hosts;
    }

    protected function load_options() {
        $o = get_option(self::ALLOWED_HOSTS_KEY);
        if (!$o) {
            $o = '.*';
            update_option(self::ALLOWED_HOSTS_KEY, $o);
        }

        $this->host_patterns = $this->option_value_to_array($o);
    }

    /*
     * TA-DAA, the actual filter function
     */
    protected function filter_url($url) {

        if (!$this->host_allowed($_SERVER['HTTP_HOST'])) {
            return $url;
        }

        $parts = parse_url($url);
        $host = apply_filters('any_hostname_host',  $_SERVER['HTTP_HOST']);
        $user_pass = $port = $query = $fragment = $path = $scheme = null;

        if (isset($parts['user']) && $parts['user']) {
            $user_pass = $parts['user'];
            if (isset($parts['pass']) && $parts['pass']) {
                $user_pass .= ":" . $parts['pass'];
            }

            $user_pass .= "@";
        }

        /* Appears this is not needed, port is part of hostname */
        /*
        if ($parts['port']) {
            $port = ":" . $parts['port'];
        }
        */

        if (isset($parts['query']) && $parts['query']) {
            $query = "?" . $parts['query'];
        }

        if (isset($parts['fragment']) && $parts['fragment']) {
            $query = "#" . $parts['fragment'];
        }

        if (isset($parts['path']) && $parts['path']) {
            $path = $parts['path'];
        }

        if (isset($parts['scheme']) && $parts['scheme']) {
            $scheme = $parts['scheme'];
        }

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            $scheme = 'https';
        }

        $url = sprintf('%s://%s%s%s%s%s%s', $scheme, $user_pass, $host, $port, $path, $query, $fragment);

        return $url;
    }

    public function host_allowed($host) {

        if (array_key_exists($host, $this->validated_hosts)) {
            return $this->validated_hosts[$host];
        }

        $hosts = apply_filters('any_hostname_allowed_hosts', $this->host_patterns);

        if (!count($hosts))
            return true;

        $result = false;
        foreach ($this->host_patterns as $pattern) {
            $result = $result || (bool)(preg_match(sprintf('/%s/', $pattern), $host));
        }

        $this->validated_hosts[$host] = $result;

        return $result;
    }

    /*
     * ======================
     * == Public functions ==
     * ======================
     */

    public static function instance() {
        if (!isset(self::$instance)) {
            $className = __CLASS__;
            self::$instance = new $className;

            self::$instance->initialize();
        }
        return self::$instance;
    }


    /*
     * ===============
     * == Callbacks ==
     * ===============
     */

    public function init_admin($hook) {
        if( $hook == sprintf('options-%s.php', $this->options_page) ) {
            wp_enqueue_script('any_hostname', plugins_url('js/admin.js', __FILE__), array('jquery'));
            //wp_enqueue_style('any_hostname', WP_PLUGIN_URL . '/any-hostname/css/admin.css');
        }
    }

    public function init_settings() {

            add_settings_section(self::SETTINGS_KEY,
                __('Any Hostname', 'anyhostname'),
                array(&$this, 'render_settings'),
                $this->options_page);

            add_settings_field(self::ALLOWED_HOSTS_KEY,
                __('Allowed hosts', 'anyhostname'),
                array(&$this, 'render_allowed_hosts_field'),
                $this->options_page,
                self::SETTINGS_KEY);

            add_settings_field(self::FILTER_PRIORITY,
                __('Filter priority', 'anyhostname'),
                array(&$this, 'render_filter_priority_field'),
                $this->options_page,
                self::SETTINGS_KEY);

            register_setting($this->options_page, self::ALLOWED_HOSTS_KEY, array(&$this, 'sanitize_allowed_host_patterns'));
            register_setting($this->options_page, self::FILTER_PRIORITY, array(&$this, 'sanitize_filter_priority_patterns'));
    }

    /*
     * Disable host filters on options-general.php in order to avoid
     * obscuring the 'home' and 'siteurl' settings, potentially resulting
     * in involuntary changing the sites default host name
     */
    public function general_options_page_init() {
        /*
         * Register hook to temporarily disable the host filters on
         * /wp-admin/options-general.php, as close to the input fields as possible
         */
        add_action('all_admin_notices', array(&$this, 'general_options_page_begin'), 20);
    }

    public function general_options_page_begin() {

        /* Enable host filters again */
        $this->disable_filters();

        /*
         * Register functions to enable the filters again,
         * as quickly as possible after the input fields has been output
         */

        // Perhaps unsafe in contrast to future page changes, but appears to be fully functional for now
        add_filter('date_formats', array(&$this, 'general_options_page_end'), 1);

        // Perhaps safer, and probably OK to run it once more to be sure
        add_action('in_admin_footer', array(&$this, 'general_options_page_end'), 1);
    }

    public function general_options_page_end($input = null) {
        $this->enable_filters();

        // Remove filters again
        remove_filter('date_formats', array(&$this, 'general_options_page_end'), 1);
        remove_action('in_admin_footer', array(&$this, 'general_options_page_end'), 1);

        return $input;
    }

    public function sanitize_allowed_host_patterns($val) {

        $hosts = $this->option_value_to_array($val);

        return implode("\n", $hosts);
    }

    public function sanitize_filter_priority_patterns($val) {
        $val = abs(intval($val));

        return $val ? $val : null;
    }

    public function render_settings() {
        $intro = __("Let's you alter all WordPress-generated URLs according to the servers current hostname, so that they will always correspond to the actual hostname as entered by the user.", 'anyhostname');

        printf('<p id="any-hostname">%s</p>', $intro);
    }

    public function render_allowed_hosts_field() {

        $option = get_option(self::ALLOWED_HOSTS_KEY);
        ?><p><textarea class="code" name="<?php echo self::ALLOWED_HOSTS_KEY; ?>" id="<?php echo self::ALLOWED_HOSTS_KEY; ?>" cols="35" rows="6"><?php echo $option; ?></textarea></p><p class="description"><?php

        $all_link = sprintf('<a href="#" id="any_hostname_all_link">.*</a>');
        $com_link = sprintf('<a href="#" id="any_hostname_dotcom_link">.*\.com</a>');
        $regex_link = sprintf('<a href="%s">%s</a>', 'http://en.wikipedia.org/wiki/Regular_expression', 'Regular expressions');

        printf(__('One host per row. This field uses %s, which means you can also use %s to allow any host, or %s to allow all .com-hosts. It is recommended that you use the input field below when adding hosts if you are unfamiliar with regular expressions.', 'anyhostname'), $regex_link, $all_link, $com_link);

        ?></p>

        <p><input id="any_hostname_add_host_field" class="regular-text" placeholder="<?php _e('example.com', 'anyhostname') ?>"> <a href="#" id="any_hostname_add_host_link" class="button"><?php _e('Add host', 'anyhostname'); ?></a></p>

        <p id="any_hostname_host_warning" class="hidden"><?php printf(__("The list of allowed host does not contain the hostname that your are currently using (%s). This might result in making the site unreachable at this hostname. Are you sure you want to continue?", 'anyhostname'), $_SERVER['HTTP_HOST']); ?></p><?php
    }

    public function render_filter_priority_field() {
        $option = get_option(self::FILTER_PRIORITY);

        ?><p><input class="code" name="<?php echo self::FILTER_PRIORITY; ?>" placeholder="20" value="<?php echo htmlentities($option); ?>"> <span class="description"><?php
            _e('A WordPress filter priority setting. Must be a positive integer.', 'anyhostname');
        ?></span></p><?php
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

    public function plugins_url($url, $path = null, $plugin = null) {
        return $this->filter_url($url);
    }

    public function content_url($url, $path = null) {
        return $this->filter_url($url);
    }

    public function upload_dir($values) {

        $values['url'] = $this->filter_url($values['url']);
        $values['baseurl'] = $this->filter_url($values['baseurl']);

        return $values;
    }


    /*
     * This function filters the option "theme_mods_{$current_theme_slug}"
     *
     * As calues are potentially (likely) stored in the database using
     * an absolute URL we'll have to filter this data as well.
     */
    public function option_theme_mods_theme($theme_mods) {
        if (is_array($theme_mods)) {
            if (isset($theme_mods['header_image'])) {
                $theme_mods['header_image'] = $this->filter_url($theme_mods['header_image']);
            }
            if (isset($theme_mods['header_image_data']) && is_object($theme_mods['header_image_data']) ) {
                if ( isset($theme_mods['header_image_data']->url)) {
                    $theme_mods['header_image_data']->url = $this->filter_url($theme_mods['header_image_data']->url);
                }
                if (isset($theme_mods['header_image_data']->thumbnail_url)) {
                    $theme_mods['header_image_data']->thumbnail_url = $this->filter_url($theme_mods['header_image_data']->thumbnail_url);
                }
            }
            if (isset($theme_mods['background_image'])) {
                $theme_mods['background_image'] = $this->filter_url($theme_mods['background_image']);
            }
            if (isset($theme_mods['background_image_thumb'])) {
                $theme_mods['background_image_thumb'] = $this->filter_url($theme_mods['background_image_thumb']);
            }
        }
        return $theme_mods;
    }


    /* This is here just for shows (might come in handy in the future) */
    /*
    public function allowed_redirect_hosts($wpp = null, $lp = null) {
        return $wpp;
    }
    */
}

function any_hostname() {
    return AnyHostname::instance();
}

$anyhostname = any_hostname();
