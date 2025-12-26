<?php

// ABOUTME: Command to list Redis keys matching a pattern.
// ABOUTME: Pattern defaults to * (all keys).

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

final class RedisKeysCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:keys';
    }

    protected function getCommandDescription(): string
    {
        return 'List keys matching a pattern';
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addArgument(
            'pattern',
            InputArgument::OPTIONAL,
            'Key pattern to match',
            '*',
        );
    }

    protected function doExecute(): int
    {
        $pattern = $this->input->getArgument('pattern');
        assert(is_string($pattern));

        $result = $this->executeOnRedis(['redis-cli', 'KEYS', $pattern]);

        if ($result->isSuccessful()) {
            $keys = array_filter(explode("\n", trim($result->output)));

            if (empty($keys)) {
                $this->output->writeln('<comment>No keys found.</comment>');
            } else {
                $this->output->writeln(sprintf('<info>Found %d keys:</info>', count($keys)));
                foreach ($keys as $key) {
                    $this->output->writeln("  - {$key}");
                }
            }

            return Command::SUCCESS;
        }

        $this->output->writeln('<error>Failed to list keys.</error>');

        return Command::FAILURE;
    }
}
