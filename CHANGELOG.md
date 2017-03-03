Changes
-------

### 0.3.2
 * WP_GeoUtil::is_geom now has a shortcircuit for strings that don't even look like WKT
 * Updated documentation!
 * WP_GeoUtil::run_spatial_query now uses a non-warning-generating method to detect if a value is spatial or not
 * Updated WP_GeoUtil::is_geojson to detect non-GeoJSON without trying to parse it.
 * Improved metaval_to_geom
 * Improved handling of MySQL geometry function call results, wrapping bare geometry GeoJSON with {"type":"Feature", "geometry": {the geometry}}
 * Split out creation of tables from creation of custom SQL functions.
 * Added filter to allow plugin devs to include their own custom SQL function files.
 * Standardized filter name prefixes to wpgm\_ (old filters will stick around for a few versions, even though I don't think anyone is using them).
 * Standardized internal callback functions to use the same name as the filter that they're callbacks for.
 * Tests are now included and work within WP-GeoMeta-Lib instead of in WP-GeoMeta.
 * Allow WP_GeoUtil::get_capabilities to be run without accessing the options table and without caching the results.
 * Cleaner loader file, working towards using spl_autoload_register in a future release.
 * Only loads geoPHP if it's needed.

### 0.3.1
 * Support for custom MySQL functions (User Defined Functions and stored functions).
 * Built-in support for some functions which may be useful for working with Lat/Lng distances and bearings.
 * Fixed issue where duplicate function names would appear in get_capabilities result set.
 * Broke out WP_GeoUtil::geom_to_geojson() to its own function. 
 * Enhancements to WP_GeoUtil::is_geojson()
 * WP_GeoUtil::metaval_to_geom() no longer converts geometry from single to multi automatically. 

### 0.3.0: Blue Blazer Irregulars
 * Separated core functionality into a library

### 0.2.2
 * Added built-in support for the [WordPress Geodata standard](https://codex.wordpress.org/Geodata)
 * Added filter and action to handle arbitrary lat/lng pair metavalues
 * Added documentation for hooks and filters
 * Leaflet is now loaded locally instead of from the CDN

### 0.2.1
 * Handle multi-feature GeoJSON correctly in MySQL 5.7 (convert to GEOMETRYCOLLECTION)
 * Use ON DUPLICATE KEY UPDATE to combine added and updated postmeta handlers.

### 0.2.0: Penny Priddy
 * Upgrading no longer truncates and rebuilds the meta tables. 
 * Fix for joins so user meta should work again (umeta_id vs meta_id key name issue).
 * A beautiful dashboard! 
 * Plugin activation hooks so that deactivating/activating without upgrading will recreate database tables
 * Translation ready!
 * Portuguese translation!
 * Code documentation!
 * Changed geometry type so that all geometries are stored as multipoint to work across MySQL versions

### 0.1.1
 * Only x.x.0 releases will get code names
 * orderby should now work
 * Much cleaner joins
 * Minor fix for when upgrades occurs

### 0.1.0: Perfect Tommy
 * Will now work as a library or a plugin. 
 * Additional functions for getting data back into GeoJSON format.
 * Working well enough to submit to the plugin repo.
 * Support for single geometry functions in meta_queries.

### 0.0.2: New Jersey
 * Improved meta query capabilities. Now support sub queries, and uses standard meta-query syntax
 * Whitelist of known spatial functions in meta_query args. Allowed args set by detecting MySQL capabilities.
 * We now delete the spatial index on activation so that we don't end up with duplicate spatial keys
 * Populate geo tables on activation with any existing geojson values
 * Submitted ticket to dbDelta SPATIAL INDEX support: https://core.trac.wordpress.org/ticket/36948
 * Conform to WP coding standards
 * Explicitly set visibility on properties and methods

### 0.0.1: Emilio Lizardo
 * Initial Release


