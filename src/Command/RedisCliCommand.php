<?php

// ABOUTME: Command to open interactive redis-cli session.
// ABOUTME: Supports both standalone Redis and cluster mode.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;

final class RedisCliCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:cli';
    }

    protected function getCommandDescription(): string
    {
        return 'Open interactive redis-cli session';
    }

    protected function doExecute(): int
    {
        $result = $this->executeOnRedis(['redis-cli'], interactive: true);

        return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
