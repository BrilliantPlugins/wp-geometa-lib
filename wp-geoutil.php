<?php
/**
 * This class has geo utils that users and WP_Geo* classes might need.
 *
 * @package wp-geometa
 * @link https://github.com/cimburadotcom/WP-GeoMeta
 * @author Michael Moore / michael_m@cimbura.com / https://profiles.wordpress.org/stuporglue/
 * @copyright Cimbura.com, 2016
 * @license GNU GPL v2
 */

/**
 * Some spatial utilities that are used by both WP_GeoQuery and WP_GeoMeta
 * and which may be available to developers at some point.
 *
 * For now, their use is not recommended since things are still in flux.
 */
class WP_GeoUtil {
	/**
	 * A GeoJSON reader (GeoPHP classes);.
	 *
	 * @var $geojson
	 */
	private static $geojson;

	/**
	 * A WKT writer (GeoPHP classes);.
	 *
	 * @var $geowkt
	 */
	private static $geowkt;

	/**
	 * EPSG:4326 is the web mercator project, such as is used by Google Maps
	 *
	 * @see https://en.wikipedia.org/wiki/World_Geodetic_System !
	 *
	 * @var $srid
	 */
	private static $srid;

	/**
	 * This is a list of all known spatial functions in MySQL 5.4.2 to 5.7.6 and MariaDB 5.1 - 10.1.2
	 * We will test for capabilities by checking if the function exists instead of
	 * checking function names.
	 *
	 * @var $all_funcs
	 */
	public static $all_funcs = array(
		'Area',
		'AsBinary',
		'AsText',
		'AsWKB',
		'AsWKT',
		'Boundary',
		'Buffer',
		'Centroid',
		'Contains',
		'ConvexHull',
		'Crosses',
		'Dimension',
		'Disjoint',
		'Distance',
		'EndPoint',
		'Envelope',
		'Equals',
		'ExteriorRing',
		'GeomCollFromText',
		'GeomCollFromWKB',
		'GeometryCollection',
		'GeometryCollectionFromText',
		'GeometryCollectionFromWKB',
		'GeometryFromText',
		'GeometryFromWKB',
		'GeometryN',
		'GeometryType',
		'GeomFromText',
		'GeomFromWKB',
		'GLength',
		'InteriorRingN',
		'Intersects',
		'IsClosed',
		'IsEmpty',
		'IsRing',
		'IsSimple',
		'LineFromText',
		'LineFromWKB',
		'LineString',
		'LineStringFromText',
		'LineStringFromWKB',
		'MBRContains',
		'MBRCoveredBy',
		'MBRDisjoint',
		'MBREqual',
		'MBREquals',
		'MBRIntersects',
		'MBROverlaps',
		'MBRTouches',
		'MBRWithin',
		'MLineFromText',
		'MLineFromWKB',
		'MPointFromText',
		'MPointFromWKB',
		'MPolyFromText',
		'MPolyFromWKB',
		'MultiLineString',
		'MultiLineStringFromText',
		'MultiLineStringFromWKB',
		'MultiPoint',
		'MultiPointFromText',
		'MultiPointFromWKB',
		'MultiPolygon',
		'MultiPolygonFromText',
		'MultiPolygonFromWKB',
		'NumGeometries',
		'NumInteriorRings',
		'NumPoints',
		'Overlaps',
		'Point',
		'PointFromText',
		'PointFromWKB',
		'PointOnSurface',
		'PointN',
		'PolyFromText',
		'PolyFromWKB',
		'Polygon',
		'PolygonFromText',
		'PolygonFromWKB',
		'SRID',
		'ST_Area',
		'ST_AsBinary',
		'ST_AsGeoJSON',
		'ST_AsText',
		'ST_AsWKB',
		'ST_AsWKT',
		'ST_Boundary',
		'ST_Buffer',
		'ST_Buffer_Strategy',
		'ST_Centroid',
		'ST_Contains',
		'ST_ConvexHull',
		'ST_Crosses',
		'ST_Difference',
		'ST_Dimension',
		'ST_Disjoint',
		'ST_Distance',
		'ST_Distance_Sphere',
		'ST_EndPoint',
		'ST_Envelope',
		'ST_Equals',
		'ST_ExteriorRing',
		'ST_GeoHash',
		'ST_GeomCollFromText',
		'ST_GeomCollFromWKB',
		'ST_GeometryCollectionFromText',
		'ST_GeometryCollectionFromWKB',
		'ST_GeometryFromText',
		'ST_GeometryFromWKB',
		'ST_GeometryN',
		'ST_GeometryType',
		'ST_GeomFromGeoJSON',
		'ST_GeomFromText',
		'ST_GeomFromWKB',
		'ST_InteriorRingN',
		'ST_Intersection',
		'ST_Intersects',
		'ST_IsClosed',
		'ST_IsEmpty',
		'ST_IsRing',
		'ST_IsSimple',
		'ST_IsValid',
		'ST_LatFromGeoHash',
		'ST_Length',
		'ST_LineFromText',
		'ST_LineFromWKB',
		'ST_LineStringFromText',
		'ST_LineStringFromWKB',
		'ST_LongFromGeoHash',
		'ST_NumGeometries',
		'ST_NumInteriorRings',
		'ST_NumPoints',
		'ST_Overlaps',
		'ST_PointFromGeoHash',
		'ST_PointFromText',
		'ST_PointFromWKB',
		'ST_PointOnSurface',
		'ST_PointN',
		'ST_PolyFromText',
		'ST_PolyFromWKB',
		'ST_PolygonFromText',
		'ST_PolygonFromWKB',
		'ST_Relate',
		'ST_Simplify',
		'ST_SRID',
		'ST_StartPoint',
		'ST_SymDifference',
		'ST_Touches',
		'ST_Union',
		'ST_Validate',
		'ST_Within',
		'ST_X',
		'ST_Y',
		'StartPoint',
		'Touches',
		'Within',
		'X',
		'Y',
		'WP_Buffer_Point_M',
		'WP_Buffer_Point_Mi',
		'WP_Buffer_Point_Real',
		'WP_Distance_Point_M',
		'WP_Distance_Point_Mi',
		'WP_Distance_Point_Real',
		'WP_First_Geom',
		'WP_Point_Bearing_Distance_To_Line',
		'WP_Point_Bearing_Distance_To_Line_M',
		'WP_Point_Bearing_Distance_To_Line_Mi',
		'WP_Point_Bearing_Distance_Coord_Pair',
		);

	/**
	 * All the functions we detect as available in MySQL
	 *
	 * @var $found_funcs
	 */
	private static $found_funcs = array();

	/**
	 * Has plugins loaded been run yet?
	 *
	 * @var $plugins_loaded_run
	 */
	public static $plugins_loaded_run = false;

	/**
	 * The instance variable
	 *
	 * @var $_instance
	 */
	private static $_instance = null;


	/**
	 * Get the singleton instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			global $wp_actions;
			self::$_instance = new self;
			self::$srid = 4326;

			if ( isset( $wp_actions['plugins_loaded'] ) ) {
				WP_GeoUtil::plugins_loaded();
			} else {
				add_action( 'plugins_loaded', array( 'WP_GeoUtil', 'plugins_loaded' ) );
			}

			spl_autoload_register( array( 'WP_GeoUtil', 'spl_autoload_register' ) );
		}

		return self::$_instance;
	}

	/**
	 * Handle autoloading when looking for the geoPHP class.
	 *
	 * @param string $class_name The class name that PHP is looking for.
	 */
	public static function spl_autoload_register( $class_name ) {
		if ( in_array( $class_name, array( 'WKT', 'GeoJSON' ), true ) ) {
			/**
			* Include geoPHP for this function
			*/
			require_once( dirname( __FILE__ ) . '/geoPHP/geoPHP.inc' );
		}
	}

	/**
	 * Load in the SRID after plugins are loaded.
	 * Check for any additional capabilities after plugins are loaded.
	 */
	public static function plugins_loaded() {

		WP_GeoUtil::$srid = apply_filters( 'wpgm_geoquery_srid', 4326 );

		/* This filter has been deprecated and will be removed in a future version. */
		WP_GeoUtil::$srid = apply_filters( 'wp_geoquery_srid', WP_GeoUtil::$srid );

		$orig_funcs = array_map( 'strtolower',WP_GeoUtil::$all_funcs );

		WP_GeoUtil::get_all_funcs();

		WP_GeoUtil::$plugins_loaded_run = true;

		$new_funcs = array_map( 'strtolower',WP_GeoUtil::$all_funcs );

		$diff = array_diff( $new_funcs, $orig_funcs );
		if ( count( $diff ) > 0 ) {
			WP_GeoUtil::get_capabilities( true, false );
		}
	}

	/**
	 * This function loads all known functions, including applying filters to load from other plugins.
	 */
	public static function get_all_funcs() {
		WP_GeoUtil::$all_funcs = apply_filters( 'wpgm_known_capabilities', WP_GeoUtil::$all_funcs );

		/* This filter has been deprecated and will be removed in a future version. */
		WP_GeoUtil::$all_funcs = apply_filters( 'wpgq_known_capabilities', WP_GeoUtil::$all_funcs );

		WP_GeoUtil::$all_funcs = array_unique( WP_GeoUtil::$all_funcs );

		return WP_GeoUtil::$all_funcs;
	}

	/**
	 * Merge one or more pieces of geojson together. Each item could be a FeatureCollection
	 * or an individual feature.
	 *
	 * All pieces will be combined to make a single FeatureCollection
	 *
	 * If only one piece is sent, then it will be converted into a FeatureCollection if
	 * it isn't already.
	 *
	 * @note This function takes as many geojson or geojson fragments as you want to pass in.
	 *
	 * @return A FeatureCollection GeoJSON array
	 */
	public static function merge_geojson() {
		$fragments = func_get_args();

		// Check if we've been given an array of fragments and act accordingly.
		// If we don't have 'type' in our keys, then there's a good chance we might have an array of geojsons as our first arg.
		if ( 1 === count( $fragments ) && is_array( $fragments[0] ) && ! array_key_exists( 'type', $fragments[0] ) ) {
			$fragments = $fragments[0];
		}

		$ret = array(
			'type' => 'FeatureCollection',
			'features' => array(),
		);

		foreach ( $fragments as $fragment ) {

			$fragment = maybe_unserialize( $fragment );

			if ( is_object( $fragment ) ) {
				$fragment = (array) $fragment;
			} else if ( is_string( $fragment ) ) {
				$fragment = json_decode( $fragment,true );
			}

			if ( ! is_array( $fragment ) ) {
				return false;
			}

			$fragment = array_change_key_case( $fragment );

			if ( ! array_key_exists( 'type',$fragment ) ) {
				continue;
			}

			if ( 0 === strcasecmp( 'featurecollection',$fragment['type'] ) && is_array( $fragment['features'] ) ) {
				$ret['features'] = array_merge( $ret['features'], $fragment['features'] );
			} else if ( 0 === strcasecmp( 'feature', $fragment['type'] ) ) {
				$ret['features'][] = $fragment;
			}
		}

		if ( empty( $ret['features'] ) ) {
			return false;
		}

		return wp_json_encode( $ret );
	}

	/**
	 * Convert a metaval to GeoJSON
	 *
	 * @param anything $metaval The value to turn into GeoJSON.
	 * @param string   $return_type The supported types are 'string' and 'array'.
	 */
	public static function metaval_to_geojson( $metaval, $return_type = 'string' ) {
		$metaval = maybe_unserialize( $metaval );

		if ( self::is_geojson( $metaval ) ) {
			return $metaval;
		}

		if ( empty( $metaval ) ) {
			return false;
		}

		// Exit early if we're a non-GeoJSON string.
		if ( is_string( $metaval ) ) {
		   	if ( strpos( $metaval,'{' ) === false || strpos( $metaval,'Feature' ) === false || strpos( $metaval,'geometry' ) === false ) {
				return false;
			} else {
				$metaval = json_decode( $metaval,true );
			}
		}

		// If it's an object, cast it to an array for consistancy.
		if ( is_object( $metaval ) ) {
			$metaval = (array) $metaval;
		}

		// Last check!
		$string_metaval = wp_json_encode( $metaval );

		if ( ! self::is_geojson( $metaval ) ) {
			return false;
		}

		if ( 'string' === $return_type ) {
			return $string_metaval;
		} else if ( 'array' === $return_type ) {
			return $metaval;
		}
	}

	/**
	 * We're going to support single GeoJSON features and FeatureCollections in either string, object or array format.
	 *
	 * @param mixed $metaval The meta value to try to convert to WKT.
	 * @param bool  $force_multi Should the value be turned into a MULTI type geometry? Default is false. This is set to true by WP-GeoMeta before storing values in the database.
	 *
	 * @return A WKT geometry string.
	 */
	public static function metaval_to_geom( $metaval = false, $force_multi = false ) {

		$maybe_geom = apply_filters( 'wpgm_metaval_to_geom', $metaval );

		/* This filter has been deprecated and will be removed in a future version. */
		$maybe_geom = apply_filters( 'wpgq_metaval_to_geom', $maybe_geom );

		if ( self::is_geom( $maybe_geom ) ) {
			return $maybe_geom;
		}

		// Everything becomes GeoJSON so that the rest of this function will be simpler.
		$make_string = ( $force_multi ? 'string' : 'array' );
		$metaval = self::metaval_to_geojson( $metaval, $make_string );

		if ( false === $metaval ) {
			return $metaval;
		}

		if ( $force_multi ) {
			$metaval = self::merge_geojson( $metaval );
		}

		if ( false === $metaval ) {
			return false;
		}

		// Stringify any arrays.
		if ( is_array( $metaval ) ) {
			$metaval = wp_json_encode( $metaval );
		}

		// Convert GeoJSON to WKT.
		try {
			$geom = self::get_geojson()->read( (string) $metaval );
			if ( is_null( $geom ) ) {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}

		try {
			$wkt = self::get_wkt()->write( $geom );

			/*
             * MUTLI all the things because MySQL 5.7 (at least, maybe others) doesn't
             * like Geometry in GeometryCollection columns.
			 */
			if ( $force_multi && false === strpos( $wkt, 'MULTI' ) ) {
				if ( 0 === strpos( $wkt, 'POINT' ) ) {
					$wkt = preg_replace( '@^POINT@','MULTIPOINT', $wkt );
				} else if ( 0 === strpos( $wkt, 'LINE' ) || 0 === strpos( $wkt, 'POLYGON' ) ) {
					$wkt = preg_replace( '@^(LINE|POLYGON)(\s*)(\(.*?\)[^,])@','MULTI$1$2($3)', $wkt );
				}
			}

			return $wkt;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Convert WKT to GeoJSON
	 *
	 * @param geometry $wkt Convert a geometry of some sort to GeoJSON.
	 *
	 * @return A GeoJSON string.
	 */
	public static function geom_to_geojson( $wkt ) {
		$maybe_geojson = apply_filters( 'wpgm_geom_to_geojson', $wkt );

		/* This filter has been deprecated and will be removed in a future version. */
		$maybe_geojson = apply_filters( 'wpgq_geom_to_geojson', $maybe_geojson );
		if ( self::is_geojson( $maybe_geojson ) ) {
			return $maybe_geojson;
		}

		// Don't know what to do non-strings.
		if ( ! is_string( $maybe_geojson ) ) {
			return false;
		}

		// WKT needs to start with one of these things.
		$maybe_geojson = trim( $maybe_geojson );
		if ( stripos( $maybe_geojson, 'POINT' ) !== 0 &&
			stripos( $maybe_geojson, 'LINESTRING' ) !== 0 &&
			stripos( $maybe_geojson, 'POLYGON' ) !== 0 &&
			stripos( $maybe_geojson, 'MULTIPOINT' ) !== 0 &&
			stripos( $maybe_geojson, 'MULTILINESTRING' ) !== 0 &&
			stripos( $maybe_geojson, 'MULTIPOLYGON' ) !== 0 &&
			stripos( $maybe_geojson, 'GEOMETRYCOLLECTION' ) !== 0
		) {
			return false;
		}

		try {
			$geom = self::get_wkt()->read( $maybe_geojson );
			$geojson = self::get_geojson()->write( $geom );

			// Do we need to wrap it?
			if ( ! empty( $geojson ) && strpos( $geojson, '"type":"Feature"' ) === false ) {
				$geojson = '{"type":"Feature","geometry":' . $geojson . ',"properties":{}}';
			}

			return $geojson;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if a value is in WKT, which is our DB-ready format.
	 *
	 * @param string $maybe_geom Something which we want to check if it's WKT or not.
	 *
	 * @return bool
	 */
	public static function is_geom( $maybe_geom ) {
		try {
			if ( ! is_string( $maybe_geom ) ) {
				return false;
			}

			if ( stripos( $maybe_geom, 'POINT' ) !== 0 &&
				stripos( $maybe_geom, 'LINESTRING' ) !== 0 &&
				stripos( $maybe_geom, 'POLYGON' ) !== 0 &&
				stripos( $maybe_geom, 'MULTIPOINT' ) !== 0 &&
				stripos( $maybe_geom, 'MULTILINESTRING' ) !== 0 &&
				stripos( $maybe_geom, 'MULTIPOLYGON' ) !== 0 &&
				stripos( $maybe_geom, 'GEOMETRYCOLLECTION' ) !== 0
			) {
				return false;
			}

			$what = self::get_wkt()->read( (string) $maybe_geom );
			if ( null !== $what ) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Check if a value is in GeoJSON, which is our code-ready forma.
	 *
	 * @param anything $maybe_geojson Check if a value is GeoJSON or not.
	 * @param bool     $string_only Should GeoJSON compatible strings and objects be counted as GeoJSON? Default is false (allow arrays/objects).
	 *
	 * @return boolean
	 */
	public static function is_geojson( $maybe_geojson, $string_only = false ) {
		try {

			if ( ! is_string( $maybe_geojson ) && $string_only ) {
				return false;
			}

			if ( is_array( $maybe_geojson ) || is_object( $maybe_geojson ) ) {
				$maybe_geojson = wp_json_encode( $maybe_geojson );
			}

			$maybe_geojson = (string) $maybe_geojson;

		   	if ( strpos( $maybe_geojson, '{' ) === false || strpos( $maybe_geojson, 'Feature' ) === false || strpos( $maybe_geojson, 'geometry' ) === false ) {
				return false;
			}

			$what = self::get_geojson()->read( $maybe_geojson );
			if ( null !== $what ) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}


	/**
	 * Fetch the found capabilities from the database
	 *
	 * If no capabilites are found, then generate them by running
	 * queries with each SQL function and seeing what the error
	 * message says
	 *
	 * @param bool $retest Should we re-check and re-store our capabilities.
	 * @param bool $lower Should all functions be lower-cased before returning.
	 * @param bool $cache_results Should our known functions be cached once they're generated.
	 */
	public static function get_capabilities( $retest = false, $lower = true, $cache_results = true ) {
		global $wpdb;

		if ( true !== WP_GeoUtil::$plugins_loaded_run ) {
			WP_GeoUtil::plugins_loaded();
		}

		if ( ! $retest ) {
			if ( empty( self::$found_funcs ) ) {
				self::$found_funcs = get_option( 'wp_geometa_capabilities',array() );
			}

			if ( ! empty( self::$found_funcs ) ) {
				if ( $lower ) {
					return array_map( 'strtolower', self::$found_funcs );
				} else {
					return self::$found_funcs;
				}
			}
		}

		$suppress = $wpdb->suppress_errors( true );
		$errors = $wpdb->show_errors( false );

		// Reset it before adding stuff!
		self::$found_funcs = array();

		foreach ( WP_GeoUtil::$all_funcs as $func ) {

			// First, check to see if a custom function exists.
			$q = "SELECT IF( COUNT(*) = 0, 'F' , 'T' ) AS ProcedureExists FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = '{$wpdb->dbname}' AND ROUTINE_TYPE = 'FUNCTION' AND UCASE(ROUTINE_NAME) = UCASE('$func');";
			$custom_func = $wpdb->get_var( $q ); // @codingStandardsIgnoreLine

			if ( 'T' === $custom_func ) {
				self::$found_funcs[] = $func;
				continue;
			}

			// Otherwise check if it's a built-in.
			$q = "SELECT $func() AS worked";
			$wpdb->query( $q ); // @codingStandardsIgnoreLine

			if ( strpos( $wpdb->last_error,'Incorrect parameter count' ) !== false || strpos( $wpdb->last_error,'You have an error in your SQL syntax' ) !== false ) {
				self::$found_funcs[] = $func;
			}
		}

		// Re-set the error settings.
		$wpdb->suppress_errors( $suppress );
		$wpdb->show_errors( $errors );

		if ( $cache_results ) {
			update_option( 'wp_geometa_capabilities',self::$found_funcs, false );
		}

		return self::get_capabilities( false, $lower, $cache_results );
	}

	/**
	 * Get the srid
	 */
	public static function get_srid() {
		return self::$srid;
	}

	/**
	 * Static magic method to support calling any geometry function.
	 *
	 * Eg. WP_GeoUtil::Buffer( $geometry, $distance);
	 *
	 * @param string $name The name of the function that is being called.
	 * @param array  $arguments The arguments for the function.
	 */
	public static function __callStatic( $name, $arguments ) {
		if ( in_array( strtolower( $name ), self::get_capabilities(), true ) ) {
			return self::run_spatial_query( $name, $arguments );
		}
	}

	/**
	 * Run the actual spatial query.
	 *
	 * Any geometries should be GeoJSON compatible.
	 *
	 * Geometry responses will be returned as GeoJSON.
	 *
	 * Other responses will be returned as is.
	 *
	 * @param string $name The name of the function that is being called.
	 * @param array  $arguments The arguments for the function.
	 */
	private static function run_spatial_query( $name, $arguments = array() ) {
		global $wpdb;

		if ( empty( $arguments ) ) {
			return false;
		}

		$q = 'SELECT ' . $name . '(';
		foreach ( $arguments as $idx => $arg ) {

			if ( $idx > 0 ) {
				$q .= ',';
			}

			$maybe_geom = self::metaval_to_geom( $arg );
			if ( false !== $maybe_geom ) {
				$arguments[ $idx ] = $maybe_geom;
				$q .= 'GeomCollFromText(%s)';
			} else {
				$q .= '%s';
			}
		}
		$q .= ')';

		/*
        -- Detect geometry.
		-- Data is WKB, with a 4 byte leading SRID (which may be 00 00 00 00)
        -- In big endian it will look like this:
		-- E6 10    00          00 01 02 00
		-- srid    endianness   WKB integer code

        -- In little endian it should look like this:
		-- 10 E6        01      01 00 00 02
		-- srid    endianness   WKB integer code
		-- Note: MySQL appears to use the Z codes, though I can't find actual support for Z values
		*/

		$real_q = 'SELECT IF( 
			COALESCE( SUBSTR(HEX(retval),5,10) IN (

				-- big endian
				\'0000010000\', -- geometry
				\'0000010100\', -- point
				\'0000010200\', -- line 
				\'0000010300\', -- polygon
				\'0000010400\', -- multipoint
				\'0000010500\', -- multiline
				\'0000010600\', -- multipolygon

				-- little endian
				\'0001000000\', -- geometry
				\'0001000001\', -- point
				\'0001000002\', -- line 
				\'0001000003\', -- polygon
				\'0001000004\', -- multipoint
				\'0001000005\', -- multiline
				\'0001000006\' -- multipolygon
			), false) , AsText( retval ), retval ) AS res FROM ( ' . $q . ' AS retval ) rq';

		$sql = $wpdb->prepare( $real_q, $arguments ); // @codingStandardsIgnoreLine

		$res = $wpdb->get_var( $sql ); // @codingStandardsIgnoreLine

		$maybe_geojson = self::geom_to_geojson( $res );
		if ( false !== $maybe_geojson ) {
			return $maybe_geojson;
		}

		return $res;
	}

	/**
	 * Get the GeoJSON object, creating it if needed.
	 */
	private static function get_geojson() {
		if ( ! isset( self::$geojson ) ) {
			self::$geojson = new GeoJSON();
		}

		return self::$geojson;
	}

	/**
	 * Get the WKT object, creating it if needed.
	 */
	private static function get_wkt() {
		if ( ! isset( self::$geowkt ) ) {
			self::$geowkt = new WKT();
		}
		return self::$geowkt;
	}
}
