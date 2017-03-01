#!/bin/bash

# This script runs phpcbf on the PHP files in the project to apply the auto-corrections that phpcbf can do.
# For safety you should commit before running this, just in case it breaks something.

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $DIR/..

phpcs --standard=WordPress ./wp-geoutil.php
phpcs --standard=WordPress ./wp-geometa.php
phpcs --standard=WordPress ./wp-geometa-lib-loader.php
phpcs --standard=WordPress ./wp-geoquery.php
