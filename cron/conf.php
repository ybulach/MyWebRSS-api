<?php
// MySQL connection informations
$mysql_host = "localhost";
$mysql_user = "root";
$mysql_pass = "";
$mysql_base = "mywebrss";

// Backups configuration
$mysqldump = "/opt/lampp/bin/mysqldump";
$backups_dir = "/opt/lampp/htdocs/MyWebRSS/cron/backups";

// The age of articles to be deleted
$MAX_ARTICLE_AGE = 30;

// The age of Tokens to be deleted
$MAX_TOKEN_AGE = 7;

?>
