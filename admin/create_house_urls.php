<?php
$houses = array('ath','aca','caz','clo','con','dav','euc','fen','hip','hoy',
		'kid','kng','lot','nsc','rid','roc','she','stb','wil','wol','co');
error_reporting((E_ALL | E_RECOVERABLE_ERROR) & ~E_STRICT);

foreach ($houses as $house) {
  print "$house:<br>\n";
  symlink($_SERVER['DOCUMENT_ROOT'] . '/public_html/',$_SERVER['DOCUMENT_ROOT'] . "/$house");
}