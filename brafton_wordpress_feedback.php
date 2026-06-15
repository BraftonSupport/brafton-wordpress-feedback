<?php
/*
Plugin Name:  Brafton Wordpress Feedback
Plugin URI:   https://github.com/BraftonSupport/brafton-wordpress-feedback/
Description:  Provide feedback for Brafton developed Wordpress Sites
Version:	  2.0.0
Author: Brafton
Author URI: http://www.brafton.com
License:	  GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: brafton-wordpress-feedback
*/

namespace Brafton\Feedback;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct file access.
}

/**
 * Version of the remote feedback widget assets. Used as the enqueue version
 * string so cache busting happens when this is bumped on a release.
 */
const VERSION = '2.0.0';

class SettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __( 'Settings Admin', 'brafton-wordpress-feedback' ),
            __( 'Brafton Feedback', 'brafton-wordpress-feedback' ),
            'manage_options',
            'brafton-feedback-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'brafton_feedback' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Brafton Feedback', 'brafton-wordpress-feedback' ); ?></h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'brafton_feedback_option_group' );
                do_settings_sections( 'brafton-feedback-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'brafton_feedback_option_group', // Option group
            'brafton_feedback', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            __( 'Feedback Settings', 'brafton-wordpress-feedback' ), // Title
            array( $this, 'print_section_info' ), // Callback
            'brafton-feedback-admin' // Page
        );

        add_settings_field(
            'project_id', // ID
            __( 'Project ID', 'brafton-wordpress-feedback' ), // Title
            array( $this, 'project_id_callback' ), // Callback
            'brafton-feedback-admin', // Page
            'setting_section_id' // Section
        );
        add_settings_field(
            'org_id', // ID
            __( 'Org ID', 'brafton-wordpress-feedback' ), // Title
            array( $this, 'org_id_callback' ), // Callback
            'brafton-feedback-admin', // Page
            'setting_section_id' // Section
        );
        add_settings_field(
            'anonymous_user',
            __( 'Enable For all users', 'brafton-wordpress-feedback' ),
            array( $this, 'anonymous_user_callback' ),
            'brafton-feedback-admin',
            'setting_section_id'
        );
        add_settings_field(
            'env',
            __( 'Put in Dev Mode. Use this for testing new feedback features', 'brafton-wordpress-feedback' ),
            array( $this, 'environment_callback' ),
            'brafton-feedback-admin',
            'setting_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['project_id'] ) )
            $new_input['project_id'] = sanitize_text_field( $input['project_id'] );

        if( isset( $input['org_id'] ) )
            $new_input['org_id'] = sanitize_text_field( $input['org_id'] );
        if( isset( $input['env'] ) )
            $new_input['env'] = absint( $input['env'] );
        if( isset( $input['anonymous_user'] ) )
            $new_input['anonymous_user'] = absint( $input['anonymous_user'] );
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        esc_html_e( 'Provide these settings below. Both the Org ID and the Project ID come from Atlas.', 'brafton-wordpress-feedback' );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function project_id_callback()
    {
        printf(
            '<input type="text" id="project_id" name="brafton_feedback[project_id]" value="%s" />',
            isset( $this->options['project_id'] ) ? esc_attr( $this->options['project_id']) : ''
        );
    }
    public function org_id_callback()
    {
        printf(
            '<input type="text" id="org_id" name="brafton_feedback[org_id]" value="%s" />',
            isset( $this->options['org_id'] ) ? esc_attr( $this->options['org_id']) : ''
        );
    }
    public function environment_callback()
    {
        printf(
            '<input type="checkbox" name="brafton_feedback[env]" value="1" %s/>',
            ! empty( $this->options['env'] ) ? 'checked' : ''
        );
    }
    public function anonymous_user_callback()
    {
        printf(
            '<input type="checkbox" name="brafton_feedback[anonymous_user]" value="1" %s/>',
            ! empty( $this->options['anonymous_user'] ) ? 'checked' : ''
        );
    }
}

if ( is_admin() ) {
    $brafton_feedback_settings_page = new SettingsPage();
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets', 1000 );
function enqueue_assets() {
    $options = get_option( 'brafton_feedback' );
    if( ! isset( $options['project_id'] ) ){
        return;
    }
    if( is_user_logged_in() || ( isset( $options['anonymous_user'] ) && $options['anonymous_user'] ) ){
        if( $options['project_id'] ){
            $env = ( isset( $options['env'] ) && $options['env'] ) ? 'dev' : 'live';
            $base_url = "https://resources.{$env}.tech.brafton.com/feedback-widget/";
            wp_enqueue_style( 'brafton-feedback-style', $base_url.'styles.css', array(), VERSION );
            wp_enqueue_script( 'brafton-feedback-main', $base_url.'main.js', array(), VERSION, true );
        }
    }
}

add_action( 'wp_footer', __NAMESPACE__ . '\\render_widget' );
function render_widget(){
    $options = get_option( 'brafton_feedback' );
    if( ! isset( $options['project_id'] ) ){
        return;
    }
    if( is_user_logged_in() || ( isset( $options['anonymous_user'] ) && $options['anonymous_user'] ) ){
        if( $options['project_id'] ){
            printf(
                '<brafton-feedback project="%s" org="%s"></brafton-feedback>',
                esc_attr( $options['project_id'] ),
                esc_attr( isset( $options['org_id'] ) ? $options['org_id'] : '' )
            );
        }
    }
}
