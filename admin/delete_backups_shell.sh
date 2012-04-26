#!/usr/local/bin/bash
basedir=`dirname "$0"`
cd "$basedir"
/usr/local/bin/php delete_backups_shell.php admin
