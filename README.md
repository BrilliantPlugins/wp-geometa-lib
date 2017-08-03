WP-GeoMeta-Lib
==============
<img align="right" src="https://raw.githubusercontent.com/BrilliantPlugins/wp-geometa-lib/media/img/logo_128.png">
WP-GeoMeta-Lib is a spatial framework for WordPress. It provides a solid foundation 
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

### [Why should I use WP-GeoMeta-Lib](docs/WHY_WPGEOMETA.md)?

### What are the server requirements for WP-GeoMeta-Lib? 

At leastWordPress 4.1 and MySQL 5.6.1. [More details in SERVER_REQUIREMENTS.md](docs/SERVER_REQUIREMENTS.md)

### Hacking

Interested on what's going on under the hood? Dive in to the code (it should be well documented) and 
check out [HACKING.md](docs/HACKING.md).
