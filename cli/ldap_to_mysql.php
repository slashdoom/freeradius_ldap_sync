<?php

  $root = realpath(dirname(__FILE__));
  
  include_once(realpath($root.'/../config/config.php'));
  
  echo "\n\r\n\r";
  echo $db_rw_user;
  echo "\n\r\n\r";
  
?>
