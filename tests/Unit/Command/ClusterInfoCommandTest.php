<?php

// ABOUTME: Tests for redis:cluster:info command.
// ABOUTME: Validates cluster information display.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\ClusterInfoCommand;
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
    $command = new ClusterInfoCommand($this->executor);

    expect($command->getName())->toBe('redis:cluster:info');
});

test('command shows cluster info', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('CLUSTER', $args, true) && in_array('INFO', $args, true);
        })
        ->andReturn(new ProcessResult(0, "cluster_state:ok\ncluster_slots_assigned:16384", ''));

    $command = new ClusterInfoCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('cluster_state');
});

test('command always uses cluster node', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('redis-node-1', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new ClusterInfoCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
});
