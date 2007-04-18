<pre>
hi

<?php
#system("ps -f -A");
#system("ps -f -A; /usr/local/bin/php -r \"print posix_getppid();\" 2>&1");
#system("whoami");
/*$process = proc_open('./test.cgi',array(array("pipe","r"),array("pipe","w"),array("pipe","w")),$pipes);
echo fread($pipes[1],1024) . fread($pipes[2],1024);
for ($ii = 0; $ii < 3; $ii++) {
  fclose($pipes[$ii]);
}
echo proc_close($process);*/
#system('./wrapper.php 2>&1');
#system('./c.cgi 2>&1');
system("whoami");
#print_r($_GLOBALS);
#print_r($_SERVER);
system("/usr/sbin/suexec usca usca test.cgi 2>&1");
?>
