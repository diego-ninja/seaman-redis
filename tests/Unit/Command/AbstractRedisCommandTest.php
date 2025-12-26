<?php

// ABOUTME: Tests for AbstractRedisCommand base class.
// ABOUTME: Validates common functionality for Redis commands.

declare(strict_types=1);

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\AbstractRedisCommand;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Command\Command;

beforeEach(function (): void {
    $this->executor = Mockery::mock(CommandExecutor::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('abstract command extends symfony command', function (): void {
    $command = new class ($this->executor) extends AbstractRedisCommand {
        protected function getCommandName(): string
        {
            return 'redis:test';
        }

        protected function getCommandDescription(): string
        {
            return 'Test command';
        }

        protected function doExecute(): int
        {
            return Command::SUCCESS;
        }
    };

    expect($command)->toBeInstanceOf(Command::class)
        ->and($command->getName())->toBe('redis:test');
});

test('abstract command has cluster option', function (): void {
    $command = new class ($this->executor) extends AbstractRedisCommand {
        protected function getCommandName(): string
        {
            return 'redis:test';
        }

        protected function getCommandDescription(): string
        {
            return 'Test command';
        }

        protected function doExecute(): int
        {
            return Command::SUCCESS;
        }
    };

    $definition = $command->getDefinition();

    expect($definition->hasOption('cluster'))->toBeTrue();
});
