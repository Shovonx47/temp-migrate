---
name: laravel-sms-broker
description: "Build the backend of a Laravel 13 SMS broker/reseller platform — customers buy credits and send SMS via web UI or REST API, while the broker routes traffic across multiple upstream gateways (SSL Wireless, Mim SMS, BulkSMSBD, Twilio, and future providers). Covers driver pattern with capability gating, per-user sender ID allocation, queued sending with retry, Redis-backed per-gateway rate limiting, balance deduction via double-entry transactions, DLR webhooks, campaigns, and a customer REST API. Use this skill whenever the user is working on the Frolax SMS broker codebase, or mentions SMS broker/reseller, multi-gateway SMS routing, gateway drivers, sender ID allocation, SMS credit or balance deduction, DLR webhooks, SMS campaign sending, customer SMS API, or names any provider (SSL Wireless, Mim, BulkSMSBD, Twilio, Alpha SMS, Greenweb) in a Laravel context. Trigger even if only one gateway or just balance deduction is mentioned — the schema is interconnected."
---

# Laravel SMS Broker Platform

A skill for building and extending the Frolax SMS broker — a Laravel 13 application where customers (users) buy credit, pick a sender ID, and send SMS through any of several upstream gateway providers. The broker (you) sits in the middle: routes traffic, deducts credits, handles delivery reports, exposes both a web UI and a REST API.

## Business model context (so design decisions make sense)

- **Customers** are end-users of the platform. Each has a `users.balance` in BDT.
- **Gateways** are upstream SMS providers the broker has accounts with. Configured once in admin, used by everyone.
- **Senders** (sender IDs) are owned by the broker, attached to a specific gateway, and allocated to specific customers via the `sender_user` pivot. A customer can only send "from" a sender they've been granted.
- **Messages** deduct per-SMS cost from the customer's balance at send-time. The actual cost the broker pays the gateway is opaque to the customer — broker's markup lives in the gap.
- **Campaigns** are bulk sends — fan out one body to many recipients (contacts, groups, or pasted lists).
- **Deposits** top up the balance (via payment gateway like SSLCommerz, Stripe, or manual admin credit).
- **Transactions** are the double-entry-ish ledger — every balance change has a row with `balance_before` and `balance_after` snapshots.

OTP is explicitly **not in scope**. The broker delivers SMS. Customers build their own OTP flows on top.

## When to use this skill

Trigger on any of these:

- User is implementing or modifying SMS sending, gateway routing, sender allocation, message/campaign dispatch, balance deduction, or DLR webhooks in the Frolax codebase
- User asks about driver patterns, capability gating, rate limiting per gateway, or failover
- User names a specific provider (SSL Wireless, Mim SMS, BulkSMSBD, Twilio, etc.) in a Laravel context
- User asks about exposing an SMS-send API for customers, or how customers should authenticate against it
- User asks about the schema, migrations, models, or relationships of any SMS-related table

If the user says "just one provider for now, keep it simple" — still use this skill. The driver layer cost is paid up-front, not later.

## References

This skill has two reference files. Read them when relevant:

- `references/drivers.md` — Per-provider API details (SSL Wireless, Mim SMS, BulkSMSBD, Twilio): credentials shape, request/response format, capabilities, delivery report payloads, webhook security. Read before writing any driver class or when the user asks about a specific provider.
- `references/code-snippets.md` — Starter implementations for `SmsManager`, `SendSmsJob`, the webhook controller, the Redis rate limiter, and a sample driver. Read when implementing those components.

## Tech constraints

- **Laravel 13** idioms throughout: `Attribute::get()` for accessors, `casts()` method on models, native enums as casts, constructor property promotion, `readonly` DTOs.
- **Phone numbers** are stored and passed around as E.164 strings (`+8801712345678`) using `Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast`. Every model column holding a phone number must use this cast. Drivers strip the `+` on the way out when their API requires it (SSL/Mim/BulkSMSBD); Twilio keeps the `+`.
- **DB-stored gateway credentials.** No `.env` keys for provider secrets. `gateways.credentials` is a JSON column, cast as `encrypted:array` on the model.
- **Redis** for rate limiting and usage counters. Not DB.
- **Enums** follow the user's `ModelColumnName` convention: `MessageStatus`, `CampaignStatus`, `DepositStatus`, `TransactionType`, `TransactionDirection`, `SenderType`, `GatewayDriver`. These live in `app/Enums/`.
- **Soft deletes** on `gateways`, `senders`, `groups`, `contacts`, `templates`, `campaigns`, `deposits` (per the migrations). Not on `messages` or `transactions` — those are immutable audit data.

## Schema reference (the truth)

The migrations are already written and are the source of truth. Treat the column names below as fixed — do not rename, restructure, or add columns without explicit user approval.

### `users`

Existing Laravel users table with extensions: `phone` (E.164, unique), `type` (default `customer`), `balance` (decimal 12,4, default 0), `app_authentication_secret` + `app_authentication_recovery_codes` (for 2FA). Soft deletes.

### `gateways`

- `id`, `name`, `driver` (string, matches a key in `DriverRegistry`)
- `credentials` (json, encrypted) — driver-specific keys
- `config` (json) — non-secret driver config: endpoint URL overrides, retries, etc.
- `daily_limit`, `monthly_limit` (nullable unsigned bigint) — soft caps enforced by `RateLimiter`
- `is_enabled` (bool, default false)
- `supports_bulk` (bool, default false) — whether the gateway accepts comma-separated recipients in one API call
- Soft deletes

### `senders`

- `id`, `gateway_id` (fk, nullOnDelete)
- `sender_id` (string) — the actual masking/numeric ID approved by the upstream
- `type` (string) — one of: `numeric`, `alphanumeric`, `shortcode`
- `is_enabled` (bool, default false)
- Unique on `(gateway_id, sender_id)`
- Soft deletes

A `sender` belongs to exactly one `gateway`. The `sender_user` pivot grants customers permission to send through specific senders.

### `sender_user` (pivot)

- `sender_id`, `user_id`, composite primary key. No timestamps, no extras.

### `groups`

- `id`, `user_id` (cascadeOnDelete), `name`, `description`. Customer-owned contact groups.

### `contacts`

- `id`, `user_id` (cascadeOnDelete)
- `name` (nullable), `phone` (E.164)
- `meta` (json) — extra fields: email, dob, custom variables for template interpolation
- `is_blocked` (bool, default false)
- Unique on `(user_id, phone)` — prevents duplicate contacts per customer

### `contact_group` (pivot)

- `contact_id`, `group_id`, composite primary key.

### `templates`

- `id`, `user_id` (cascadeOnDelete)
- `name`, `body`, `variables` (json array, e.g. `["name", "otp", "amount"]`)
- The `variables` column is the contract — body interpolation expects `{name}`, `{otp}`, `{amount}` placeholders and validates against this list before sending.

### `campaigns`

- `id`, `user_id`, `template_id` (nullOnDelete), `sender_id` (nullOnDelete)
- `name`, `body`
- `status` (string) — `draft`, `scheduled`, `running`, `paused`, `completed`, `failed`
- `scheduled_at`, `started_at`, `completed_at`

### `messages`

- `id`, `user_id` (cascadeOnDelete), `campaign_id` (nullOnDelete), `sender_id` (nullOnDelete)
- **`gateway_id` snapshot** — set at send-time. Doesn't change if the sender is later reassigned. This is the audit anchor.
- `to` (E.164), `body`
- `status` (string) — `pending`, `sent`, `delivered`, `failed`
- `gateway_message_id` (nullable) — the upstream's reference, used for DLR matching
- `cost` (decimal 10,4) — what was deducted from the customer's balance
- `failed_reason` (text, nullable)
- `sent_at`, `delivered_at`, `failed_at`

No soft deletes. Messages are immutable audit data.

### `deposits`

- `id`, `user_id` (cascadeOnDelete)
- `amount` (decimal 12,4), `currency` (default `BDT`)
- `payment_method` — `sslcommerz`, `stripe`, `manual`, etc.
- `transaction_id` (unique, nullable) — payment gateway's ID
- `status` — `pending`, `completed`, `failed`, `refunded`
- `meta` (json) — gateway response payload
- `paid_at`

### `transactions`

- `id`, `user_id` (cascadeOnDelete)
- `transactionable_type`, `transactionable_id` (nullable polymorphic) — points to a Deposit, Campaign, Message, or null for manual adjustments
- `type` — `deposit`, `campaign_deduction`, `sms_deduction`, `manual_credit`, `manual_debit`, `refund`
- `amount` (decimal 10,2), `direction` — `credit` or `debit`
- `balance_before`, `balance_after` (decimal 10,2 snapshots)
- `description` (nullable)

No soft deletes. Append-only ledger.

### `delivery_reports`

- `id`, `gateway_id` (cascadeOnDelete), `message_id` (nullOnDelete)
- `gateway_message_id` (nullable, indexed) — for retroactive matching
- `status` — normalized: `delivered`, `failed`, `rejected`, `pending`, `unknown`
- `raw_payload` (json)
- `received_at`

Append-only. Unmatched DLRs are still stored (`message_id` null) so debugging is possible.

## Architecture

Three layers, top-down:

```
SmsManager (the only thing customer-facing code touches)
   ├── Validates: sender belongs to user, user has balance, body fits template variables
   ├── Resolves gateway from sender_id (sender.gateway_id, snapshotted onto message)
   ├── Creates Message row (status=pending), debits balance, writes Transaction
   ├── Dispatches SendSmsJob to the 'sms' queue
   └── Returns the Message model

SendSmsJob (queued)
   ├── Re-checks gateway rate limit (RateLimiter)
   ├── Instantiates driver via DriverRegistry
   ├── Capability-checks: unicode body vs driver capabilities
   ├── Calls $driver->send($request)
   ├── On success: status=sent, gateway_message_id captured
   ├── On transient failure: releases back to queue with backoff
   ├── On permanent failure: status=failed, refund Transaction written
   └── Fires events (MessageSent, MessageFailed)

Driver (one class per provider)
   ├── ->capabilities(): which features it supports
   ├── ->send(SendRequest): hits provider API, returns DriverResult
   ├── ->parseDeliveryReport(array): converts webhook payload → DeliveryUpdate or null
   └── ->verifySignature(Request): optional webhook auth check
```

The driver layer is the only place that knows provider quirks. Everything above is provider-agnostic.

## File layout

```
app/
├── Services/Sms/
│   ├── SmsManager.php           (entrypoint, validation + orchestration)
│   ├── DriverRegistry.php       (driver string → class map)
│   ├── RateLimiter.php          (Redis-backed)
│   ├── BalanceService.php       (debit/credit with Transaction ledger)
│   ├── DTOs/
│   │   ├── SendRequest.php      (readonly: to, body, sender, gateway, user)
│   │   ├── DriverResult.php     (readonly: ok, providerId, raw, error)
│   │   └── DeliveryUpdate.php   (readonly: providerId, status, raw)
│   ├── Contracts/
│   │   └── SmsDriver.php
│   ├── Drivers/
│   │   ├── SslWirelessDriver.php
│   │   ├── MimSmsDriver.php
│   │   ├── BulkSmsBdDriver.php
│   │   └── TwilioDriver.php
│   └── Exceptions/
│       ├── SmsException.php
│       ├── InsufficientBalanceException.php
│       ├── SenderNotAllowedException.php
│       ├── GatewayUnavailableException.php
│       ├── GatewayRateLimitedException.php
│       └── CapabilityMissingException.php
├── Jobs/
│   ├── SendSmsJob.php
│   └── DispatchCampaignJob.php  (fans out a campaign into individual SendSmsJobs)
├── Http/Controllers/
│   ├── Api/V1/
│   │   ├── SmsController.php    (customer-facing send endpoint)
│   │   └── MessageController.php (status lookup)
│   └── Webhooks/
│       └── SmsDeliveryWebhookController.php
├── Models/
│   ├── User.php, Gateway.php, Sender.php, Group.php, Contact.php,
│   ├── Template.php, Campaign.php, Message.php, Deposit.php,
│   ├── Transaction.php, DeliveryReport.php
├── Events/
│   ├── MessageSent.php, MessageFailed.php, MessageDelivered.php
└── Enums/
    ├── MessageStatus.php       (Pending, Sent, Delivered, Failed)
    ├── CampaignStatus.php      (Draft, Scheduled, Running, Paused, Completed, Failed)
    ├── DepositStatus.php       (Pending, Completed, Failed, Refunded)
    ├── TransactionType.php     (Deposit, CampaignDeduction, SmsDeduction, ManualCredit, ManualDebit, Refund)
    ├── TransactionDirection.php (Credit, Debit)
    ├── SenderType.php          (Numeric, Alphanumeric, Shortcode)
    └── GatewayDriver.php       (open registry — common values, but new drivers are added by string)
```

## Capabilities

Each driver declares what it supports. The manager/job checks before dispatching:

```php
const CAPABILITIES = [
    'unicode',          // Bangla / non-ASCII
    'long_sms',         // multipart > 160 chars
    'delivery_reports', // gateway sends DLR webhooks
    'scheduling',       // accepts future send-at
    'bulk',             // single API call, many recipients (matches gateways.supports_bulk)
    'two_way',          // can receive inbound
];
```

Capability checks at send-time:
- Body contains non-ASCII → driver must have `unicode`, else throw `CapabilityMissingException`
- Body > 160 chars and driver lacks `long_sms` → warn (most providers segment server-side anyway), don't block
- Bulk dispatch path checks `supports_bulk` on the gateway row

See `references/drivers.md` for the actual capabilities of each first-class driver.

## Driver contract

```php
namespace App\Services\Sms\Contracts;

use App\Models\Gateway;
use App\Services\Sms\DTOs\SendRequest;
use App\Services\Sms\DTOs\DriverResult;
use App\Services\Sms\DTOs\DeliveryUpdate;
use Illuminate\Http\Request;

interface SmsDriver
{
    public function __construct(Gateway $gateway);

    /** @return string[] */
    public function capabilities(): array;

    public function send(SendRequest $request): DriverResult;

    /** Returns null if the payload isn't a recognized DLR. */
    public function parseDeliveryReport(array $payload): ?DeliveryUpdate;

    /** Default true if the provider has no signing mechanism. */
    public function verifySignature(Request $request): bool;
}
```

The constructor receives the `Gateway` model — credentials and config are accessible via `$this->gateway->credentials['api_token']`, `$this->gateway->config['endpoint']`, etc.

## SmsManager — the public surface

This is what customer-facing code (controllers, jobs) touches. Keep it small.

```php
// Single send — from a controller serving the customer API
$message = Sms::for($user)
    ->from($sender)           // a Sender model the user has access to
    ->to('+8801712345678')
    ->body('Hello from Frolax')
    ->send();

// From a template
$message = Sms::for($user)
    ->from($sender)
    ->to($contact->phone)
    ->template($template, ['name' => $contact->name])
    ->send();

// Bulk (campaign fan-out happens via DispatchCampaignJob, not here)
```

The `Sms` facade resolves to `SmsManager`. The builder validates each call before queueing:

1. Sender belongs to the user (check `sender_user` pivot)
2. Sender's gateway is enabled
3. User has sufficient balance for estimated cost
4. Body fits template variables (if template used)
5. Phone is valid E.164

On `send()`:
- Snapshot `gateway_id` onto the Message row
- Debit balance via `BalanceService` (writes Transaction with type `sms_deduction`)
- Dispatch `SendSmsJob` to the `sms` queue

If the job ultimately fails (after retries), `MessageFailed` event fires and a refund Transaction is written (`type=refund`, direction `credit`).

## Queue & retry

`SendSmsJob` configuration:
- Queue: `sms` (dedicated)
- `$tries = 3`
- `backoff()`: `[10, 60, 300]` seconds
- `$timeout = 30`

Idempotency: job checks `if (in_array($message->status, ['sent', 'delivered', 'failed'])) return;` at the top.

Job re-checks the rate limiter — between dispatch and execution another job may have spent the quota. If rate-limited, `$this->release(30)` puts it back without consuming a retry attempt.

## Rate limiting (Redis)

Per-gateway sliding window. Keys:

- `sms:rl:{gateway_id}:minute:{YYYYMMDDHHMM}` — for fine-grained throttling (config-driven, defaults to no limit unless `config.rate_limit_per_minute` is set)
- `sms:rl:{gateway_id}:day:{YYYYMMDD}` — enforces `gateways.daily_limit`
- `sms:rl:{gateway_id}:month:{YYYYMM}` — enforces `gateways.monthly_limit`

All three are `INCR`-on-send-success, never on dispatch. Failed sends don't count.

`RateLimiter::allow(Gateway $g): bool` checks all three; `RateLimiter::record(Gateway $g)` increments after a successful send. Use the standard Redis facade — no rolling your own sliding window.

## Webhooks (delivery reports)

Single route per gateway:

```php
Route::post('/webhooks/sms/{gateway:name}', [SmsDeliveryWebhookController::class, 'handle'])
    ->name('webhooks.sms');
```

Bind `{gateway:name}` so the gateway is resolved by its `name` column. The controller:

1. Looks up the `Gateway` by name (404 if not found or disabled)
2. Instantiates the driver via `DriverRegistry`
3. Calls `$driver->verifySignature($request)` — abort 403 if false
4. Calls `$driver->parseDeliveryReport($request->all())`
5. If null → stores raw payload in `delivery_reports` with `status=unknown`, returns 200 (so provider stops retrying)
6. If parsed → upserts `delivery_reports` row, updates matching `messages` row (`status`, `delivered_at` or `failed_at`)
7. Returns the response shape the provider wants — most accept `OK` plain text

The match key is `messages.gateway_message_id = delivery_reports.gateway_message_id`. If no message matches (e.g. DLR arrives before the send response was processed), the report row is still saved with `message_id=null` and a `ReconcileDeliveryReportJob` can re-match later.

## Balance & double-entry transactions

`BalanceService::debit(User $user, Money $amount, string $type, Model $linkable, ?string $desc)`:

1. Begin DB transaction
2. `User::lockForUpdate()` reload — prevents race conditions on concurrent sends
3. Check sufficient balance, throw `InsufficientBalanceException` if not
4. Snapshot `balance_before`, compute `balance_after`
5. Update `users.balance`
6. Insert `transactions` row with `transactionable` polymorphic pointer to the Message/Campaign/Deposit
7. Commit

`credit()` mirrors it for refunds, deposits, and manual credits.

Every balance change MUST go through `BalanceService`. Direct `$user->balance -= $cost; $user->save()` is forbidden — it skips the audit trail.

## Pricing

Out of scope as a schema concern (no `pricing` table yet). For now, `BalanceService::estimateCost(Sender $sender, string $body)` is the central function — implement it as a flat per-segment rate read from `gateways.config.cost_per_segment` (BDT). Segment count: 160 chars for GSM-7, 70 for Unicode.

If the user later asks for tiered/per-user pricing, that's a separate `pricing_rules` table — note it as future work but don't build it speculatively.

## Customer API (REST)

Versioned under `/api/v1/`. Authentication via Laravel Sanctum personal access tokens — each customer generates one or more tokens from their dashboard, sends as `Authorization: Bearer ...`.

Endpoints:

```
POST   /api/v1/sms                    Send single SMS
POST   /api/v1/sms/bulk               Send to many recipients (creates a Campaign internally)
GET    /api/v1/sms/{uuid}             Get message status
GET    /api/v1/senders                List senders the customer has access to
GET    /api/v1/balance                Current balance
GET    /api/v1/messages               List messages (paginated, filterable by status/date/sender)
```

`POST /api/v1/sms` body:
```json
{ "sender_id": 5, "to": "+8801712345678", "body": "..." }
```

Returns `201` with the Message resource. Errors return RFC 7807-ish JSON: `{ "error": "insufficient_balance", "message": "...", "details": {...} }`.

Rate limit per token: Laravel's `throttle:60,1` middleware as a baseline; tighten per-customer later if needed.

Customer-facing IDs in the API: use `messages.id` directly for now. If you want unguessable IDs, add a `uuid` column later (not in the current migrations — confirm with user before adding).

## Campaign fan-out

When a campaign is started:

1. `DispatchCampaignJob` picks the campaign, transitions to `running`
2. Resolves recipients (contacts via groups, or pasted list — the campaign body interpolates per-contact variables from `contacts.meta`)
3. For each recipient: validates balance is still sufficient, creates a Message + Transaction, dispatches `SendSmsJob`
4. If balance runs out mid-campaign, transitions to `paused`, fires an event
5. When all child jobs complete (use Laravel Job Batches), transitions to `completed`

Use Laravel's `Bus::batch()` for the SendSmsJob fan-out so progress + cancellation are first-class.

## Adding a new driver — checklist

When the user asks "how do I add Provider X":

1. Create `app/Services/Sms/Drivers/{X}Driver.php` implementing `SmsDriver`
2. Register in `DriverRegistry`: `'x' => XDriver::class`
3. (Optional) Add `X` as a case in `GatewayDriver` enum if you want type-safety in admin UI dropdowns — but the registry accepts any string, so the enum is just a convenience
4. Document the credentials shape in a docblock at the top of the driver
5. Insert a `gateways` row with `driver='x'` and the right `credentials` JSON shape
6. If X sends DLRs, wire up the webhook route and `parseDeliveryReport` logic
7. If X requires signature verification, implement `verifySignature()` — otherwise return `true` from the parent

See `references/drivers.md` for the credentials shape and capabilities of each first-class driver.

## What this skill does NOT cover

- Admin UI / customer dashboard (Filament, Inertia, etc.) — build separately
- Pricing rules / tiered customer pricing — future schema work
- Number portability / HLR lookups
- Two-way SMS / inbound — schema columns aren't there yet
- OTP issuance/verification — explicitly out of scope, customers build this on top of the send API

## Output expectations

When asked to implement a piece of this system:

- Write code that matches the schema column names EXACTLY as defined above. Do not invent `phone_number` when the column is `phone`. Do not invent `provider_id` when the column is `gateway_message_id`.
- Use the `E164PhoneNumberCast` on every phone column in every model.
- Use `Attribute::get()` for accessors (Laravel 13 style), not the old `getXxxAttribute` magic methods.
- Use the `casts()` method on models, not the `$casts` property.
- Use native PHP enums for status/type fields, cast them in `casts()`.
- Keep responses focused. The user prefers concise, direct output over verbose explanation. If implementing a feature touches 10 files, don't dump all 10 — build in chunks and confirm before continuing.
- When writing Bengali responses (the user often communicates in Bengali for technical sessions), keep code/keywords English and the prose in Bengali.