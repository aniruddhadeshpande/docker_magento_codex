# OpenSearch Spec

## Goal

Define OpenSearch as Magento Open Source 2.4.9's catalog search service.

Magento owns indexing and search behavior. This spec only defines the OpenSearch infrastructure and Magento-facing configuration.

## Service Requirements

The Docker infrastructure must include:

```text
opensearch
```

Use an OpenSearch 3 image compatible with Magento Open Source 2.4.9:

```text
opensearchproject/opensearch:3
```

If the exact tag is unavailable, document the selected compatible OpenSearch 3 tag.

Use local single-node mode.

Disable the OpenSearch security plugin for learning simplicity.

For Docker, use:

```text
DISABLE_SECURITY_PLUGIN=true
DISABLE_INSTALL_DEMO_CONFIG=true
```

Expose OpenSearch on:

```text
http://localhost:9200
```

Store data in:

```text
opensearch_data
```

## Compose Contract

The final `docker-compose.yml` must define the OpenSearch service with these settings:

```yaml
opensearch:
  image: opensearchproject/opensearch:3
  restart: unless-stopped
  environment:
    discovery.type: single-node
    cluster.name: magento-learning
    node.name: opensearch
    bootstrap.memory_lock: "true"
    OPENSEARCH_JAVA_OPTS: "-Xms512m -Xmx512m"
    DISABLE_INSTALL_DEMO_CONFIG: "true"
    DISABLE_SECURITY_PLUGIN: "true"
  ulimits:
    memlock:
      soft: -1
      hard: -1
    nofile:
      soft: 65536
      hard: 65536
  ports:
    - "${OPENSEARCH_PORT:-9200}:9200"
  volumes:
    - opensearch_data:/usr/share/opensearch/data
  networks:
    - learning_network
  healthcheck:
    test: ["CMD-SHELL", "curl -fsS http://localhost:9200/_cluster/health || exit 1"]
    interval: 15s
    timeout: 10s
    retries: 20
    start_period: 60s
```

Do not add OpenSearch Dashboards for the first infrastructure implementation unless a later spec explicitly asks for it.

## Environment Variables

Use:

```env
OPENSEARCH_HOST=opensearch
OPENSEARCH_PORT=9200
OPENSEARCH_INDEX_PREFIX=magento2
OPENSEARCH_JAVA_OPTS=-Xms512m -Xmx512m
```

Magento must use the Docker service name `opensearch` for container-to-container communication.

## Magento Configuration

Magento setup must configure OpenSearch with:

```text
search-engine=opensearch
opensearch-host=opensearch
opensearch-port=9200
opensearch-index-prefix=${OPENSEARCH_INDEX_PREFIX}
```

Magento will create and manage search indexes. Do not create a custom `products` index or standalone PHP search script for this infrastructure spec.

## Healthcheck

OpenSearch must include a healthcheck against:

```text
http://localhost:9200/_cluster/health
```

The host verification URL remains:

```text
http://localhost:9200
```

## Host Requirement

Linux hosts must support OpenSearch's memory map requirement:

```bash
sysctl -w vm.max_map_count=262144
```

The final run instructions should mention this command for Linux users if OpenSearch fails with a bootstrap or memory-map error.

## Image Tag Caveat

`opensearchproject/opensearch:3` is aligned with Magento Open Source 2.4.9 and tracks the current OpenSearch 3 release. The final implementation may pin a specific 3.x tag, for example `opensearchproject/opensearch:3.3.2`, if repeatable local builds are preferred.

## Acceptance Criteria

- OpenSearch container starts healthy.
- `http://localhost:9200` responds from the host.
- Magento setup can connect to OpenSearch using host `opensearch`.
- Magento can run catalog indexing through `bin/magento indexer:reindex`.
- OpenSearch data persists in `opensearch_data`.
- OpenSearch connection details are read from `.env`.
