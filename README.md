# aladin-sp-v3

WRLC SAML Service Provider with multi-SSO authentication, Alma user authorization, and memcached-based sessions/cookies.

## Archicture/Major Dependencies

### Prerequisites (installed automatically in Dockerfile for local dev)
* PHP 8.1.x (SimpleSAMLphp 2.x does not yet support newer releases of PHP)
* Composer
* SimpleSAMLphp prerequisites: https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#prerequisites
* MySQL, MariaDB, PostgreSQL, or SQLite (included Docker config for local dev uses MySQL)
* nginx or Apache (included Docker config for local dev uses nginx and php-fpm)
* Memcached, including PHP Memcached extension

### External Dependencies
* WRLC Patron Authorization Service (for authorization using Ex Libris Alma API): 
  * node.js app: https://github.com/WRLC/patron-authorization-service
  * FastAPI Azure Function app: https://github.com/WRLC/patron-authorization-service-azure-func
* WRLC local-dev-traefik (for local networking of Docker containers): https://github.com/WRLC/local-dev-traefik

### Dependencies ncluded in repo
* Symfony 6.x (w/Doctrine, Twig, and other core components): https://symfony.com/
* SimpleSAMLphp 2.x (w/Metarefresh, Cron, and Multiauth modules): https://simplesamlphp.org/
* SSP Metarefresh module: https://simplesamlphp.org/docs/contrib_modules/metarefresh/simplesamlphp-automated_metadata.html

## Installation

There are a lot of moving parts to make Aladin-SP, SimpleSAMLphp and Symfony play nice with each other.

### Server preparation

#### Production environment only

1. Create a database with a user granted all privileges

#### All environments

2. Clone the repository: `git clone git@github.com:WRLC/aladin-sp-v3.git`
3. cd to repo root: `cd aladin-sp-v3`

#### Development environment only

4. Start the traefik container: see https://github.com/WRLC/local-dev-traefik
5. Start the containers: `docker-compose up -d`
6. SSH into the Symfony container: `docker exec -i -t aladin-sp /bin/bash`

#### Production environment only

7. Configure Apache, nginx, or another webserver to make your Symfony installation publicly accessible

### Symfony configuration

#### All environments

1. Copy the Symfony `.env` file: `cp .env.template .env`

#### Production environment only

2. Copy the Symfony `.env.local` file 
3. Add production values to the Symfony `.env.local` file:
   1. `APP_ENV`: set temporarily to `dev` (to make deployment commands available)
   2. `APP_SECRET`: an app secret for your Symfony app
   3. `DATABASE_URL`: uncomment the value pertaining to your database server and replace value with credentials for the database and user created in step 1

### SimpleSAMLphp configuration

#### All Environments

4. Copy the SimpleSAMLphp `.env` file: `cp aladin-config/simplesamlphp/config/.env.template aladin-config/simplesamlphp/config/.env`

#### Production environment only

5. Add production env values to the SimpleSAMLphp config `.env` file:
    1. `SSP_BASEURLPATH`: the public URL for your Aladin-SP, with the `/simplesaml` path appended
    2. `SSP_METADATA_DIR`: replace `/app/` with the server path to Aladin-SP; the rest should be unchanged
    3. `SSP_CERTDIR`: replace `/app/` with the server path to Aladin-SP; the rest should be unchanged
    4. `SSP_LOGDIR`: replace `/app/` with the server path to Aladin-SP; the rest should be unchanged
    5. `SSP_TECHNICALCONTACT_NAME`
    6. `SSP_TECHNICALCONTACT_EMAIL`
    7. `SSP_SECRETSALT`: a secret salt for your SSP application
    8. `SSP_ADMINPASSWORD`: the password for your SSP admin, which will be used to access the Aladin-SP admin UI
    9. `SSP_DB_DSN`: the data source name string for the database created in step 1
    10. `SSP_DB_USERNAME`: the database user created in step 1
    11. `SSP_DB_PASSWORD`: the database user password created in step 1
    12. `SSP_MEMCACHE_SERVER`: the URI of your Memcached server (`localhost` if installed locally)
    13. `SSP_MEMCACHE_PORT`: the port number where Memcached is accessed, probably `11211`

#### All environments

6. Copy the SimpleSAMLphp `authsources.php` file: `cp aladin-config/simplesamlphp/config/authsources.php.dist aladin-config/simplesamlphp/config/authsources.php`
7. Create a SimpleSAMLphp service provider in the `authsources.php` file. (See: https://simplesamlphp.org/docs/stable/simplesamlphp-sp.html#configuring-the-sp)
8. Copy the SSP Metarefresh configuration file: `cp aladin-config/simplesamlphp/config/module_metarefresh.php.dist aladin-config/simplesamlphp/config/module_metarefresh.php`
9. Add entries for IdP metadata URLs to `module_metarefresh.php` (see "Adding Identity Providers" section below)
10. Copy the SimpleSAMLphp `saml20-idp-remote.php` file: `cp aladin-config/simplesamlphp/metadata/saml20-idp-remote.php.dist aladin-config/simplesamlphp/metadata/saml20-idp-remote.php`
11. Add entries for IdP metadata to `saml20-idp-remote.php` (see "Adding Identity Providers" section below)

#### Production environment only 

12. Copy the SSP Cron configuration file: `cp aladin-config/simplesamlphp/config/module_cron.php.dist aladin-config/simplesamlphp/config/module_cron.php`
13. Configure `module_cron.php`

#### All environments

14. Create a self-signed certificate for the SP: `openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out aladin-config/simplesamlphp/cert/saml.crt -keyout aladin-config/simplesamlphp/cert/saml.pem`

### Symfony Deployment 

#### All environments

1. Create the Symfony database tables/schema: `php bin/console make:migration && php bin/console doctrine:migrations:migrate`
2. Add the config fixtures to the database: `php bin/console doctrine:fixtures:load`

### Aladin-SP Configuration

#### All environments

1. Visit the Aladin-SP homepage (`https://simplesamlphp.wrlc.localhost/` for development environment)
2. Log in with the SSP admin user password (`another_secret_here` for development environment)
3. Configure Aladin-SP settings at https://{your-aladin-sp-url}/config
   1. Patron Authorization endpoint URL: the API endpoint for your Patron Authorization service
      1. node.js app: see https://github.com/WRLC/patron-authorization-service
      2. FastAPI Azure Function app: see https://github.com/WRLC/patron-authorization-service-azure-func
   2. SSP Service Provider: Select the service provider created in `aladin-config/simplesamlphp/config/authsources.php`.
   3. Memcached Host: `localhost` if installed on same production server (`aladinsp-memcached` in development environment).
   4. Memcached Port: probably `11211`.
   5. Cookie Prefix: This can be any string, but should be unique to your Aladin-SP.
   6. Cookie Domain: This should be the domain—but not subdomain—of the Aladin-SP instance. (Default: `.wrlc.localhost`)
26. Create the SSP PDO tables: https://{your-aladin-sp-url}/config/pdo


## Adding Identity Providers (IdPs)

Instructions coming soon...