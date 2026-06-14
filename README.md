# Laravel Request ID

Propagate request, session and correlation IDs across the request lifecycle: read
them from incoming headers, echo them on the response, and attach them (plus the
authenticated user) to every log record.

- `X-Request-Id` — taken from the incoming header or generated as a UUID v4.
- `X-Session-Id` / `X-Correlation-Id` — propagated from upstream only; left null when absent.

Only valid UUID v4 values are accepted from upstream; anything else is treated as
absent (and regenerated for `request_id`).

## Installation

```bash
composer require mxnwire/laravel-request-id
```

The service provider is auto-discovered. By default the middleware is prepended to
the global HTTP stack, so every request is covered with no further setup.

## Configuration

Publish the config to customise behaviour:

```bash
php artisan vendor:publish --tag=request-id-config
```

Key options in `config/request-id.php`:

- `enabled` — master switch; when false the middleware passes requests through untouched.
- `register_global_middleware` — when false, register manually with the `request-id` alias instead of the global stack.
- `headers` — the incoming/outgoing header name for each ID.
- `generate` — which IDs are generated as a UUID v4 when missing (default: `request_id` only).
- `attributes` — the `$request->attributes` keys the IDs are stored under.
- `log` — master switch for attaching the IDs (and user) to log records; when false they are still resolved and echoed on the response, but nothing is pushed to the log.
- `log_destination` — where the fields are placed on each record: `'context'` (default) or `'extra'`. See [Logging](#logging).
- `log_channels` — extra log channels to attach the processor to (the default driver is always covered).
- `log_user` / `user_fields` — attach authenticated-user fields to each log record; each field is a model attribute name or a callable receiving the user.

### Manual middleware registration

Set `register_global_middleware` to `false`, then apply the alias where needed:

```php
Route::middleware('request-id')->group(function () {
    // ...
});
```

## Reading the IDs

```php
$requestId = $request->attributes->get('x_request_id');
```

## Logging

When `log` is enabled the IDs (and, when `log_user` is on, the authenticated-user
fields) are attached to every log record by a Monolog processor. The processor is
always pushed to the default log driver, plus any channels listed in
`log_channels`.

Where the fields land on the record is controlled by `log_destination`:

- `'context'` (default) — merged flat into the record's context. Reads best with
  the `LineFormatter`, where it produces a single `{...}` block, and collapses
  onto any matching keys already in context (e.g. `user_id`).
- `'extra'` — kept in Monolog's `extra` bucket. With the `LineFormatter` this
  shows as a second trailing `{...}` block, but with a JSON formatter it becomes
  a clean, separate `extra` object.

### JSON log channel (preferred)

For anything beyond local debugging, **a JSON-formatted channel paired with
`log_destination => 'extra'` is the recommended setup.** It keeps the tracing
metadata in its own namespace, cleanly separated from the request payload and
trivially queryable by your log aggregator:

```jsonc
{
  "message": "http_request",
  "context": { "method": "GET", "url": "...", "status": 200 },
  "extra":   { "request_id": "d0cee2c7-...", "session_id": null, "user_id": 1 }
}
```

Point a channel at Monolog's `JsonFormatter` in `config/logging.php`:

```php
use Monolog\Formatter\JsonFormatter;

'requests' => [
    'driver'    => 'daily',
    'path'      => storage_path('logs/requests.log'),
    'formatter' => JsonFormatter::class,
    'days'      => 14,
],
```

then set `log_destination => 'extra'` and add the channel to `log_channels` so
the processor is attached to it.

With a plain `LineFormatter` (e.g. the stock `single`/`daily` channels), leave
`log_destination` as `'context'` so the IDs read as part of the main entry.

## Testing

```bash
composer install
composer test
```

## License

MIT
