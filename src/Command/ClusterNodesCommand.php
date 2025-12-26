<?php

// ABOUTME: Command to display Redis Cluster nodes.
// ABOUTME: Shows all nodes with their roles, slots, and status.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;

final class ClusterNodesCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:cluster:nodes';
    }

    protected function getCommandDescription(): string
    {
        return 'Display Redis Cluster nodes';
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
        $result = $this->executeOnRedis(['redis-cli', 'CLUSTER', 'NODES']);

        if ($result->isSuccessful()) {
            $this->output->writeln('<info>Redis Cluster Nodes:</info>');
            $this->output->writeln($result->output);

            return Command::SUCCESS;
        }

        $this->output->writeln('<error>Failed to get cluster nodes. Is the cluster running?</error>');

        return Command::FAILURE;
    }
}
