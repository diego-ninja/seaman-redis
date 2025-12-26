<?php

// ABOUTME: Tests for redis:flush command.
// ABOUTME: Validates flush operation with confirmation.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisFlushCommand;
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
    $command = new RedisFlushCommand($this->executor);

    expect($command->getName())->toBe('redis:flush');
});

test('command flushes with --force flag', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('FLUSHALL', $args, true);
        })
        ->andReturn(new ProcessResult(0, 'OK', ''));

    $command = new RedisFlushCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['--force' => true]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('flushed');
});

test('command requires confirmation without --force', function (): void {
    $command = new RedisFlushCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->setInputs(['no']);
    $tester->execute([]);

    expect($tester->getDisplay())->toContain('Aborted');
});

test('command flushes after confirmation', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->andReturn(new ProcessResult(0, 'OK', ''));

    $command = new RedisFlushCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->setInputs(['yes']);
    $tester->execute([]);

    expect($tester->getDisplay())->toContain('flushed');
});
