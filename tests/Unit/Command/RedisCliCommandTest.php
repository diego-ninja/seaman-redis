<?php

// ABOUTME: Tests for redis:cli command.
// ABOUTME: Validates interactive CLI execution.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisCliCommand;
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
    $command = new RedisCliCommand($this->executor);

    expect($command->getName())->toBe('redis:cli');
});

test('command has description', function (): void {
    $command = new RedisCliCommand($this->executor);

    expect($command->getDescription())->toContain('redis-cli');
});

test('command executes on standalone redis by default', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('redis', $args, true) && in_array('redis-cli', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new RedisCliCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
});

test('command executes on cluster with --cluster flag', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('redis-node-1', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new RedisCliCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['--cluster' => true]);

    expect($tester->getStatusCode())->toBe(0);
});
