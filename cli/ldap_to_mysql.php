#!/usr/bin/php
<?php

  $root = realpath(dirname(__FILE__));
  
  require_once(realpath($root.'/../config/config.php'));
  require_once(realpath($root.'/../lib/logging.php'));
  require_once(realpath($root.'/../lib/ldap.php'));
  require_once(realpath($root.'/../lib/mysql.php'));
  require_once(realpath($root.'/../lib/misc.php'));
  
  touch(realpath($root.'/../log/_default.log'));
  $logger = new logger(realpath($root.'/../log/_default.log'),$logging_level);

  $ldap_users = array();
  $mysql_users = array();
  
  echo "\n\rstarting sync...\n\r";
  $logger->debug("starting sync...");
  
  // get all users in group from ldap
  echo "reading ldap user...\n\r";
  $ldap_users = ldap_get_members(
      $ldap_fqdn,$ldap_port,$ldap_search_user,$ldap_search_pass,$ldap_user_group,$logging_level,realpath($root.'/../log/_ldap.log')
                                );
  // get all user in mysql db
  echo "reading mysql user...\n\r";
  $mysql_users = mysql_get_users($db_host,$db_rw_user,$db_rw_pass,$db_name,$logging_level,realpath($root.'/../log/_mysql.log'));

  // get users in ldap but not mysql
  echo "diff ldap against mysql users...\n\r";
  $diff_add = array_diff(array_column($ldap_users,'username'), $mysql_users);
  echo "diff mysql against ldap users...\n\r";
  // get users in mysql but not ldap
  $diff_rem = array_diff($mysql_users, array_column($ldap_users,'username'));

  // debug only
  echo " \r\nldap raw: \r\n";
  print_r($ldap_users);
  echo " \r\nldap: \r\n";
  print_r(array_column($ldap_users,'username'));
  echo " \r\nmysql: \r\n";
  print_r($mysql_users);
  echo " \r\nadd: \r\n";
  print_r($diff_add);
  echo " \r\nrem: \r\n";
  print_r($diff_rem);
  
  echo "\r\nstarting removals...\r\n";
  foreach ($diff_rem as $rem_user) {
    // logging only
    $logger->debug("starting removals...");
    echo "removing user ".$rem_user."\r\n";
    $logger->debug("removing user ".$rem_user);
    
    // remove user from mysql db
    mysql_remove_user($db_host,$db_rw_user,$db_rw_pass,$db_name,$rem_user,$logging_level,realpath($root.'/../log/_mysql.log'));
  }

  echo "\r\nstarting any adds...\r\n";
  foreach ($diff_add as $add_user) {
    // logging only
    $logger->debug("starting adds...");
    echo "adding user ".$add_user."\r\n";
    $logger->debug("adding user ".$add_user);
    
    // generate 'random' password
    $add_user_pass = random_str($pass_len, $pass_char);
    
    // get e-mail address from ldap array
    $key = array_search($add_user, array_column($ldap_users, 'username'));
    $add_user_mail = $ldap_users[$key]['mail'];
    
    // add user to mysql db
    mysql_add_user($db_host,$db_rw_user,$db_rw_pass,$db_name,$add_user,$add_user_pass,$add_user_mail,$logging_level,realpath($root.'/../log/_mysql.log'));
  }
  
  echo "...done.\r\n";

?>
