<?php
// MySQL connection informations
$mysql_host = "localhost";
$mysql_user = "root";
$mysql_pass = "";
$mysql_base = "mywebrss";

////////////////////////////////////////////////////////////////////////
// API
////////////////////////////////////////////////////////////////////////
// Persona audience (let it blank to guess it)
$PERSONA_AUDIENCE = "http://localhost";

////////////////////////////////////////////////////////////////////////
// CRON
////////////////////////////////////////////////////////////////////////
// Backups configuration (cron/backup.php)
$mysqldump = "/usr/bin/mysqldump";
$backups_dir = "/var/www/MyWebRSS-api/cron/backups/";

// The age of articles to be deleted (in days) by cron/cleanArticles.php
$MAX_ARTICLE_AGE = 30;

// The age of Tokens to be deleted (in days) by cron/cleanTokens.php
$MAX_TOKEN_AGE = 7;

// The age of inactive users to be deleted (in days) by cron/cleanUsers.php
$MAX_INACTIVE_USER_AGE = 60;

// The interval between checks on a feed (in minutes) by cron/refreshFeeds.php
$REFRESH_INTERVAL = 5;

// The maximum time (in days) for keeping wrong feeds and trying to refresh them (cron/refreshFeeds.php)
$WRONG_FEED_TIME = 15;

?>
