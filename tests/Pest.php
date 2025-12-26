<?php

// ABOUTME: Pest configuration file.
// ABOUTME: Sets up the test environment for the seaman/redis plugin.

declare(strict_types=1);

uses()->beforeEach(function (): void {
    Mockery::close();
})->in('Unit');
