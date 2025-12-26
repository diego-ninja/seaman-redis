<?php

// ABOUTME: Main plugin class for seaman/redis.
// ABOUTME: Provides Redis standalone and cluster services with management commands.

declare(strict_types=1);

namespace Seaman\Redis;

use Seaman\Contract\CommandExecutor;
use Seaman\Enum\ServiceCategory;
use Seaman\Service\Process\RealCommandExecutor;
use Seaman\Plugin\Attribute\AsSeamanPlugin;
use Seaman\Plugin\Attribute\ProvidesCommand;
use Seaman\Plugin\Attribute\ProvidesService;
use Seaman\Plugin\Config\ConfigSchema;
use Seaman\Plugin\PluginInterface;
use Seaman\Plugin\ServiceDefinition;
use Seaman\Redis\Command\ClusterInfoCommand;
use Seaman\Redis\Command\ClusterNodesCommand;
use Seaman\Redis\Command\RedisCliCommand;
use Seaman\Redis\Command\RedisFlushCommand;
use Seaman\Redis\Command\RedisInfoCommand;
use Seaman\Redis\Command\RedisKeysCommand;
use Seaman\Redis\Command\RedisMonitorCommand;
use Seaman\ValueObject\HealthCheck;

#[AsSeamanPlugin(
    name: 'seaman/redis',
    version: '1.0.0',
    description: 'Redis and Redis Cluster plugin for Seaman',
)]
final class RedisPlugin implements PluginInterface
{
    private ConfigSchema $schema;

    /** @var array<string, mixed> */
    private array $config = [];

    private ?CommandExecutor $executor = null;

    public function __construct()
    {
        $this->schema = ConfigSchema::create()
            ->string('version', default: '7-alpine')
                ->label('Redis version')
                ->description('Docker image tag to use')
                ->enum(['6-alpine', '7-alpine', 'alpine', 'latest'])
            ->integer('port', default: 6379, min: 1, max: 65535)
                ->label('Standalone port')
                ->description('Host port for Redis standalone')
            ->integer('cluster_base_port', default: 6380, min: 1, max: 65535)
                ->label('Cluster base port')
                ->description('Starting port for cluster nodes (uses 6 consecutive ports)')
            ->boolean('persistence', default: false)
                ->label('Enable persistence')
                ->description('Enable Redis data persistence with AOF');

        $this->config = $this->schema->validate([]);
    }

    public function getName(): string
    {
        return 'seaman/redis';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Redis and Redis Cluster plugin for Seaman';
    }

    public function configSchema(): ConfigSchema
    {
        return $this->schema;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function configure(array $values): void
    {
        $this->config = $this->schema->validate($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    #[ProvidesService(name: 'redis', category: ServiceCategory::Cache)]
    public function redisService(): ServiceDefinition
    {
        $port = $this->config['port'];
        assert(is_int($port));

        $version = $this->config['version'];
        assert(is_string($version));

        $persistence = $this->config['persistence'];
        assert(is_bool($persistence));

        return new ServiceDefinition(
            name: 'redis',
            template: __DIR__ . '/../templates/redis.yaml.twig',
            displayName: 'Redis',
            description: 'In-memory data store for cache and sessions',
            icon: '🔴',
            category: ServiceCategory::Cache,
            ports: [$port],
            internalPorts: [6379],
            defaultConfig: [
                'version' => $version,
                'port' => $port,
                'persistence' => $persistence,
            ],
            healthCheck: new HealthCheck(
                test: ['CMD', 'redis-cli', 'ping'],
                interval: '10s',
                timeout: '5s',
                retries: 5,
            ),
            configSchema: $this->schema,
        );
    }

    #[ProvidesService(name: 'redis-cluster', category: ServiceCategory::Cache)]
    public function redisClusterService(): ServiceDefinition
    {
        $basePort = $this->config['cluster_base_port'];
        assert(is_int($basePort));

        $version = $this->config['version'];
        assert(is_string($version));

        $persistence = $this->config['persistence'];
        assert(is_bool($persistence));

        $ports = [];
        for ($i = 0; $i < 6; $i++) {
            $ports[] = $basePort + $i;
        }

        return new ServiceDefinition(
            name: 'redis-cluster',
            template: __DIR__ . '/../templates/redis-cluster.yaml.twig',
            displayName: 'Redis Cluster',
            description: 'Redis Cluster with 3 masters and 3 replicas',
            icon: '🔴',
            category: ServiceCategory::Cache,
            ports: $ports,
            internalPorts: [6379],
            defaultConfig: [
                'version' => $version,
                'cluster_base_port' => $basePort,
                'persistence' => $persistence,
            ],
            healthCheck: new HealthCheck(
                test: ['CMD', 'redis-cli', 'ping'],
                interval: '10s',
                timeout: '5s',
                retries: 5,
            ),
            configSchema: $this->schema,
        );
    }

    public function setCommandExecutor(CommandExecutor $executor): void
    {
        $this->executor = $executor;
    }

    private function getExecutor(): CommandExecutor
    {
        if ($this->executor === null) {
            $this->executor = new RealCommandExecutor();
        }

        return $this->executor;
    }

    #[ProvidesCommand]
    public function cliCommand(): RedisCliCommand
    {
        return new RedisCliCommand($this->getExecutor());
    }

    #[ProvidesCommand]
    public function flushCommand(): RedisFlushCommand
    {
        return new RedisFlushCommand($this->getExecutor());
    }

    #[ProvidesCommand]
    public function infoCommand(): RedisInfoCommand
    {
        return new RedisInfoCommand($this->getExecutor());
    }

    #[ProvidesCommand]
    public function monitorCommand(): RedisMonitorCommand
    {
        return new RedisMonitorCommand($this->getExecutor());
    }

    #[ProvidesCommand]
    public function keysCommand(): RedisKeysCommand
    {
        return new RedisKeysCommand($this->getExecutor());
    }

    #[ProvidesCommand]
    public function clusterInfoCommand(): ClusterInfoCommand
    {
        return new ClusterInfoCommand($this->getExecutor());
    }

    #[ProvidesCommand]
    public function clusterNodesCommand(): ClusterNodesCommand
    {
        return new ClusterNodesCommand($this->getExecutor());
    }
}
