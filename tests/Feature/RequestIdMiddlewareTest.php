<?php

namespace Mxnwire\RequestId\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Mxnwire\RequestId\Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/_test', function () {
            return response()->json([
                'request_id'     => request()->attributes->get('x_request_id'),
                'session_id'     => request()->attributes->get('x_session_id'),
                'correlation_id' => request()->attributes->get('x_correlation_id'),
            ]);
        });
    }

    public function test_it_generates_a_request_id_when_absent(): void
    {
        $response = $this->getJson('/_test');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('X-Request-Id'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            $response->headers->get('X-Request-Id')
        );
    }

    public function test_it_propagates_a_valid_incoming_request_id(): void
    {
        $id = '11111111-1111-4111-8111-111111111111';

        $response = $this->getJson('/_test', ['X-Request-Id' => $id]);

        $response->assertOk()->assertJsonPath('request_id', $id);
        $this->assertSame($id, $response->headers->get('X-Request-Id'));
    }

    public function test_it_replaces_an_invalid_request_id(): void
    {
        $response = $this->getJson('/_test', ['X-Request-Id' => 'not-a-uuid']);

        $response->assertOk();
        $this->assertNotSame('not-a-uuid', $response->headers->get('X-Request-Id'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            $response->headers->get('X-Request-Id')
        );
    }

    public function test_session_and_correlation_ids_are_only_propagated_from_upstream(): void
    {
        $response = $this->getJson('/_test');

        $response->assertOk()
            ->assertJsonPath('session_id', null)
            ->assertJsonPath('correlation_id', null);

        $this->assertNull($response->headers->get('X-Session-Id'));
        $this->assertNull($response->headers->get('X-Correlation-Id'));
    }

    public function test_it_propagates_incoming_session_and_correlation_ids(): void
    {
        $session = '22222222-2222-4222-8222-222222222222';
        $correlation = '33333333-3333-4333-8333-333333333333';

        $response = $this->getJson('/_test', [
            'X-Session-Id'     => $session,
            'X-Correlation-Id' => $correlation,
        ]);

        $response->assertOk()
            ->assertJsonPath('session_id', $session)
            ->assertJsonPath('correlation_id', $correlation);

        $this->assertSame($session, $response->headers->get('X-Session-Id'));
        $this->assertSame($correlation, $response->headers->get('X-Correlation-Id'));
    }
}
