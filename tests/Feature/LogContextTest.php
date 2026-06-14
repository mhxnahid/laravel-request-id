<?php

namespace Mxnwire\RequestId\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Mxnwire\RequestId\Tests\TestCase;

class LogContextTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Default to a Monolog channel backed by a TestHandler so the records
        // the middleware's processor produced can be inspected after a request.
        $app['config']->set('logging.default', 'capture');
        $app['config']->set('logging.channels.capture', [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/_log', function () {
            Log::info('hello');

            return response('ok');
        });
    }

    /** Pull the TestHandler off the default channel's underlying Monolog logger. */
    protected function handler(): TestHandler
    {
        foreach (Log::driver()->getLogger()->getHandlers() as $handler) {
            if ($handler instanceof TestHandler) {
                return $handler;
            }
        }

        $this->fail('TestHandler not registered on the default log channel.');
    }

    /** Read the "extra" bag from a captured record across Monolog 2/3. */
    protected function extra($record): array
    {
        return $record instanceof \Monolog\LogRecord ? $record->extra : $record['extra'];
    }

    public function test_it_adds_request_id_to_log_records(): void
    {
        $id = '11111111-1111-4111-8111-111111111111';

        $this->get('/_log', ['X-Request-Id' => $id])->assertOk();

        $records = $this->handler()->getRecords();
        $this->assertNotEmpty($records, 'expected a log record to be captured');

        $extra = $this->extra($records[0]);
        $this->assertSame($id, $extra['request_id']);
        $this->assertNull($extra['session_id']);
        $this->assertNull($extra['correlation_id']);
    }

    public function test_it_does_not_log_when_logging_is_disabled(): void
    {
        config()->set('request-id.log', false);

        $this->get('/_log', ['X-Request-Id' => '11111111-1111-4111-8111-111111111111'])
            ->assertOk();

        $extra = $this->extra($this->handler()->getRecords()[0]);
        $this->assertArrayNotHasKey('request_id', $extra);
    }
}
