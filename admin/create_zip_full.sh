#!/usr/local/bin/bash
basedir=`dirname "$0"`
cd "$basedir"
/usr/local/bin/php -f create_zip.php admin optimize all mail $1
