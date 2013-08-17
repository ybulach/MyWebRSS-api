<?php
// MySQL connection informations
$mysql_host = "localhost";
$mysql_user = "root";
$mysql_pass = "";
$mysql_base = "mywebrss";

// Backups configuration
$mysqldump = "/opt/lampp/bin/mysqldump";
$backups_dir = "/opt/lampp/htdocs/MyWebRSS/cron/backups";

// The age of articles to be deleted (in days)
$MAX_ARTICLE_AGE = 30;

// The age of inactive users to be deleted (in days)
$MAX_INACTIVE_USER_AGE = 60;

// The interval between checks on a feed (in minutes)
$REFRESH_INTERVAL = 5;

?>
