<?php
function str_split($str)
{
  $str_array=array();
  $len=strlen($str);
  for($i=0;$i<$len;$i++) $str_array[]=$str{$i};
  return $str_array;
}
?>