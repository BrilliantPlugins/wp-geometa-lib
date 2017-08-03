Why WP-GeoMeta-Lib?
===================

In WordPress all metadata goes into a big meta table regardless of what type of data it is.  All values are stored 
as longtext and there's not even an index on the column. This is fine for looking up values if you 
know the post ID and the key name. It's less than ideal if you need to search for a certain value
and it's absolutely terrible if you want to store or search for spatial data.

WP-GeoMeta-Lib detects when you are storing or querying spatial data and transparently re-routes those 
data and queries to a set of spatial metadata tables. These spatial metadata tables are indexed and give you
the ability to use all of the spatial functions built in to MySQL.

Integration with Other Plugins
------------------------------

Even if you don't need spatial queries yourself, using WP-GeoMeta-Lib allows other developers to 
query your data more easily. 

For example, if you were creating a restaurant locations plugin, and someone else had a neighborhood
boundary plugin, the website developer could query which neighborhood a restaurant is in, or which
restaurants are within a given neighborhood. 


Why not separate lat and long fields?
-------------------------------------

Storing lat and long in separate fields means that you have to implement your own 
[complicated queries](http://stackoverflow.com/questions/20795835/wordpress-and-haversine-formula)
if you want to search by distance. You won't be able to store lines or polygons, 
there's no indexing on your points and geographers everywhere will cry. 

### OK, fine, but I really need separate fields

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
