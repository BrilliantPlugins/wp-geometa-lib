WP-GeoMeta-Lib
==============
<img align="right" src="https://raw.githubusercontent.com/BrilliantPlugins/wp-geometa-lib/media/img/logo_128.png">
WP-GeoMeta-Lib is a spatial framework for WordPress. It provides a solid foundation for spatial data using MySQL's native spatial support. With WP-GeoMeta-Lib you can store and search spatial metadata like you do any other metadata, but using MySQL spatial indexes.

WP-GeoMeta-Lib was created with developers in mind. If you find it cumbersome, buggy or missing features, let us know! 

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
- [Hacking](#hacking)
- [Quotes](#quotes)

<!-- /toc -->

Quick Start
-----------

1. Download WP-GeoMeta-Lib to your plugin. 
 
 ```
 	git clone https://github.com/BrilliantPlugins/wp-geometa-lib
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

TL;DR: WordPress 4.1, MySQL 5.6.1

[More details in SERVER_REQUIREMENTS.md](img/SERVER_REQUIREMENTS.md)

Hacking
-------

Interested on what's going on under the hood? Dive in to the code (it should be well documented) and 
check out [HACKING.md](docs/HACKING.md).

Quotes
-----
 * "The ACF of Geo Queries" -- Nick
 * "No matter where you go, there you are"
