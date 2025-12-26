<?php

// ABOUTME: Command to display Redis Cluster information.
// ABOUTME: Shows cluster state, slots, and node status.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;

final class ClusterInfoCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:cluster:info';
    }

    protected function getCommandDescription(): string
    {
        return 'Display Redis Cluster information';
    }

    protected function configure(): void
    {
        // Skip parent configure to remove --cluster option (always uses cluster)
        $this->setDescription($this->getCommandDescription());
    }

    protected function getContainerName(): string
    {
        // Always use cluster node
        return 'redis-node-1';
    }

    protected function doExecute(): int
    {
        $result = $this->executeOnRedis(['redis-cli', 'CLUSTER', 'INFO']);

        if ($result->isSuccessful()) {
            $this->output->writeln('<info>Redis Cluster Info:</info>');
            $this->output->writeln($result->output);

            return Command::SUCCESS;
        }

        $this->output->writeln('<error>Failed to get cluster info. Is the cluster running?</error>');

        return Command::FAILURE;
    }
}
