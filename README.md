WP-GeoMeta-Lib
==============

WP-GeoMeta-Lib is a spatial framework for WordPress.  It provides a solid foundation
for spatial data using MySQL's native spatial support. With WP-GeoMeta-Lib you can store 
and search spatial metadata like you do any other metadata, but using MySQL spatial indexes.

WP-GeoMeta-Lib was created with developers in mind. If you find it cumbersome, buggy or 
missing features, let us know! 

<!-- #toc -->

- [Quick Start](#quick-start)
- [Usage](#usage)
    - [Writing and Reading Data](#writing-and-reading-data)
    - [Querying](#querying)
        - [Support Comparison Operations](#support-comparison-operations)
        - [ORDER BY](#order-by)
    - [WordPress Hooks](#wordpress-hooks)
        - [Filters](#filters)
        - [Actions](#actions)
    - [Calling Spatial Functions From PHP](#calling-spatial-functions-from-php)
        - [Examples](#examples)
    - [Custom Auxiliary Functions](#custom-auxiliary-functions)
- [Why WP-GeoMeta-Lib?](#why-wp-geometa-lib)
    - [Integration with Other Plugins](#integration-with-other-plugins)
    - [Why not separate lat and long fields?](#why-not-separate-lat-and-long-fields)
        - [OK, fine, but I really need separate fields](#ok-fine-but-i-really-need-separate-fields)
- [Important Notes](#important-notes)
- [Server Requirements](#server-requirements)
    - [WordPress](#wordpress)
    - [MySQL](#mysql)
    - [PHP](#php)
- [Hacking](#hacking)
- [Quotes](#quotes)

<!-- /toc -->

Quick Start
-----------

1. Download WP-GeoMeta-Lib to your plugin. 
 
 ```
 	git clone https://github.com/cimburadotcom/wp-geometa-lib.git 
 ```
2. Require `wp-geometa-lib-loader.php` in your plugin code.
 
 ```
   require_once('wp-geometa-lib/wp-geometa-lib-loader.php');
 ```

3. Set up an activation hook to install WP_GeoMeta when your plugin activates.

 ```
   register_activation_hook( __FILE__ , array('WP_GeoMeta','install'));
 ```

 If you already have your own activation hook, you can simply add WP_GeoMeta::install() to your hook.

4. Save GeoJSON metadata!
 
 ```
   update_post_meta( 15, 'my_meta_key', '[{GeoJSON}](http://geojson.org)' );
 ```

Once you are storing spatial data you (or anyone else!) can query it
using spatial queries!

Usage
-----

### Writing and Reading Data
Store GeoJSON strings as metadata like you would for any other metadata. 

Add geometry to a post:

    $single_feature = '{ "type": "Feature", "geometry": {"type": "Point", "coordinates": [102.0, 0.5]}, "properties": {"prop0": "value0"} }';
    add_post_meta(15,'singlegeom',$single_feature,false);

Update the post geometry: 	

    $single_feature = '{ "type": "Feature", "geometry": {"type": "Point", "coordinates": [-93.5, 45]}, "properties": {"prop0": "value0"} }';
    update_post_meta(15,'singlegeom',$single_feature,false);

Read GeoJSON back from the post;

    $single_feature = get_post_meta(15, 'singlegeom'); 
	print $singlegeom;
	// '{ "type": "Feature", "geometry": {"type": "Point", "coordinates": [-93.5, 45]}, "properties": {"prop0": "value0"} }';

### Querying

Querying is done through the WP_Query meta_query argument. See
[WP Spatial Capabilities Check](https://wordpress.org/plugins/wp-spatial-capabilities-check/) 
to generate a list of supported spatial functions for your system. 

There are three styles of queries supported, to cover three different classes of spatial functions

1. Query comparing geometries

 This style of query is for all spatial functions which accept two geometries as arguments and which 
 return a boolean as a result. For example ST_INTERSECTS, CONTAINS or MBROverlaps. 
 
 The meta_query _compare_ is the function to use, and the _value_ should be a GeoJSON representation
 of the geometry to use for the second argument. The geometry meta field indicated by the _key_ parameter
 will be used as the first argument to the _compare_ function.

 ```
    $q = new WP_Query( array(
    	'meta_query' => array(
    		array(
    			'key' => 'singlegeom',
    			'compare' => 'ST_INTERSECTS',
    			'value' => '{"type":"Feature","geometry":{"type":"Point","coordinates":[-93.5,45]}}',
    		)
    	)
    ));
    
    while($q->have_posts() ) {
    	$q->the_post();
    	print "\t* " . get_the_title() . "\n";
    }
 ```

2. Query geometry properties

 This style of query is for all spatial functions which accept a single geometry as an argument and
 which return a boolean as a result. For example ST_IsSimple, IsClosed or ST_IsEmpty.
 
 The _compare_ argument should be the function just like above, but no value is needed.

 ```
    $q = new WP_Query(array( 
    	'meta_query' => array( 
    		array( 
    		'key' => 'wpgeometa_test',
    		'compare' => 'ST_IsEmpty'
    		)
    	)));
 ```

3. Compare the results of geometry functions

 This style of query is for spatial functions which accept a single geometry as an argument but return
 a non-boolean response. For example, GLength, ST_Area or ST_SRID.
 
 In these queries you may want to use a normal meta_query comparison (=, >, BETWEEN, etc.) but against
 the result of a spatial function. To accomodate this type of case, you will need to add an additional
 parameter _geom_op_. 
 
 The _key_, _compare_ and _value_ are used in the regular WP_Query way, but the comparison will be 
 made against the result of applying the geometry function to the spatial metadata specified.

 ```
    $q = new WP_Query(array(
    	'meta_query' => array(
    		array( 
    		'key' => 'wpgeometa_test',
    		'compare' => '>',
    		'value' => '100',
    		'geom_op' => 'NumPoints'
    	)
    	))); 
 ```

#### Support Comparison Operations

Any spatial operation that takes two geometries and returns a boolean, 
or which takes one geometry and returns a boolean or a value is
supported, if your version of MySQL supports it. 

The following function should work, if your install of MySQL supports them: 

<table>
<tr>
<td>Area</td>
<td>Contains</td>
<td>Crosses</td>
<td>Dimension</td>
</tr>
<tr>
<td>Disjoint</td>
<td>Equals</td>
<td>GLength</td>
<td>GeometryType</td>
</tr>
<tr>
<td>Intersects</td>
<td>IsClosed</td>
<td>IsEmpty</td>
<td>IsRing</td>
</tr>
<tr>
<td>IsSimple</td>
<td>MBRContains</td>
<td>MBRCoveredBy</td>
<td>MBRDisjoint</td>
</tr>
<tr>
<td>MBREqual</td>
<td>MBREquals</td>
<td>MBRIntersects</td>
<td>MBROverlaps</td>
</tr>
<tr>
<td>MBRTouches</td>
<td>MBRWithin</td>
<td>NumGeometries</td>
<td>NumInteriorRings</td>
</tr>
<tr>
<td>NumPoints</td>
<td>Overlaps</td>
<td>SRID</td>
<td>ST_Area</td>
</tr>
<tr>
<td>ST_Contains</td>
<td>ST_Crosses</td>
<td>ST_Difference</td>
<td>ST_Dimension</td>
</tr>
<tr>
<td>ST_Disjoint</td>
<td>ST_Distance</td>
<td>ST_Distance_Sphere</td>
<td>ST_Equals</td>
</tr>
<tr>
<td>ST_GeometryType</td>
<td>ST_Intersects</td>
<td>ST_IsClosed</td>
<td>ST_IsEmpty</td>
</tr>
<tr>
<td>ST_IsRing</td>
<td>ST_IsSimple</td>
<td>ST_IsValid</td>
<td>ST_Length</td>
</tr>
<tr>
<td>ST_NumPoints</td>
<td>ST_Overlaps</td>
<td>ST_SRID</td>
<td>ST_Touches</td>
</tr>
<tr>
<td>ST_Within</td>
<td>Touches</td>
<td>Within</td>
<td></td>
</tr>
</table>

To see what your install of MySQL supports, install 
[WP Spatial Capabilities Check](https://wordpress.org/plugins/wp-spatial-capabilities-check/). 
We recommend using MySQL 5.6.1 or higher since it included many important updates to 
spatial operators.

#### ORDER BY

orderby with named meta clauses should work.

1) Single arg orderby (eg. Dimension, GLength, ST_Area)

    $wpq = new WP_Query(array(
    	'post_type' => 'geo_test',
    	'orderby' => ARRAY( 'dimensions' => 'ASC',  'titlemeta' => 'ASC' ),
    	'meta_query' => array(
    		'dimensions' => array( 
    			'key' => 'wpgeometa_test',
    			'geom_op' => 'Dimension'
    		)
		)));

2) Two argument function that returns a value, eg. ST_Distance. Note that I use 
```'type' => 'DECIMAL(10,7)'``` so that sorting is done numerically, instead of alphabetically.

    $wpq = new WP_Query(array(
    	'post_type' => 'geo_test',
    	'orderby' => 'distance',
    	'order' => 'ASC',
    	'meta_query' => array(
    		'distance' => array( 
    			'key' => 'wpgeometa_test',
    			'compare' => 'ST_Distance',
    			'value' => '{"type":"Feature","geometry":{"type":"Polygon","coordinates":[[[-1.26,1.08],[-1.26,1.09],[-1.21,1.09],[-1.21,1.08],[-1.26,1.08]]]}}',
    			'type' => 'DECIMAL(10,7)'
    		)
    	))); 


### WordPress Hooks
-------------------

The power of WordPress come partly from its Hooks system. WP-GeoMeta-Lib tries to provide the neccessary hooks
so that you can extend it to suit your needs. If there's a hook that you need send me a pull request or file an issue.

#### Filters

 * wpgm_pre_metaval_to_geom

 This filter is called right before WP-GeoMeta tries to convert the incoming meta value 
 to geometry. It is used internally to handle separate latitude and longitude values and
 could be used to support other unusual situations. If you just need to convert a non-geojson
 geometry to WKT, you should use wpgq_metaval_to_geom instead.

 Usage:
 ```
	add_filter( 'wpgm_pre_metaval_to_geom', 'myplugin_handle_pre_metaval', 10, 2 );

	/*
	 * @param array  $meta_args Array with the meta_id that was just saved, the object_id it was for, the meta_key and meta_values used.
	 *  $meta_args[0] -- meta_id from insert.
	 *  $meta_args[1] -- object_id which this applies to.
	 *  $meta_args[2] -- meta key.
	 *  $meta_args[3] -- the meta value.
	 *
	 * @param string $object_type Which WP type is it? (comment/user/post/term).
	 */
	public static function handle_latlng_meta( $meta_args, $object_type ) {
		// Return early if it's not the key we're looking for.
		if ( 'special meta key' !== $meta_args[2] ) {
			return $meta_args;
		}

		// Do some stuff, then return $meta_args.
		return $meta_args;
	}
 ```

 * wpgm_pre_delete_geometa

 This filter is called after a meta value has been deleted from the regular meta table, right 
 before WP-GeoMeta deletes the corresponding value from the geometa table. Deletions are 
 done based on the regular meta table's meta ID. This filter is used internally to delete
 the geo meta value when a latitude or longitude meta value is deleted from the non-geo 
 meta tables. 

 Usage:
 ```
	add_filter( 'wpgm_pre_delete_geometa', 'special_delete_scenario', 10, 5 );

	/*
	 * @param array  $meta_ids The Meta IDs that will be deleted.
	 * @param string $type The type of object whose meta is being deleted.
	 * @param int    $object_id The ID of the object whose meta is being deleted.
	 * @param string $meta_key The name of the meta key which is being deleted.
	 * @param string $meta_value The value which is being deleted.
	 */
	public function special_delete_scenario( $meta_ids, $type, $object_id, $meta_key, $meta_value ) {

		if ( 'special meta key' !== $meta_key ) {
			return $meta_ids;
		}

		// Do some stuff, then return $meta_ids;
		return $meta_ids;
	}
 ```

 * wpgm_geoquery_srid

 This filter is called during plugins_loaded. It sets the [SRID](https://en.wikipedia.org/wiki/Spatial_reference_system) 
 that will be used when storing values in the database. The default value is 4326 
 (for EPSG:4326), which is the standard for GeoJSON. 

 MySQL doesn't support ST_Transform, and will complain if two geometries being compared
 have different SRIDs. As such, this option is dangerous and should be left alone unless 
 you know what you're doing.

 * wpgm_metaval_to_geom

 This filter is called within WP_GeoUtil::metaval_to_geom. It offers an opportunity to 
 support non-GeoJSON geometry types. 

 Functions implementing this filter should either return the incoming $metaval untouched
 or return WKT (best) or GeoJSON (will work).

 Usage:
 ```
	add_filter( 'wpgm_metaval_to_geom', 'kml_to_geometry' );

	/**
	  * @param string $metaval The metavalue that we're about to store.
	  */
	function kml_to_geometry( $metaval ) {

		if ( !is_kml( $metaval ) ) {
			return $metaval;
		}

		$wkt = kml_to_wkt( $metaval );

		return $wkt;
	}
 ```

 * wpgm_geom_to_geojson

 This filter is called when converting a geometry from the database into GeoJSON
 so it can be displayed on a map (or whatever). 

 This could be used to do transformations or other alterations to geometries before
 displaying them. 

 Usage:
 ```
	add_filter( 'wpgm_geom_to_geojson', 'myplugin_geom_to_geojson' );

	/**
	  * @param string $wkt The well known text representation of the geometry
	  from the database.
	  */
	function myplugin_geom_to_geojson( $wkt ) {
		$geojson = myfunc_wkt_to_geojson( $wkt );

		// Do something to the geojson
		return $geojson;
	}
 ```

 * wpgm_known_capabilities

 This filter is available so you can make your custom MySQL functions known to other users of WP-GeoMeta-Lib. 
 Your function will be included in the list returned by `WP_GeoUtil::get_capabilities()`, if it is in fact available
 in MySQL.

 You can also use it to detect if an optional library is installed. 

 NOTE: 
 During your plugin activation you should run `WP_GeoUtil::get_capabilities( true );`, otherwise
 the cached list of capabilites will continue to be used.

 Usage: 
 ```
	add_filter( 'wpgm_known_capabilities', 'myplugin_add_support_for_my_func' );

	function myplugin_add_support_for_my_func( $all_funcs ) {
		$all_funcs[] = 'my_custom_mysql_function';
		return $all_funcs;
	}

	// ... later ...

	$all_caps = WP_GeoUtil::get_capabilities();
	if ( in_array( 'my_custom_mysql_function', $all_caps ) ) {
		// Do my stuff
	} else {
		// Notify the admin
	}
 ```

 * wpgm_extra_sql_functions

 This filter allows you to add additional custom SQL functions to MySQL. Combined with wpgm_known_capabilites
 you can fully integrate your custom SQL into WP-GeoMeta-Lib. 

 This filter produces an array of file system paths to files containing SQL functions. The files should use
`$$` as the delimiter for the function. Please see any of the .sql files in this project for examples.

 Usage:
 ```
	add_filter( 'wpgm_extra_sql_functions', 'myplugin_add_extra_sql' );

	function myplugin_add_extra_sql( $all_sql_files ) {

		$my_sql_files = array(
			'custom_func1.sql',
			'custom_func2.sql'
		);

		foreach( $my_sql_files as $my_file ) {
			$full_path = dirname( __FILE__ ) . '/sql/' . $my_file;

			if ( !in_array( $full_path, $all_sql_files ) ) {
				$all_sql_files[] = $full_path;
			}
		}

		return $all_sql_files;
	}
 ```

#### Actions

 * wpgm_populate_geo_tables

 This action is called at the end of WP_GeoMeta->populate_geo_tables() to give you
 an opportunity to populate the geo metatables with any non-GeoJSON types of geometry
 you are supporting. It is used internally to support populating the geo metatables
 with any latitude/longitude pairs added through WP_GeoMeta::add_latlng_field. 

 Usage:
 ```
	add_filter( 'wpgm_populate_geo_tables', 'myplugin_populate_kml' );

	function myplugin_populate_kml() {
		global $wpdb;

		$wpgeometa = WP_GeoMeta::get_instance();

		$query = "SELECT post_id, meta_id, meta_key, meta_value 
			FROM wp_postmeta 
			WHERE meta_value LIKE '<?xml%/kml/2.2%'";

		$res = $query->get_results( $query, ARRAY_A );
		foreach( $res as $row ) {
			// We don't have to convert KML to WKT because the filters we have set up will get called.
			$wpgeometa->updated_post_meta( $row[ 'meta_id' ], $row[ 'post_id' ], $row[ 'meta_key' ], $row[ 'meta_value' ] );
		}
	}
 ```


### Calling Spatial Functions From PHP

 Sometimes you might just need to run a spatial operation on a spatial value. WP-GeoMeta-Lib makes
 this easy! 

 You can call any spatial function your install of MySQL supports as a static method of WP_GeoUtil. 
 These functions conveniently accept GeoJSON geometry so you don't have to convert your spatial
 data into Well Known Text (WKT) for MySQL. 

#### Examples

 Check if two GeoJSON shapes intersect
 ```
 $do_they_intersect = WP_GeoUtil::ST_Intersects({PointGeoJSON},{PolygonGeoJSON});
 ```

 Union (combine) two GeoJSON polygons
 ```
 $spatial_union = WP_GeoUtil::ST_Union({PolygonGeoJSON_1},{PolygonGeoJSON_2});
 ```

### Custom Auxiliary Functions

 Besides the default spatial functions included with MySQL, WP-GeoMeta-Lib 
 provides the filter wpgq_known_capabilities which lets you add support
 for your own spatial SQL functions. These can be UDF (User Defined Functions) or Stored Functions. 

 WP-GeoMeta-Lib includes several stored functions for your convenience.

 The functions included are: 

 * **wp_buffer_point_m( p POINT, radius FLOAT, segments INT)**

	Buffer a point by a number of meters. Returns a polygon approximating a circle.

	- _p_ A single point geometry with coordinates in EPSG:4326 (the common latitude/longitude format, like 45.0,-93.3)
	- _radius_ The distance to buffer the point, in meters.
	- _segments_ The number of segments per quarter of a circle. Eg. If you set this to 4, then your resulting polygon will have 16 segments.

 * **wp_buffer_point_mi( p POINT, radius FLOAT, segments INT)**

	Buffer a point by a number of miles. Returns a polygon approximating a circle.

	- _p_ A single point geometry with coordinates in EPSG:4326.
	- _radius_ The distance to buffer the point, in miles.
	- _segments_ The number of segments per quarter of a circle. Eg. If you set this to 4, then your resulting polygon will have 16 segments.

 * **wp_buffer_point_real( p POINT, radius FLOAT, segments INT, eradius INTEGER)**

	Buffer a point assuming an earth with a specified radius. Returns a polygon approximating a circle.

	- _p_ A single point geometry with coordinates in EPSG:4326.
	- _radius_ The distance to buffer the point, in any units.
	- _segments_ The number of segments per quarter of a circle. Eg. If you set this to 4, then your resulting polygon will have 16 segments.
	- _eradius_ The radius of the earth in the same units as _radius_

 * **wp_distance_point_m( p1 POINT, p2 POINT)**
	
	Get the distance between two points in meters.

	- _p1_ A single point geometry with coordinates in EPSG:4326.
	- _p2_ A single point geometry with coordinates in EPSG:4326.

 * **wp_distance_point_mi( p1 POINT, p2 POINT)**
	
	Get the distance between two points in miles.

	- _p1_ A single point geometry with coordinates in EPSG:4326.
	- _p2_ A single point geometry with coordinates in EPSG:4326.

 * **wp_distance_point_real( p1 POINT, p2 POINT, radius FLOAT)**

	Get the distance between two points for a given radius of the earth.

	- _p1_ A single point geometry with coordinates in EPSG:4326.
	- _p2_ A single point geometry with coordinates in EPSG:4326.
	- _radius_ The radius of the earth in the units you want your results in.

 * **wp_first_geom( p GEOMETRY )**

	Get the first geometry from a multi-geometry. If _p_ is not a multi-geometry, it will be returned unchanged.

	- _p_ A geometry object.

 * **wp_point_bearing_distance_to_line_m(p POINT, bearing FLOAT, distance FLOAT)**

	Create a linestring given a starting point, a bearing and a distance in meters.

	_p_ A single point geometry with coordinates in EPSG:4326.
	_bearing_ The bearing to travel in degrees. 0 degrees is north.
	_distance_ The number of meters to travel.

 * **wp_point_bearing_distance_to_line_mi(p POINT, bearing FLOAT, distance FLOAT)**

	Create a linestring given a starting point, a bearing and a distance in miles.

	_p_ A single point geometry with coordinates in EPSG:4326.
	_bearing_ The bearing to travel in degrees. 0 degrees is north.
	_distance_ The number of miles to travel.

 * **wp_point_bearing_distance_to_line(p POINT, bearing FLOAT, distance FLOAT, eradius INTEGER)**

	Create a linestring given a starting point, a bearing, a distance and the radius of the earth.

	_p_ A single point geometry with coordinates in EPSG:4326.
	_bearing_ The bearing to travel in degrees. 0 degrees is north.
	_distance_ The distance to travel.
	_eradius_ The radius of the earth in the same units as _distance_.


 * **wp_point_bearing_distance_coord_pair(p POINT, bearing FLOAT, distance FLOAT, eradius INTEGER)**

	Get the point a given distance from a starting point on a given bearing for a given radius of the earth.

	_p_ A single point geometry with coordinates in EPSG:4326.
	_bearing_ The bearing to travel in degrees. 0 degrees is north.
	_distance_ The distance to travel.
	_eradius_ The radius of the earth in the same units as _distance_.


Why WP-GeoMeta-Lib?
-------------------

If your plugin or website uses location data, then you should be using WP-GeoMeta-Lib. WP-GeoMeta-Lib
makes using spatial data *easy* and *efficient*. 

All metadata goes into a big meta table, regardless of what type of data it is.  All values are stored 
as longtext and there's not even an index on the column. This is fine for looking up values if you 
know the post ID and the key name. It's less than ideal if you need to search for a certain value
and it's absolutely terrible if you want to store or search for spatial data. 

WP-GeoMeta-Lib detects when you are storing or querying spatial data and transparently re-routes those 
data and queries to a set of spatial metadata tables. These spatial metadata tables are indexed and give you
the ability to use all of the spatial functions built in to MySQL.


### Integration with Other Plugins

Even if you don't need spatial queries yourself, using WP-GeoMeta-Lib allows other developers to 
query your data more easily. 

For example, if you were creating a restaurant locations plugin, and someone else had a neighborhood
boundary plugin, the website developer could query which neighborhood a restaurant is in, or which
restaurants are within a given neighborhood. 


### Why not separate lat and long fields?

Storing lat and long in separate fields means that you have to implement your own 
[complicated queries](http://stackoverflow.com/questions/20795835/wordpress-and-haversine-formula)
if you want to search by distance. You won't be able to store lines or polygons, 
there's no indexing on your points and geographers everywhere will cry. 

#### OK, fine, but I really need separate fields

If you really need (or already are using) separate longitude and latitude fields
Using separate Latitude and Longitude fields is slightly more complex, but is 
supported by WP-GeoMeta. You will need to register your new latitude/longitude
meta keys so that WP-GeoMeta knows about them. You can do this any time after
plugins_loaded. 

```
add_action('plugins_loaded', function() {
	// WP_GeoMeta::add_latlng_field( <latitude field name>, <longitude field name>, <spatial meta_key name> );
	WP_GeoMeta::add_latlng_field( 'myplugin_lat', 'myplugin_lng', 'myplugin_geo' );
});
```

A few caveats with handling latitude and longitude:

 1. The spatial meta key will only be present in the wp_postmeta_geo table ( or
 other applicable geo metatable ). Any spatial queries will need to use the 
 spatial meta key you register. 
 2. There's a chance of conflicts. If your latitude or longitude field is named
 the same as another plugin's latitude or longitude field the resulting behavior 
 is undefined and unsupported. 

*Note*: The [WordPress Geodata meta keys](https://codex.wordpress.org/Geodata) are 
supported out of the box. 

Important Notes
---------------

* For more complex spatial operations you can always use ```$wpdb->query()``` with custom SQL.

* MySQL 5.6.1 brought **HUGE** improvements to its spatial capabilities. You should use ```WP_GeoUtil::get_capabilities()``` 
to see if the function you're about to use is available.

* Some MySQL spatial functions only work on the Bounding Box of the shape and not the actual geometry. For details about
when and why this is a problem, see [this 2013 blog post from Percona](https://www.percona.com/blog/2013/10/21/using-the-new-mysql-spatial-functions-5-6-for-geo-enabled-applications/).

Server Requirements
-------------------

### WordPress
This library supports storing spatial metadata for posts, users, comments and
terms. 

Setting, getting and querying values should work in 4.1 with some missing functionality. 
Running orderby doesn't work until 4.2
Searching term metadata arrived in WordPress 4.4, but other
functionality should still work in older versions of WordPress.

### MySQL

MySQL 5.6.1 or higher is strongly recommended. Lower than MySQL 5.1.72 is untested.

WP_GeoMeta will probably work on MySQL 5.4, but spatial support was pretty weak 
before version 5.6.1. 

Before MySQL 5.6.1 spatial functions worked against the mininum bounding rectangle 
instead of the actual geometry.

MySQL 5.7 brough spatial indexes to InnoDB tables. Before that only MyISAM tables
supported spatial indexes. Anything else required a full table scan. 

If you are using MySQL 5.7, good for you, and consider converting your geo tables
to InnoDB! (and let me know how it goes).

### PHP
PHP 5.2.4 and higher are supported, just like WordPress's minimum version.
Please report any PHP errors you come across and we'll fix them up.

Hacking
-------

Interested on what's going on under the hood? Dive in to the code (it should be well documented) and 
check out [HACKING.md](HACKING.md).

Quotes
-----
 * "The ACF of Geo Queries" -- Nick
 * "No matter where you go, there you are"
