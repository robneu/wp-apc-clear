<?php
/**
 * Install functions.
 *
 * @package      WP APC Clear
 * @author       Robert Neu <http://wpbacon.com/>
 * @copyright    Copyright (c) 2014, FAT Media, LLC
 * @license      GPL2+
 *
 */

// Exit if accessed directly
defined( 'WPINC' ) or die;

// Grab the plugin file.
$_wpapcc_plugin_file = $_wpapcc_dir . 'wp-apc-clear.php';

/**
 * Check to see if APC is installed or enabled.
 *
 * @since 0.2.1
 * @return bool
 */
function wpapcc_is_apc_installed() {
	if ( extension_loaded( 'apc' ) || ini_get( 'apc.enabled' ) ) {
		return true;
	}
	return fasle;
}

/**
 * Install
 *
 * Runs on plugin install and checks to make sure Genesis is activated.
 *
 * @since 0.2.1
 * @return void
 */
function wpapcc_install( $_wpapcc_plugin_file ) {

	$is_apc_installed = wpapcc_is_apc_installed();

	// Deactivate the plugin if APC isn't instlaled.
	if ( ! $is_apc_installed ) {
		// Deactivate the plugin.
		deactivate_plugins( $_wpapcc_plugin_file );
		// Display a reason why the plugin has been deactivated.
		wp_die( 'Sorry, this plugin requires APC. Please install APC and then activate the plugin again.' );
	}

	// Create the cache status option if it doesn't already exist.
	if ( ! get_option( 'wpapcc_cache_status' ) ) {
		add_option( 'wpapcc_cache_status', 'stale', '', 'no' );
	}

	// Create the debug status option if it doesn't already exist.
	if ( ! get_option( 'wpapcc_debug_status' ) ) {
		add_option( 'wpapcc_debug_status', 'disabled', '', 'no' );
	}
}
register_activation_hook( $_wpapcc_plugin_file, 'wpapcc_install' );

// Clean up
unset( $_wpapcc_plugin_file );
