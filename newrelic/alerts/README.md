# Alert Starters

These are learning thresholds. Tune them after collecting a local baseline.

## Magento APM

| Condition | NRQL |
| --- | --- |
| High error rate | `FROM TransactionError SELECT count(*) WHERE appName = 'docker-magento-learning'` |
| Slow p95 | `FROM Transaction SELECT percentile(duration, 95) WHERE appName = 'docker-magento-learning'` |

## Queue

| Condition | NRQL |
| --- | --- |
| Queue backlog | `FROM Metric SELECT latest(rabbitmq.queue.messages) FACET queue.name` |
| Missing consumers | `FROM Metric SELECT latest(rabbitmq.queue.consumers) FACET queue.name` |

## PHP-FPM

| Condition | NRQL |
| --- | --- |
| Listen queue nonzero | `FROM Metric SELECT latest(phpfpm_listen_queue)` |
| Max children reached | `FROM Metric SELECT latest(phpfpm_max_children_reached)` |

## Varnish

| Condition | NRQL |
| --- | --- |
| Backend failures increasing | `FROM Metric SELECT rate(sum(varnish_main_backend_fail), 1 minute)` |
| Low cache effectiveness | `FROM Metric SELECT latest(varnish_main_cache_hit), latest(varnish_main_cache_miss)` |

## MySQL And OpenSearch

| Condition | NRQL |
| --- | --- |
| MySQL connection pressure | `FROM Metric SELECT latest(mysql_global_status_threads_connected)` |
| OpenSearch cluster not green | Confirm ingested `elasticsearch_cluster_health_*` metric names, then alert on yellow/red state. |

## Containers

| Condition | NRQL |
| --- | --- |
| Container restarts | Confirm container restart metric name from `Metric`, then alert on positive restart delta. |
| Missing telemetry | `FROM Metric SELECT count(*) WHERE metricName LIKE 'varnish_%'` |
