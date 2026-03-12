#!/bin/bash

# This script runs phpcbf on the PHP files in the project to apply the auto-corrections that phpcbf can do.
# For safety you should commit before running this, just in case it breaks something.

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $DIR/..

php -l ./wp-geometa-lib-loader.php
php -l ./wp-geometa-lib.php
php -l ./wp-geoutil.php
php -l ./wp-geometa.php
php -l ./wp-geoquery.php
