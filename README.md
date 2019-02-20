Copied from https://sourceforge.net/p/workshiftdb0/code/ because it's only
available in CVS which is being deprecated, and the tar.gz says it's corrupt.

I haven't run this yet but it should be possible to use this to re-create
oldworkshift.bsc.coop. It won't get your data though, just the code.

To set up databases:
Edit php_includes/Local_Settings.php with the host
On the mysql server, create some databases:
```
# mysql -u root
mysql> CREATE DATABASE bsc1933_shiftclo;
mysql> GRANT ALL PRIVILEGES ON bsc1933_shiftclo.* TO 'bsc1933'@'%' IDENTIFIED BY 's3cr3tpa55w0rd';
mysql> exit;
# cd /var/www/workshiftdb/admin/utils
# php create_table_all_houses.php clo
```
However, there are still databases that don't get created by this script, and
cause crashes. I'm hoping to be able to import the existing database(s) from
central instead of having to continue to reverse engineer them.
