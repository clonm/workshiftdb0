#!/usr/bin/perl
use CGI;
$query = new CGI;
$,="<br>";
print "Content-type: text/html\n\n";
print $query->param;
print `whoami`;
print $$;
exit;
