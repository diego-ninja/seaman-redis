<?php

// ABOUTME: Command to flush all Redis keys.
// ABOUTME: Requires confirmation unless --force is used.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class RedisFlushCommand extends AbstractRedisCommand
{
    protected function getCommandName(): string
    {
        return 'redis:flush';
    }

    protected function getCommandDescription(): string
    {
        return 'Flush all Redis keys';
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Skip confirmation prompt',
        );
    }

    protected function doExecute(): int
    {
        $force = $this->input->getOption('force');

        if (!$force) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'This will delete ALL keys. Continue? [y/N] ',
                false,
            );

            if (!$helper->ask($this->input, $this->output, $question)) {
                $this->output->writeln('<comment>Aborted.</comment>');

                return Command::SUCCESS;
            }
        }

        $result = $this->executeOnRedis(['redis-cli', 'FLUSHALL']);

        if ($result->isSuccessful()) {
            $this->output->writeln('<info>All keys flushed successfully.</info>');

            return Command::SUCCESS;
        }

        $this->output->writeln('<error>Failed to flush keys.</error>');

        return Command::FAILURE;
    }
}
