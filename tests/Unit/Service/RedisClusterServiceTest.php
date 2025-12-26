<?php

// ABOUTME: Tests for Redis Cluster service definition.
// ABOUTME: Validates 6-node cluster configuration.

declare(strict_types=1);

use Seaman\Enum\ServiceCategory;
use Seaman\Plugin\ServiceDefinition;
use Seaman\Redis\RedisPlugin;

test('redis cluster service has correct name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service)->toBeInstanceOf(ServiceDefinition::class)
        ->and($service->name)->toBe('redis-cluster');
});

test('redis cluster service has cache category', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service->category)->toBe(ServiceCategory::Cache);
});

test('redis cluster uses 6 consecutive ports', function (): void {
    $plugin = new RedisPlugin();
    $plugin->configure(['cluster_base_port' => 6380]);

    $service = $plugin->redisClusterService();

    expect($service->ports)->toBe([6380, 6381, 6382, 6383, 6384, 6385]);
});

test('redis cluster template exists', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect(file_exists($service->template))->toBeTrue();
});

test('redis cluster has display name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service->displayName)->toBe('Redis Cluster');
});

test('redis cluster has health check', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service->healthCheck)->not->toBeNull()
        ->and($service->healthCheck->test)->toContain('redis-cli');
});
