# aladin-sp-v3

WRLC SAML Service Provider with multi-SSO authentication, Alma user authorization, and memcached-based sessions/cookies.

## Archicture/Major Dependencies

* Symfony (w/Doctrine and Twig)
* SimpleSAMLphp (w/multiauth and metarefresh)
* Ex Libris Alma Users API (Get user details - `GET /almaws/v1/users/{user_id}`)
* Memcached
* Docker (w/Traefik, PHP-FPM, nginx, MySQL) for local development
