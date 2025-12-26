<?php

// ABOUTME: Abstract base class for Redis commands.
// ABOUTME: Provides common functionality for executing Redis operations.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Seaman\Contract\CommandExecutor;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRedisCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(
        protected readonly CommandExecutor $executor,
    ) {
        parent::__construct($this->getCommandName());
    }

    abstract protected function getCommandName(): string;

    abstract protected function getCommandDescription(): string;

    abstract protected function doExecute(): int;

    protected function configure(): void
    {
        $this
            ->setDescription($this->getCommandDescription())
            ->addOption(
                'cluster',
                'c',
                InputOption::VALUE_NONE,
                'Execute on Redis Cluster (first node)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->doExecute();
    }

    /**
     * @param list<string> $command
     */
    protected function executeOnRedis(array $command, bool $interactive = false): ProcessResult
    {
        $container = $this->getContainerName();

        $dockerCommand = ['docker', 'exec'];
        if ($interactive) {
            $dockerCommand[] = '-it';
        }
        $dockerCommand[] = $container;
        $dockerCommand = [...$dockerCommand, ...$command];

        return $this->executor->execute($dockerCommand);
    }

    protected function getContainerName(): string
    {
        $isCluster = $this->input->getOption('cluster');

        return $isCluster ? 'redis-node-1' : 'redis';
    }

    protected function isClusterMode(): bool
    {
        return (bool) $this->input->getOption('cluster');
    }
}
