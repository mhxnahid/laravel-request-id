<?php

namespace Mxnwire\RequestId\Tests;

use Mxnwire\RequestId\RequestIdServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RequestIdServiceProvider::class,
        ];
    }
}
