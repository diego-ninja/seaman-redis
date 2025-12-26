# seaman/redis

Redis and Redis Cluster plugin for [Seaman](https://github.com/diego-ninja/seaman).

## Installation

```bash
composer require seaman/redis
```

Or via Seaman CLI:

```bash
seaman plugin:install seaman/redis
```

## Services

### Redis Standalone

Single Redis instance with optional persistence.

```bash
seaman service:add redis
```

### Redis Cluster

6-node Redis Cluster (3 masters + 3 replicas) with automatic initialization.

```bash
seaman service:add redis-cluster
```

## Commands

| Command | Description |
|---------|-------------|
| `redis:cli` | Open interactive redis-cli session |
| `redis:info [--section]` | Display Redis server information |
| `redis:keys [pattern]` | List keys matching a pattern |
| `redis:flush [--force]` | Flush all Redis keys |
| `redis:monitor` | Monitor Redis commands in real-time |
| `redis:cluster:info` | Display Redis Cluster information |
| `redis:cluster:nodes` | Display Redis Cluster nodes |

### Examples

```bash
# Open Redis CLI
seaman redis:cli

# Open Redis CLI on cluster
seaman redis:cli --cluster

# Show memory info
seaman redis:info --section memory

# List all user keys
seaman redis:keys "user:*"

# Monitor commands in real-time
seaman redis:monitor

# Flush all data (with confirmation)
seaman redis:flush

# Flush without confirmation
seaman redis:flush --force

# Show cluster status
seaman redis:cluster:info
seaman redis:cluster:nodes
```

## Configuration

In your `seaman.yaml`:

```yaml
plugins:
  seaman/redis:
    version: '7-alpine'      # Redis Docker image tag
    port: 6379               # Standalone Redis port
    cluster_base_port: 6380  # Starting port for cluster nodes (uses 6 consecutive ports)
    persistence: false       # Enable AOF persistence
```

### Available Versions

- `7-alpine` (default)
- `6-alpine`
- `alpine`
- `latest`

## Requirements

- PHP 8.4+
- Seaman 1.1.0+

## License

MIT
