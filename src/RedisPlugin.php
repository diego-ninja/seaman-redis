<?php

// ABOUTME: Main plugin class for seaman/redis.
// ABOUTME: Provides Redis standalone and cluster services with management commands.

declare(strict_types=1);

namespace Seaman\Redis;

use Seaman\Plugin\Attribute\AsSeamanPlugin;
use Seaman\Plugin\Config\ConfigSchema;
use Seaman\Plugin\PluginInterface;

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
}
