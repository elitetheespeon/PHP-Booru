Welcome fellow traveller to PHP-Booru, this is an image board software similar to [Danbooru](https://danbooru.donmai.us/), written in PHP and backed by MySQL. It aims to be a custom implementation of an image board written from the ground up several different times with similar features, and lots of new ones.
Below you will find information on setting up your own instance of this imageboard.

NOTE: AS OF 2020, THIS REPO IS NO LONGER MAINTAINED.

## Required Server Software

- Webserver with PHP 5.6 support (Tested under [Litespeed](https://www.litespeedtech.com/products/litespeed-web-server)/[Openlitespeed](https://www.litespeedtech.com/open-source/openlitespeed)) and mod_rewrite enabled
- PHP 5.6 with the following modules enabled: bcmath, curl, date, dom, ereg, fileinfo, gd, hash, json, libxml, mbstring, mcrypt, mysql, mysqli, mysqlnd, pcre, PDO, pdo_mysql, Phar, session, SimpleXML, xml, zlib
- [MySQL 5.7](https://dev.mysql.com/downloads/mysql/) or [MariaDB 10.x](https://mariadb.org/download/) (Tested on MySQL 5.7)

## On to the setup (assuming you have installed/setup all required software):

- First, set your vhost document root in the web server to point to the html directory of this project, and allow **ALL** htaccess overrides.
- Now you will need to import the database schema into your database server, you can do this by running the following commands (replace *myboard* with the name you want your database to be):

```
cd /path/to/project/
echo "create database myboard" | mysql -u root -p
mysql -u root -p myboard < database.sql
```
- Now that the database has been created, we need to make the config file. Copy the default config file from html/config_default.ini and name it config.ini. Now edit this file and fill in your database server information, the site URL, thumbnail URL and site name. You may want to look at all the options for additional configuration.
- We are almost done with setup, now you just need to install the composer dependencies (extra PHP libs), you can do this by running the following commands:

```
cd /path/to/project/
./composer.phar install
```
- At this point you should be able to hit the website at the URL you provided in the config file. You should see the main page with your site name. Login with the following default user to get started with your image board:

```
Username: admin
Password: zmycoolboard123
```
- Remember to change the password for admin account to protect your install from malicious actions.


## Troubleshooting

If you encounter any issues during setup, you can enable more verbose error info in the config.ini file by setting the debug mode to 1 or higher (5 would be max verbosity, 1 would be low verbosity.)

## NOTE

There is currently NO installer and all files are provided as is (you still need to set up webserver and mysql yourself), if you want to help by making a self installer or making it easier for others to set up, please submit a PR with the changes.
