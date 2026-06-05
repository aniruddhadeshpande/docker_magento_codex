# Varnish Cache Spec

## Goal

Define Varnish as the HTTP cache layer in front of Nginx for the Magento Open Source 2.4.9 learning stack.

Request path:

```text
TLS proxy -> Varnish -> Nginx -> PHP-FPM
```

## Version Requirement

Use:

```text
Varnish 7
```

Use `varnish:7` to align with Magento's Varnish 6/7 VCL generator support.

## Required File

Create:

```text
varnish/default.vcl
```

The VCL should be readable and commented for learning.

## Backend

Varnish backend must be:

```text
nginx:80
```

## Cache Behavior

Cache only safe request methods:

```text
GET
HEAD
```

Pass write or unsafe request methods:

```text
POST
PUT
PATCH
DELETE
```

The VCL file must include simple comments explaining:

- What the backend is.
- Why unsafe methods are passed.
- How Magento cache headers influence caching.
- How to observe cache hits and misses.

## Magento Compatibility

Prefer using or adapting Magento-generated VCL for the final implementation when Magento is installed.

The Varnish configuration must preserve Magento cache behavior and must not cache:

- Admin routes.
- Checkout and customer session routes.
- Requests with session or authentication cookies.
- Unsafe write methods.

## Headers

Add a basic response header to make cache behavior easier to observe.

Suggested header:

```text
X-Cache
```

Possible values:

```text
HIT
MISS
```

## Acceptance Criteria

- Requests reach Magento through Varnish.
- Safe `GET` or `HEAD` requests are eligible for caching.
- Unsafe methods are passed and not cached.
- Admin, checkout, and session-sensitive requests are not cached.
- Varnish logs show traffic.
- Response headers make cache behavior observable.
- Varnish remains between the TLS proxy and Nginx.
