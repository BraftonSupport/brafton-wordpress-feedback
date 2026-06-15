<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes the plugin's stored settings so nothing is left behind in the
 * options table after deletion.
 */

// Exit if uninstall is not invoked by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'brafton_feedback' );
