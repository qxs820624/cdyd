<?php
//dbconn.php
require_once('globalconfig.php');
$con = mysql_connect($db_domain,$db_admin_name,$db_passwd);
// error_log($webtab,3,"./err.txt","webtabname");
mysql_select_db($db_name, $con);
//set charset for mysql
mysql_query("SET names $db_chractor");

?>