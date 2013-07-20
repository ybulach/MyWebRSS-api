<?php
// Script to run every day
///////////////////////////////////////////////////////////////////////////////
require_once("lib.php");

try {
	// Delete the unused feeds
	$date = date("Y-m-d_H-i-s");
	exec($mysqldump." --user=".$mysql_user." --password=".$mysql_pass." --host=".$mysql_host." ".$mysql_base." > ".$backups_dir."/".$date);
	
	// Delete old backups
	exec("find ".$backups_dir." -type f -mtime +7 | xargs rm");
}
catch( Exception $e ){
	send_error($e->getMessage());
}
?>
