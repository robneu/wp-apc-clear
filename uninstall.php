<?php
/**
 * Uninstall functions.
 *
 * @package      WP APC Clear
 * @author       Robert Neu <http://wpbacon.com/>
 * @copyright    Copyright (c) 2014, FAT Media, LLC
 * @license      GPL2+
 *
 */

// Exit if accessed directly
defined( 'WP_UNINSTALL_PLUGIN' ) or die;

// Delete all options used by the plugin.
delete_option( 'wpapcc_cache_status' );
delete_option( 'wpapcc_debug_status' );
