Server Requirements
===================

WordPress
---------
This library supports storing spatial metadata for posts, users, comments and
terms. 

Setting, getting and querying values should work in 4.1 with some missing functionality. 
Running orderby doesn't work until 4.2
Searching term metadata arrived in WordPress 4.4, but other
functionality should still work in older versions of WordPress.

MySQL
-----
MySQL 5.6.1 or higher is strongly recommended. Lower than MySQL 5.1.72 is untested.

WP_GeoMeta will probably work on MySQL 5.4, but spatial support was pretty weak 
before version 5.6.1. 

Before MySQL 5.6.1 spatial functions worked against the mininum bounding rectangle 
instead of the actual geometry.

MySQL 5.7 brough spatial indexes to InnoDB tables. Before that only MyISAM tables
supported spatial indexes. Anything else required a full table scan. 

If you are using MySQL 5.7, good for you, and consider converting your geo tables
to InnoDB! (and let me know how it goes).

PHP
---
PHP 5.2.4 and higher are supported, just like WordPress's minimum version.
Please report any PHP errors you come across and we'll fix them up.
