<?php

/**
 * This file is part of authentication and xmlrpc log writer
 * define the plugin working class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class axLogWriter {
    // Name of the array
    protected $option_name = 'ax_logwriter';

    // Default values
    protected $defaults = array() ;

    // Configurated options
    private $options ;

    /**
     * Start up
     */
    public function __construct()
       {
        // Set the defaults options
        $this->ax_logwriter_set_defaults_option();
        // Get current options
        $this->options = $this->ax_logwriter_get_options();

        // Listen for the activate event
        register_activation_hook(AX_LOGWRITER_FILE, array($this, 'ax_logwriter_activate'));
        // Listen for the uninstall event
        register_uninstall_hook(AX_LOGWRITER_FILE, array('axLogWriter', 'ax_logwriter_uninstall'));

        add_action( 'wp_login_failed', array( $this, 'ax_logwriter_login_failed_hook' ) );
        add_filter( 'xmlrpc_pingback_error', array( $this, 'ax_logwriter_pingback_error_hook' ), 1 );
        add_filter( 'plugin_action_links_' . AX_LOGWRITER_BASENAME, array( $this, 'ax_logwriter_settings_link' ) );
        add_filter( 'plugin_row_meta', array( $this, 'ax_logwriter_row_meta' ), 10, 2 );
        add_action( 'plugins_loaded', array( $this, 'ax_logwriter_load_plugin_textdomain' ) );

        // Add a pingback log if option enabled
        if( isset( $this->options[ 'security_log_each_pingback' ] ) &&
            !empty( $this->options[ 'security_log_each_pingback' ] ) )
           {
            // Need to log each pingback request
            add_action( 'xmlrpc_call', array( $this, 'ax_logwriter_pingback_request_hook' ) );
           }

        // Add a user enumeration log and stop it if option enabled
        if( isset( $this->options[ 'security_stop_user_enumeration' ] ) &&
            !empty( $this->options[ 'security_stop_user_enumeration' ] ) )
           {
            // Need to log and stop user enumeration
            $this->ax_logwriter_security_stop_user_enumeration_hook();
           }

        // Add a remove wp version and generator meta feature if option enabled
        if( isset( $this->options[ 'security_remove_generator' ] ) &&
            !empty( $this->options[ 'security_remove_generator' ] ) )
           {
            // Need to remove wp version and generator meta
            remove_action( 'wp_head', 'wp_generator' ); // Remove version number
            add_filter('the_generator', create_function('', 'return "";'));
           }

        // Add a disable xmlrpc authenticated methods feature if option enabled
        if( isset( $this->options[ 'security_disable_xmlrpc_authenticated_methods' ] ) &&
            !empty( $this->options[ 'security_disable_xmlrpc_authenticated_methods' ] ) )
           {
            // Need to disable xmlrpc authenticated methods
            add_filter( 'xmlrpc_enabled', '__return_false' );
           }

        // Add a kill multiple requests in a single xmlrpc call feature if option enabled
        if( isset( $this->options[ 'security_kill_xmlrpc_on_login_error' ] ) &&
            !empty( $this->options[ 'security_kill_xmlrpc_on_login_error' ] ) )
           {
            // Need to kill multiple requests in a single xmlrpc call
            add_filter( 'xmlrpc_login_error', array( $this, 'ax_logwriter_xmlrpc_login_failed_hook' ), 10, 2 ) ;
           }

       }

    /**
     * HOOK functions
     */
    public function ax_logwriter_load_plugin_textdomain()
       {
        load_plugin_textdomain( 'authentication-and-xmlrpc-log-writer', FALSE, basename( dirname( AX_LOGWRITER_FILE ) ) . '/languages/' );
       }

    public function ax_logwriter_login_failed_hook($username)
       {
        $site_name = "unknown" ;
        if( function_exists( "get_bloginfo" ) ) {
            $tmp_site_name = get_bloginfo('name') ;
            if( !empty( $tmp_site_name ) ) {
                $site_name = $tmp_site_name;
            }
        }
        $real_ip = $this->ax_logwriter_get_real_ip() ;
        $this->ax_logwriter_log_writer("Authentication failure on [".$site_name."] for ".$username." from ".$real_ip."");
       }

    public function ax_logwriter_pingback_error_hook($ixr_error)
       {
        $site_name = "unknown" ;
        if( function_exists( "get_bloginfo" ) ) {
            $tmp_site_name = get_bloginfo('name') ;
            if( !empty( $tmp_site_name ) ) {
                $site_name = $tmp_site_name;
            }
        }
        if ( $ixr_error->code === 48 ) return $ixr_error; // don't punish duplication
        $real_ip = $this->ax_logwriter_get_real_ip() ;
        $this->ax_logwriter_log_writer("Pingback error ".$ixr_error->code." generated on [".$site_name."] from ".$real_ip."");
        return $ixr_error;
       }

    public function ax_logwriter_pingback_request_hook($call)
       {
        $site_name = "unknown" ;
        if( function_exists( "get_bloginfo" ) ) {
            $tmp_site_name = get_bloginfo('name') ;
            if( !empty( $tmp_site_name ) ) {
                $site_name = $tmp_site_name;
            }
        }

        if ('pingback.ping' == $call) {
            global $wp_xmlrpc_server;

            $args = array();
                if ( is_object( $wp_xmlrpc_server ) ) {
                   $args = $wp_xmlrpc_server->message->params;
                }

            $to = 'unknown';
            if ( ! empty( $args[1] ) ) {
                $to = esc_url_raw( $args[1] );
            }
            $real_ip = $this->ax_logwriter_get_real_ip() ;
            $this->ax_logwriter_log_writer("Pingback requested for '".$to."' on [".$site_name."] from ".$real_ip."");
        }
       }

     public function ax_logwriter_security_stop_user_enumeration_hook()
       {
        if ( !is_admin() && isset($_SERVER['REQUEST_URI'])){
            if( !preg_match('/(wp-comments-post)/', $_SERVER['REQUEST_URI']) && !empty($_REQUEST['author']) && (int) $_REQUEST['author'] ) {
                $site_name = "unknown" ;
                if( function_exists( "get_bloginfo" ) ) {
                    $tmp_site_name = get_bloginfo('name') ;
                    if( !empty( $tmp_site_name ) ) {
                        $site_name = $tmp_site_name;
                    }
                }
                $real_ip = $this->ax_logwriter_get_real_ip() ;
                $this->ax_logwriter_log_writer("User enumeration attempt generated on [".$site_name."] from ".$real_ip."");

                // Make a redirection to the home with 301 code
                header ('HTTP/1.1 301 Moved Permanently');
                header ('Location: ' . home_url() );
                exit();
            }
        }
       }

    public function ax_logwriter_xmlrpc_login_failed_hook($error, $user)
       {
        wp_die( $error->message, "Authentication request fail", array( 'response' => 401 ) ) ;
       }

    public function ax_logwriter_settings_link( $links )
       {
        // Add settings link on plugin page
        $action_links = array( 'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=axlw-setting-admin' ) ) . '" title="' . esc_attr( __('Settings', 'General') ) . ' ">' . __('Settings', 'General') . '</a>', );

        return array_merge( $action_links, $links );
       }

    /**
     * Show row meta on the plugin screen.
     *
     * @access  public
     * @param   mixed $links Plugin Row Meta
     * @param   mixed $file  Plugin Base file
     * @return  array
     */
    public function ax_logwriter_row_meta( $links, $file )
       {
        if ( $file == AX_LOGWRITER_BASENAME )
           {
            $row_meta = array(
                'home'    => '<a href="' . esc_url( 'https://wordpress.org/plugins/authentication-and-xmlrpc-log-writer/' ) . '" target="_blank" title="' . esc_attr__( 'WP Plugin Homepage','authentication-and-xmlrpc-log-writer' ) . '">'.__( 'Home','authentication-and-xmlrpc-log-writer' ).'</a>',
                'faq'     => '<a href="' . esc_url( 'https://wordpress.org/plugins/authentication-and-xmlrpc-log-writer/faq/' ) . '" target="_blank" title="' . esc_attr__( 'WP Plugin FAQ Page','authentication-and-xmlrpc-log-writer' ) . '">'.__( 'FAQ','authentication-and-xmlrpc-log-writer' ).'</a>',
                'support' => '<a href="' . esc_url( 'https://wordpress.org/support/plugin/authentication-and-xmlrpc-log-writer' ) . '" target="_blank" title="' . esc_attr__( 'WP Plugin Support Page','authentication-and-xmlrpc-log-writer' ) . '">'.__( 'Support','authentication-and-xmlrpc-log-writer' ).'</a>',
                'rate'    => '<a href="' . esc_url( 'https://wordpress.org/support/view/plugin-reviews/authentication-and-xmlrpc-log-writer' ) . '" target="_blank" title="' . esc_attr__( 'WP Plugin Review Page','authentication-and-xmlrpc-log-writer' ) . '">'.__( 'Rate','authentication-and-xmlrpc-log-writer' ).'</a>',
                'donate'  => '<a href="' . esc_url( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8N6D7LHAUYNQA' ) . '" target="_blank" title="' . esc_attr__( 'Donate for this plugin','authentication-and-xmlrpc-log-writer' ) . '">'.__( 'Donate','authentication-and-xmlrpc-log-writer' ).'</a>',
            );

            return array_merge( $links, $row_meta );
           }

        return (array) $links;
       }

    public function ax_logwriter_activate()
       {
        // Add option to WP option table
        update_option($this->option_name, $this->defaults);
       }

    static function ax_logwriter_uninstall()
       {
        // Remove option from WP option table
        delete_option('ax_logwriter');
       }

    /**
     * Operational functions
     */
    public function ax_logwriter_log_writer( $data_to_write = "Unkown error for authentication or xmlrpc" )
       {
        /**
         * Function used for writing the final log
         */

        // Get current and defaults options
        $options = $this->options;
        $defaults = $this->defaults;

        $error_type         = ( isset( $options[ 'error_type' ] ) ? $options[ 'error_type' ] : $defaults[ 'error_type' ] );
        $error_log_path     = ( isset( $options[ 'error_log_path' ] ) ? $options[ 'error_log_path' ] : $defaults[ 'error_log_path' ] );
        $error_log_name     = ( isset( $options[ 'error_log_name' ] ) ? $options[ 'error_log_name' ] : $defaults[ 'error_log_name' ] );
        $error_log_timezone = ( isset( $options[ 'error_log_timezone' ] ) ? $options[ 'error_log_timezone' ] : $defaults[ 'error_log_timezone' ] );


        // Manage php timestamp with microseconds
        date_default_timezone_set($error_log_timezone);
        $time = $this->ax_logwriter_date_with_micro("D M d H:i:s.u Y");

        switch( $error_type )
           {
            case "SYSTEM" :
                // Error by SYSLOG
                openlog('wordpress('.$_SERVER['HTTP_HOST'].')', LOG_NDELAY|LOG_PID, LOG_AUTHPRIV);
                syslog(LOG_NOTICE,$data_to_write);
                break;
            case "APACHE" :
                // Error by APACHE ERROR LOG
                error_log('wordpress('.$_SERVER['HTTP_HOST'].') '.$data_to_write, 0);
                break;
            case "CUSTOM" :
                // Error by custom log
                if( is_dir( $error_log_path ) && is_writable( $error_log_path ) && !empty( $error_log_name ) ) error_log('['.$time.'] wordpress('.$_SERVER['HTTP_HOST'].') '.$data_to_write.PHP_EOL, 3, $error_log_path.$error_log_name );
                else error_log('wordpress('.$_SERVER['HTTP_HOST'].') '.$data_to_write, 0);
                break;
            default :
                // Error by APACHE ERROR LOG
                error_log('wordpress('.$_SERVER['HTTP_HOST'].') '.$data_to_write, 0);
           }
        }

    public function ax_logwriter_get_real_ip()
       {
        /**
         * Function used to analyze the user's IP address
         */
        $ip = getenv("REMOTE_ADDR"); // default

        if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");

        return $ip ;
       }

    private function ax_logwriter_date_with_micro($format, $timestamp = null)
       {
        /**
         * Function used to calculate the date with microseconds
         */
        if (is_null($timestamp) || $timestamp === false)
           {
            $timestamp = microtime(true);
           }
        $timestamp_int = (int) floor($timestamp);
        $microseconds = (int) round(($timestamp - floor($timestamp)) * 1000000.0, 0);

        $format_with_micro = str_replace("u", $microseconds, $format);
        return date($format_with_micro, $timestamp_int);
       }

    private function ax_logwriter_set_defaults_option()
       {
        /**
         * Function used to sets the defaults options
         */
        $error_type = 'CUSTOM' ;
        $error_log_path = '/storage/www/logs/' ;
        $error_log_name = 'sites_auth_errors.log' ;
        $error_log_timezone = 'Europe/Rome' ;
        $wp_time_zone_string = get_option('timezone_string') ;
        if( $wp_time_zone_string &&
            !empty( $wp_time_zone_string ) )
           {
            $error_log_timezone = $wp_time_zone_string;
           }
        $security_log_each_pingback = '0' ;
        $security_stop_user_enumeration = '0' ;
        $security_remove_generator = '0' ;
        $security_kill_xmlrpc_on_login_error = '0' ;
        $security_disable_xmlrpc_authenticated_methods = '0' ;

        $this->defaults = array(
            'error_type'            => $error_type,
            'error_log_path'        => $error_log_path,
            'error_log_name'        => $error_log_name,
            'error_log_timezone'    => $error_log_timezone,
            'security_log_each_pingback' => $security_log_each_pingback,
            'security_stop_user_enumeration' => $security_stop_user_enumeration,
            'security_remove_generator' => $security_remove_generator,
            'security_kill_xmlrpc_on_login_error' => $security_kill_xmlrpc_on_login_error,
            'security_disable_xmlrpc_authenticated_methods' => $security_disable_xmlrpc_authenticated_methods
            ) ;
       }

    public function ax_logwriter_get_defaults_option()
       {
        $this->ax_logwriter_set_defaults_option();
        return $this->defaults ;
       }

    public function ax_logwriter_get_options()
       {
        return get_option( $this->option_name );
       }
}
