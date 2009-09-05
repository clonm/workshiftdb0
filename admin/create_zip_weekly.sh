#!/bin/bash
basedir=`dirname "$0"`
cd "$basedir"
/usr/local/bin/php create_zip.php admin optimize quick mail
