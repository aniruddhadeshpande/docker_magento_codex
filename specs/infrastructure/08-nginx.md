# Nginx Spec

## Goal

Define the internal Nginx web server for Magento Open Source 2.4.9.

Nginx must sit behind Varnish and forward PHP execution to PHP-FPM.

Request path:

```text
TLS proxy -> Varnish -> Nginx -> PHP-FPM
```

## Version Requirement

Use an Nginx version compatible with Magento Open Source 2.4.9:

```text
Nginx 1.30
```

If the exact image tag is unavailable, document the selected compatible tag.

## Service Requirement

The Docker Compose service must be named:

```text
nginx
```

The service must:

- Listen on internal port `80`.
- Join `learning_network`.
- Mount Magento files from `./magento` to `/var/www/html`.
- Use `nginx/default.conf`.
- Forward PHP requests to `php-fpm:9000`.
- Use restart policy `unless-stopped`.
- Include a basic healthcheck.

Nginx should not publish a public HTTP port in the first implementation. Public browser traffic must enter through `tls-proxy` on host port `443`.

## Web Root

Magento's public web root must be:

```text
/var/www/html/pub
```

Do not expose the full Magento application root as the web root.

## Magento Routing

The Nginx configuration must support:

- Magento front controller routing through `pub/index.php`.
- Static assets under `/static/`.
- Media files under `/media/`.
- Admin and storefront routes.
- Health or status checks that do not require Magento admin access.

The configuration may be based on Magento's own Nginx sample configuration, simplified only where needed for local learning.

## PHP-FPM Upstream

PHP requests must be forwarded to:

```text
php-fpm:9000
```

The configuration must pass common FastCGI parameters, including:

```text
SCRIPT_FILENAME
DOCUMENT_ROOT
HTTPS
HTTP_X_FORWARDED_PROTO
```

Because TLS terminates before Varnish, Nginx must preserve enough forwarded headers for Magento to understand that the public request scheme is HTTPS.

## Cache Headers

Nginx must preserve response headers needed by Varnish and Magento.

Do not strip Magento cache headers such as:

```text
X-Magento-Tags
X-Magento-Cache-Control
Cache-Control
```

## Acceptance Criteria

- `nginx/default.conf` exists.
- Nginx starts successfully.
- Nginx serves Magento from `/var/www/html/pub`.
- PHP requests are forwarded to `php-fpm:9000`.
- Requests from Varnish reach Magento.
- `https://magento.docker` loads through TLS proxy, Varnish, Nginx, and PHP-FPM.
- Nginx does not expose the full Magento root as a public document root.
