<?php
/**
 * This is the WP-GeoMeta-Lib.
 *
 * @package wp-geometa-lib
 *
 * This file loads or sets up loading for needed classes, handles upgrades and other stuff that the actual classes shouldn't need to do.
 */

/**
 * Load and initialize the main classes.
 * In the future we should make it so they can get loaded on demand.
 */
defined( 'ABSPATH' ) or die( 'No direct access' );

require_once( dirname( __FILE__ ) . '/wp-geoquery.php' );
require_once( dirname( __FILE__ ) . '/wp-geoutil.php' );
require_once( dirname( __FILE__ ) . '/wp-geometa.php' );
WP_GeoMeta::$version = key( WP_GeoMeta_Installs::$versions );

$wpgeo = WP_GeoMeta::get_instance();
$wpgq = WP_GeoQuery::get_instance();
$wpgu = WP_GeoUtil::get_instance();

/**
 * Handle database upgrades if our version is higher than the WP-GeoMeta-Lib version in the database.
 *
 * This should allow use of WP_GeoUtil without touching the database,
 * if someone installed WP Spatial Capabilites Check or something.
 */
$wp_geometa_db_version = get_option( 'wp_geometa_db_version', '0.0.0' );
if ( '0.0.0' !== $wp_geometa_db_version ) { // 0.0.0. means no version in the DB at all.
	$db_version_compare = version_compare( WP_GEOMETA_VERSION, $wp_geometa_db_version );
	if ( $db_version_compare > 0 ) {
		$wpgeo->upgrade();
	}
}
