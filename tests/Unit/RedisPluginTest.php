<?php

// ABOUTME: Tests for RedisPlugin main class.
// ABOUTME: Validates plugin registration, services, and configuration.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\ClusterInfoCommand;
use Seaman\Redis\Command\ClusterNodesCommand;
use Seaman\Redis\Command\RedisCliCommand;
use Seaman\Redis\Command\RedisFlushCommand;
use Seaman\Redis\Command\RedisInfoCommand;
use Seaman\Redis\Command\RedisKeysCommand;
use Seaman\Redis\Command\RedisMonitorCommand;
use Seaman\Redis\RedisPlugin;

test('plugin has correct name', function (): void {
    $plugin = new RedisPlugin();

    expect($plugin->getName())->toBe('seaman/redis');
});

test('plugin has correct version', function (): void {
    $plugin = new RedisPlugin();

    expect($plugin->getVersion())->toBe('1.0.0');
});

test('plugin has description', function (): void {
    $plugin = new RedisPlugin();

    expect($plugin->getDescription())->toContain('Redis');
});

test('plugin provides config schema', function (): void {
    $plugin = new RedisPlugin();

    $schema = $plugin->configSchema();
    $fields = $schema->getFields();

    expect($fields)->toHaveKeys(['version', 'port', 'cluster_base_port', 'persistence']);
});

test('plugin validates configuration', function (): void {
    $plugin = new RedisPlugin();

    $plugin->configure(['version' => '7-alpine', 'port' => 6380]);

    // No exception means success
    expect($plugin->getConfig())->toHaveKey('port')
        ->and($plugin->getConfig()['port'])->toBe(6380);
});

test('plugin uses default values when not configured', function (): void {
    $plugin = new RedisPlugin();

    $config = $plugin->getConfig();

    expect($config['version'])->toBe('7-alpine')
        ->and($config['port'])->toBe(6379)
        ->and($config['cluster_base_port'])->toBe(6380)
        ->and($config['persistence'])->toBeFalse();
});

test('plugin provides cli command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->cliCommand())->toBeInstanceOf(RedisCliCommand::class);
});

test('plugin provides flush command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->flushCommand())->toBeInstanceOf(RedisFlushCommand::class);
});

test('plugin provides info command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->infoCommand())->toBeInstanceOf(RedisInfoCommand::class);
});

test('plugin provides monitor command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->monitorCommand())->toBeInstanceOf(RedisMonitorCommand::class);
});

test('plugin provides keys command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->keysCommand())->toBeInstanceOf(RedisKeysCommand::class);
});

test('plugin provides cluster info command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->clusterInfoCommand())->toBeInstanceOf(ClusterInfoCommand::class);
});

test('plugin provides cluster nodes command', function (): void {
    $executor = Mockery::mock(CommandExecutor::class);
    $plugin = new RedisPlugin();
    $plugin->setCommandExecutor($executor);

    expect($plugin->clusterNodesCommand())->toBeInstanceOf(ClusterNodesCommand::class);
});

test('plugin throws exception when executor not set', function (): void {
    $plugin = new RedisPlugin();

    expect(fn() => $plugin->cliCommand())->toThrow(\RuntimeException::class);
});
