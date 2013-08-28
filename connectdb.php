<?php
function connectDB(){
require("data.php");
   $dsn = $DatabaseType.":host=".$DatabaseServer.";dbname=".$DatabaseName;
   $dbh = new PDO($dsn, "$DatabaseUsername", "$DatabasePassword");
   return $dbh;
}
?>
