Welcome fellow traveller to PHP-Booru, this is an image board software similar to [Danbooru](https://danbooru.donmai.us/), written in PHP and backed by MySQL. It aims to be a custom implementation of an image board written from the ground up several different times with similar features, and lots of new ones.
Below you will find information on setting up your own instance of this imageboard.

## Required Server Software

- Web server with URL rewriting enabled (Apache `mod_rewrite` or equivalent). Tested under [Litespeed](https://www.litespeedtech.com/products/litespeed-web-server)/[Openlitespeed](https://www.litespeedtech.com/open-source/openlitespeed).
- PHP **8.4** (aligned with the Docker runtime in `docker/php84/Dockerfile`).
- PHP extensions required: `bcmath`, `curl`, `gd`, `mbstring`, `mysqli`, `pdo`, `pdo_mysql`, `soap`, `xml`, `zip`.
- Common runtime/core extensions expected by app and dependencies: `json`, `fileinfo`, `libxml`, `openssl`, `phar`, `session`, `tokenizer`, `ctype`, `zlib`.
- Database:
  - [MySQL 5.7](https://dev.mysql.com/downloads/mysql/) (current Docker Compose default)
  - [MariaDB 10.11 LTS](https://mariadb.org/download/) recommended for new non-Docker installs

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

## Docker Compose (local development)

This repository includes a Docker setup for local development with:

- PHP 8.4 + Apache (`app` service)
- MySQL 5.7 (`db` service)
- Automatic initial schema import from `database.sql`

Recommended tool versions for quick start:

- Docker Engine 24.x+
- Docker Compose plugin v2.20+

### Included files

- `docker-compose.yml`
- `docker/php84/Dockerfile`
- `docker/php84/apache-vhost.conf`
- `docker/php84/php-custom.ini`
- `docker/php84/entrypoint.sh`

### First run

From the project root:

```bash
docker compose up --build
```

Then open:

- http://localhost:12345/

On first start, the app container will:

- Copy `html/config_default.ini` to `html/config.ini` if missing
- Copy `html/webhooks_default.ini` to `html/webhooks.ini` if missing
- Inject DB/site settings into `html/config.ini` from compose environment variables
- Run `php composer.phar install` if `vendor/autoload.php` is missing

### Service details

- App container name: `phpbooru-app`
- DB container name: `phpbooru-db`
- App URL: `http://localhost:12345/`
- DB host from app: `db`
- DB port on host: `33066`
- Default DB credentials:
  - database: `phpbooru`
  - user: `phpbooru`
  - password: `hahaha-I-am-A-Password-123`
  - root password: `hahaha-I-am-The-ROOT-Password-123`

### Reset database state

If you want to re-run a fresh DB import:

```bash
docker compose down -v
docker compose up --build
```

This removes the named MySQL volume so `database.sql` is imported again.
