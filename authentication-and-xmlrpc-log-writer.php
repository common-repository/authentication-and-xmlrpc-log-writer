<?php
/**
 * Plugin Name: Authentication and xmlrpc log writer
 * Plugin URI:
 * Description: Log of failed access, pingbacks, user enumeration, disable xmlrpc authenticated methods, kill xmlrpc request on authentication error.
 * Version: 1.2.2
 * Author: Federico Rota
 * Author URI: http://www.spazioquattro.it
 * License: GPL2
 * Text Domain: authentication-and-xmlrpc-log-writer
 * Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define('AX_LOGWRITER_FILE', __FILE__);
define('AX_LOGWRITER_PATH', plugin_dir_path(__FILE__));
define('AX_LOGWRITER_BASENAME', plugin_basename( __FILE__ ));

require AX_LOGWRITER_PATH.'includes/plugin-class.php';
require AX_LOGWRITER_PATH.'admin/admin-class.php';

$axlw = new axLogWriter() ;
