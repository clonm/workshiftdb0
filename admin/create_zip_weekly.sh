#!/bin/bash
basedir='/home/bsccoo5/public_html/cvsworkshift/workshiftdb0'
cd $basedir/admin/
/usr/local/bin/php $basedir/admin/create_zip.php admin optimize quick mail
