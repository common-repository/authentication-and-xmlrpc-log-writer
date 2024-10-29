=== Plugin Name ===
Contributors: mrrotella
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8N6D7LHAUYNQA
Tags: authentication logger, fail2ban, brute force, xmlrpc hack, security, syslog, login, pingback, user enumeration, meta generator, version number
Requires at least: 3.5.1
Tested up to: 4.7
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Log of failed access, pingbacks, user enumeration, disable xmlrpc authenticated methods, kill xmlrpc request on authentication error.

== Description ==

This plugin writes the log of failed access attempts (brute force attack) and invalids pingbacks requests ( by xmlrpc.php ). Very useful to process data via fail2ban.
You can activate the log for each pingback request feature and stop the user enumeration method (by redirecting to the home) with log.
If activated it remove the wordpress version number and meta generator in the head section of your site.
If activated it disable xmlrpc methods that require authentication, in order to avoid brute force attack by xmlrpc. Use this feature if you don't need these xmlrpc methods.
If activated can kill multiple requests in a single xmlrpc call returning a 401 code on xmlrpc login error. This feature may be useful to prevent server overloading on brute force attack by xmlrpc.
You can also view your CUSTOM error log in the admin panel.

= You can write error by =

1. SYSLOG
2. APACHE ERROR_LOG
3. CUSTOM a custom error log file (the used path need to be writable or APACHE ERROR LOG wil be used)

= Log examples =

* SYSLOG

        Dec 17 14:21:02 webserver wordpress(`SERVER_HTTP_HOST`)[2588]: Authentication failure on [`WORDPRESS_SITE_NAME`] for `USED_LOGIN` from `111.222.333.444`
        Dec 17 14:21:02 webserver wordpress(`SERVER_HTTP_HOST`)[2588]: Pingback error `IXR_ERROR_CODE` generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`
        Dec 17 14:21:02 webserver wordpress(`SERVER_HTTP_HOST`)[2588]: Pingback requested for `PINGBACK_URL` generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`
        Dec 17 14:21:02 webserver wordpress(`SERVER_HTTP_HOST`)[2588]: User enumeration attempt generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`

* APACHE

        [Thu Dec 17 14:23:33.662339 2015] [:error] [pid 2580:tid 140001350244096] [client 111.222.333.444:52599] wordpress(`SERVER_HTTP_HOST`) Authentication failure on [`WORDPRESS_SITE_NAME`] for `USED_LOGIN` from `111.222.333.444`, referer: SITE_ADDRESS/wp-login.php
        [Thu Dec 17 14:23:33.662339 2015] [:error] [pid 2580:tid 140001350244096] [client 111.222.333.444:52599] wordpress(`SERVER_HTTP_HOST`) Pingback error `IXR_ERROR_CODE` generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`, referer: SITE_ADDRESS/xmlrpc.php
        [Thu Dec 17 14:23:33.662339 2015] [:error] [pid 2580:tid 140001350244096] [client 111.222.333.444:52599] wordpress(`SERVER_HTTP_HOST`) Pingback requested for `PINGBACK_URL` generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`, referer: SITE_ADDRESS/xmlrpc.php
        [Thu Dec 17 14:23:33.662339 2015] [:error] [pid 2580:tid 140001350244096] [client 111.222.333.444:52599] wordpress(`SERVER_HTTP_HOST`) User enumeration attempt generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`

* CUSTOM

        [Thu Dec 17 14:25:34.000000 2015] wordpress(`SERVER_HTTP_HOST`) Authentication failure on [`WORDPRESS_SITE_NAME`] for `USED_LOGIN` from `111.222.333.444`
        [Thu Dec 17 14:25:34.000000 2015] wordpress(`SERVER_HTTP_HOST`) Pingback error `IXR_ERROR_CODE` generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`
        [Thu Dec 17 14:25:34.000000 2015] wordpress(`SERVER_HTTP_HOST`) Pingback requested for `PINGBACK_URL` generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`
        [Thu Dec 17 14:25:34.000000 2015] wordpress(`SERVER_HTTP_HOST`) User enumeration attempt generated on [`WORDPRESS_SITE_NAME`] from `111.222.333.444`

= fail2ban configuration =

See the FAQ section

= Log viewer =

Log viewer is available only in CUSTOM mode. Note: the log path and the file must exist.

= Localization =
* English (default) - always included
* Italian - since 1.1.3 version

== Installation ==

= Minimum Requirements =

* WordPress 3.5 or greater
* PHP version 4 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of "authentication and xmlrpc log writer", log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "authentication and xmlrpc log writer" and click Search Plugins. Once you've found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

1. Upload `authentication-and-xmlrpc-log-writer.php` to the `/wp-content/plugins/` directory or install via zip
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How can I configure the plugin? =

You can defines the options in the AX Logwriter Settings page.

* **Error Type**: define the error type

    **Options:**

        SYSTEM -> write into SYSLOG;
        APACHE -> write into APCACHE ERROR LOG;
        CUSTOM -> write into log file defined into admin panel;

* **CUSTOM Error Log Path**: error log file absolute path ( only in CUSTOM mode )

        e.g. /your/error/logs/path/

* **CUSTOM Error Log Name**: error log file name ( only in CUSTOM mode )

        e.g. sites_auth_errors.log

* **TIMEZONE**: time zone to use ( only if current_time() WP function not exists )

        e.g. Europe/Rome

* **Log each pingback request**: enable the log of each pingback request

* **Stop User Enumeration**: enable the log of user enumeration attempts. Make also a redirect to the site home

* **Remove WP version and generator tag**: remove the wordpress version number and generator meta from the head section of your site

* **Kill multiple xmlrpc request on xmlrpc login error**: kill multiple requests in a single xmlrpc call returning a 401 code on xmlrpc login error to prevent server overloading on brute force attack by xmlrpc.

* **Disable xmlrpc authenticated methods**: disable all xmlrpc methods that require authentication in order to avoid brute force attack by xmlrpc. Use this feature if you don't need these xmlrpc methods.

= How can I configure fail2ban to work with this log? =

1. Create new filter called **wp-auth-and-xmlrpc.conf** into **/filter.d** path of fail2ban
2. Filter content:

        [Definition]
        failregex = ^.*Authentication failure on .* from <HOST>.*$
                    ^.*Pingback error .* generated on .* from <HOST>.*$
        ignoreregex =

3. Create new jail called **wp-auth-and-xmlrpc.conf** into **/jail.d** path of fail2ban
4. Jail content:

        [wp-auth-and-xmlrpc]
        enabled  = true
        logpath  = /storage/www/logs/sites_auth_errors.log
        maxretry = 5
        bantime  = 600
        findtime = 60
        filter   = wp-auth-and-xmlrpc
        action   = %(action_mwl)s

    **logpath must exists before activate the jail and need to be the same used for this plugin**

5. Reload or restart `fail2ban`

== Screenshots ==

1. Settings view.
2. Custom log viewer.

== Changelog ==

= 1.2.2 =
* Fixed error path error on no custom error type selection.

= 1.2.1 =
* Fixed the php fatal error on update.

= 1.2 =
* Fixed the path error if the path not exists on no custom mode.
* Added more backend control on the log viewer page.

= 1.1.7 =
* Fixed php warning on xmlrpc login error.

= 1.1.6 =
* Added kill multiple xmlrpc request on xmlrpc login error feature.
* Added disable xmlrpc authenticated methods feature.

= 1.1.5 =
* Added remove version number and generator meta feature.

= 1.1.4 =
* Added log each pingback feature.
* Added log and stop user enumeration feature.
* Fixed: Show errors on update options process.

= 1.1.3 =
* Fixed: Use of php functions to determinate timestamp with microseconds.
* Added italian translation.

= 1.1.2 =
* Added log viewer for CUSTOM log mode.
* Added plugin admin menu entry.

= 1.1.1 =
* fixed current time issue for old wp version.

= 1.1.0 =
* Added plugin config options page.

= 1.0.1 =
* Added fail2ban config instructions to readme.

= 1.0.0 =
* Release version.

== Translations ==

* English - default, always included
* Italiano - disponibile dalla versione 1.1.3

*Note:* Feel free to translate this plugin in your language. This is very important for all users worldwide. So please contribute your language to the plugin to make it even more useful. For translating I recommend the ["Poedit Editor"](http://www.poedit.net/).