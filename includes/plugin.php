<?php
/**
 * Core plugin class.
 *
 * @package      WP APC Clear
 * @author       Robert Neu <http://wpbacon.com/>
 * @copyright    Copyright (c) 2014, FAT Media, LLC
 * @license      GPL2+
 *
 */

// Exit if accessed directly
defined( 'WPINC' ) or die;

// Start up the engine
class WP_APC_Clear {
	/**
	 * Static property to hold our singleton instance
	 * @var WP_APC_Clear
	 */
	static $instance = false;

	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return WP_APC_Clear
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ), 10 );
		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		add_action( 'admin_notices', array( $this,'admin_notices' ) );
		add_action( 'admin_bar_menu', array( $this, 'toolbar_link' ), 999 );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return WP_APC_Clear
	 */
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Load the plugin.
	 */
	public function load() {

		self::textdomain();
		self::define_constants();

	}

	/**
	 * load textdomain
	 *
	 * @return WP_APC_Clear
	 */
	public function textdomain() {

		load_plugin_textdomain( 'wpapcc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Define useful constants.
	 */
	public function define_constants() {
		// Plugin version.
		if ( ! defined( 'WPAPCC_VERSION' ) ) {
			define( 'WPAPCC_VERSION', '0.2.0' );
		}

		// Plugin root file.
		if ( ! defined( 'WPAPCC_FILE' ) ) {
			define( 'WPAPCC_FILE', dirname( dirname( __FILE__ ) ) . '/wp-apc-clear.php' );
		}

		// Plugin directory URL.
		if ( ! defined( 'WPAPCC_URL' ) ) {
			define( 'WPAPCC_URL', plugin_dir_url( WPAPCC_FILE ) );
		}

		// Plugin directory path.
		if ( ! defined( 'WPAPCC_DIR' ) ) {
			define( 'WPAPCC_DIR', plugin_dir_path( WPAPCC_FILE ) );
		}

	}

	/**
	 * Clear the APC cache.
	 *
	 * @peram $cache_type the type of cache to clear.
	 */
	function apc_clear( $cache_type ) {
		// Set the default cache type to opcode.
		if ( ! $cache_type ) {
			$cache_type = 'opcode';
		}
		// Clear the cache.
		apc_clear_cache( $cache_type );
	}

	/**
	 * Helper function to determine if we're on the right page.
	 *
	 * @return bool
	 */
	function is_admin_page() {
		$current_screen = get_current_screen();

		// Return true if we're on the plugin admin page.
		if ( 'tools_page_wp-clear-apc' === $current_screen->base ) {
			return true;
		}
		return false;
	}

	/**
	 * Scripts and stylesheets
	 *
	 * @return System_Snapshot_Report
	 */

	public function scripts_styles() {
		if ( ! $this->is_admin_page() ) {
			return;
		}
		wp_enqueue_style( 'wp-apc-clear', WPAPCC_URL . 'assets/css/admin-style.css', array(), BACON_BAR_VERSION );
	}

	/**
	 * Add Clear APC menu under Tools menu.
	 *
	 * @global $wpapccf_admin_page
	 */
	function create_admin_page() {
		add_management_page( __( 'WP Clear APC', 'wpapcc'), __( 'Clear APC', 'wpapcc'), 'manage_options', 'wp-clear-apc', array( $this, 'apc_clear_page' ) );
	}

	/**
	 * Display APC clear report.
	 *
	 * @return null if user can't manage options.
	 */
	function apc_clear_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>

		<div class="wrap wp-clear-apc-wrap">
			<div class="icon32" id="icon-tools"><br></div>
			<h2><?php _e( 'WP Clear APC', 'wpapccf' ) ?></h2>
			<form action="<?php echo esc_url( admin_url( 'tools.php?page=wp-clear-apc' ) ); ?>" method="post" dir="ltr">
				<p>
					<input type="submit" value="<?php _e( 'Clear Opcode Cache', 'wpapcc' ) ?>" class="button button-primary clear-opcode" name="clear-apc">
					<input type="submit" value="<?php _e( 'Clear User Cache', 'wpapcc' ) ?>" class="button button-secondary clear-user" name="clear-apc">
				</p>
				<?php $this->clear_the_cache(); ?>
				<?php $this->debug_info(); ?>
			</form>
		</div>

	<?php }

	/**
	 * Clear the cache.
	 *
	 * @global $wpapccf_admin_page
	 * @return null if we're not on the plugin page.
	 */
	function clear_the_cache() {
		if ( !isset( $_POST['clear-apc'] ) )
			return;

		if ( ! get_option( 'wpapccf_cache_status' ) ) {
			add_option( 'wpapccf_cache_status', 'stale', '', 'no' );
		}

		if ( $_POST['clear-apc'] === __( 'Clear Opcode Cache', 'wpapcc' ) ) {
			$this->apc_clear();
			update_option( 'wpapccf_cache_status', 'opcode-cleared' );
		}
		elseif ( $_POST['clear-apc'] === __( 'Clear User Cache', 'wpapcc' ) ) {
			$this->apc_clear( 'user' );
			update_option( 'wpapccf_cache_status', 'user-cleared' );
		}
	}

	/**
	 * Add notices to the admin page to show if the cache has been cleard.
	 *
	 * @global $wpapccf_admin_page
	 * @return null if we're not on the plugin page.
	 */
	function admin_notices() {
		// Do nothing if we're not on the plugin admin page.
		if ( ! $this->is_admin_page() ||  ! isset( $_POST['clear-apc'] ) ) {
			return;
		}

		// Get the current cache status.
		$cache_status = get_option( 'wpapccf_cache_status' );

		// Display an error if the cache is stale.
		$notice = '<div id="message" class="error">';
			$notice .= '<p>' . __( 'Clearing APC has failed!', 'wpapccf' ) . '</p>';
		$notice .= '</div>';

		// Display a success notice if the cache has been cleared.
		if ( 'opcode-cleared' === $cache_status ) {
			$notice = '<div id="message" class="updated">';
				$notice .= '<p>' . __( 'APC opcode cache has been cleared!', 'wpapccf' ) . '</p>';
			$notice .= '</div>';
		}
		elseif ( 'user-cleared' === $cache_status ) {
			$notice = '<div id="message" class="updated">';
				$notice .= '<p>' . __( 'APC user cache has been cleared!', 'wpapccf' ) . '</p>';
			$notice .= '</div>';
		}

		// Set the cache back to stale.
		update_option( 'wpapccf_cache_status', 'stale' );

		echo $notice;
	}

	function debug_info() {
		if ( ! isset( $_POST['clear-apc'] ) ) {
			return;
		}
		echo '<p>' . __( 'Here\'s some information about APC for debugging:', 'wpapccf' ) .'</p>';
		echo '<textarea name="wp-clear-apc" readonly="readonly" class="wp-clear-apc">';
			print_r( apc_cache_info() );
		echo '</textarea>';
	}

	/**
	 * Add a link to clear the cache from the admin toolbar.
	 *
	 *  $wp_admin_bar
	 */
	function toolbar_link( $wp_admin_bar ) {

		if ( ! current_user_can('manage_options' ) ) {
			return;
		}

		$args = array(
			'id'    => 'clear-apc',
			'title' => 'Clear APC',
			'href'  => '/wp-admin/tools.php?page=clear_php_apc',
			'meta'  => array(
				'class' => 'clear-apc',
				'title' => 'Clear APC'
			)
		);

		$wp_admin_bar->add_node( $args );
	}
}
