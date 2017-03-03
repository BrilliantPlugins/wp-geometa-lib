#!/bin/bash

# This script will run phpcs on all the php files in the project to report any violations of the WordPress coding standard.

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $DIR/..

phpcs --standard=WordPress ./wp-geometa-lib-loader.php
phpcs --standard=WordPress ./wp-geometa-lib.php
phpcs --standard=WordPress ./wp-geoutil.php
phpcs --standard=WordPress ./wp-geometa.php
phpcs --standard=WordPress ./wp-geoquery.php

echo "Done. If this is the only output, all files passed."
