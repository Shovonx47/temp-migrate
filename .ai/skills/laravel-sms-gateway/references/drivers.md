# SMS Driver Reference

Per-provider details for the four first-class drivers in the Frolax SMS broker. Read this before writing or modifying a driver class.

## Table of contents

- [SSL Wireless](#ssl-wireless) — BD enterprise default, banking-grade
- [Mim SMS](#mim-sms) — cheaper BD alternative
- [BulkSMSBD](#bulksmsbd) — budget BD option, bulk-friendly
- [Twilio](#twilio) — international fallback
- [Choosing a driver at runtime](#choosing-a-driver-at-runtime)
- [Phone number formats per driver](#phone-number-formats-per-driver)

---

## SSL Wireless

**Best for**: OTP traffic from your customers, transactional, banking-grade. Most reliable BD routes. Most expensive.

### Credentials shape (`gateways.credentials`)
```json
{
  "api_token": "...",
  "sid": "DefaultBrandSID",
  "csms_id_prefix": "FROLAX"
}
```

### Config shape (`gateways.config`)
```json
{
  "endpoint": "https://smsplus.sslwireless.com/api/v3/send-sms/single",
  "bulk_endpoint": "https://smsplus.sslwireless.com/api/v3/send-sms/bulk",
  "cost_per_segment": 0.45,
  "rate_limit_per_minute": 600
}
```

Note: the `sid` in credentials is the default — actual per-message `sid` comes from the `Sender` model attached to the message. SSL Wireless requires each masking SID be pre-registered with them.

### Send request
POST JSON:
```json
{
  "api_token": "{credentials.api_token}",
  "sid": "{sender.sender_id}",
  "msisdn": "8801712345678",
  "sms": "Your message",
  "csms_id": "{prefix}-{ulid}"
}
```

Phone format: leading `880`, no `+`, no spaces. Strip with `ltrim($to, '+')`.

### Response
Success:
```json
{
  "status": "SUCCESS",
  "status_code": 200,
  "smsinfo": [{ "sms_status": "SUCCESS", "reference_id": "abc123", "msisdn": "..." }]
}
```
The `reference_id` becomes `messages.gateway_message_id`.

Failure: `status: "FAILED"` with `error_message`. Map common codes:
- `4003` IP Blacklisted → permanent, mark `failed`
- `4001` Invalid token → permanent (admin needs to update credentials)
- `5xx` codes → transient, retry

### Capabilities
`['unicode', 'long_sms', 'delivery_reports']`

No `bulk` in v3 single endpoint — use the separate bulk endpoint if `supports_bulk=true` on the gateway and the campaign warrants it. No scheduling via API.

### Delivery reports
SSL POSTs to whatever URL you've configured with them. Payload:
```json
{
  "csms_id": "FROLAX-01HXXX...",
  "reference_id": "abc123",
  "msisdn": "8801712345678",
  "status": "DELIVERED",
  "sent_time": "...",
  "delivered_time": "..."
}
```

Status mapping:
- `DELIVERED` → `delivered`
- `UNDELIVERED` → `failed`
- `REJECTED` → `rejected`
- `PENDING` → ignore (don't update message)

No signature — `verifySignature()` returns `true`, but restrict the webhook route by source IP at the web server / firewall level. SSL provides a fixed IP range; ask their support.

---

## Mim SMS

**Best for**: bulk transactional and promotional. Cheaper than SSL, no DLR support.

### Credentials shape
```json
{
  "api_key": "...",
  "username": "..."
}
```
(For Mim, `UserName` and `Apikey` are usually the same value — confirm with your Mim account manager.)

### Config shape
```json
{
  "endpoint": "https://api.mimsms.com/api/SmsSending/SMS",
  "cost_per_segment": 0.30
}
```

### Send request
POST JSON:
```json
{
  "UserName": "{credentials.username}",
  "Apikey": "{credentials.api_key}",
  "MobileNumber": "8801712345678",
  "CampaignId": "null",
  "SenderName": "{sender.sender_id}",
  "TransactionType": "T",
  "Message": "Hello"
}
```

`TransactionType`: `T` for transactional, `P` for promotional. Map from the campaign category or default to `T` for single sends.

### Response
```json
{ "responseResult": "Success", "ApiResponse": "...", "ReferenceID": "..." }
```

`ReferenceID` → `gateway_message_id`.

### Capabilities
`['unicode']`

No `long_sms` concatenation (Mim bills per 160-char segment but doesn't reassemble — segment manually if body > 160). No `delivery_reports`. No `bulk` endpoint.

### Delivery reports
Not supported. Treat `sent` as terminal. `parseDeliveryReport()` returns `null` always.

`verifySignature()` returns `true` (never called in practice since no webhooks).

---

## BulkSMSBD

**Best for**: low-cost dev/staging, marketing blasts, large campaigns. The bulk API call is the cheapest way to fan out.

### Credentials shape
```json
{
  "api_key": "..."
}
```

### Config shape
```json
{
  "endpoint": "http://bulksmsbd.net/api/smsapi",
  "cost_per_segment": 0.25
}
```

Note: base tier is HTTP-only. If you have the HTTPS paid tier, override `endpoint` to `https://...`.

### Send request (single)
POST form-encoded:
```
api_key={api_key}&senderid={sender.sender_id}&number=8801712345678&message=Hello&type=text
```

For Bangla / unicode body: `type=unicode`.

### Send request (bulk)
Same endpoint, comma-separated numbers:
```
api_key=...&senderid=...&number=8801712345678,8801812345678,...&message=Hello&type=text
```

Response covers all recipients in one call. Use this path when `supports_bulk=true` AND the campaign has > 50 recipients (single-call overhead dominates otherwise).

### Response
```json
{ "response_code": 202, "message_id": 12345, "success_message": "SMS Submitted Successfully" }
```

Codes:
- `202` accepted → store `message_id` as `gateway_message_id`
- `1001` invalid number → permanent
- `1002` invalid sender ID → permanent
- `1003` invalid request → permanent
- `1005` internal error → transient, retry
- `1006` / `1007` balance issues → mark failed, alert admin (broker's upstream balance is empty)

### Capabilities
`['unicode', 'bulk']`

No DLR webhook on base tier — there's a pull-based "report" endpoint that's out of scope here. No `long_sms` reassembly.

### Delivery reports
Not via webhook. `parseDeliveryReport()` returns `null`. If you later add the pull-report integration, do it as a scheduled command, not part of the driver contract.

---

## Twilio

**Best for**: international recipients, US/EU traffic, when you need signed webhook callbacks.

### Credentials shape
```json
{
  "account_sid": "ACxxxxxxxxxxxx",
  "auth_token": "...",
  "from": "+14155552671"
}
```

The `from` in credentials is the Twilio-owned phone number. The `Sender` model's `sender_id` should match this (or be set to the Messaging Service SID — Twilio's recommended pattern).

### Config shape
```json
{
  "cost_per_segment": 1.20
}
```
Twilio is significantly more expensive in BDT terms — markup accordingly.

### Send request
Use the SDK: `composer require twilio/sdk`.
```php
$client = new \Twilio\Rest\Client($creds['account_sid'], $creds['auth_token']);
$msg = $client->messages->create(
    $request->to, // E.164, keep the +
    [
        'from' => $request->sender->sender_id,
        'body' => $request->body,
        'statusCallback' => route('webhooks.sms', ['gateway' => $this->gateway->name]),
    ]
);
return DriverResult::ok($msg->sid, $msg->toArray());
```

### Capabilities
`['unicode', 'long_sms', 'delivery_reports', 'two_way', 'scheduling']`

No `bulk` — Twilio's API is one-message-per-call. Don't set `supports_bulk=true` on Twilio gateway rows.

### Delivery reports
Twilio POSTs to `statusCallback`:
- Form-encoded body with `MessageSid`, `MessageStatus`, `ErrorCode`, etc.
- Statuses: `queued`, `sent`, `delivered`, `failed`, `undelivered`

Status mapping for `parseDeliveryReport`:
- `delivered` → `delivered`
- `failed` → `failed`
- `undelivered` → `failed`
- `sent`, `queued` → ignore (don't update — these are mid-flight)

**Signature verification**: REQUIRED. Twilio signs every webhook with `X-Twilio-Signature`. Use:
```php
$validator = new \Twilio\Security\RequestValidator($creds['auth_token']);
return $validator->validate(
    $request->header('X-Twilio-Signature'),
    $request->fullUrl(),
    $request->all()
);
```

---

## Choosing a driver at runtime

In the broker model, the customer picks a Sender; the Sender determines the Gateway; the Gateway determines the Driver. There is no automatic "pick the cheapest gateway" logic in this skill's scope.

```
Customer's API call
  → sender_id (in request body)
  → Sender model (verified via sender_user pivot)
  → Sender.gateway_id
  → Gateway model (must be is_enabled = true)
  → Gateway.driver (string)
  → DriverRegistry::resolve(driver) → driver class
```

This means:
- The customer effectively chooses which upstream their SMS goes through, by choosing the sender
- The broker controls allocation by deciding which senders each customer gets via `sender_user` pivot
- No mid-flight failover. If the gateway is down, retries are on the same gateway. If it stays down, the message ends up `failed` and balance is refunded.

If the user later asks for cross-gateway failover, that's a `FailoverPolicy` extension to `SmsManager` — note as future work, don't build speculatively.

---

## Phone number formats per driver

Stored everywhere as E.164 (`+8801712345678`) via `E164PhoneNumberCast`. Each driver normalizes on the way out:

| Driver       | Format           | Code              |
|--------------|------------------|-------------------|
| SSL Wireless | `8801712345678`  | `ltrim($to, '+')` |
| Mim SMS      | `8801712345678`  | `ltrim($to, '+')` |
| BulkSMSBD    | `8801712345678`  | `ltrim($to, '+')` |
| Twilio       | `+8801712345678` | use as-is         |

Reject anything that doesn't parse as valid E.164 at the `SmsManager` layer — fail fast with a 422 before queueing.
