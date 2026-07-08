#!/bin/bash
# Run PHPUnit tests

PHP_BIN="/Applications/MAMP/bin/php/php8.3.28/bin/php"
PHPUNIT="/usr/local/bin/phpunit"
CONFIG="/Applications/MAMP/htdocs/php8/theory/phpunit.xml"

cd /Applications/MAMP/htdocs/php8/theory

$PHP_BIN $PHPUNIT --configuration $CONFIG "$@"
