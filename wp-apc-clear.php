<?php
/*
Plugin Name: WP APC Clear
Plugin URI: https://github.com/fatmedia/wp-apc-clear
Description: This is a simple, single purpose plugin to clear the APC cache.
Author: Robert Neu, TJ Stein
Version: 0.2.0
Author URI: http://wpbacon.com
License: GPL2+
*/

/**
 * @package WP APC Clear
 * @version 0.1.1
 * @author  TJ Stein <http://tjstein.com/articles/apc-cache-clear-plugin-for-wordpress/>
 * @author  Kaspars Dambis <http://kaspars.net/blog/wordpress/clear-apc-cache-button-for-wordpress>
 * @author  Robert Neu <http://wpbacon.com>
 */

// Exit if accessed directly
defined( 'WPINC' ) or die;

// Grab this directory
$_wpapcf_dir = dirname( __FILE__ ) . '/';

// Include our core plugin files.
include( $_wpapcf_dir . 'includes/plugin.php' );

// Clean up
unset( $_wpapcf_dir );

// Instantiate our class
$_wp_apc_clear = WP_APC_Clear::getInstance();
