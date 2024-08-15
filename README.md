# aladin-sp-v3

WRLC SAML Service Provider with multi-SSO authentication, Alma user authorization, and memcached-based sessions/cookies.

## Archicture/Major Dependencies

### Installed automatically in Docker Container (for local dev)
* PHP 8.1 or higher 
* MySQL, MariaDB, PostgreSQL, or SQLite 
* nginx or Apache
* Memcached (incl. PHP Memcached extension)

### External Dependencies
* WRLC Patron Authorization Service (for authorization using Ex Libris Alma API): https://github.com/WRLC/patron-authorization-service
* WRLC local-dev-traefik (for local networking of Docker containers): https://github.com/WRLC/local-dev-traefik

### Included in codebase
* Symfony 6.x (w/Doctrine, Twig, and other core components) (already included in codebase): https://symfony.com/
* SimpleSAMLphp 2.2.x (w/Metarefresh, Cron, and Multiauth modules) (already included in codebase): https://simplesamlphp.org/

## Installation (Local Development)

### Terminal

1. Clone the repository: `git clone git@github.com:WRLC/aladin-sp-v3.git`
2. cd to repo root: `cd aladin-sp-v3`
3. Start the containers: `docker-compose up -d`
4. SSH into the Symfony container: `docker exec -i -t aladin-sp /bin/bash`
5. Copy the Symfony `.env` file: `cp .env.template .env`
6. Copy the SimpleSAMLphp `.env` file: `cp aladin-config/simplesamlphp/config/.env.template aladin-config/simplesamlphp/config/.env`
7. Copy the SimpleSAMLphp `authsources.php` file: `cp aladin-config/simplesamlphp/config/authsources.php.dist aladin-config/simplesamlphp/config/authsources.php`
8. Create a SimpleSAMLphp service provider in the `authsources.php` file. (See: https://simplesamlphp.org/docs/stable/simplesamlphp-sp.html#configuring-the-sp)
9. Copy the SSP Metarefresh configuration file: `cp aladin-config/simplesamlphp/config/module_metarefresh.php.dist aladin-config/simplesamlphp/config/module_metarefresh.php`
10. Copy the SSP Cron configuration file: `cp aladin-config/simplesamlphp/config/module_cron.php.dist aladin-config/simplesamlphp/config/module_cron.php`
11. Copy the SimpleSAMLphp `saml20-idp-remote.php` file: `cp aladin-config/simplesamlphp/metadata/saml20-idp-remote.php.dist aladin-config/simplesamlphp/metadata/saml20-idp-remote.php`
12. Create a self-signed certificate for the SP: `openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out aladin-config/simplesamlphp/cert/saml.crt -keyout aladin-config/simplesamlphp/cert/saml.pem`
13. Create the Symfony database tables/schema: `php bin/console make:migration && php bin/console doctrine:migrations:migrate`
14. Add the config fixtures to the database: `php bin/console doctrine:fixtures:load`

### Web Browser

1. Visit the Aladin-SP homepage: `https://simplesamlphp.wrlc.localhost/`
2. Login with the password set for `SSP_ADMINPASSWORD` in the `aladin-config/simplesamlphp/config/.env` file. (Default: `another_secret_here`)
3. Configure Aladin-SP settings at https://simplesamlphp.wrlc.localhost/config
   1. Patron Authorization endpoint URL: If using the WRLC Patron Authorization Service with Docker, this will be http://patronauth:8080/lookup/patrons.
   2. SSP Service Provider: Select the service provider created in `aladin-config/simplesamlphp/config/authsources.php`.
   3. Memcached Host: If using the Docker container, this will be `aladinsp-memcached`.
   4. Memcached Port: If using the Docker container, this will be `11211`.
   5. Cookie Prefix: This can be any string, but should be unique to Aladin-SP.
   6. Cookie Domain: This should be the domain of the Aladin-SP instance. (Default: `.wrlc.localhost`)
4. Create the SSP PDO tables: https://simplesamlphp.wrlc.localhost/config/pdo

## Adding external Identity Providers (IdPs)

Instructions coming soon...