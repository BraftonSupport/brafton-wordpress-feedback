<?php
/*
Plugin Name:  Brafton Wordpress Feedback
Plugin URI:   https://github.com/BraftonSupport/brafton-wordpress-feedback/
Description:  Provide feedback for Brafton developed Wordpress Sites
Version:	  1.0.0
Author: Brafton
Author URI: http://www.brafton.com
License:	  GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: braftonium
*/
class MySettingsPage
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
            'Settings Admin', 
            'Brafton Feedback', 
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
            <h1>Brafton Feedback</h1>
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
            'Feedback Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'brafton-feedback-admin' // Page
        );  

        add_settings_field(
            'project_id', // ID
            'Project ID', // Title 
            array( $this, 'project_id_callback' ), // Callback
            'brafton-feedback-admin', // Page
            'setting_section_id' // Section           
        );      
        add_settings_field(
            'editround_id', // ID
            'Edit Round ID', // Title 
            array( $this, 'editround_id_callback' ), // Callback
            'brafton-feedback-admin', // Page
            'setting_section_id' // Section           
        );  
        // add_settings_field(
        //     'admin_only', 
        //     'Enable For Logged-In Users Only', 
        //     array( $this, 'admin_only_callback' ), 
        //     'brafton-feedback-admin', 
        //     'setting_section_id'
        // );    
        add_settings_field(
            'anonymous_user', 
            'Enable For all users', 
            array( $this, 'anonymous_user_callback' ), 
            'brafton-feedback-admin', 
            'setting_section_id'
        ); 
        add_settings_field(
            'env', 
            'Put in Dev Mode. Use this for testing new feedback features', 
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
        
        if( isset( $input['editround_id'] ) )
            $new_input['editround_id'] = sanitize_text_field( $input['editround_id'] );
        if( isset( $input['env'] ) )
            $new_input['env'] = absint( $input['env'] );
        if( isset( $input['admin_only'] ) )
            $new_input['admin_only'] = absint( $input['admin_only'] );
        if( isset( $input['anonymous_user'] ) )
            $new_input['anonymous_user'] = absint( $input['anonymous_user'] );
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Provide these settings below:';
       
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
    public function editround_id_callback()
    {
        printf(
            '<input type="text" id="editround_id" name="brafton_feedback[editround_id]" value="%s" />',
            isset( $this->options['editround_id'] ) ? esc_attr( $this->options['editround_id']) : ''
        );
    }
    public function environment_callback()
    {
        printf(
            '<input type="checkbox" name="brafton_feedback[env]" value="1" %s/>',
            isset( $this->options['env'] ) && $this->options['env'] === 1 ? 'checked' : ''
        );
    }
    /** 
     * Get the settings option array and print one of its values
     */
    // public function admin_only_callback()
    // {
    //     printf(
    //         '<input type="checkbox" name="brafton_feedback[admin_only]" value="1" %s/>',
    //         isset( $this->options['admin_only'] ) && $this->options['admin_only'] === 1 ? 'checked' : ''
    //     );
    // }
    public function anonymous_user_callback()
    {
        printf(
            '<input type="checkbox" name="brafton_feedback[anonymous_user]" value="1" %s/>',
            isset( $this->options['anonymous_user'] ) && $this->options['anonymous_user'] === 1 ? 'checked' : ''
        );
    }
}

if( is_admin() )
    $my_settings_page = new MySettingsPage();

add_action( 'wp_enqueue_scripts', 'feedback_enqueue_styles', 1000);
function feedback_enqueue_styles() {
    $options = get_option( 'brafton_feedback' );
    if(!isset($options['project_id'])){
        return;
    }
    if(is_user_logged_in() || (isset($options['anonymous_user']) && $options['anonymous_user']) ){
        if($options['project_id']){
            $env = $options['env'] ? 'dev' : 'live';
            $base_url = "https://resources.${env}.tech.brafton.com/feedback/0.x/";
            wp_enqueue_style( 'feedback-style', $base_url.'styles.css',99999);
            


            wp_enqueue_script( 'poly-main', $base_url.'polyfills.js',array(), false, true);
            wp_enqueue_script( 'poly-main-ie', $base_url.'polyfills-es5.js',array(), false, true);
            wp_enqueue_script( 'feedback-main', $base_url.'main.js',array(), false, true);
        }
    }
    
}
add_action('wp_footer', 'add_project');
function add_project(){
    $options = get_option( 'brafton_feedback' );
    if(!isset($options['project_id'])){
        return;
    }
    if(is_user_logged_in() || (isset($options['anonymous_user']) && $options['anonymous_user']) ){
        if($options['project_id']){
            printf('<brafton-feedback project="%s" editRound="%s"></brafton-feedback>', $options['project_id'], $options['editround_id']);
        }
    }

}