=== Plugin Name ===
Contributors: chokladzingo, scottatron, feedmeastraycat
Donate link: http://dessibelle.se/
Tags: host, domain, name, hostname, domainname, multiple, any, many, url, address
Requires at least: 2.7
Tested up to: 3.8.1
Stable tag: 1.0.5

Any Hostname alters all WordPress-generated URLs according to the servers current hostname, allowing you to use a single site on multiple hostnames.

== Description ==

Any Hostname alters all WordPress-generated URLs according to the servers current hostname, so that they will always correspond to the actual hostname as entered by the user, as opposed to always using the URL specified in the WordPress options. The plugin is ideal for making a site available across multiple domains or running local development servers.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `any-hostname` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Optionally edit the list of allowed hosts under 'Options' » 'General' (Privacy for WordPress versions below 3.5)

== Screenshots ==

1. Any Hostname settings

== Changelog ==

= 1.0.5 =
* Added filter to the option `option_theme_mods_{$current_theme_slug}` to filter the URL to header image and background image when using for example `get_header_image()` or `get_theme_mods()` (Thanks to [feedmeastraycat](http://profiles.wordpress.org/feedmeastraycat)).
* Added Ukranian localization (Thanks to Michael Yunat)

= 1.0.4 =
* Due to a [WordPress bug](http://core.trac.wordpress.org/ticket/9296) plugin settings have been moved to Settings / General.

= 1.0.3 =
* **Settings are now located under Settings / Permalinks on WordPress 3.5 or higher**, as the Privacy page has been removed.
* Bugfix for servers using HTTPS on redirected domains
* Added slovak localization by Branco Radenovich of [WebHostingGeeks.com](http://webhostinggeeks.com/blog/)

= 1.0.2 =
* Minor bugfixes and localization adjustments

= 1.0.1 =
* Plugin is now using `plugins_url()` instead of its own filter in order to get the correct path for its javascript.
* Added filters for `plugins_url`, `content_url` and `upload_dir`.
* Fixed an issue with the URL filter for URLs that have a port number

= 1.0 =
* Added localization support and Swedish localization.

= 1.0b2 =
* Added host name caching, preventing a host from being evaluated against the regular expression patterns more than once per page load.
* The plugin will nog disable host filters on the Options » General page in order to avoid obscuring the Site URL (`home`) and WordPress URL (`siteurl`) settings, potentially resulting in involuntary changing the sites default host name
* The settings page will now display a warning when the list of allowed hosts does not include the current host.
* **Bug fix**: Javascript needed used by the plugin on the admin pages will now also load from the filtered URL instead of `WP_PLUGIN_URL`

= 1.0b1 =
* Initial Release

== Known Issues ==

This plugin will not be able to alter the contents of constants such as `WP_CONTENT_URL` and `WP_PLUGIN_URL` as these are (quite naturally) defined before any plugins are loaded. Plugin developers should instead rely on either one of the `plugins_url()`, `content_url()` or `get_option()` functions, which will always return the filtered hostname. An example of this is the WPtouch plugin, in which case you can override WPtouch's `compat_get_wp_content_url` function, as described by this Gist: https://gist.github.com/1401269.

Any Hostname might also obscure the value of WordPress and Site URL settings on the Options » General settings page, due to the fact that these values are retrieved using the `get_option()` function. The values actually stored in WordPress' database is in fact your site's true URL. From 1.0b2 up the plugin will deactivate the host filters on this page, which might cause some page resources to load from the default URL (potentially being unreachable).

Any Hostname will not work on WordPress Network sites (WPMU) due to the fact that the pages in a network install stores its URLs explicitily in the database. Any ideas on how to circumvent this would be greatly appreciated.

Due to a bug in the WordPress Settings API (http://core.trac.wordpress.org/ticket/9296) the plugin settings are located on the Privacy page on WordPress versions below 3.5. From 3.5 and up the settings can be found on the Permalinks page.

== Filters ==

Any Hostname has two filters, allowing you to programatically override the filtered hostname or the list of allowed hostnames. These are described below.

= `any_hostname_host` =
* Arguments: `$host` (required)
* Return value: `$host` (required)

Takes a host as argument, and returns a host that will be used when substituting the domain in URLs supplied by WordPress. This filter can be used to make the plugin use a specific host under certain circumstances.

= `any_hostname_allowed_hosts` =
* Arguments: `$hosts` (required)
* Return value: `$hosts` (required)

Takes an array of allowed hosts as argument, and returns an array of hosts to be used when checking if the user specified host should be allowed. Hosts should be treated as regular expression patterns.

== Credits ==
* Slovak localization by Branco Radenovich of [WebHostingGeeks.com](http://webhostinggeeks.com/blog/)
* Ukranian localization by <a href="http://getvoip.com/blog">Michael Yunat</a>
