# Dashboard Starters

Confirm actual metric names with `newrelic/queries/verification.nrql` before
treating these dashboards as final.

## Magento Application

- Request count and throughput from `Transaction`.
- p50/p95/p99 duration from `Transaction`.
- Slowest transaction names.
- `TransactionError` count by class and transaction.
- Datastore time split by MySQL, Redis, and OpenSearch when APM reports it.

Starter NRQL:

```sql
FROM Transaction SELECT rate(count(*), 1 minute), percentile(duration, 50, 95, 99) WHERE appName = 'docker-magento-learning' TIMESERIES
FROM TransactionError SELECT count(*) WHERE appName = 'docker-magento-learning' FACET error.class TIMESERIES
FROM Transaction SELECT max(duration) WHERE appName = 'docker-magento-learning' FACET name LIMIT 20
```

## Infrastructure

- Container CPU and memory.
- Restart count and health status.
- Host disk usage.
- Docker log volume.

Starter NRQL:

```sql
FROM Metric SELECT uniques(metricName) WHERE metricName LIKE 'container.%' OR metricName LIKE 'docker.%' LIMIT 100
FROM Log SELECT count(*) WHERE project = 'docker-magento' TIMESERIES
```

## Queue And Cache

- RabbitMQ ready/unacked messages by queue.
- Consumer count.
- Redis memory, evictions, hit ratio.
- Varnish hit/miss/backend failure counters.

Starter NRQL:

```sql
FROM Metric SELECT uniques(metricName) WHERE metricName LIKE 'rabbitmq.%' LIMIT 100
FROM Metric SELECT uniques(metricName) WHERE metricName LIKE 'redis.%' LIMIT 100
FROM Metric SELECT latest(varnish_main_cache_hit), latest(varnish_main_cache_miss), latest(varnish_main_backend_fail) TIMESERIES
```

## Data And Search

- MySQL connected threads, questions/sec, slow/deadlock indicators if exposed.
- OpenSearch cluster health, JVM heap, disk, rejected operations, indexing/search rates.

Starter NRQL:

```sql
FROM Metric SELECT latest(mysql_global_status_threads_connected), latest(mysql_global_status_questions) TIMESERIES
FROM Metric SELECT uniques(metricName) WHERE metricName LIKE 'elasticsearch_%' LIMIT 100
```
