---
name: action-patterns
description: Laravel Action pattern for organizing single-purpose use cases with thin controllers.
origin: ECC
---

# Laravel Development Patterns

Action pattern for scalable, maintainable Laravel applications.

## When to Use

- Building Laravel web applications or APIs
- Structuring controllers and domain logic
- Keeping single-purpose use cases isolated and testable

## How It Works

- Structure the app around clear boundaries: controllers -> actions -> models.
- Keep controllers thin; put single-purpose logic in actions.
- Each action handles one use case and is easy to test in isolation.

## Examples

### Controllers -> Actions

Keep controllers thin. Put single-purpose logic in actions.

```php
final class CreateOrderAction
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(CreateOrderData $data): Order
    {
        return $this->orders->create($data);
    }
}

final class OrdersController extends Controller
{
    public function __construct(private CreateOrderAction $createOrder) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->createOrder->handle($request->toDto());

        return response()->json([
            'success' => true,
            'data' => OrderResource::make($order),
            'error' => null,
            'meta' => null,
        ], 201);
    }
}
```
