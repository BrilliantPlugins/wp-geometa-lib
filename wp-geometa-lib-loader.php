<?php
/**
 * This is the loader file for WP-GeoMeta-lib.
 *
 * @package wp-geometa-lib
 *
 * Version: 0.3.2
 *
 * To include spatial metadata support in your plugin, simply include this file.
 *
 * WP-GeoMeta-lib handles having multiple versions of itself installed corretly, always loading the latest version.
 *
 * It also handles setting up the spatial meta tables, so this file should be included directly, and not inside a hook or action.
 */

defined( 'ABSPATH' ) or die( 'No direct access' );

$wp_geometa_version = '0.3.2'; 

if ( ! class_exists( 'WP_GeoMeta_Installs' ) ) {
	/**
	 * This class is deliberately simple, because if it ever changes
	 * the changes need to be backwards compatible.
	 *
	 * We're using a singleton instead of a global array to capture
	 * each WP-GeoMeta's version and location.
	 */
	class WP_GeoMeta_Installs {
		/**
		 * List of installs
		 *
		 * @var $installs
		 */
		public static $installs = array();

		/**
		 * List of versions
		 *
		 * @var $versions
		 */
		public static $versions = array();

		/**
		 * Add an install listing
		 *
		 * @param string $file __FILE__ of wp-geometa.php.
		 * @param string $version the version of wp-geometa.php.
		 */
		public static function add( $file, $version ) {
			WP_GeoMeta_Installs::$installs[ $file ] = $version;
			WP_GeoMeta_Installs::$versions[ $version ] = $file;
		}

		/**
		 * Get the list of installs with versions.
		 */
		public static function get_list() {
			return WP_GeoMeta_Installs::$installs;
		}

		/**
		 * A loader function for apl_autoload_register.
		 *
		 * This gets called by PHP if LeafletPHP is not a class.
		 *
		 * This is preferable to always loading the class, since
		 * this will avoid loading it if it's not needed.
		 *
		 * @param string $class_name The class name that PHP is looking for.
		 */
		public static function load( $class_name ) {
			if ( in_array( $class_name, array( 'WP_GeoMeta', 'WP_GeoQuery', 'WP_GeoUtil' ), true ) ) {
				WP_GeoMeta_Installs::load_now();
			}
		}

		/**
		 * This method will go away eventually.
		 */
		public static function load_now() {
			// Sort keys by version_compare.
			uksort( WP_GeoMeta_Installs::$versions, 'version_compare' );

			// Go to the end of the array and require the file.
			// Then get the directory.
			$this_dir = dirname( end( WP_GeoMeta_Installs::$versions ) );

			// Require the wp-geometa-lib file which will handle DB upgrades, initializing stuff and setting up needed hooks.
			require_once( $this_dir . '/wp-geometa-lib.php' );
		}
	}

	// Let PHP auto loading only include the file if needed.
	spl_autoload_register( array( 'WP_GeoMeta_Installs', 'load' ) );

	add_action( 'plugins_loaded', array( 'WP_GeoMeta_Installs', 'load_now' ) );
}

// Add ourself to the list of installs.
WP_GeoMeta_Installs::add( __FILE__, $wp_geometa_version );

/**
 * Legacy handling
 *
 * If we detect an older version of WP_GeoMeta, do the old stuff to make sure we get loaded.
 */
$wp_geometa_max_version = get_option( 'wp_geometa_version', '0.0.0' );

/**
 * -1 means that our version is lower.
 * 0 means they are equal.
 * 1 means our version is higher.
 */
if ( '0.0.0' !== $wp_geometa_max_version && 1 === version_compare( $wp_geometa_version, $wp_geometa_max_version ) ) {
	require_once( dirname( __FILE__ ) . '/wp-geometa-lib.php' );

	if ( ! function_exists( 'wp_geometa_load_older_version' ) ) {
		/**
		 * Do nothing, just stop older versions from defining this function which would cause them to load.
		 */
		function wp_geometa_load_older_version() {
		}
	}

	if ( ! function_exists( 'wpgeometa_setup_latlng_fields' ) ) {
		/**
		 * Do nothing, just stop older versions from defining this function.
		 */
		function wpgeometa_setup_latlng_fields() {
		}
	}
}
