<?php

// ABOUTME: Tests for Redis standalone service definition.
// ABOUTME: Validates service configuration and template generation.

declare(strict_types=1);

use Seaman\Enum\ServiceCategory;
use Seaman\Plugin\ServiceDefinition;
use Seaman\Redis\RedisPlugin;

test('redis service has correct name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service)->toBeInstanceOf(ServiceDefinition::class)
        ->and($service->name)->toBe('redis');
});

test('redis service has cache category', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service->category)->toBe(ServiceCategory::Cache);
});

test('redis service has health check', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service->healthCheck)->not->toBeNull()
        ->and($service->healthCheck->test)->toContain('redis-cli');
});

test('redis service uses configured port', function (): void {
    $plugin = new RedisPlugin();
    $plugin->configure(['port' => 6380]);

    $service = $plugin->redisService();

    expect($service->ports)->toContain(6380);
});

test('redis service template exists', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect(file_exists($service->template))->toBeTrue();
});

test('redis service has display name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service->displayName)->toBe('Redis');
});

test('redis service has description', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service->description)->toContain('cache');
});
