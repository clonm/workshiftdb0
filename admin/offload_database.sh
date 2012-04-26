#!/bin/bash
basedir=`dirname "$0"`
cd "$basedir"
/usr/local/bin/php offload_database.php admin
