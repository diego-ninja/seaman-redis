<?php

// ABOUTME: Tests for redis:cluster:nodes command.
// ABOUTME: Validates cluster nodes display.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\ClusterNodesCommand;
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
    $command = new ClusterNodesCommand($this->executor);

    expect($command->getName())->toBe('redis:cluster:nodes');
});

test('command shows cluster nodes', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('CLUSTER', $args, true) && in_array('NODES', $args, true);
        })
        ->andReturn(new ProcessResult(0, "node1 127.0.0.1:6379 master - 0 0 1", ''));

    $command = new ClusterNodesCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('master');
});

test('command always uses cluster node', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('redis-node-1', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new ClusterNodesCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
});

test('command shows error when cluster not running', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->andReturn(new ProcessResult(1, '', 'error'));

    $command = new ClusterNodesCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(1);
    expect($tester->getDisplay())->toContain('Failed');
});
