<?php

// ABOUTME: Tests for redis:keys command.
// ABOUTME: Validates key listing with pattern matching.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisKeysCommand;
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
    $command = new RedisKeysCommand($this->executor);

    expect($command->getName())->toBe('redis:keys');
});

test('command lists all keys by default', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('KEYS', $args, true) && in_array('*', $args, true);
        })
        ->andReturn(new ProcessResult(0, "key1\nkey2\nkey3", ''));

    $command = new RedisKeysCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('Found 3 keys');
});

test('command uses custom pattern', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args): bool {
            return in_array('KEYS', $args, true) && in_array('user:*', $args, true);
        })
        ->andReturn(new ProcessResult(0, "user:1\nuser:2", ''));

    $command = new RedisKeysCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['pattern' => 'user:*']);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('Found 2 keys');
});

test('command shows message when no keys found', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new RedisKeysCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getDisplay())->toContain('No keys found');
});

test('command shows error when keys fails', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->andReturn(new ProcessResult(1, '', 'error'));

    $command = new RedisKeysCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(1);
    expect($tester->getDisplay())->toContain('Failed');
});
