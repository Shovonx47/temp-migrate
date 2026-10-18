---
name: laravel-manager-pattern
description: Build driver-based services in Laravel using Illuminate\Support\Manager. Use this skill whenever the user is implementing a service with swappable backends — SMS gateways, payment gateways, notification channels, storage providers, search engines, AI/LLM providers, OAuth providers, geocoders, captcha solvers, or any feature where the same interface needs multiple interchangeable implementations selected by config. Also use when the user mentions terms like "driver", "gateway", "provider", "adapter", "strategy pattern", "swappable backend", or asks how to refactor a hardcoded third-party integration into something configurable. Trigger this skill even when the user names a specific service ("add Twilio SMS", "integrate Stripe and Paddle") if multiple providers are or could be involved.
---

# Laravel Manager Pattern

A skill for building driver-based services using `Illuminate\Support\Manager`. This is the same pattern Laravel uses internally for `Cache`, `Queue`, `Mail`, `Session`, `Hash`, and `Auth`.

## When to use it

The Manager pattern fits when **one capability has multiple interchangeable implementations**, and the choice is a config concern, not a code concern. Classic signals:

- "Send SMS via Twilio, but in staging use the log driver."
- "We support Stripe and Paddle, customer picks at checkout."
- "Geocode with Google, fall back to Mapbox."
- "Test environment should use a null/fake driver."

If there will only ever be one implementation, skip this pattern — a plain service class is simpler. The Manager pattern earns its complexity once the second driver appears, or when testability demands a fake.

## Where it lives

Put driver-based services under `app/Services/<Domain>/`, **not** `app/Support/`. The split is intentional:

- `app/Support/` — stateless helpers, value objects, formatters, traits. No framework lifecycle dependency.
- `app/Services/` — stateful, framework-aware coordinators. Reads config, resolves from the container, talks to external systems. A Manager is exactly this.

Recommended layout per service:

```
app/Services/Sms/
├── SmsManager.php           ← extends Manager, factory methods per driver
├── Contracts/
│   └── SmsDriver.php        ← the interface every driver implements
└── Drivers/
    ├── TwilioDriver.php
    ├── VonageDriver.php
    └── LogDriver.php        ← for local/testing
```

If the service is being extracted into a standalone package (e.g., FrolaxPay built on `spatie/package-skeleton-laravel`), the manager lives inside the package at `src/`, not in `app/`. The host app just consumes it.

## Building it: the five pieces

A complete Manager-based service has five components. Build them in this order — each depends on the previous.

### 1. The contract (interface)

The contract is the API every driver honors. Keep it small and behavioral; don't leak gateway-specific concepts.

```php
namespace App\Services\Sms\Contracts;

interface SmsDriver
{
    public function send(string $to, string $message): SmsResult;
}
```

A bad contract leaks vendor concepts — e.g., `sendViaTwilioMessagingService()` belongs inside the Twilio driver, not in the interface. If you find yourself adding driver-specific methods to the contract, the abstraction is wrong.

### 2. The drivers

Each driver implements the contract. Constructor takes config (an array, not the full container) so drivers are independently testable.

```php
namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\SmsResult;
use Twilio\Rest\Client;

final class TwilioDriver implements SmsDriver
{
    private Client $client;

    public function __construct(private array $config)
    {
        $this->client = new Client($config['sid'], $config['token']);
    }

    public function send(string $to, string $message): SmsResult
    {
        $response = $this->client->messages->create($to, [
            'from' => $this->config['from'],
            'body' => $message,
        ]);

        return new SmsResult(
            id: $response->sid,
            status: $response->status,
        );
    }
}
```

A `LogDriver` is worth writing even before you need a second real provider — it's the testing escape hatch.

```php
final class LogDriver implements SmsDriver
{
    public function __construct(private array $config) {}

    public function send(string $to, string $message): SmsResult
    {
        logger()->info('SMS', ['to' => $to, 'message' => $message]);

        return new SmsResult(id: 'log_'.uniqid(), status: 'queued');
    }
}
```

### 3. The Manager

The Manager extends `Illuminate\Support\Manager`. Two requirements:

- Implement `getDefaultDriver()` — returns the config key for the default driver.
- Add a `create<Name>Driver()` method per driver. The name after `create` and before `Driver` is the driver identifier used in config and calls to `->driver('name')`.

```php
namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsDriver;
use App\Services\Sms\Drivers\LogDriver;
use App\Services\Sms\Drivers\TwilioDriver;
use App\Services\Sms\Drivers\VonageDriver;
use Illuminate\Support\Manager;

final class SmsManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('sms.default');
    }

    protected function createTwilioDriver(): SmsDriver
    {
        return new TwilioDriver($this->config->get('sms.drivers.twilio'));
    }

    protected function createVonageDriver(): SmsDriver
    {
        return new VonageDriver($this->config->get('sms.drivers.vonage'));
    }

    protected function createLogDriver(): SmsDriver
    {
        return new LogDriver($this->config->get('sms.drivers.log', []));
    }
}
```

The parent `Manager` class handles caching (each driver is instantiated once per request), the `extend()` extension API, and dispatching `->driver('name')` calls to the right `create...Driver()` method.

### 4. The config

```php
// config/sms.php
return [
    'default' => env('SMS_DRIVER', 'log'),

    'drivers' => [
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        'vonage' => [
            'key' => env('VONAGE_KEY'),
            'secret' => env('VONAGE_SECRET'),
            'from' => env('VONAGE_FROM'),
        ],
        'log' => [],
    ],
];
```

Default to the safest driver (`log` or a null driver) so a fresh clone of the repo doesn't blow up on first run with missing credentials.

### 5. The service provider

Register the manager as a singleton. Drivers are managed inside it, not bound separately.

```php
namespace App\Providers;

use App\Services\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

final class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/sms.php', 'sms');

        $this->app->singleton(SmsManager::class, fn ($app) => new SmsManager($app));
    }
}
```

Register the provider in `bootstrap/providers.php` (Laravel 11+) or `config/app.php` (older).

## How it's used

Inject the manager and pick a driver explicitly, or let the default win:

```php
final class OrderConfirmationNotifier
{
    public function __construct(private SmsManager $sms) {}

    public function send(Order $order): void
    {
        // Default driver from config
        $this->sms->send($order->phone, "Order #{$order->id} confirmed");

        // Or pick one explicitly
        $this->sms->driver('vonage')->send($order->phone, '...');
    }
}
```

`SmsManager` proxies unknown method calls to the default driver via `Manager::__call()`, so `$this->sms->send(...)` works without `->driver()`.

## Testing

The pattern's biggest payoff. Swap the driver in tests using the `extend()` mechanism — no mocking framework needed.

```php
use App\Services\Sms\SmsManager;
use App\Services\Sms\Contracts\SmsDriver;

beforeEach(function () {
    app(SmsManager::class)->extend('fake', fn () => new class implements SmsDriver {
        public array $sent = [];
        public function send(string $to, string $message): SmsResult
        {
            $this->sent[] = compact('to', 'message');
            return new SmsResult(id: 'fake', status: 'sent');
        }
    });

    config(['sms.default' => 'fake']);
});
```

Or, more commonly, just point the `SMS_DRIVER` env at `log` in `phpunit.xml` and assert against the log channel.

## Common pitfalls

**Leaky contracts.** If a driver-specific method ends up on the interface, the abstraction has failed. Vendor-specific behavior either goes inside the driver (private) or becomes a *capability* the orchestrator queries — e.g., `if ($driver instanceof SupportsScheduledSending)`. The FrolaxPay capability-gating approach is exactly this.

**Manager methods that aren't drivers.** The `create<X>Driver()` naming is enforced by `Manager::createDriver()` via string concatenation. Don't add unrelated helper methods to the Manager and prefix them with `create` — they'll be mistaken for drivers when someone calls `->driver('x')`.

**Constructor injection of `$app`.** The base `Manager` constructor takes `$app` (the container). When extending, don't override the constructor unless you need to — and if you do, call `parent::__construct($app)`. Drivers themselves shouldn't take `$app`; pass them their config array.

**Singleton vs per-request state.** The Manager caches each driver instance for the request lifetime. If a driver holds request-scoped state (e.g., a user-specific API token resolved at runtime), you have two options: re-resolve the driver per call (`$manager->driver('x')` returns the cached instance, so that doesn't help — you need to clear with `forgetDrivers()`), or make the state a method parameter rather than constructor state. The second is usually cleaner.

**Skipping the contract.** Some codebases skip the interface and let drivers be loosely related classes. Don't. The contract is what makes the pattern testable and what stops the Manager from becoming a grab-bag.

## When the pattern isn't enough

If drivers need richer coordination — fallback chains, multi-driver fan-out, per-tenant credentials loaded from the database, capability negotiation — the Manager is still a fine core, but it'll need a thin orchestrator wrapping it. That's the shape FrolaxPay takes: `PaymentManager` as the driver registry, plus an orchestrator handling refund routing, idempotency keys, and webhook dispatch. Build the Manager first, then layer the orchestrator above it once the needs become concrete.
