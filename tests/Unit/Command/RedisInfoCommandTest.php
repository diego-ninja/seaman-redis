<?php

// ABOUTME: Tests for redis:info command.
// ABOUTME: Validates info display with optional section filter.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisInfoCommand;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->executor = Mockery::mock(CommandExecutor::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('command has correct name', function (): void {
    $command = new RedisInfoCommand($this->executor);

    expect($command->getName())->toBe('redis:info');
});

test('command shows all info by default', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('INFO', $args, true) && !in_array('memory', $args, true);
        })
        ->andReturn(new ProcessResult(0, "# Server\nredis_version:7.0.0", ''));

    $command = new RedisInfoCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('redis_version');
});

test('command filters by section', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('INFO', $args, true) && in_array('memory', $args, true);
        })
        ->andReturn(new ProcessResult(0, "# Memory\nused_memory:1000", ''));

    $command = new RedisInfoCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['--section' => 'memory']);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('used_memory');
});
