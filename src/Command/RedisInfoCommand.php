<?php

// ABOUTME: Command to display Redis server information.
// ABOUTME: Supports filtering by section (memory, stats, etc.).

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

final class RedisInfoCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:info';
    }

    protected function getCommandDescription(): string
    {
        return 'Display Redis server information';
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'section',
            's',
            InputOption::VALUE_REQUIRED,
            'Info section (server, clients, memory, stats, replication, cpu, cluster, keyspace)',
        );
    }

    protected function doExecute(): int
    {
        $section = $this->input->getOption('section');

        $command = ['redis-cli', 'INFO'];
        if (is_string($section) && $section !== '') {
            $command[] = $section;
        }

        $result = $this->executeOnRedis($command);

        if ($result->isSuccessful()) {
            $this->output->writeln($result->output);

            return Command::SUCCESS;
        }

        $this->output->writeln('<error>Failed to get Redis info.</error>');

        return Command::FAILURE;
    }
}
