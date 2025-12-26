<?php

// ABOUTME: Command to monitor Redis commands in real-time.
// ABOUTME: Shows all commands received by the server.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;

final class RedisMonitorCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:monitor';
    }

    protected function getCommandDescription(): string
    {
        return 'Monitor Redis commands in real-time';
    }

    protected function doExecute(): int
    {
        $this->output->writeln('<info>Monitoring Redis commands (Ctrl+C to stop)...</info>');

        $result = $this->executeOnRedis(['redis-cli', 'MONITOR'], interactive: true);

        return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
