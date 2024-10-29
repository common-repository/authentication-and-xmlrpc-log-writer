<?php

/**
 * This file is part of authentication and xmlrpc log writer
 * define the plugin option admin class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class axLogWriterSettingsPage extends axLogWriter {

    /**
     * Holds the values to be used in the fields callbacks
     */

    /**
     * Start up
     */
    public function __construct()
       {
        $this->defaults = $this->ax_logwriter_get_defaults_option();
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
       }

    /**
     * Add options page
     */
    public function add_plugin_page()
       {
        // Create a top level menu
        add_menu_page(
            __('AX Log Writer Settings','authentication-and-xmlrpc-log-writer'),
            __('AX Log Writer','authentication-and-xmlrpc-log-writer'),
            'manage_options',
            'axlw-setting-admin',
            array( $this, 'create_admin_page' )
        );

        // Create a sublevel entry for log viewer

        $logviewer_page = add_submenu_page(
            'axlw-setting-admin',
            __('Custom Log Viewer','authentication-and-xmlrpc-log-writer'),
            __('Custom Log Viewer','authentication-and-xmlrpc-log-writer'),
            'manage_options',
            'axlw-logviewer-admin',
            array( $this, 'create_logviewer_page' )
        );

        // Use custom style in logviewer page
        add_action( 'admin_print_styles-'.$logviewer_page, array( $this, 'axlw_logwiewer_admin_styles' ) );
       }

    /**
     * Options page callback
     */
    public function create_admin_page()
       {
        // Set class property
        $this->options = $this->ax_logwriter_get_options();

        ?>
        <div class="wrap">
            <h2><?php echo __( 'AX LogWriter Settings', 'authentication-and-xmlrpc-log-writer' ); ?></h2>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'axlw_general_option_group' );
                do_settings_sections( 'axlw-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
       }

    /**
     * Logviewer page callback
     */
    public function create_logviewer_page()
       {
        // Set class property
        $this->options = $this->ax_logwriter_get_options();

        $lines =(!isset($_GET['lines'])) ? '10': $_GET['lines'];

        $error_type         = isset( $this->options['error_type'] ) ? $this->options['error_type'] : $this->defaults['error_type'] ;
        $error_log_path     = isset( $this->options[ 'error_log_path' ] ) ? $this->options[ 'error_log_path' ] : $this->defaults[ 'error_log_path' ] ;
        $error_log_name     = isset( $this->options[ 'error_log_name' ] ) ? $this->options[ 'error_log_name' ] : $this->defaults[ 'error_log_name' ] ;

        $file = $error_log_path.$error_log_name ;
        $file_exists = ( !empty( $file ) && file_exists( $file ) ) ? TRUE : FALSE ;

        ?>
        <div class="wrap">
         <div class="axlw_admin_logviewer_header">
          <h2><?php echo __( 'AX Log Writer - Custom Log Viewer', 'authentication-and-xmlrpc-log-writer' ); ?></h2>
        <?php
        if( $error_type == "CUSTOM" && $file_exists )
           {
        ?>
          <h3 class="axlw_admin_logviewer_content-subhead"><?php printf( __( 'Here are the last %s rows of your Custom Log', 'authentication-and-xmlrpc-log-writer' ), $lines ); ?><small>(<?php echo $file ; ?>)</small></h3>
          <p><?php echo __( 'How many lines to display?', 'authentication-and-xmlrpc-log-writer' ) ; ?></p>
          <form action="" method="get">
           <input type="hidden" name="page" value="axlw-logviewer-admin">
            <select name="lines" onchange="this.form.submit()">
             <option value="10" <?= ($lines=='10') ? 'selected':'' ?>>10</option>
             <option value="50" <?= ($lines=='50') ? 'selected':'' ?>>50</option>
             <option value="100" <?= ($lines=='100') ? 'selected':'' ?>>100</option>
             <option value="500" <?= ($lines=='500') ? 'selected':'' ?>>500</option>
            </select>
          </form>
        <?php
           }
        ?>
         </div>
         <div class="axlw_admin_logviewer_content">
          <code>
           <pre style="font-size:14px;font-family:monospace;color:black;white-space: inherit">
        <?php
        if( $error_type == "CUSTOM" )
           {
            if( $file_exists )
               {
                $output = $this->axlw_tail($file, $lines);
                $output = trim($output);
               if( !empty($output) )
                   {
                ?>
            <ol reversed>
                <?php
                $outlines = explode("\n", $output);
                // Latest first
                $outlines = array_reverse($outlines);
                foreach ($outlines as $outline) {
                    if(trim($outline)!=''){
                        echo '<li>'.htmlspecialchars($outline).'</li>';
                    }
                }
                ?>
            </ol>
            <?php
                   }
                else
                   {
                    ?>
                    <span><?php echo __( 'Great!! Log is empty :)', 'authentication-and-xmlrpc-log-writer' ); ?></span>
                    <?php
                   }
               }
            else
               {
                ?>
                <span><?php echo __( 'Unable to display Custom Log. File not exists. It will be created at the first occurence', 'authentication-and-xmlrpc-log-writer' ); ?></span>
                <?php
               }
           }
        else
           {
            ?>
            <span><?php echo __( 'Unable to display Custom Log. This feature is only available in CUSTOM mode.', 'authentication-and-xmlrpc-log-writer' ); ?></span>
            <?php
           }
        ?>
           </pre>
          </code>
         </div>
        </div>
        <?php
       }

    /**
     * Register and add settings
     */
    public function page_init()
       {
        register_setting(
            'axlw_general_option_group', // Option group
            $this->option_name, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'axlw_general_section', // ID
            __( 'General Settings', 'authentication-and-xmlrpc-log-writer' ), // Title
            array( $this, 'print_section_info' ), // Callback
            'axlw-setting-admin' // Page
        );

        add_settings_field(
            'error_type', // ID
            __( 'Error Type', 'authentication-and-xmlrpc-log-writer' ), // Title
            array( $this, 'error_type_callback' ), // Callback
            'axlw-setting-admin', // Page
            'axlw_general_section' // Section
        );

        add_settings_field(
            'error_log_path',
            __( 'CUSTOM Error Log Path (only for CUSTOM error type)', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'error_log_path_callback' ),
            'axlw-setting-admin',
            'axlw_general_section'
        );

        add_settings_field(
            'error_log_name',
            __( 'CUSTOM Error Log Name (only for CUSTOM error type)', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'error_log_name_callback' ),
            'axlw-setting-admin',
            'axlw_general_section'
        );

        add_settings_field(
            'error_log_timezone',
            __( 'TIMEZONE', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'error_log_timezone_callback' ),
            'axlw-setting-admin',
            'axlw_general_section'
        );

        add_settings_section(
            'axlw_secuity_section', // ID
            __( 'Security Settings', 'authentication-and-xmlrpc-log-writer' ), // Title
            array( $this, 'print_security_section_info' ), // Callback
            'axlw-setting-admin' // Page
        );

        add_settings_field(
            'security_log_each_pingback',
            __( 'Pingback Request Log', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'security_log_each_pingback_callback' ),
            'axlw-setting-admin',
            'axlw_secuity_section'
        );

        add_settings_field(
            'security_stop_user_enumeration',
            __( 'Stop User Enumeration', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'security_stop_user_enumeration_callback' ),
            'axlw-setting-admin',
            'axlw_secuity_section'
        );

        add_settings_field(
            'security_remove_generator',
            __( 'Remove WP version and generator tag', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'security_remove_generator_callback' ),
            'axlw-setting-admin',
            'axlw_secuity_section'
        );

        add_settings_field(
            'security_kill_xmlrpc_on_login_error',
            __( 'Kill multiple xmlrpc request on xmlrpc login error', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'security_kill_xmlrpc_on_login_error_callback' ),
            'axlw-setting-admin',
            'axlw_secuity_section'
        );

        add_settings_field(
            'security_disable_xmlrpc_authenticated_methods',
            __( 'Disable xmlrpc authenticated methods', 'authentication-and-xmlrpc-log-writer' ),
            array( $this, 'security_disable_xmlrpc_authenticated_methods_callback' ),
            'axlw-setting-admin',
            'axlw_secuity_section'
        );

        // Register the logviewer style
        wp_register_style( 'axlw_logviewer_style', plugins_url('style/admin-style-min.css', __FILE__) );
       }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
       {
        $new_input = array();

        if( isset( $input['error_type'] ) )
           {
            $new_input['error_type'] = sanitize_text_field( $input['error_type'] );
            // Cannot be blank
            if( empty( $new_input['error_type'] ) )
               {
                add_settings_error(
                        $this->option_name.'[error_type]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please select a valid VALUE for Error Type', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['error_type'] = $this->defaults['error_type'];
               }
            // Verify the value
            if( $new_input['error_type'] != "SYSTEM" &&
                $new_input['error_type'] != "APACHE" &&
                $new_input['error_type'] != "CUSTOM" )
               {
                add_settings_error(
                        $this->option_name.'[error_type]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please select a valid VALUE for Error Type', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['error_type'] = $this->defaults['error_type'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['error_type'] = $this->defaults['error_type'];
           }

        if( isset( $input['error_log_path'] ) )
           {
            $new_input['error_log_path'] = sanitize_text_field( $input['error_log_path'] );
            $new_input['error_log_path'] = trailingslashit( $new_input['error_log_path'] );
            // Cannot be blank
            if( empty( $new_input['error_log_path'] ) )
               {
                add_settings_error(
                        $this->option_name.'[error_log_path]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for CUSTOM Error Log Path', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['error_log_path'] = $this->defaults['error_log_path'];
               }
            // Verify if dir exists and is writable
            if( isset( $input['error_type'] ) && $input['error_type'] == "CUSTOM" && ( !is_dir( $new_input['error_log_path'] ) or !is_writable( $new_input['error_log_path'] ) ) )
               {
                add_settings_error(
                        $this->option_name.'[error_log_path]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'CUSTOM Error Log Path not exist or is not writable', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['error_log_path'] = $this->defaults['error_log_path'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['error_log_path'] = $this->defaults['error_log_path'];
           }

        if( isset( $input['error_log_name'] ) )
           {
            $new_input['error_log_name'] = sanitize_text_field( $input['error_log_name'] );
            // Cannot be blank
            if( empty( $new_input['error_log_name'] ) )
               {
                add_settings_error(
                        $this->option_name.'[error_log_name]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for CUSTOM Error Log Name', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['error_log_name'] = $this->defaults['error_log_name'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['error_log_name'] = $this->defaults['error_log_name'];
           }

        if( isset( $input['error_log_timezone'] ) )
           {
            $new_input['error_log_timezone'] = sanitize_text_field( $input['error_log_timezone'] );
            // Cannot be blank
            if( empty( $new_input['error_log_timezone'] ) )
               {
                add_settings_error(
                        $this->option_name.'[error_log_timezone]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for TIMEZONE', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['error_log_timezone'] = $this->defaults['error_log_timezone'];
               }
            // Verify the value
            if( function_exists( "timezone_identifiers_list" ) )
               {
                $zones = timezone_identifiers_list() ;
                if( empty( $zones ) or !in_array( $new_input['error_log_timezone'], $zones ) )
                   {
                    add_settings_error(
                            $this->option_name.'[error_log_timezone]',                     // Setting title
                            esc_attr( 'settings_updated' ),           // Error ID
                            __( 'Please select a valid VALUE for TIMEZONE', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                            'error'                           // Type of message
                    );
                    $new_input['error_log_timezone'] = $this->defaults['error_log_timezone'];
                   }
               }
           }
        else
           {
            // Set it to the default value
            $new_input['error_log_timezone'] = $this->defaults['error_log_timezone'];
           }

        if( isset( $input['security_log_each_pingback'] ) )
           {
            $new_input['security_log_each_pingback'] = sanitize_text_field( $input['security_log_each_pingback'] );

            // Admitted value: 0/1
            if( !preg_match( '/^[0-1]{1,1}$/', $new_input['security_log_each_pingback'] ) )
               {
                add_settings_error(
                        $this->option_name.'[security_log_each_pingback]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for Pingback Request Log', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['security_log_each_pingback'] = $this->defaults['security_log_each_pingback'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['security_log_each_pingback'] = $this->defaults['security_log_each_pingback'];
           }

        if( isset( $input['security_stop_user_enumeration'] ) )
           {
            $new_input['security_stop_user_enumeration'] = sanitize_text_field( $input['security_stop_user_enumeration'] );

            // Admitted value: 0/1
            if( !preg_match( '/^[0-1]{1,1}$/', $new_input['security_stop_user_enumeration'] ) )
               {
                add_settings_error(
                        $this->option_name.'[security_stop_user_enumeration]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for Stop User Enumeration', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['security_stop_user_enumeration'] = $this->defaults['security_stop_user_enumeration'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['security_stop_user_enumeration'] = $this->defaults['security_stop_user_enumeration'];
           }

        if( isset( $input['security_remove_generator'] ) )
           {
            $new_input['security_remove_generator'] = sanitize_text_field( $input['security_remove_generator'] );

            // Admitted value: 0/1
            if( !preg_match( '/^[0-1]{1,1}$/', $new_input['security_remove_generator'] ) )
               {
                add_settings_error(
                        $this->option_name.'[security_remove_generator]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for Remove WP version and generator tag', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['security_remove_generator'] = $this->defaults['security_remove_generator'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['security_remove_generator'] = $this->defaults['security_remove_generator'];
           }

        if( isset( $input['security_kill_xmlrpc_on_login_error'] ) )
           {
            $new_input['security_kill_xmlrpc_on_login_error'] = sanitize_text_field( $input['security_kill_xmlrpc_on_login_error'] );

            // Admitted value: 0/1
            if( !preg_match( '/^[0-1]{1,1}$/', $new_input['security_kill_xmlrpc_on_login_error'] ) )
               {
                add_settings_error(
                        $this->option_name.'[security_kill_xmlrpc_on_login_error]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for Kill multiple xmlrpc request on xmlrpc login error', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['security_kill_xmlrpc_on_login_error'] = $this->defaults['security_kill_xmlrpc_on_login_error'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['security_kill_xmlrpc_on_login_error'] = $this->defaults['security_kill_xmlrpc_on_login_error'];
           }

        if( isset( $input['security_disable_xmlrpc_authenticated_methods'] ) )
           {
            $new_input['security_disable_xmlrpc_authenticated_methods'] = sanitize_text_field( $input['security_disable_xmlrpc_authenticated_methods'] );

            // Admitted value: 0/1
            if( !preg_match( '/^[0-1]{1,1}$/', $new_input['security_disable_xmlrpc_authenticated_methods'] ) )
               {
                add_settings_error(
                        $this->option_name.'[security_disable_xmlrpc_authenticated_methods]',                     // Setting title
                        esc_attr( 'settings_updated' ),           // Error ID
                        __( 'Please enter a valid VALUE for Disable xmlrpc authenticated methods', 'authentication-and-xmlrpc-log-writer' ),     // Error message
                        'error'                           // Type of message
                );
                $new_input['security_disable_xmlrpc_authenticated_methods'] = $this->defaults['security_disable_xmlrpc_authenticated_methods'];
               }
           }
        else
           {
            // Set it to the default value
            $new_input['security_disable_xmlrpc_authenticated_methods'] = $this->defaults['security_disable_xmlrpc_authenticated_methods'];
           }

        return $new_input;
       }

    /**
     * Print the Section text
     */
    public function print_section_info()
       {
        print __( 'Enter your settings below:', 'authentication-and-xmlrpc-log-writer' );
       }

    public function print_security_section_info()
       {
        print __( 'Enter your additional security settings below:', 'authentication-and-xmlrpc-log-writer' );
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function error_type_callback()
       {
        $selected = isset( $this->options['error_type'] ) ? $this->options['error_type'] : $this->defaults['error_type'] ;
        $select_options = array( 'SYSTEM' => array( __( 'SYSTEM', 'authentication-and-xmlrpc-log-writer' ), __( 'log will be written by SYSLOG', 'authentication-and-xmlrpc-log-writer' ) ),
                                 'APACHE' => array( __( 'APACHE', 'authentication-and-xmlrpc-log-writer' ), __( 'log will be written by APACHE error log', 'authentication-and-xmlrpc-log-writer' ) ),
                                 'CUSTOM' => array( __( 'CUSTOM', 'authentication-and-xmlrpc-log-writer' ), __( 'log will be written into setted CUSTOM Error Log Path', 'authentication-and-xmlrpc-log-writer' ) ) ) ;
        print '<select id="error_type" name="'.$this->option_name.'[error_type]">' ;
        foreach( $select_options as $Value => $Data )
           {
            print '<option value="'.$Value.'" '.( $selected == $Value ? 'selected="selected"' : '' ).' >'.$Data[0].'</option>' ;
           }
        print '</select>' ;

        // Desciption text
        print ' <span>'.__( 'use this to set where the log will be written', 'authentication-and-xmlrpc-log-writer' ).'
        <ul>' ;
        foreach( $select_options as $Value => $Data )
           {
            print '<li>'.$Data[0].' - '.$Data[1].'</li>' ;
           }
        print '
         </ul>
        </span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function error_log_path_callback()
       {
        printf(
            '<input type="text" id="error_log_path" name="'.$this->option_name.'[error_log_path]" value="%s" />',
            isset( $this->options['error_log_path'] ) ? esc_attr( $this->options['error_log_path']) : esc_attr( $this->defaults['error_log_path'])
        );
        // Desciption text
        print ' <span>'.__( 'this path will be used in CUSTOM mode. This path must exist and must be writable. E.g.', 'authentication-and-xmlrpc-log-writer' ).' <b>'.__( '/your/custom/path/', 'authentication-and-xmlrpc-log-writer' ).'</b></span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function error_log_name_callback()
       {
        printf(
            '<input type="text" id="error_log_name" name="'.$this->option_name.'[error_log_name]" value="%s" />',
            isset( $this->options['error_log_name'] ) ? esc_attr( $this->options['error_log_name']) :  esc_attr( $this->defaults['error_log_name'])
        );
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function error_log_timezone_callback()
       {
        if( !function_exists( "timezone_identifiers_list" ) )
           {
            printf(
                '<input type="text" id="error_log_timezone" name="'.$this->option_name.'[error_log_timezone]" value="%s" />',
                isset( $this->options['error_log_timezone'] ) ? esc_attr( $this->options['error_log_timezone']) :  esc_attr( $this->defaults['error_log_timezone'])
            );
           }
        else
           {
            $zones = timezone_identifiers_list() ;
            $selected = isset( $this->options['error_log_timezone'] ) ? $this->options['error_log_timezone'] : $this->defaults['error_log_timezone'] ;

            print '<select id="error_log_timezone" name="'.$this->option_name.'[error_log_timezone]">' ;
            foreach( $zones as $zone )
               {
                print '<option value="'.$zone.'" '.( $selected == $zone ? 'selected="selected"' : '' ).' >'.$zone.'</option>' ;
               }
            print '</select>' ;
           }
        // Desciption text
        print ' <span>'.__( 'Used to prevent WP timezone bug. Use your current server timezone.', 'authentication-and-xmlrpc-log-writer' ).'</span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function security_log_each_pingback_callback()
       {
        $value = ( isset( $this->options['security_log_each_pingback'] ) ? esc_attr( $this->options['security_log_each_pingback']) :  esc_attr( $this->defaults['security_log_each_pingback']) ) ;
        print '<input type="checkbox" id="security_log_each_pingback" name="'.$this->option_name.'[security_log_each_pingback]"  value="1" '.checked( '1', $value, false ).' />' ;
        // Desciption text
        print ' <span>'.__( 'Check if you want to log each pingback request.', 'authentication-and-xmlrpc-log-writer' ).'</span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function security_stop_user_enumeration_callback()
       {
        $value = ( isset( $this->options['security_stop_user_enumeration'] ) ? esc_attr( $this->options['security_stop_user_enumeration']) :  esc_attr( $this->defaults['security_stop_user_enumeration']) ) ;
        print '<input type="checkbox" id="security_stop_user_enumeration" name="'.$this->option_name.'[security_stop_user_enumeration]"  value="1" '.checked( '1', $value, false ).' />' ;
        // Desciption text
        print ' <span>'.__( 'Check if you want to stop and log user enumeration. Hackers use User Enumeration method to get your username.', 'authentication-and-xmlrpc-log-writer' ).'</span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function security_remove_generator_callback()
       {
        $value = ( isset( $this->options['security_remove_generator'] ) ? esc_attr( $this->options['security_remove_generator']) :  esc_attr( $this->defaults['security_remove_generator']) ) ;
        print '<input type="checkbox" id="security_remove_generator" name="'.$this->option_name.'[security_remove_generator]"  value="1" '.checked( '1', $value, false ).' />' ;
        // Desciption text
        print ' <span>'.__( 'Check if you want to remove from your site the WordPress version number and meta "generator".', 'authentication-and-xmlrpc-log-writer' ).'</span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function security_kill_xmlrpc_on_login_error_callback()
       {
        $value = ( isset( $this->options['security_kill_xmlrpc_on_login_error'] ) ? esc_attr( $this->options['security_kill_xmlrpc_on_login_error']) :  esc_attr( $this->defaults['security_kill_xmlrpc_on_login_error']) ) ;
        print '<input type="checkbox" id="security_kill_xmlrpc_on_login_error" name="'.$this->option_name.'[security_kill_xmlrpc_on_login_error]"  value="1" '.checked( '1', $value, false ).' />' ;
        // Desciption text
        print ' <span>'.__( 'Check if you want to kill multiple requests in a single xmlrpc call returning 401 code on xmlrpc login error ( stop brute force by xmlrpc ).', 'authentication-and-xmlrpc-log-writer' ).'</span>' ;
       }

    /**
     * Get the settings option array and print one of its values
     */
    public function security_disable_xmlrpc_authenticated_methods_callback()
       {
        $value = ( isset( $this->options['security_disable_xmlrpc_authenticated_methods'] ) ? esc_attr( $this->options['security_disable_xmlrpc_authenticated_methods']) :  esc_attr( $this->defaults['security_disable_xmlrpc_authenticated_methods']) ) ;
        print '<input type="checkbox" id="security_disable_xmlrpc_authenticated_methods" name="'.$this->option_name.'[security_disable_xmlrpc_authenticated_methods]"  value="1" '.checked( '1', $value, false ).' />' ;
        // Desciption text
        print ' <span>'.__( 'Check if you want to disable all xmplrpc methods that requires authentication ( to avoid brute force by xmlrpc ).', 'authentication-and-xmlrpc-log-writer' ).'</span>' ;
       }

    public function axlw_tail($filename, $lines = 10, $buffer = 4096)
       {
        // Open the file
        $f = fopen($filename, "rb");
        // Jump to last character
        fseek($f, -1, SEEK_END);
        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if(fread($f, 1) != "\n") $lines -= 1;
        // Start reading
        $output = '';
        $chunk = '';
        // While we would like more
        while(ftell($f) > 0 && $lines >= 0)
        {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);
            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);
            // Read a chunk and prepend it to our output
            $chunk = fread($f, $seek) ;
            $output = $chunk.$output;

            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");
        }
        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while($lines++ < 0)
        {
            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);
        }
        // Close file and return
        fclose($f);
        return $output;
       }

    public function axlw_logwiewer_admin_styles()
       {
        /*
         * It will be called only on your plugin admin page, enqueue our stylesheet here
         */
        wp_enqueue_style( 'axlw_logviewer_style' );
       }
}

if( is_admin() ) $axlw_settings_page = new axLogWriterSettingsPage();