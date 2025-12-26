<?php

// ABOUTME: Tests for redis:monitor command.
// ABOUTME: Validates real-time command monitoring.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisMonitorCommand;
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
    $command = new RedisMonitorCommand($this->executor);

    expect($command->getName())->toBe('redis:monitor');
});

test('command executes monitor', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('MONITOR', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new RedisMonitorCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('Monitoring');
});
