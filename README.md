# aladin-sp-v3

WRLC SAML Service Provider with multi-SSO authentication, Alma user authorization, and memcached-based sessions/cookies.

[SimpleSAMLphp](https://simplesamlphp.org/) provides the SAML Service Provider (SP) functionality, while Symfony provides the application UI, logic and database management.

Multiple applications can re-use Aladin-SP as their SAML service provider, alleviating the need to set up SSO each time a new application is deployed.

In addition, Aladin-SP can be configured to authenticate users with multiple identity providers (IdPs). For authentication an application, Aladin-SP provides users with a list of IdPs to choose from.

Once authenticated, Aladin-SP can check a user's authorization to access an application based on username, Alma patron record details, or solely based on successful IdP authentication.

Once a user is authorized for an application, user details are stored in memcached. A cookie is set with the memcached key as its value. The external application can then use the memcached key in the cookie to retrieve user details from the memcached session.

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

### Dependencies included in repo
* Symfony 6.x (w/Doctrine, Twig, and other core components): https://symfony.com/
* SimpleSAMLphp 2.x (w/Metarefresh, Cron, and Multiauth modules): https://simplesamlphp.org/
* SSP Metarefresh module: https://simplesamlphp.org/docs/contrib_modules/metarefresh/simplesamlphp-automated_metadata.html

## Local Development

The repo includes a Docker Compose configuration for local development. The configuration includes containers for the PHP-FPM (including Aladin-SP/Symfony/SimpleSAMLphp, MariaDB, nginx, and memcached), nginx, memcached, and MariaDB.

For git functionality in the PHP container, the following two files should be present on the host machine:

* `~/.gitconfig`: a git configuration that includes the username and email address associated with your user on the repo's remote origin (e.g., Github).
* `~/.ssh/id_rsa`: an RSA private key associated with your user on the repo's remote origin.

### Starting the Docker Containers

1. Start the **Traefik** container: see https://github.com/WRLC/local-dev-traefik
2. Start the **patron-authorization-service** container; see:
   * node.js app: https://github.com/WRLC/patron-authorization-service; or
   * FastAPI Azure Function app: https://github.com/WRLC/patron-authorization-service-azure-func
3. Clone the **Aladin-SP** repository:
    ```bash
    git clone git@github.com:WRLC/aladin-sp-v3.git`
    ```
4. cd to repo root: 
    ```bash
    cd aladin-sp-v3
    ```
5. Start the Aladin-SP containers:
    ```bash
    docker-compose up -d
    ```

### Setting Up the Aladin-SP Application
1. SSH into the Symfony container (this should drop you into the `/app` directory, which is the top level of the Symfony application): 
    ```bash
    docker exec -i -t aladin-sp /bin/bash`
    ```
2. Copy the Symfony `.env` file:
    ```bash
    cp .env.template .env`
    ```
3. Copy the Symfony `.env.local` file: 
    ```bash
    cp .env.local.template .env.local`
    ```
4. Create a self-signed certificate for the SimpleSAMLphp Service Provider (SP): 
    ```bash
    openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out aladin-config/simplesamlphp/cert/saml.crt -keyout aladin-config/simplesamlphp/cert/saml.pem
    ```
5. Copy the SSP `authsources.php` file: 
    ```bash
    cp aladin-config/simplesamlphp/config/authsources.php.dist aladin-config/simplesamlphp/config/authsources.php
    ```
6. Copy the SSP Metarefresh config file `module_metarefresh.php`: 
    ```bash
    cp aladin-config/simplesamlphp/config/module_metarefresh.php.dist aladin-config/simplesamlphp/config/module_metarefresh.php
    ```
5. Create the Symfony database tables/schema: 
    ```bash
    php bin/console make:migration && php bin/console doctrine:migrations:migrate
    ```
6. Visit the Aladin-SP homepage: https://simplesamlphp.wrlc.localhost/
7. Log in with the SSP admin user password: `a_secret_here`
8. Create the SSP PDO tables: https://simplesamlphp.wrlc.localhost/idps/pdo

## Adding/Updating Identity Providers (IdPs)

SAML IdPs can be added to Aladin-SP in two ways:

1. SSP's metarefresh module (w/cron job); or
2. via flatfile.

Aladin-SP stores IdP metadata in its database, so IdP metadata from either source must be converted to a PDO.

### Metarefresh (Automatic Metadata Management)

1. Add/edit IdP entries in `aladin-sp-v3/aladin-config/simplesamlphp/config/module_metarefresh`
   * For the proper format of a metarefresh IdP entry, see https://simplesamlphp.org/docs/contrib_modules/metarefresh/simplesamlphp-automated_metadata.html#configuring-the-metarefresh-module
   * The two most important settings in a metarefresh IdP are:
     1. `'sources' => 'src'`: the URL where Metadata can be retrieved for the Idp; and
     2. `'outputFormat'`: should be set to `'pdo'`
2. Manually run metarefresh to retrieve the IdP's metadata: https://simplesamlphp.wrlc.localhost/idps/metarefresh

### Flatfile (Manual Metadata Management)

1. Add/edit IdP metadata in `aladin-sp-v3/aladin-config/simplesamlphp/metadata/saml20-idp-remote.php`
   * For SSP's documentation, see https://simplesamlphp.org/docs/2.3/simplesamlphp-sp.html#adding-idps-to-the-sp
   * IdP metadata will probably be provided in XML format, but must be stored in the flatfile as a PHP associative array; SSP provides a tool to convert the XML to PHP that can be accessed at: https://{your-aladin-sp-url}/simplesaml/module.php/admin/federation/metadata-converter
2. Convert the contents of `saml20-idp-remote.php` to PDO: https://simplesamlphp.wrlc.localhost/idps/flatfile

To delete an IdP, first delete it from Aladin-SP's IdPs list (), then delete its entry in the metarefresh config file or flatfile. If it's not deleted from there, it will re-appear in the IdP list the next time cron runs or the flatfile is converted to PDO.

To update metadata for an IdP listed in the flatfile, simply update the metadata in the file and convert the contents to PDO again. SSP will update the corresponding database entry automatically.