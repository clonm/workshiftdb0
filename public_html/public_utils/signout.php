<?
Header( "HTTP/1.1 301 Moved Permanently" );
$uri = str_replace('signout.php','signoff.php',$_SERVER['REQUEST_URI']);
Header( "Location: http" .  (array_key_exists('HTTPS',$_SERVER) && $_SERVER['HTTPS'] == 'on'?'s':'') .
        "://{$_SERVER['HTTP_HOST']}:{$_SERVER['HTTP_PORT']}{$uri}");
?> 
