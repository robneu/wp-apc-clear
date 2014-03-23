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
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return WP_APC_Clear
	 */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Load the plugin.
	 */
	public function load() {
		self::textdomain();
		self::define_constants();
		self::hooks();
	}

	/**
	 * load textdomain
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpapcc', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
	}

	/**
	 * Define useful constants.
	 */
	public function define_constants() {
		// Plugin version.
		if ( ! defined( 'WPAPCC_VERSION' ) ) {
			define( 'WPAPCC_VERSION', '0.2.1' );
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
	 * Hook into WordPress to run actions and filters.
	 */
	function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ), 10 );
		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		add_action( 'admin_notices', array( $this,'admin_notices' ) );
		add_action( 'admin_bar_menu', array( $this, 'toolbar_link' ), 999 );
	}

	/**
	 * Clear the APC cache.
	 *
	 * @param $cache_type the type of cache to clear.
	 */
	function apc_clear( $cache_type = '' ) {

		// Use the built-in APC cache clear function.
		$cache_clear = apc_clear_cache( $cache_type );

		// Use the WP APC Back end flush if it exists and we're not clearing the system cache.
		if ( function_exists( 'wp_cache_flush' ) && ! empty( $cache_type ) ) {
			$cache_clear = wp_cache_flush();
		}

		// Clear the cache.
		return $cache_clear;
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
	 * @return null if not on the plugin page.
	 */
	public function scripts_styles() {
		if ( ! $this->is_admin_page() ) {
			return;
		}
		wp_enqueue_style(  'wp-apc-clear',  WPAPCC_URL . 'assets/css/admin-style.css', array(), WPAPCC_VERSION );
		wp_enqueue_script( 'highlight',     WPAPCC_URL . 'assets/js/highlight.pack.js', array(), WPAPCC_VERSION );
		wp_enqueue_script( 'wp-apc-admin',  WPAPCC_URL . 'assets/js/admin.js', array( 'jquery', 'highlight' ), WPAPCC_VERSION );
	}

	/**
	 * Add Clear APC menu under Tools menu.
	 *
	 * @global $wpapcc_admin_page
	 */
	function create_admin_page() {
		add_management_page( __( 'WP Clear APC', 'wpapcc'), __( 'Clear APC', 'wpapcc'), 'manage_options', 'wp-clear-apc', array( $this, 'apc_clear_page' ) );
	}

	/**
	 * Display the WP APC Clear admin page.
	 *
	 * @return null if user can't manage options.
	 */
	function apc_clear_page() {
		// Do nothing if the user can't manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Set a url for the APC Manual page.
		$url = 'http://www.php.net/manual/en/ref.apc.php';
		// Set the link text to eplxain the plugin.
		$apc_link = sprintf( __( 'This tool is meant to clear cached data stored in APC. For more information about how APC works, visit the <a target="_blank" href="%s">APC manual page</a>.', 'wpapcc' ), esc_url( $url ) );
		?>
		<div class="wrap wp-clear-apc-wrap">
			<div class="icon32" id="icon-tools"><br></div>
			<h2><?php _e( 'WP Clear APC', 'wpapcc' ) ?></h2>
			<p><?php echo $apc_link; ?></p>
			<form action="<?php echo esc_url( admin_url( 'tools.php?page=wp-clear-apc' ) ); ?>" method="post" dir="ltr">
				<p>
					<input type="submit" value="<?php _e( 'Clear User Cache', 'wpapcc' ) ?>" class="button button-primary clear-user" name="clear-apc">
					<input type="submit" value="<?php _e( 'Clear System Cache', 'wpapcc' ) ?>" class="button button-secondary clear-system" name="clear-apc">
					<?php echo $this->get_debug_toggle(); ?>
				</p>
			</form>
			<?php
			// Clear the APC cache.
			$this->clear_the_cache();
			// Process the debug toggle button.
			$this->process_debug_toggle();
			// Show debugging information if it's been enabled
			$this->maybe_show_debugging();
			?>
		</div>

	<?php }

	/**
	 * Clear the cache when the user submits a form.
	 *
	 * @return null if the user hasn't submitted the cache clearing form.
	 */
	function clear_the_cache() {
		// Do nothing if the cache hasn't been cleared.
		if ( ! isset( $_POST['clear-apc'] ) ) {
			return;
		}
		// Clear the user cache and update the cache status accordingly.
		if ( $_POST['clear-apc'] === __( 'Clear User Cache', 'wpapcc' ) ) {
			update_option( 'wpapcc_cache_status', 'user-cleared' );
			$this->apc_clear( 'user' );
		}
		// Clear the system cache and update the cache status accordingly.
		elseif ( $_POST['clear-apc'] === __( 'Clear System Cache', 'wpapcc' ) ) {
			update_option( 'wpapcc_cache_status', 'system-cleared' );
			$this->apc_clear();
		}
	}

	/**
	 * Add notices to the admin page to show if the cache has been cleared.
	 *
	 * @return null if the user hasn't submitted the cache clearing form.
	 */
	function admin_notices() {
		// Do nothing if we're not on the plugin admin page.
		if ( ! isset( $_POST['clear-apc'] ) ) {
			return;
		}

		// Get the current cache status.
		$cache_status = get_option( 'wpapcc_cache_status' );

		// Display an error if the cache is stale.
		$notice = '<div id="message" class="error">';
			$notice .= '<p>' . __( 'Clearing APC has failed!', 'wpapcc' ) . '</p>';
		$notice .= '</div>';

		// If the cache status hasn't been updated, end here.
		if ( 'stale' === $cache_status ) {
			echo $notice;
			return;
		}

		// Display a success notice if the user cache has been cleared.
		if ( $_POST['clear-apc'] === __( 'Clear User Cache', 'wpapcc' ) ) {
			$notice = '<div id="message" class="updated">';
				$notice .= '<p>' . __( 'APC user cache has been cleared!', 'wpapcc' ) . '</p>';
			$notice .= '</div>';
		}

		// Display a success notice if the system cache has been cleared.
		if ( $_POST['clear-apc'] === __( 'Clear System Cache', 'wpapcc' ) ) {
			$notice = '<div id="message" class="updated">';
				$notice .= '<p>' . __( 'APC system cache has been cleared!', 'wpapcc' ) . '</p>';
			$notice .= '</div>';
		}

		// Set the cache back to stale.
		update_option( 'wpapcc_cache_status', 'stale' );

		echo $notice;
	}

	/**
	 * Display a debug toggle button.
	 *
	 * @return string a button to enable or disable debugging.
	 */
	function get_debug_toggle() {
		// Assign the toggle button statuses to some variables for reuse.
		$show_info = '<input type="submit" value="' . __( 'Show Debugging Info', 'wpapcc' ) . '" class="button button-secondary show-debug" name="debug-apc">';
		$hide_info = '<input type="submit" value="' . __( 'Hide Debugging Info', 'wpapcc' ) . '" class="button button-secondary show-debug" name="debug-apc">';

		// Display the show info button when debugging is disabled.
		$button = $show_info;

		// Display the hide info button when debugging is enabled.
		if ( get_option( 'wpapcc_debug_status' ) === 'enabled' ) {
			$button = $hide_info;
		}

		// If the debugging toggle hasn't been submitted, end here.
		if ( ! isset( $_POST['debug-apc'] ) ) {
			return $button;
		}

		// Display the show info button when debugging is disabled.
		$button = $show_info;

		// Display the hide info button when debugging is enabled.
		if ( $_POST['debug-apc'] === __( 'Show Debugging Info', 'wpapcc' ) ) {
			$button = $hide_info;
		}

		return $button;
	}

	/**
	 * Process the debug toggle button when it's clicked.
	 *
	 * @return null if the toggle button hasn't been used.
	 */
	function process_debug_toggle() {
		// Do nothing if the debug status hasn't been changed.
		if ( ! isset( $_POST['debug-apc'] ) ) {
			return;
		}

		if ( $_POST['debug-apc'] === __( 'Show Debugging Info', 'wpapcc' ) ) {
			update_option( 'wpapcc_debug_status', 'enabled' );
		}
		else {
			update_option( 'wpapcc_debug_status', 'disabled' );
		}
	}

	/**
	 * Display the debugging info only when it's enabled.
	 *
	 * @return null if the user has disabled debugging.
	 */
	function maybe_show_debugging() {
		// Do nothing if we're not on the plugin admin page.
		if ( ! $this->is_admin_page() ) {
			return;
		}
		// Get the current debug status.
		$debug_status =  get_option( 'wpapcc_debug_status' );

		// Do nothing if debugging has been disabled.
		if ( 'disabled' === $debug_status ) {
			return;
		}

		// Show the APC debugging information.
		$this->debug_info();
	}

	/**
	 * HTML output of the APC cache for debugging purposes.
	 *
	 * @uses apc_cache_info().
	 */
	function debug_info() {
		?>
		<div id="wpapcc_content_top" class="postbox-container">
			<div class="meta-box-sortables">
				<h2 id="wpapcc-tabs" class="nav-tab-wrapper">
					<a href="#top#user-cache" id="user-cache-tab" class="nav-tab"><?php _e('APC User Cache', 'wpapcc' ); ?></a>
					<a href="#top#system-cache" id="system-cache-tab" class="nav-tab"><?php _e('APC System Cache', 'wpapcc' ); ?></a>
				</h2>
				<div class="tabwrapper>">
					<div class="wpapcc-tab" id="user-cache">
						<p><?php _e( 'Here\'s some information about the APC user cache for debugging:', 'wpapcc' ) ?></p>
						<pre class="wp-clear-apc">
							<code class="php">
							<?php print_r( apc_cache_info( 'user' ) ); ?>
							</code>
						</pre>
					</div>
					<div class="wpapcc-tab" id="system-cache">
						<p><?php _e( 'Here\'s some information about the APC system cache for debugging:', 'wpapcc' ) ?></p>
						<pre class="wp-clear-apc">
							<code class="php">
							<?php print_r( apc_cache_info() ); ?>
							</code>
						</pre>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add a link to clear the cache from the admin toolbar.
	 *
	 *  @uses  $wp_admin_bar
	 */
	function toolbar_link( $wp_admin_bar ) {

		if ( ! current_user_can('manage_options' ) ) {
			return;
		}

		$args = array(
			'id'    => 'clear-apc',
			'title' => 'Clear APC',
			'href'  => '/wp-admin/tools.php?page=wp-clear-apc',
			'meta'  => array(
				'class' => 'clear-apc',
				'title' => 'Clear APC'
			)
		);

		$wp_admin_bar->add_node( $args );
	}
}
