#!/bin/bash
basedir='/home/bsccoo5/public_html/workshift'
cd $basedir/admin/
/usr/local/bin/php $basedir/admin/create_zip.php admin optimize all mail
