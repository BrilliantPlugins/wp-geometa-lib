<?php
/**
 * This class handles creating spatial tables and saving geo metadata
 *
 * @package wp-geometa
 * @link https://github.com/BrilliantPlugins/wp-geometa
 * @author Michael Moore / michael.moore@luminfire.com / https://profiles.wordpress.org/stuporglue/
 * @copyright LuminFire, 2016
 * @license GNU GPL v2
 */

defined( 'ABSPATH' ) or die( 'No direct access' );

if ( !class_exists( 'WP_GeoMeta', false ) ) {

	/**
	 * WP_GeoMeta is responsible for detecting when the user
	 * saves GeoJSON and adding a spatial version to the meta_geo
	 * tables
	 */
	class WP_GeoMeta {
		/**
		 * The version of WP_GeoMeta.
		 *
		 * Gets set by autoloader
		 *
		 * @var $version
		 */
		public static $version;

		/**
		 * Seems like if we call dbDelta twice in rapid succession then we end up
		 * with a MySQL error, at least on MySQL 5.5. Other versions untested.
		 *
		 * This gets set to true after calling create_geo_tables the first time
		 * which prevents it from running again.
		 *
		 * @var $create_geo_tables_called
		 */
		private $create_geo_tables_called = false;

		/**
		 * What kind of meta are we handling?
		 *
		 * @var $meta_types
		 *
		 * @note Still missing sitemeta
		 *
		 * We could use pre_get_posts, pre_get_comments, pre_get_terms, pre_get_users
		 */
		public $meta_types = array( 'comment','post','term','user' );

		/**
		 * What kind of meta actions are we handling?
		 *
		 * @var $meta_actions
		 *
		 * @note We can ignore get, since we would just return the GeoJSON anyways
		 */
		public $meta_actions = array( 'added','updated','deleted' );


		/**
		 * Keep track of our lat/lng fields
		 *
		 * @var $latlngs
		 */
		public static $latlngs = array();

		/**
		 * Track just the lat/lng names so we can quickly check if we're processing a
		 *
		 * @var $latlngs_index
		 */
		public static $latlngs_index = array();

		/**
		 * List of extra SQL files to load.
		 *
		 * @var $extra_sql
		 */
		private $extra_sql = array( 'first_geometry.sql', 'buffer_point.sql' , 'distance_point.sql', 'point_bearing_distance.sql' );

		/**
		 * Singleton variable
		 *
		 * @var $_instance
		 */
		private static $_instance = null;

		/**
		 * Get the singleton instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self;
			}

			if ( ! defined( 'WP_GEOMETA_VERSION' ) && ! empty( WP_GeoMeta::$version ) ) {
				define( 'WP_GEOMETA_VERSION', WP_GeoMeta::$version );
			}

			return self::$_instance;
		}

		/**
		 * Set up our filters
		 */
		protected function __construct() {
			define( 'WP_GEOMETA_HOME', dirname( __FILE__ ) );

			foreach ( $this->meta_types as $type ) {
				foreach ( $this->meta_actions as $action ) {
					add_action( "{$action}_{$type}_meta", array( $this, "{$action}_{$type}_meta" ),10,4 );
				}
			}

			WP_GeoMeta::add_latlng_field( 'geo_latitude', 'geo_longitude', 'geo_' );

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded') );
			add_filter( 'wpgm_pre_metaval_to_geom', array( $this, 'wpgm_pre_metaval_to_geom' ), 10, 2 );
			add_filter( 'wpgm_populate_geo_tables', array( $this, 'wpgm_populate_geo_tables' ) );
			add_filter( 'wpgm_pre_delete_geometa', array( $this, 'wpgm_pre_delete_geometa' ), 10, 5 );
			add_filter( 'wpgm_extra_sql_functions', array( $this, 'wpgm_extra_sql_functions' ) );
		}

		/**
		 * Make sure that everything we need is defined, if no one else did already.
		 */
		public function plugins_loaded() {
			if ( !defined( 'WP_GEOMETA_DEBUG' ) ) {
				define( 'WP_GEOMETA_DEBUG', false );
			}
		}

		/**
		 * Install the tables and custom SQL queries
		 */
		public static function install() {
			$wpgm = WP_GeoMeta::get_instance();
			update_option( 'wp_geometa_db_version', WP_GEOMETA_VERSION );

			// This will go away once autoloading is configured.
			update_option( 'wp_geometa_version', WP_GEOMETA_VERSION );
			$wpgm->create_geo_tables();
			$wpgm->install_extra_sql_functions();
		}

		/**
		 * Upgrade databases, if they exist.
		 */
		public function upgrade() {
			$this->create_geo_tables();
			$this->install_extra_sql_functions();

			update_option( 'wp_geometa_db_version', WP_GEOMETA_VERSION );
			update_option( 'wp_geometa_version', WP_GEOMETA_VERSION );

			$wp_geoutil = WP_GeoUtil::get_instance();
			$wp_geoutil->get_capabilities( true );
		}

		/**
		 * Run SQL to create geo tables
		 *
		 * @param bool $force Should we force re-creation.
		 */
		public function create_geo_tables( $force = false ) {
			if ( $this->create_geo_tables_called && ! $force ) {
				return;
			} else {
				$this->create_geo_tables_called = true;
			}

			global $wpdb;

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$charset_collate = $wpdb->get_charset_collate();
			$max_index_length = 191;

		/*
			Only MyISAM supports spatial indexes, at least in MySQL older versions.
			Spatial indexes can only contain non-null columns.
		 */
			$geotables = 'CREATE TABLE ' . _get_meta_table( 'post' ) . "_geo (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				post_id bigint(20) unsigned NOT NULL default '0',
				fk_meta_id bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value geometrycollection NOT NULL,
				PRIMARY KEY  (meta_id),
		KEY post_id (post_id),
		UNIQUE KEY fk_postmeta_id (fk_meta_id),
		KEY meta_key (meta_key($max_index_length))
	) ENGINE=MyISAM $charset_collate;

			CREATE TABLE " . _get_meta_table( 'comment' ) . "_geo (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				comment_id bigint(20) unsigned NOT NULL default '0',
				fk_meta_id bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value geometrycollection NOT NULL,
				PRIMARY KEY  (meta_id),
		KEY comment_id (comment_id),
		UNIQUE KEY fk_commentmeta_id (fk_meta_id),
		KEY meta_key (meta_key($max_index_length))
	) ENGINE=MyISAM $charset_collate;

			CREATE TABLE " . _get_meta_table( 'term' ) . "_geo (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				term_id bigint(20) unsigned NOT NULL default '0',
				fk_meta_id bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value geometrycollection NOT NULL,
				PRIMARY KEY  (meta_id),
		KEY term_id (term_id),
		UNIQUE KEY fk_termmeta_id (fk_meta_id),
		KEY meta_key (meta_key($max_index_length))
	) ENGINE=MyISAM $charset_collate;

			CREATE TABLE " . _get_meta_table( 'user' ) . "_geo (
				umeta_id bigint(20) unsigned NOT NULL auto_increment,
				user_id bigint(20) unsigned NOT NULL default '0',
				fk_meta_id bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value geometrycollection NOT NULL,
				PRIMARY KEY  (umeta_id),
		KEY user_id (user_id),
		UNIQUE KEY fk_usermeta_id (fk_meta_id),
		KEY meta_key (meta_key($max_index_length))
	) ENGINE=MyISAM $charset_collate;
";

		/*
		Pre WP 4.6, dbDelta had a problem with SPATIAL INDEX, so we run those separate.
		https://core.trac.wordpress.org/ticket/36948

		Once WP 4.6 is out we can revisit this.
		 */
					dbDelta( $geotables );

					$suppress = $wpdb->suppress_errors( true );
					$errors = $wpdb->show_errors( false );

					foreach ( $this->meta_types as $type ) {
						$show_index		= 'SHOW INDEX FROM ' . _get_meta_table( $type ) . '_geo WHERE Key_name=\'meta_val_spatial_idx\';';
						$add_index	= 'CREATE SPATIAL INDEX meta_val_spatial_idx ON ' . _get_meta_table( $type ) . '_geo (meta_value);';

						$found_query = $wpdb->query( $show_index ); // @codingStandardsIgnoreLine
						if ( $found_query === 0 ) {
							$wpdb->query( $add_index ); // @codingStandardsIgnoreLine
						}
					}

					$wpdb->suppress_errors( $suppress );
					$wpdb->show_errors( $errors );
		}

		/**
		 * Install handy extra SQL functions
		 */
		public function install_extra_sql_functions() {
			global $wpdb;

			$sql_files = apply_filters( 'wpgm_extra_sql_functions', array() );

			$suppress = $wpdb->suppress_errors( true );
			$errors = $wpdb->show_errors( false );

			// These files add extra SQL support.
			foreach ( $sql_files as $sql_file ) {

				if ( ! is_file( $sql_file ) ) {
					continue;
				}

				$sql_code = file_get_contents( $sql_file ); // @codingStandardsIgnoreLine
				$sql_code = explode( '$$', $sql_code );
				$sql_code = array_map( 'trim',$sql_code );
				$sql_code = array_filter($sql_code, function( $statement ) {
					if ( empty( $statement ) ) {
						return false;
					}
					if ( strpos( $statement, 'DELIMITER' ) !== false ) {
						return false;
					}
					return true;
				});
				foreach ( $sql_code as $statement ) {
					$res = $wpdb->query( $statement ); // @codingStandardsIgnoreLine
				}
			}

			$wpdb->suppress_errors( $suppress );
			$wpdb->show_errors( $errors );
		}

		/**
		 * Un-create the geo tables
		 */
		public function uninstall() {
			$this->uninstall_tables();
			$this->uninstall_extra_sql_functions();
		}

		/**
		 * Un-create the geo tables
		 */
		public function uninstall_tables() {
			global $wpdb;

			$suppress = $wpdb->suppress_errors( true );
			$errors = $wpdb->show_errors( false );

			foreach ( $this->meta_types as $type ) {
				$drop = 'DROP TABLE ' . _get_meta_table( $type ) . '_geo';
				$wpdb->query( $drop ); // @codingStandardsIgnoreLine
			}

			$wpdb->suppress_errors( $suppress );
			$wpdb->show_errors( $errors );
		}

		/**
		 * Remove the extra SQL functions
		 */
		public function uninstall_extra_sql_functions() {
			global $wpdb;

			$sql_files = apply_filters( 'wpgm_extra_sql_functions', array() );

			$suppress = $wpdb->suppress_errors( true );
			$errors = $wpdb->show_errors( false );

			foreach ( $sql_files as $sql_file ) {

				if ( ! is_file( $sql_file ) ) {
					continue;
				}

				$sql_code = file_get_contents( $sql_file ); // @codingStandardsIgnoreLine
				$sql_code = explode( '$$', $sql_code );
				$sql_code = array_map( 'trim',$sql_code );
				$sql_code = array_filter($sql_code, function( $statement ) {
					if ( strpos( $statement, 'DROP FUNCTION' ) === false ) {
						return false;
					}
					return true;
				});
				foreach ( $sql_code as $statement ) {
					$res = $wpdb->query( $statement ); // @codingStandardsIgnoreLine
				}
			}

			$wpdb->suppress_errors( $suppress );
			$wpdb->show_errors( $errors );
		}

		/**
		 * Truncate the geo tables.
		 */
		public function truncate_tables() {
			global $wpdb;

			$suppress = $wpdb->suppress_errors( true );
			$errors = $wpdb->show_errors( false );

			foreach ( $this->meta_types as $type ) {
				$drop = 'TRUNCATE TABLE ' . _get_meta_table( $type ) . '_geo';
				$wpdb->query( $drop ); // @codingStandardsIgnoreLine
			}

			$wpdb->suppress_errors( $suppress );
			$wpdb->show_errors( $errors );
		}

		/**
		 * Handle all the variations of add/update/delete post/user/comment
		 *
		 * @param String $name The name of the function we're asking for.
		 * @param Mixed  $arguments All the function arguments.
		 */
		public function __call( $name, $arguments ) {
			$parts = explode( '_', $name );
			if ( count( $parts ) !== 3 ) {
				return;
			}

			$action = $parts[0];
			$type = $parts[1];

			if ( ! in_array( $action, $this->meta_actions, true ) || ! in_array( $type, $this->meta_types, true ) ) {
				return;
			}

			if ( 'deleted' === $action ) {
				$geometry = false;
			} else {
				$arguments = apply_filters( 'wpgm_pre_metaval_to_geom', $arguments, $type );
				$geometry = WP_GeoUtil::metaval_to_geom( $arguments[3], true );
				$arguments[3] = $geometry;
			}

			if ( 'deleted' === $action ) {
				array_unshift( $arguments,$type );
				return call_user_func_array( array( $this, 'deleted_meta' ), $arguments );
			} else if ( $geometry ) {
				array_unshift( $arguments,$type );
				return call_user_func_array( array( $this, 'upsert_meta' ), $arguments );
			}
		}

		/**
		 * Callback for adding or updating meta
		 *
		 * @param string $meta_type The type of meta we are targeting.
		 * @param int    $meta_id The ID of the non-geo meta object which was just saved.
		 * @param int    $object_id The ID of the object the meta is for.
		 * @param mixed  $meta_key The key for this metadata pair.
		 * @param mixed  $meta_value The value for this metadata pair.
		 *
		 * The function uses INSERT ... ON DUPLICATE KEY UPDATE so it handles meta added and updated cases.
		 */
		private function upsert_meta( $meta_type, $meta_id, $object_id, $meta_key, $meta_value ) {
			global $wpdb;

			if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
				return false;
			}

			$object_id = absint( $object_id );
			if ( ! $object_id ) {
				return false;
			}

			$table = _get_meta_table( $meta_type );
			if ( ! $table ) {
				return false;
			}

			$table .= '_geo';

			// @codingStandardsIgnoreStart
			$result = $wpdb->query( 
				$wpdb->prepare(
					"INSERT INTO $table 
					(
			{$meta_type}_id,
			fk_meta_id,
			meta_key,
			meta_value
		) VALUES (
			%d,
			%d,
			%s,
			ST_GeomFromText(%s,%d, 'axis-order=long-lat')
		) ON DUPLICATE KEY UPDATE meta_value=ST_GeomFromText(%s,%d, 'axis-order=long-lat')",
					array(
						$object_id,
						$meta_id,
						$meta_key,
						$meta_value,
						WP_GeoUtil::get_srid(),
						$meta_value,
						WP_GeoUtil::get_srid(),
					)
				)
			);
					// @codingStandardsIgnoreEnd

					if ( ! $result ) {
						return false;
					}

					$mid = (int) $wpdb->insert_id;

					wp_cache_delete( $object_id, $meta_type . '_metageo' );

					return $mid;
		}

		/**
		 * Callback for deleting meta
		 *
		 * @param string $meta_type The type of meta we are targeting.
		 * @param int    $meta_ids The ID of the non-geo meta object which was just saved.
		 * @param int    $object_id The ID of the object the meta is for.
		 * @param mixed  $meta_key The key for this metadata pair.
		 * @param mixed  $meta_value The value for this metadata pair.
		 */
		private function deleted_meta( $meta_type, $meta_ids, $object_id, $meta_key, $meta_value ) {
			global $wpdb;

			if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) && ! $delete_all ) {
				return false;
			}

			$object_id = absint( $object_id );
			if ( ! $object_id && ! $delete_all ) {
				return false;
			}

			$table = _get_meta_table( $meta_type );
			if ( ! $table ) {
				return false;
			}

			$table .= '_geo';

			$type_column = sanitize_key( $meta_type . '_id' );
			$id_column = 'user' === $meta_type ? 'umeta_id' : 'meta_id';

			$meta_ids = apply_filters( 'wpgm_pre_delete_geometa', $meta_ids, $meta_type, $object_id, $meta_key, $meta_value );

			$meta_ids = array_map( 'intval', $meta_ids );

			$sql = "DELETE FROM $table WHERE fk_meta_id IN (" . implode( ',',$meta_ids ) . ')';

			$count = $wpdb->query( $sql ); // @codingStandardsIgnoreLine

			if ( ! $count ) {
				return false;
			}

			wp_cache_delete( $object_id, $meta_type . '_metageo' );

			return true;
		}

		/**
		 * Repopulate the geometa tables based on the non-geo meta rows that hold GeoJSON.
		 */
		public function populate_geo_tables() {
			global $wpdb;

			foreach ( $this->meta_types as $meta_type ) {
				$metatable = _get_meta_table( $meta_type );
				$geotable = $metatable . '_geo';

				$meta_pkey = 'meta_id';
				if ( 'user' === $meta_type  ) {
					$meta_pkey = 'umeta_id';
				}

				$maxid = -1;
				do {
					$q = "SELECT $metatable.* 
						FROM $metatable 
						LEFT JOIN {$metatable}_geo ON ({$metatable}_geo.fk_meta_id = $metatable.$meta_pkey )
						WHERE 
						( 
							$metatable.meta_value LIKE '{%Feature%geometry%}%' -- By using a leading { we can get some small advantage from MySQL indexes
							OR $metatable.meta_value LIKE '{%geometry%Feature%}%' -- By using a leading { we can get some small advantage from MySQL indexes
							OR $metatable.meta_value LIKE 'a:%{%Feature%geometry%}%' -- But we also need to handle serialized GeoJSON arrays
							OR $metatable.meta_value LIKE 'a:%{%geometry%Feature%}%' -- But we also need to handle serialized GeoJSON arrays
						)
						AND {$metatable}_geo.fk_meta_id IS NULL
						AND $metatable.$meta_pkey > $maxid 
						ORDER BY $metatable.$meta_pkey
						LIMIT 100";

					$res = $wpdb->get_results( $q,ARRAY_A ); // @codingStandardsIgnoreLine
					$found_rows = count( $res );

					foreach ( $res as $row ) {
						$geometry = WP_GeoUtil::metaval_to_geom( $row['meta_value'], true );
						if ( $geometry ) {
							$this->upsert_meta( $meta_type,$row[ $meta_pkey ],$row[ $meta_type . '_id' ],$row['meta_key'],$geometry );
						}
						$maxid = $row[ $meta_pkey ];
					}
				} while ($found_rows);
			}

			do_action( 'wpgm_populate_geo_tables' );
		}

		/**
		 * Add the names of latitude and longitude fields which will be coerced into a Point GeoJSON representation automatically
		 *
		 * @param string $latitude_name The name of the latitude meta field.
		 * @param string $longitude_name The name of the longitude meta field.
		 * @param string $geojson_name The name of the geojson meta field to put in the meta table.
		 */
		public static function add_latlng_field( $latitude_name, $longitude_name, $geojson_name ) {
			$idx = count( WP_GeoMeta::$latlngs );
			WP_GeoMeta::$latlngs[] = array(
				'lat' => $latitude_name,
				'lng' => $longitude_name,
				'geo' => $geojson_name,
			);

			WP_GeoMeta::$latlngs_index[ $latitude_name ] = WP_GeoMeta::$latlngs[ $idx ];
			WP_GeoMeta::$latlngs_index[ $longitude_name ] = WP_GeoMeta::$latlngs[ $idx ];
		}


		/**
		 * Handle lat/lng values from the WP Geodata standard: https://codex.wordpress.org/Geodata
		 *
		 * Any time geo_latitude or geo_longitude are saved to (eg.) wp_postmeta, this will run.
		 * We check if the other piece of the coordinate is present so we can make a coordinate pair
		 * then always modify the args so that we save a single value to the geometa table.
		 *
		 * The key we use in the geometa tables is 'geo_'.
		 *
		 * Since the value has already been saved to the regular postmeta table this won't mess with those values.
		 *
		 * @param array  $meta_args Array with the meta_id that was just saved, the object_id it was for, the meta_key and meta_values used.
		 *  $meta_args[0] -- meta_id from insert.
		 *  $meta_args[1] -- object_id which this applies to.
		 *  $meta_args[2] -- meta key.
		 *  $meta_args[3] -- the meta value.
		 *
		 * @param string $object_type Which WP type is it? (comment/user/post/term).
		 */
		public static function wpgm_pre_metaval_to_geom( $meta_args, $object_type ) {
			$object_id = $meta_args[1];
			$metakey = $meta_args[2];
			$metaval = $meta_args[3];

			// Quick return if the meta key isn't something we recognize as a lat or lng meta key.
			if ( ! array_key_exists( $metakey, WP_GeoMeta::$latlngs_index ) ) {
				return $meta_args;
			}

			$thepair = WP_GeoMeta::$latlngs_index[ $metakey ];

			$the_other_field = ( $thepair['lat'] === $metakey  ? $thepair['lng'] : $thepair['lat'] );

			$func = 'get_' . $object_type. '_meta';
			$the_other_value = $func( $object_id, $the_other_field, true );

			if ( empty( $the_other_value ) ) {
				return $meta_args;
			}

			if ( $thepair['lat'] === $metakey ) {
				$coordinates = array( $the_other_value, $metaval );
			} else {
				$coordinates = array( $metaval, $the_other_value );
			}

			$geojson = array(
				'type' => 'Feature',
				'geometry' => array(
					'type' => 'Point',
					'coordinates' => $coordinates,
				),
				'properties' => array(),
			);

			$meta_args[2] = $thepair['geo'];
			$meta_args[3] = wp_json_encode( $geojson );
			return $meta_args;
		}

		/**
		 * When WP_GeoMeta::populate_geo_tables() is called, an action will trigger this call.
		 *
		 * It gives us an opportunity to re-populate the meta table if needed.
		 */
		public function wpgm_populate_geo_tables() {
			global $wpdb;

			$latitude_fields = array();
			$longitude_fields = array();

			foreach ( WP_GeoMeta::$latlngs as $latlng ) {
				$latitude_fields[] = $latlng['lat'];
				$longitude_fields[] = $latlng['lng'];
			}

			if ( 0 === count( $latitude_fields ) ) {
				return;
			}

			$pmtables_range = range( 0, count( $latitude_fields ) - 1 );
			$pmtables = '`pm' . implode( '`.`meta_value`, `pm', $pmtables_range ) . '`.`meta_value`';

			foreach ( $this->meta_types as $type ) {
				$meta_table = _get_meta_table( $type );

				$query = 'SELECT
					`pm`.`meta_key`,
					`pm`.`' . $type . '_id` AS `obj_id`,';

				$query .= ( 'user' === $type ? ' `pm`.`umeta_id`, ' : ' `pm`.`meta_id`, ' );

				$query .= '`pm`.`meta_value` AS `lat`,	
					COALESCE(' . $pmtables . ') AS `lng`
					FROM
					`' . $meta_table  . '` `pm` ';

				foreach ( $longitude_fields as $idx => $lng ) {
					$query .= "LEFT JOIN `$meta_table` `pm$idx` ON ( `pm`.`meta_key`='{$latitude_fields[ $idx ]}' AND `pm$idx`.`meta_key`='{$longitude_fields[ $idx ] }' AND `pm`.`{$type}_id`=`pm$idx`.`{$type}_id` )\n";
				}

				$query .= 'WHERE pm.meta_key IN (\'' . implode( "','", $latitude_fields ) . '\')';
				$query .= ' AND COALESCE(' . $pmtables . ') IS NOT NULL';

				$res = $wpdb->get_results( $query, ARRAY_A ); // @codingStandardsIgnoreLine

				$func = "updated_{$type}_meta";

				foreach ( $res as $row ) {
					$geojson = array(
						'type' => 'Feature',
						'geometry' => array(
							'type' => 'Point',
							'coordinates' => array( $row['lng'], $row['lat'] ),
						),
						'properties' => array(),
					);

					$meta_key = WP_GeoMeta::$latlngs_index[ $row['meta_key'] ]['geo'];

					$this->$func( $row['meta_id'], $row['obj_id'], $meta_key, $geojson );
				}
			}
		}

		/**
		 * For a given set of Meta IDs, determine which meta IDs should actually be deleted.
		 *
		 * In the case of lat/lng data, we don't know which meta_id ended up in the geometa table (as fk_meta_id)
		 * so we need to add both to the list of meta_ids.
		 *
		 * Note that this WILL break having multiple meta keys with different values, but if you're doing that
		 * I don't know how you could possibly be separating out the lat/lng pairs anyways
		 *
		 * @param array  $meta_ids The Meta IDs that will be deleted.
		 * @param string $type The type of object whose meta is being deleted.
		 * @param int    $object_id The ID of the object whose meta is being deleted.
		 * @param string $meta_key The name of the meta key which is being deleted.
		 * @param string $meta_value The value which is being deleted.
		 *
		 * @return The array of meta IDs to delete.
		 */
		public function wpgm_pre_delete_geometa( $meta_ids, $type, $object_id, $meta_key, $meta_value ) {
			global $wpdb;

			if ( ! array_key_exists( $meta_key, WP_GeoMeta::$latlngs_index ) ) {
				return $meta_ids;
			}

			$meta_ids = array_map( 'absint', $meta_ids );
			$meta_ids = array_filter( $meta_ids );

			if ( empty( $meta_ids ) ) {
				return $meta_ids;
			}

			$table = _get_meta_table( $type );
			if ( ! $table ) {
				return $meta_ids;
			}

			$thepair = WP_GeoMeta::$latlngs_index[ $meta_key ];

			$the_other_field = ( $thepair['lat'] === $meta_key ? $thepair['lng'] : $thepair['lat'] );

			$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE {$type}_id = %d AND meta_key = %s", array( $object_id, $the_other_field ) ), ARRAY_A ); // @codingStandardsIgnoreLine
			
			$id_column = ( 'user' === $type ) ? 'umeta_id' : 'meta_id';
			
			if(is_array($meta) && array_key_exists($id_column, $meta)) {
				$meta_ids[] = $meta[ $id_column ];
			}

			return $meta_ids;
		}

		/**
		 * Callback to turn $this->extra_sql into absolute paths.
		 *
		 * @param array $sql_files An array of extra SQL files to load functions from.
		 */
		public function wpgm_extra_sql_functions( $sql_files ) {
			foreach ( $this->extra_sql as $extra_sql ) {
				$full_path = dirname( __FILE__ ) . '/sql/' . $extra_sql;
				if ( ! in_array( $full_path, $sql_files, true ) ) {
					$sql_files[] = $full_path;
				}
			}
			return $sql_files;
		}
	}

}
