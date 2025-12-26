# seaman/redis Plugin Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the seaman/redis plugin with Redis standalone, Redis Cluster (3+3), and 7 management commands.

**Architecture:** External Composer package that overrides bundled plugin. Uses Seaman's plugin system with `#[ProvidesService]` and `#[ProvidesCommand]` attributes.

**Tech Stack:** PHP 8.4, Symfony Console, Twig, PHPUnit, PHPStan level 10

---

## Task 1: Project Setup

**Files:**
- Create: `composer.json`
- Create: `phpstan.neon`
- Create: `phpunit.xml`
- Create: `.php-cs-fixer.dist.php`
- Create: `.gitignore`

**Step 1: Create composer.json**

```json
{
    "name": "seaman/redis",
    "description": "Redis and Redis Cluster plugin for Seaman",
    "type": "seaman-plugin",
    "license": "MIT",
    "authors": [
        {
            "name": "Diego Rin",
            "email": "yosoy@diego.ninja"
        }
    ],
    "require": {
        "php": "^8.4",
        "symfony/console": "^7.0"
    },
    "require-dev": {
        "pestphp/pest": "^3.0",
        "phpstan/phpstan": "^2.0",
        "friendsofphp/php-cs-fixer": "^3.0",
        "mockery/mockery": "^1.6"
    },
    "autoload": {
        "psr-4": {
            "Seaman\\Redis\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Seaman\\Redis\\Tests\\": "tests/"
        }
    },
    "extra": {
        "seaman": {
            "plugin-class": "Seaman\\Redis\\RedisPlugin"
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "stable"
}
```

**Step 2: Create phpstan.neon**

```neon
parameters:
    level: 10
    paths:
        - src
    tmpDir: .phpstan-cache
    treatPhpDocTypesAsCertain: false
```

**Step 3: Create phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    <coverage>
        <report>
            <text outputFile="php://stdout" showOnlySummary="true"/>
        </report>
    </coverage>
</phpunit>
```

**Step 4: Create .php-cs-fixer.dist.php**

```php
<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PER-CS:risky' => true,
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
```

**Step 5: Create .gitignore**

```
/vendor/
/.phpstan-cache/
/.phpunit.cache/
/.php-cs-fixer.cache
composer.lock
```

**Step 6: Create directory structure**

```bash
mkdir -p src/Command src/Service templates tests/Unit/Command tests/Unit/Service
```

**Step 7: Install dependencies**

Run: `composer install`

**Step 8: Commit**

```bash
git add -A
git commit -m "chore: initial project setup with composer and tooling"
```

---

## Task 2: RedisPlugin Main Class

**Files:**
- Create: `src/RedisPlugin.php`
- Create: `tests/Unit/RedisPluginTest.php`

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for RedisPlugin main class.
// ABOUTME: Validates plugin registration, services, and commands.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit;

use Seaman\Redis\RedisPlugin;

test('plugin has correct name', function (): void {
    $plugin = new RedisPlugin();

    expect($plugin->getName())->toBe('seaman/redis');
});

test('plugin has correct version', function (): void {
    $plugin = new RedisPlugin();

    expect($plugin->getVersion())->toBe('1.0.0');
});

test('plugin has description', function (): void {
    $plugin = new RedisPlugin();

    expect($plugin->getDescription())->toContain('Redis');
});

test('plugin provides config schema', function (): void {
    $plugin = new RedisPlugin();

    $schema = $plugin->configSchema();
    $fields = $schema->getFields();

    expect($fields)->toHaveKeys(['version', 'port', 'cluster_base_port', 'persistence']);
});

test('plugin validates configuration', function (): void {
    $plugin = new RedisPlugin();

    $plugin->configure(['version' => '7-alpine', 'port' => 6380]);

    // No exception means success
    expect(true)->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/RedisPluginTest.php`
Expected: FAIL with "Class 'Seaman\Redis\RedisPlugin' not found"

**Step 3: Write minimal implementation**

```php
<?php

// ABOUTME: Main plugin class for seaman/redis.
// ABOUTME: Provides Redis standalone and cluster services with management commands.

declare(strict_types=1);

namespace Seaman\Redis;

use Seaman\Enum\ServiceCategory;
use Seaman\Plugin\Attribute\AsSeamanPlugin;
use Seaman\Plugin\Attribute\ProvidesService;
use Seaman\Plugin\Config\ConfigSchema;
use Seaman\Plugin\PluginInterface;
use Seaman\Plugin\ServiceDefinition;
use Seaman\ValueObject\HealthCheck;

#[AsSeamanPlugin(
    name: 'seaman/redis',
    version: '1.0.0',
    description: 'Redis and Redis Cluster plugin for Seaman',
)]
final class RedisPlugin implements PluginInterface
{
    private ConfigSchema $schema;

    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct()
    {
        $this->schema = ConfigSchema::create()
            ->string('version', default: '7-alpine')
                ->label('Redis version')
                ->description('Docker image tag to use')
                ->enum(['6-alpine', '7-alpine', 'alpine', 'latest'])
            ->integer('port', default: 6379, min: 1, max: 65535)
                ->label('Standalone port')
                ->description('Host port for Redis standalone')
            ->integer('cluster_base_port', default: 6380, min: 1, max: 65535)
                ->label('Cluster base port')
                ->description('Starting port for cluster nodes (uses 6 consecutive ports)')
            ->boolean('persistence', default: false)
                ->label('Enable persistence')
                ->description('Enable Redis data persistence with AOF');

        $this->config = $this->schema->validate([]);
    }

    public function getName(): string
    {
        return 'seaman/redis';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Redis and Redis Cluster plugin for Seaman';
    }

    public function configSchema(): ConfigSchema
    {
        return $this->schema;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function configure(array $values): void
    {
        $this->config = $this->schema->validate($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/RedisPluginTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/RedisPlugin.php tests/Unit/RedisPluginTest.php
git commit -m "feat: add RedisPlugin main class with config schema"
```

---

## Task 3: Redis Standalone Service

**Files:**
- Modify: `src/RedisPlugin.php`
- Create: `templates/redis.yaml.twig`
- Create: `tests/Unit/RedisServiceTest.php`

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for Redis standalone service definition.
// ABOUTME: Validates service configuration and template generation.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit;

use Seaman\Enum\ServiceCategory;
use Seaman\Plugin\ServiceDefinition;
use Seaman\Redis\RedisPlugin;

test('redis service has correct name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service)->toBeInstanceOf(ServiceDefinition::class);
    expect($service->name)->toBe('redis');
});

test('redis service has cache category', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service->category)->toBe(ServiceCategory::Cache);
});

test('redis service has health check', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect($service->healthCheck)->not->toBeNull();
    expect($service->healthCheck->test)->toContain('redis-cli');
});

test('redis service uses configured port', function (): void {
    $plugin = new RedisPlugin();
    $plugin->configure(['port' => 6380]);

    $service = $plugin->redisService();

    expect($service->ports)->toContain(6380);
});

test('redis service template exists', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisService();

    expect(file_exists($service->template))->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/RedisServiceTest.php`
Expected: FAIL with "Call to undefined method redisService"

**Step 3: Create template file**

Create `templates/redis.yaml.twig`:

```twig
redis:
  image: redis:{{ version }}
  ports:
    - "${REDIS_PORT:-{{ port }}}:6379"
{% if persistence %}
  volumes:
    - redis_data:/data
{% endif %}
  command: redis-server --appendonly {{ persistence ? 'yes' : 'no' }}
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 10s
    timeout: 5s
    retries: 5
  restart: unless-stopped
```

**Step 4: Add service method to RedisPlugin**

Add to `src/RedisPlugin.php`:

```php
#[ProvidesService(name: 'redis', category: ServiceCategory::Cache)]
public function redisService(): ServiceDefinition
{
    $port = $this->config['port'];
    assert(is_int($port));

    $version = $this->config['version'];
    assert(is_string($version));

    $persistence = $this->config['persistence'];
    assert(is_bool($persistence));

    return new ServiceDefinition(
        name: 'redis',
        template: __DIR__ . '/../templates/redis.yaml.twig',
        displayName: 'Redis',
        description: 'In-memory data store for caching and sessions',
        icon: '🔴',
        category: ServiceCategory::Cache,
        ports: [$port],
        internalPorts: [6379],
        defaultConfig: [
            'version' => $version,
            'port' => $port,
            'persistence' => $persistence,
        ],
        healthCheck: new HealthCheck(
            test: ['CMD', 'redis-cli', 'ping'],
            interval: '10s',
            timeout: '5s',
            retries: 5,
        ),
        configSchema: $this->schema,
    );
}
```

**Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/RedisServiceTest.php`
Expected: PASS

**Step 6: Commit**

```bash
git add src/RedisPlugin.php templates/redis.yaml.twig tests/Unit/RedisServiceTest.php
git commit -m "feat: add Redis standalone service with template"
```

---

## Task 4: Redis Cluster Service

**Files:**
- Modify: `src/RedisPlugin.php`
- Create: `templates/redis-cluster.yaml.twig`
- Create: `tests/Unit/RedisClusterServiceTest.php`

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for Redis Cluster service definition.
// ABOUTME: Validates 6-node cluster configuration.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit;

use Seaman\Enum\ServiceCategory;
use Seaman\Plugin\ServiceDefinition;
use Seaman\Redis\RedisPlugin;

test('redis cluster service has correct name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service)->toBeInstanceOf(ServiceDefinition::class);
    expect($service->name)->toBe('redis-cluster');
});

test('redis cluster service has cache category', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service->category)->toBe(ServiceCategory::Cache);
});

test('redis cluster uses 6 consecutive ports', function (): void {
    $plugin = new RedisPlugin();
    $plugin->configure(['cluster_base_port' => 6380]);

    $service = $plugin->redisClusterService();

    expect($service->ports)->toBe([6380, 6381, 6382, 6383, 6384, 6385]);
});

test('redis cluster template exists', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect(file_exists($service->template))->toBeTrue();
});

test('redis cluster has display name', function (): void {
    $plugin = new RedisPlugin();

    $service = $plugin->redisClusterService();

    expect($service->displayName)->toBe('Redis Cluster');
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/RedisClusterServiceTest.php`
Expected: FAIL with "Call to undefined method redisClusterService"

**Step 3: Create cluster template**

Create `templates/redis-cluster.yaml.twig`:

```twig
{% for i in 1..6 %}
redis-node-{{ i }}:
  image: redis:{{ version }}
  ports:
    - "{{ cluster_base_port + i - 1 }}:6379"
  command: >
    redis-server
    --cluster-enabled yes
    --cluster-config-file nodes.conf
    --cluster-node-timeout 5000
    --appendonly {{ persistence ? 'yes' : 'no' }}
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 10s
    timeout: 5s
    retries: 5
  restart: unless-stopped
{% if persistence %}
  volumes:
    - redis_node_{{ i }}_data:/data
{% endif %}

{% endfor %}
redis-cluster-init:
  image: redis:{{ version }}
  depends_on:
{% for i in 1..6 %}
    redis-node-{{ i }}:
      condition: service_healthy
{% endfor %}
  command: >
    redis-cli --cluster create
    redis-node-1:6379 redis-node-2:6379 redis-node-3:6379
    redis-node-4:6379 redis-node-5:6379 redis-node-6:6379
    --cluster-replicas 1 --cluster-yes
  restart: "no"
```

**Step 4: Add cluster service method to RedisPlugin**

Add to `src/RedisPlugin.php`:

```php
#[ProvidesService(name: 'redis-cluster', category: ServiceCategory::Cache)]
public function redisClusterService(): ServiceDefinition
{
    $basePort = $this->config['cluster_base_port'];
    assert(is_int($basePort));

    $version = $this->config['version'];
    assert(is_string($version));

    $persistence = $this->config['persistence'];
    assert(is_bool($persistence));

    $ports = [];
    for ($i = 0; $i < 6; $i++) {
        $ports[] = $basePort + $i;
    }

    return new ServiceDefinition(
        name: 'redis-cluster',
        template: __DIR__ . '/../templates/redis-cluster.yaml.twig',
        displayName: 'Redis Cluster',
        description: 'Redis Cluster with 3 masters and 3 replicas',
        icon: '🔴',
        category: ServiceCategory::Cache,
        ports: $ports,
        internalPorts: [6379],
        defaultConfig: [
            'version' => $version,
            'cluster_base_port' => $basePort,
            'persistence' => $persistence,
        ],
        healthCheck: new HealthCheck(
            test: ['CMD', 'redis-cli', 'ping'],
            interval: '10s',
            timeout: '5s',
            retries: 5,
        ),
        configSchema: $this->schema,
    );
}
```

**Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/RedisClusterServiceTest.php`
Expected: PASS

**Step 6: Commit**

```bash
git add src/RedisPlugin.php templates/redis-cluster.yaml.twig tests/Unit/RedisClusterServiceTest.php
git commit -m "feat: add Redis Cluster service with 6-node template"
```

---

## Task 5: Abstract Base Command

**Files:**
- Create: `src/Command/AbstractRedisCommand.php`
- Create: `tests/Unit/Command/AbstractRedisCommandTest.php`

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for AbstractRedisCommand base class.
// ABOUTME: Validates common functionality for Redis commands.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit\Command;

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\AbstractRedisCommand;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Command\Command;
use Mockery;

beforeEach(function (): void {
    $this->executor = Mockery::mock(CommandExecutor::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('abstract command extends symfony command', function (): void {
    $command = new class($this->executor) extends AbstractRedisCommand {
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

    expect($command)->toBeInstanceOf(Command::class);
    expect($command->getName())->toBe('redis:test');
});

test('command can execute docker command on redis container', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->with(['docker', 'exec', '-it', 'redis', 'redis-cli', 'ping'])
        ->andReturn(new ProcessResult(0, 'PONG', ''));

    $command = new class($this->executor) extends AbstractRedisCommand {
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
            $result = $this->executeOnRedis(['redis-cli', 'ping']);
            return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
        }
    };

    // We would need to execute the command through Application to test this properly
    expect($command)->toBeInstanceOf(Command::class);
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/Command/AbstractRedisCommandTest.php`
Expected: FAIL with "Class 'AbstractRedisCommand' not found"

**Step 3: Write minimal implementation**

```php
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
        $dockerCommand = array_merge($dockerCommand, $command);

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
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/Command/AbstractRedisCommandTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Command/AbstractRedisCommand.php tests/Unit/Command/AbstractRedisCommandTest.php
git commit -m "feat: add AbstractRedisCommand base class"
```

---

## Task 6: Redis CLI Command

**Files:**
- Create: `src/Command/RedisCliCommand.php`
- Create: `tests/Unit/Command/RedisCliCommandTest.php`
- Modify: `src/RedisPlugin.php` (add ProvidesCommand)

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for redis:cli command.
// ABOUTME: Validates interactive CLI execution.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit\Command;

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisCliCommand;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Mockery;

beforeEach(function (): void {
    $this->executor = Mockery::mock(CommandExecutor::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('command has correct name', function (): void {
    $command = new RedisCliCommand($this->executor);

    expect($command->getName())->toBe('redis:cli');
});

test('command has description', function (): void {
    $command = new RedisCliCommand($this->executor);

    expect($command->getDescription())->toContain('redis-cli');
});

test('command executes on standalone redis by default', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args) {
            return in_array('redis', $args, true) && in_array('redis-cli', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new RedisCliCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
});

test('command executes on cluster with --cluster flag', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args) {
            return in_array('redis-node-1', $args, true);
        })
        ->andReturn(new ProcessResult(0, '', ''));

    $command = new RedisCliCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['--cluster' => true]);

    expect($tester->getStatusCode())->toBe(0);
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/Command/RedisCliCommandTest.php`
Expected: FAIL with "Class 'RedisCliCommand' not found"

**Step 3: Write implementation**

```php
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
```

**Step 4: Register command in plugin**

Add to `src/RedisPlugin.php`:

```php
use Seaman\Plugin\Attribute\ProvidesCommand;
use Seaman\Redis\Command\RedisCliCommand;
use Seaman\Contract\CommandExecutor;

// Add property
private ?CommandExecutor $executor = null;

// Add setter method
public function setCommandExecutor(CommandExecutor $executor): void
{
    $this->executor = $executor;
}

#[ProvidesCommand]
public function cliCommand(): RedisCliCommand
{
    if ($this->executor === null) {
        throw new \RuntimeException('CommandExecutor not set');
    }

    return new RedisCliCommand($this->executor);
}
```

**Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/Command/RedisCliCommandTest.php`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Command/RedisCliCommand.php tests/Unit/Command/RedisCliCommandTest.php src/RedisPlugin.php
git commit -m "feat: add redis:cli command"
```

---

## Task 7: Redis Flush Command

**Files:**
- Create: `src/Command/RedisFlushCommand.php`
- Create: `tests/Unit/Command/RedisFlushCommandTest.php`
- Modify: `src/RedisPlugin.php`

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for redis:flush command.
// ABOUTME: Validates flush operation with confirmation.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit\Command;

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisFlushCommand;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Mockery;

beforeEach(function (): void {
    $this->executor = Mockery::mock(CommandExecutor::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('command has correct name', function (): void {
    $command = new RedisFlushCommand($this->executor);

    expect($command->getName())->toBe('redis:flush');
});

test('command flushes with --force flag', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args) {
            return in_array('FLUSHALL', $args, true);
        })
        ->andReturn(new ProcessResult(0, 'OK', ''));

    $command = new RedisFlushCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['--force' => true]);

    expect($tester->getStatusCode())->toBe(0);
    expect($tester->getDisplay())->toContain('flushed');
});

test('command requires confirmation without --force', function (): void {
    $command = new RedisFlushCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->setInputs(['no']);
    $tester->execute([]);

    expect($tester->getDisplay())->toContain('Aborted');
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/Command/RedisFlushCommandTest.php`
Expected: FAIL

**Step 3: Write implementation**

```php
<?php

// ABOUTME: Command to flush all Redis keys.
// ABOUTME: Requires confirmation unless --force is used.

declare(strict_types=1);

namespace Seaman\Redis\Command;

use Symfony\Component\Console\Command\Command;
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
```

**Step 4: Register in plugin and run tests**

**Step 5: Commit**

```bash
git add src/Command/RedisFlushCommand.php tests/Unit/Command/RedisFlushCommandTest.php src/RedisPlugin.php
git commit -m "feat: add redis:flush command"
```

---

## Task 8: Redis Info Command

**Files:**
- Create: `src/Command/RedisInfoCommand.php`
- Create: `tests/Unit/Command/RedisInfoCommandTest.php`
- Modify: `src/RedisPlugin.php`

**Step 1: Write the failing test**

```php
<?php

// ABOUTME: Tests for redis:info command.
// ABOUTME: Validates info display with optional section filter.

declare(strict_types=1);

namespace Seaman\Redis\Tests\Unit\Command;

use Seaman\Contract\CommandExecutor;
use Seaman\Redis\Command\RedisInfoCommand;
use Seaman\ValueObject\ProcessResult;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Mockery;

beforeEach(function (): void {
    $this->executor = Mockery::mock(CommandExecutor::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('command has correct name', function (): void {
    $command = new RedisInfoCommand($this->executor);

    expect($command->getName())->toBe('redis:info');
});

test('command shows all info by default', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args) {
            return in_array('INFO', $args, true) && !in_array('memory', $args, true);
        })
        ->andReturn(new ProcessResult(0, '# Server\nredis_version:7.0.0', ''));

    $command = new RedisInfoCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute([]);

    expect($tester->getStatusCode())->toBe(0);
});

test('command filters by section', function (): void {
    $this->executor
        ->shouldReceive('execute')
        ->once()
        ->withArgs(function (array $args) {
            return in_array('INFO', $args, true) && in_array('memory', $args, true);
        })
        ->andReturn(new ProcessResult(0, '# Memory\nused_memory:1000', ''));

    $command = new RedisInfoCommand($this->executor);
    $app = new Application();
    $app->add($command);

    $tester = new CommandTester($command);
    $tester->execute(['--section' => 'memory']);

    expect($tester->getStatusCode())->toBe(0);
});
```

**Step 2-5: Implement, test, commit**

```php
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
```

---

## Task 9: Redis Monitor Command

**Files:**
- Create: `src/Command/RedisMonitorCommand.php`
- Create: `tests/Unit/Command/RedisMonitorCommandTest.php`

Implementation follows same pattern - executes `redis-cli MONITOR` with optional timeout.

---

## Task 10: Redis Keys Command

**Files:**
- Create: `src/Command/RedisKeysCommand.php`
- Create: `tests/Unit/Command/RedisKeysCommandTest.php`

Implementation executes `redis-cli KEYS <pattern>` with pattern argument defaulting to `*`.

---

## Task 11: Cluster Info Command

**Files:**
- Create: `src/Command/ClusterInfoCommand.php`
- Create: `tests/Unit/Command/ClusterInfoCommandTest.php`

Implementation executes `redis-cli CLUSTER INFO` on cluster node.

---

## Task 12: Cluster Nodes Command

**Files:**
- Create: `src/Command/ClusterNodesCommand.php`
- Create: `tests/Unit/Command/ClusterNodesCommandTest.php`

Implementation executes `redis-cli CLUSTER NODES` and formats output.

---

## Task 13: Register All Commands in Plugin

**Files:**
- Modify: `src/RedisPlugin.php`

Add all `#[ProvidesCommand]` methods:

```php
#[ProvidesCommand]
public function cliCommand(): RedisCliCommand { ... }

#[ProvidesCommand]
public function flushCommand(): RedisFlushCommand { ... }

#[ProvidesCommand]
public function infoCommand(): RedisInfoCommand { ... }

#[ProvidesCommand]
public function monitorCommand(): RedisMonitorCommand { ... }

#[ProvidesCommand]
public function keysCommand(): RedisKeysCommand { ... }

#[ProvidesCommand]
public function clusterInfoCommand(): ClusterInfoCommand { ... }

#[ProvidesCommand]
public function clusterNodesCommand(): ClusterNodesCommand { ... }
```

---

## Task 14: Run PHPStan and Fix Issues

Run: `./vendor/bin/phpstan analyse`

Fix any level 10 errors.

---

## Task 15: Run php-cs-fixer

Run: `./vendor/bin/php-cs-fixer fix`

---

## Task 16: Verify Test Coverage

Run: `./vendor/bin/pest --coverage`

Ensure >= 95% coverage.

---

## Task 17: Final Commit and Tag

```bash
git add -A
git commit -m "feat: complete seaman/redis plugin v1.0.0"
git tag v1.0.0
```
