#!/bin/bash

# This script runs phpcbf on the PHP files in the project to apply the auto-corrections that phpcbf can do.
# For safety you should commit before running this, just in case it breaks something.

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $DIR/..

phpcbf --standard=WordPress ./wp-geometa-lib-loader.php
phpcbf --standard=WordPress ./wp-geometa-lib.php
phpcbf --standard=WordPress ./wp-geoutil.php
phpcbf --standard=WordPress ./wp-geometa.php
phpcbf --standard=WordPress ./wp-geoquery.php
