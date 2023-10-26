<?php
/**
 * Load WP and define some functions we'll need.
 */

ini_set('display_errors',1);
error_reporting(E_ALL);
define('WP_INSTALLING', true);

define( 'WP_GEOMETA_TESTDIR', dirname( __FILE__ ) . '/..' );
define( 'WP_GEOMETA_TEST_WIDTH', 60 );
if ( !defined( 'WP_GEOMETA_DEBUG' ) ) {
	define( 'WP_GEOMETA_DEBUG', 1 );
}

// Find wp-load.php and load it.
$curdir = dirname( __FILE__ );
do {
	$prevdir = $curdir;
	$curdir = dirname( $prevdir );
} while ( $curdir !== $prevdir && !file_exists( $curdir . DIRECTORY_SEPARATOR . 'wp-load.php' ) );

$wp_load = $curdir . DIRECTORY_SEPARATOR . 'wp-load.php';

if ( ! file_exists( $wp_load ) ) {
	die( "Couldn't find wp-load. Tests are meant to be run when WP_GeoMeta is installed as a plugin.\n" ); 
}
require_once( $wp_load );
defined('ABSPATH') or die('No direct access');

// Load WP GeoMeta in case it's not active.
$wpgm = WP_GEOMETA_TESTDIR . '/../wp-geometa-lib-loader.php';
if ( !file_exists( $wpgm ) ) {
	die( "Couldn't find wp-geometa.php at: " . $wpgm . "\n" );
}
require_once( $wpgm );
do_action( 'plugins_loaded' );
require_once( WP_GEOMETA_TESTDIR . '/tests/__SqlFormatter.php');

WP_GeoMeta::get_instance();

print "Running tests for WP-GeoMeta-Lib " . WP_GEOMETA_VERSION . " in " . WP_GEOMETA_HOME . "\n\n";

// A post type for testing with.
$args = array(
	"label" => 'GeoTests',
	"labels" => array(
		"name" => 'GeoTests',
		"singular_name" => 'GeoTest',
	),
	"description" => "",
	"public" => true,
	"show_ui" => true,
	"show_in_rest" => false,
	"rest_base" => "",
	"has_archive" => false,
	"show_in_menu" => true,
	"exclude_from_search" => false,
	"capability_type" => "post",
	"map_meta_cap" => true,
	"hierarchical" => false,
	"rewrite" => array( "slug" => "geo_test", "with_front" => true ),
	"query_var" => true,

	"supports" => array( "title", "editor", "thumbnail" ),                
);
register_post_type( "geo_test", $args );

/**
 * Print a fail face.
 *
 * @param string $wpq The query that failed.
 */
function fail( $wpq = null ) {
	$sty = getenv('STY');
	if ( empty( $sty ) ) {
		print "😡\n";
	} else {
		print ":-(\n";
	}

	if ( WP_GEOMETA_DEBUG > 0) {
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		print "\n" . basename($caller['file']) . ':' . $caller['line'] . "\n";

		prettyQuery( $wpq );
	}
}

/**
 * Print a pass face.
 */
function pass(){
	$sty = getenv('STY');
	if ( empty( $sty ) ) {
		print "😎\n";
	} else {
		print ":-)\n";
	}
}

/**
 * This function is unsupported. Tell them.
 *
 * @param string $unsupported The function name that we were trying to use.
 */
function unsupported( $unsupported_func = '' ) {
	$sty = getenv('STY');
	if ( empty( $sty ) ) {
		print "😐\n";
	} else {
		print ":-|\n";
	}

	if ( WP_GEOMETA_DEBUG > 0 ) {
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		print basename($caller['file']) . ':' . $caller['line'] . "\n";

		print "This test uses $unsupported_func, a function not supported by your install\n\n";
	}
}

/**
 * Format the SQL that failed.
 *
 * @param string $wpq The query itself.
 */
function prettyQuery( $wpq = null ) {
	if ( !empty( $wpq ) ) {
		ob_start();
		print "\n" . SqlFormatter::format($wpq->request) . "\n";
		$sql = ob_get_clean();
		$sql = str_replace("\n","\n\t",$sql);
		print $sql;
	}
}
