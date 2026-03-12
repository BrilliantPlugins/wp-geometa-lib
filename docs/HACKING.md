Hacking on WP-GeoMeta-Lib
==========================

Send me your bug reports, suggestions and pull requests.

Please feel free to use this library in **your** plugin. Standardization makes
life easier for everyone. If this plugin isn't meeting your needs, or you have other 
great ideas, lets talk!

Overview
--------

### The Basics

There are two main classes: WP_GeoQuery and WP_GeoMeta

* ```WP_GeoMeta``` — Handles adding, updating and deleting spatial values.
* ```WP_GeoQuery``` — Handles spatial meta queries.

When a user runs add_post_meta (etc.) and passes in a GeoJSON string or GeoJSON compatible
array, WP_GeoMeta will store the geometry in a spatial column. 

WP_GeoQuery sets up a handler for the ```get_meta_sql``` action to spatial queries and orderby operations.

An additional class ```WP_GeoUtil``` handles data checking and conversion.

### Saving Spatial Metadata

WP_GeoMeta builds on both MySQL's spatial support and the WordPress meta data system.

On plugin activation WP-GeoMeta will create a parallel spatial set of meta tables. Where 
only wp_postmeta existed you will now also find wp_postmeta_geo. 

WP-GeoMeta uses the actions (added|updated|delete)_(comment|post|term|user)_meta to
do the right thing AFTER ```add_post_meta``` (etc.) have done their jobs. 

    $single_feature = '{ 
					"type": "Feature", 
					"geometry": {
						"type": "Point", 
						"coordinates": [-93.5, 45]
					}, 
					"properties": {
						"prop0": "value0"
					} 
	}';
    update_post_meta(48,'my_shape',$single_feature);

![Update_Post_Meta](https://raw.githubusercontent.com/BrilliantPlugins/wp-geometa-lib/media/img/update_post_meta.png)

Since [GeoJSON](http://geojson.org/) is the one true format for spatial data on the web, all getting and
setting of spatial data is done in this format. Someone could add a plugin 
to support other formats though!

FeatureClass objects and individual Feature objects will both be accepted. String
object and array representations of GeoJSON are be accepted.

WP-GeoMeta stores data in EPSG:4326 by default, which is (a) the official format
for GeoJSON and (b) the most common format for web maps.

WP-GeoMeta does't act on any of the ```get_{$meta_type}_meta``` filters because we want the 
orignal input data to be returned to the user with the GeoJSON properties it had at the
beginning. 

### Querying Spatial Metadata

WP_GeoQuery adds support to the ```meta_query``` argument (in WP_Query, get_posts, WP_User_Query, get_users, WP_Comment_Query and get_comments) for known spatial comparison operations.

It uses the `get_meta_sql` action to inspect and modify the query, re-routing the spatial portions of the query to the wp_postmeta_geo table.

This is the most fragile part of WP-GeoMeta-Lib, for now. It looks through the meta_query definition and generates replacement `INNER JOIN`  and `WHERE`
clauses. It then uses `str_replace` to alter the original SQL.

![WP_Query](https://raw.githubusercontent.com/BrilliantPlugins/wp-geometa-lib/media/img/wp_query.png)

See the [USAGE.md](USAGE.md) for examples of how to use WP_GeoQuery.
