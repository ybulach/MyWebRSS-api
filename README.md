MyWebRSS-api
========
If you don't want to install it, you can use: https://api.mywebrss.net.

PRESENTATION
------------
MyWebRSS-api is a web RSS API initialy made for MyWebRSS webapp (https://github.com/ybulach/MyWebRSS). It is OpenSource and can be installed on every server with PHP and MySQL.

The API is developped in PHP and uses a MySQL/InnoDB database. The datas send are in JSON.

INSTALLATION
------------
If you want to install it, you will need a PHP server. A MySQL database needs to be created using the **mywebrss.sql** file and the credentials needs to be set in the **config.inc.php** file.

You only need to configure the **api/** folder in your web server. For example, here is a sample VirtualHost for Apache:

	<VirtualHost *:80>
		ServerName api.domain.com
		DocumentRoot /var/www/MyWebRSS-api/api
		<Directory /var/www/MyWebRSS-api/api>
			Options -Indexes FollowSymLinks MultiViews
			AllowOverride All
			Order allow,deny
			allow from all
		</Directory>
	</VirtualHost>

If you want to force SSL to access **api/**, you may want to uncomment this lines in **api/.htaccess**:

	RewriteCond %{SERVER_PORT} 80
	RewriteRule ^(.*)$ https://%{SERVER_NAME}%{REQUEST_URI} [R,L]

The scripts in the **cron/** folder need to be executed periodically:

	# m h  dom mon dow   command
	* * * * * cd /var/www/MyWebRSS-api/cron/ && php refreshFeeds.php > /dev/null 2>&1
	#0 0 * * * cd /var/www/MyWebRSS-api/cron/ && php backup.php > /dev/null 2>&1			Optional
	#0 * * * * cd /var/www/MyWebRSS-api/cron/ && php cleanArticles.php > /dev/null 2>&1	Optional
	#0 * * * * cd /var/www/MyWebRSS-api/cron/ && php cleanFeeds.php > /dev/null 2>&1		Optional
	#0 * * * * cd /var/www/MyWebRSS-api/cron/ && php cleanTokens.php > /dev/null 2>&1		Optional
	#0 * * * * cd /var/www/MyWebRSS-api/cron/ && php cleanUsers.php > /dev/null 2>&1		Optional
	
To use the **cron/backup.php** script, you have to set your **mysqldump** executable path and the backup directory in **config.inc.php**:

	// Backups configuration
	$mysqldump = "/usr/bin/mysqldump";
	$backups_dir = "/var/www/MyWebRSS-api/cron/backups/";

If you are running the API from Windows, CURL won't verify the SSL certificate. You will need to disable the check by turning the line from **api/lib.persona.php**:

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

to this:

	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

or follow this: https://github.com/mozilla/browserid-cookbook/blob/master/php/README.windows.txt

API
---
The **api/** offers the hability to get datas in JSON. These are the URL (rewriting with .htaccess):
	
	URL											|	Request parameters										|	Result variables
	----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	http(s)://api.mywebrss.net/feed/add				token (id), feed (url)										success, [error], feed (id)
								   /delete			token (id), feed (id)										success, [error]
								   /import			token (id), file (opml content)								success, [error], percentage (of added feeds)
							       /list			token (id)													success, [error], result { id, title, description, error (0 or 1), unread (articles count) }
							       /show			token (id), feed (id), [articles_count], [page]				success, [error], feed (title), result { id, title, description, url, image, date, feed (title), status ("" or "new" }
							  /article/unread		token (id), article (id)									success, [error]
							  /user/login			assertion (personna)										success, [error], token
							       /logout			token (id)													success, [error]

If the **success** variable returned is set to 0, the **error** variable indicate the reason of failure (the name of the parameter of the request, or a full sentence).

The **result** variable, if present, is an array and includes the variables shown between the {}.

For each request, the Token is needed, to identify the user. The request **/user/login** checks the Persona assertion and returns the email address associated with it, and generate a token.

The use of HTTPS allow more secure exchanges, for the sending of the Token.

CRON
----
This scripts need to be launch periodically (using crontab for example):
	
	Script					|	Frequency		|	Description
	----------------------------------------------------------------------------------------------------------
	backup.php					every day			dump the database
	cleanArticles.php			every day			delete old articles (30 days for example)
	cleanFeeds.php				every day			delete unused and wrong feeds
	cleanTokens.php				every day			delete old Tokens
	cleanUsers.php				every day			delete inactive users
	refreshFeeds.php			every minutes		refresh articles for feeds (get new datas every 5 minutes)

DATABASE
--------
The database uses MySQL with InnoDB.

Scheme
		
		tokens	>-------	users	-------<	user_feeds	>-------	 feeds	-------<	articles
		
		users	-------<	user_articles	>-------	 articles

Detail
	
	users			users informations
			user_id					BIGINT(10)
			user_email				VARCHAR(255)
			user_lastlogin			INT(10)			linux timestamp
		=> user_id PRIMARY auto_increment
		=> user_email UNIQUE
	tokens      Tokens of each users
			token_id				VARCHAR(40)		random SHA-1 with salt (token_date, user_ref)
			user_ref				BIGINT(10)
			token_date				INT(10)			linux timestamp
		=> token_id PRIMARY
		=> user_ref ON DELETE CASCADE ON UPDATE CASCADE
	user_feeds		RSS feeds used by each users
			user_ref				BIGINT(10)
			feed_ref				BIGINT(10)
		=> (user_ref, feed_ref) PRIMARY
		=> user_ref ON DELETE CASCADE ON UPDATE CASCADE
		=> feed_ref ON DELETE CASCADE ON UPDATE CASCADE
	feeds			RSS feeds
			feed_id					BIGINT(10)
			feed_url				VARCHAR(255)
			feed_title				VARCHAR(255)
			feed_description		TEXT
			feed_date				INT(10)			linux timestamp
			feed_error				INT(10)			linux timestamp
		=> feed_id PRIMARY auto_increment
		=> feed_url UNIQUE
	articles		articles of each RSS feeds
			article_id				BIGINT(10)
			feed_ref				BIGINT(10)
			article_url				VARCHAR(255)
			article_guid			VARCHAR(255)
			article_title			VARCHAR(255)
			article_description		TEXT
			article_image			VARCHAR(255)
			article_date			INT(10)			linux timestamp
		=> article_id PRIMARY auto_increment
		=> feed_ref ON DELETE CASCADE ON UPDATE CASCADE
		=> article_url UNIQUE
		=> (feed_ref, article_guid) UNIQUE
	user_articles	articles state for each users (read or not)
			user_ref				BIGINT(10)
			article_ref				BIGINT(10)
		=> (user_ref, article_ref) PRIMARY
		=> user_ref ON DELETE CASCADE ON UPDATE CASCADE
		=> article_ref ON DELETE CASCADE ON UPDATE CASCADE
