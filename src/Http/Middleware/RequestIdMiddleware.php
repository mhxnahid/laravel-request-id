<?php

namespace Mxnwire\RequestId\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Monolog\LogRecord;

/**
 * Resolve request, session and correlation IDs for the request, echo them back
 * on the response and attach them (plus the authenticated user) to every log
 * record. Behaviour is driven entirely by config/request-id.php.
 *
 * - request_id (by default) is taken from the incoming header or generated as
 *   a UUID v4 when missing or invalid.
 * - session_id and correlation_id (by default) are only propagated from
 *   upstream and left null when absent.
 */
class RequestIdMiddleware
{
    /** IDs handled by the middleware, in propagation order. */
    protected const IDS = ['request_id', 'session_id', 'correlation_id'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('request-id.enabled', true)) {
            return $next($request);
        }

        $values = [];

        foreach (self::IDS as $id) {
            $values[$id] = $this->resolve($request, $id);
            $request->attributes->set($this->attributeKey($id), $values[$id]);
        }

        $this->addLogContext($request);

        $response = $next($request);

        foreach (self::IDS as $id) {
            if ($values[$id] !== null) {
                $response->headers->set($this->header($id), $values[$id]);
            }
        }

        return $response;
    }

    /**
     * Resolve a single ID from the incoming header, generating a UUID v4 when
     * the ID is configured to be generated and the header is missing/invalid.
     */
    protected function resolve(Request $request, string $id): ?string
    {
        $value = $request->header($this->header($id));

        if (! empty($value) && $this->isValidUuid($value)) {
            return $value;
        }

        return $this->shouldGenerate($id) ? (string) Str::uuid() : null;
    }

    protected function isValidUuid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    /**
     * Push a processor that adds the resolved IDs and the authenticated user
     * (when configured) to every log record.
     */
    protected function addLogContext(Request $request): void
    {
        if (! config('request-id.log', true)) {
            return;
        }

        // The fields are resolved lazily at log time so records emitted after
        // the user authenticates still pick up the user. The processor handles
        // both Monolog 3 (immutable LogRecord) and Monolog 2 (plain array) so
        // the package works across Laravel 8–11.
        $processor = function ($record) use ($request) {
            $fields = $this->logFields($request);

            if ($record instanceof LogRecord) {
                return $record->with(extra: array_merge($record->extra, $fields));
            }

            $record['extra'] = array_merge($record['extra'] ?? [], $fields);

            return $record;
        };

        // Illuminate\Log\Logger proxies pushProcessor to the underlying Monolog.
        Log::driver()->pushProcessor($processor);

        foreach ((array) config('request-id.log_channels', []) as $channel) {
            try {
                Log::channel($channel)->pushProcessor($processor);
            } catch (\Throwable $e) {
                // Skip channels that aren't configured rather than failing the request.
            }
        }
    }

    /**
     * Build the fields added to each log record: the resolved IDs plus the
     * authenticated user (when enabled).
     */
    protected function logFields(Request $request): array
    {
        $fields = [];

        foreach (self::IDS as $id) {
            $fields[$id] = $request->attributes->get($this->attributeKey($id));
        }

        $user = $request->user();

        if (config('request-id.log_user', true) && $user !== null) {
            foreach (config('request-id.user_fields', []) as $key => $field) {
                $fields[$key] = is_callable($field)
                    ? $field($user)
                    : data_get($user, $field);
            }
        }

        return $fields;
    }

    protected function header(string $id): string
    {
        return config("request-id.headers.$id", Arr::get([
            'request_id'     => 'X-Request-Id',
            'session_id'     => 'X-Session-Id',
            'correlation_id' => 'X-Correlation-Id',
        ], $id));
    }

    protected function attributeKey(string $id): string
    {
        return config("request-id.attributes.$id", 'x_' . $id);
    }

    protected function shouldGenerate(string $id): bool
    {
        return (bool) config("request-id.generate.$id", $id === 'request_id');
    }
}
