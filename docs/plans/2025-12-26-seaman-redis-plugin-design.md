# seaman/redis Plugin Design

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create an external Redis plugin that provides Redis standalone, Redis Cluster (3 masters + 3 replicas), and management commands.

**Architecture:** Composer package with `type: seaman-plugin` that overrides the bundled redis plugin when installed. Uses `#[ProvidesService]` and `#[ProvidesCommand]` attributes.

**Tech Stack:** PHP 8.4, Symfony Console, Twig templates, PHPUnit/Pest, PHPStan level 10.

---

## Project Structure

```
seaman-redis/
├── composer.json
├── phpstan.neon
├── phpunit.xml
├── .php-cs-fixer.dist.php
├── src/
│   ├── RedisPlugin.php
│   ├── Command/
│   │   ├── RedisCliCommand.php
│   │   ├── RedisFlushCommand.php
│   │   ├── RedisInfoCommand.php
│   │   ├── RedisMonitorCommand.php
│   │   ├── RedisKeysCommand.php
│   │   ├── ClusterInfoCommand.php
│   │   └── ClusterNodesCommand.php
│   └── Service/
│       ├── RedisServiceFactory.php
│       └── RedisClusterServiceFactory.php
├── templates/
│   ├── redis.yaml.twig
│   └── redis-cluster.yaml.twig
└── tests/
    └── Unit/
        ├── RedisPluginTest.php
        ├── Command/
        └── Service/
```

## Services

### Redis Standalone (`redis`)
- Image: `redis:7-alpine` (configurable)
- Port: 6379 (configurable)
- Health check: `redis-cli ping`
- Optional persistence volume

### Redis Cluster (`redis-cluster`)
- 6 containers: `redis-node-1` to `redis-node-6`
- 3 masters + 3 replicas (auto-assigned)
- Ports: 6380-6385 (base configurable)
- Init container to create cluster
- Per-node health checks

## Configuration Schema

```php
ConfigSchema::create()
    ->string('version', default: '7-alpine')
        ->label('Redis version')
        ->enum(['6-alpine', '7-alpine', 'alpine', 'latest'])
    ->integer('port', default: 6379, min: 1, max: 65535)
        ->label('Standalone port')
    ->integer('cluster_base_port', default: 6380, min: 1, max: 65535)
        ->label('Cluster base port')
    ->boolean('persistence', default: false)
        ->label('Enable persistence');
```

## Commands

### Basic Commands (standalone + cluster)

| Command | Description | Options |
|---------|-------------|---------|
| `redis:cli` | Interactive redis-cli | `--cluster` connect to cluster |
| `redis:flush` | Flush all keys | `--force` skip confirmation |
| `redis:info` | Server statistics | `--section` (memory, stats, etc.) |
| `redis:monitor` | Real-time command monitor | `--timeout` duration |
| `redis:keys [pattern]` | List keys | Pattern defaults to `*` |

### Cluster Commands

| Command | Description |
|---------|-------------|
| `redis:cluster:info` | Cluster state: slots, healthy nodes |
| `redis:cluster:nodes` | Node list with IP, port, role, slots |

## Templates

### redis.yaml.twig

```yaml
redis:
  image: redis:{{ version }}
  ports:
    - "${REDIS_PORT:-6379}:6379"
  volumes:
    {% if persistence %}
    - redis_data:/data
    {% endif %}
  command: redis-server --appendonly {{ persistence ? 'yes' : 'no' }}
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 10s
    timeout: 5s
    retries: 5
```

### redis-cluster.yaml.twig

```yaml
{% for i in 1..6 %}
redis-node-{{ i }}:
  image: redis:{{ version }}
  ports:
    - "{{ cluster_base_port + i - 1 }}:6379"
  command: redis-server --cluster-enabled yes --cluster-config-file nodes.conf --cluster-node-timeout 5000
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 10s
    timeout: 5s
    retries: 5
{% endfor %}

redis-cluster-init:
  image: redis:{{ version }}
  depends_on:
    redis-node-1:
      condition: service_healthy
    redis-node-2:
      condition: service_healthy
    redis-node-3:
      condition: service_healthy
    redis-node-4:
      condition: service_healthy
    redis-node-5:
      condition: service_healthy
    redis-node-6:
      condition: service_healthy
  command: >
    redis-cli --cluster create
    redis-node-1:6379 redis-node-2:6379 redis-node-3:6379
    redis-node-4:6379 redis-node-5:6379 redis-node-6:6379
    --cluster-replicas 1 --cluster-yes
  restart: "no"
```

## Quality Requirements

- PHPStan level 10
- 95% test coverage minimum
- PER coding style (php-cs-fixer)
- PHP 8.4 features

## Testing Strategy

1. **Unit tests** for all classes
2. Mock `CommandExecutor` for command tests
3. Test `ServiceDefinition` generation
4. Test config validation

## composer.json

```json
{
  "name": "seaman/redis",
  "description": "Redis and Redis Cluster plugin for Seaman",
  "type": "seaman-plugin",
  "license": "MIT",
  "require": {
    "php": "^8.4"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "phpstan/phpstan": "^2.0",
    "friendsofphp/php-cs-fixer": "^3.0",
    "mockery/mockery": "^1.6"
  },
  "autoload": {
    "psr-4": {
      "Seaman\\Redis\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Seaman\\Redis\\Tests\\": "tests/"
    }
  },
  "extra": {
    "seaman": {
      "plugin-class": "Seaman\\Redis\\RedisPlugin"
    }
  }
}
```

## Plugin Override Behavior

When installed via Composer, this plugin overrides the bundled `seaman/redis` plugin because:
1. Both have the same name (`seaman/redis`)
2. Composer plugins load after bundled plugins
3. `PluginRegistry::register()` overwrites by name
