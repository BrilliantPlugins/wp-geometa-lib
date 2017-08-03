WP-GeoMeta-Lib
==============
<img align="right" src="https://raw.githubusercontent.com/BrilliantPlugins/wp-geometa-lib/media/img/logo_128.png">
WP-GeoMeta-Lib is a spatial library for WordPress plugins. It provides a solid foundation 
for spatial data using MySQL's native spatial support. With WP-GeoMeta-Lib you
can store and search spatial metadata like you do any other metadata, but using MySQL spatial indexes.

If your plugin or website uses location data, you should be using WP-GeoMeta-Lib.
WP-GeoMeta-Lib makes using spatial data *easy* and *efficient*. 

WP-GeoMeta-Lib was created with developers in mind. If you find it cumbersome, buggy or missing features, let us know! 

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

3. Set up an activation hook to install WP-GeoMeta-lib when your plugin activates.

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

Tell Me More
------------

### Why should I use WP-GeoMeta-lib

We believe that WP-GeoMeta-lib is the best and easiest way to work spatial data in WordPress. If an opinion alone isn't enough, [see some of our reasons](docs/WHY_WPGEOMETA.md) and decide on your own.

### What are the server requirements for WP-GeoMeta-Lib? 

You'll need at leastWordPress 4.1 and MySQL 5.6.1. [More details here](docs/SERVER_REQUIREMENTS.md).

### How does it work? 

WP-GeoMeta-lib uses WordPress hooks to detect when spatial metadata is being stored, or when it's being queried. It then routes the spatial data, or the spatial part of the query to a set of tables that support spatial data. 

Interested in contributing? Dive in to the code (it should be well documented) and check out [HACKING.md](docs/HACKING.md).
