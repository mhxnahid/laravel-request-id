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

## Testing

```bash
composer install
composer test
```

## License

MIT
